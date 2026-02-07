<?php

namespace App\Support\Timeline;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\InventoryAdjustmentEntry;
use App\Models\Note;
use App\Models\PendingTask;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Aggregates timeline events from multiple domain sources for a given entity.
 *
 * This is the "glue" layer: it queries existing domain tables,
 * maps results to TimelineEvent DTOs, merges and sorts them.
 */
final class TimelineBuilder
{
    /**
     * Allowed entity types (security allowlist).
     *
     * @var array<class-string<Model>, string>
     */
    public const ALLOWED_ENTITIES = [
        Product::class => 'Product',
        Asset::class => 'Asset',
        Employee::class => 'Employee',
        PendingTask::class => 'PendingTask',
    ];

    /**
     * View gates per entity type.
     *
     * @var array<class-string<Model>, string>
     */
    public const VIEW_GATES = [
        Product::class => 'inventory.view',
        Asset::class => 'inventory.view',
        Employee::class => 'inventory.manage',
        PendingTask::class => 'inventory.manage',
    ];

    private const PER_SOURCE_LIMIT = 50;

    /**
     * @param  class-string<Model>  $entityType
     * @param  list<string>|null  $filterCategories  Filter category keys from TimelineEventType::FILTER_CATEGORIES
     */
    public function __construct(
        private readonly string $entityType,
        private readonly int $entityId,
        private readonly bool $canViewAttachments,
        private readonly ?array $filterCategories = null,
        private readonly ?Carbon $beforeCursor = null,
        private readonly ?string $beforeSortKey = null,
        private readonly int $pageSize = 25,
    ) {}

    /**
     * Build and return sorted timeline events.
     *
     * @return list<TimelineEvent>
     */
    public function build(): array
    {
        $events = [];

        $allowedTypes = $this->resolveAllowedTypes();

        // Collect events from each source
        $events = array_merge(
            $events,
            $this->movements($allowedTypes),
            $this->adjustments($allowedTypes),
            $this->notes($allowedTypes),
            $this->attachments($allowedTypes),
            $this->auditLogs($allowedTypes),
        );

        // Sort descending by occurred_at, then by source+id for stable order
        usort($events, static function (TimelineEvent $a, TimelineEvent $b): int {
            $dateCmp = $b->occurredAt->timestamp <=> $a->occurredAt->timestamp;
            if ($dateCmp !== 0) {
                return $dateCmp;
            }

            return strcmp($b->sortKey(), $a->sortKey());
        });

        $events = $this->applyCursor($events);

        // Apply page size
        return array_slice($events, 0, $this->pageSize);
    }

    /**
     * Apply cursor filtering after sorting to prevent skipping events with the same timestamp.
     *
     * @param  list<TimelineEvent>  $events
     * @return list<TimelineEvent>
     */
    private function applyCursor(array $events): array
    {
        if ($this->beforeCursor === null) {
            return $events;
        }

        if ($this->beforeSortKey === null) {
            return array_values(array_filter(
                $events,
                fn (TimelineEvent $e): bool => $e->occurredAt->lt($this->beforeCursor),
            ));
        }

        return array_values(array_filter(
            $events,
            fn (TimelineEvent $e): bool => $e->occurredAt->lt($this->beforeCursor)
                || ($e->occurredAt->eq($this->beforeCursor) && strcmp($e->sortKey(), $this->beforeSortKey) < 0),
        ));
    }

    /**
     * Resolve allowed event types based on filter categories.
     *
     * @return list<string>|null null means "all types allowed"
     */
    private function resolveAllowedTypes(): ?array
    {
        if ($this->filterCategories === null || $this->filterCategories === []) {
            return null;
        }

        $types = [];
        foreach ($this->filterCategories as $category) {
            if (isset(TimelineEventType::FILTER_CATEGORIES[$category])) {
                $types = array_merge($types, TimelineEventType::FILTER_CATEGORIES[$category]);
            }
        }

        return $types === [] ? null : array_unique($types);
    }

    private function isTypeAllowed(string $type, ?array $allowedTypes): bool
    {
        return $allowedTypes === null || in_array($type, $allowedTypes, true);
    }

    /**
     * @return list<TimelineEvent>
     */
    private function movements(?array $allowedTypes): array
    {
        $events = [];

        if ($this->entityType === Product::class) {
            $events = array_merge($events, $this->productQuantityMovements($allowedTypes));
        } elseif ($this->entityType === Asset::class) {
            $events = array_merge($events, $this->assetMovements($allowedTypes));
        } elseif ($this->entityType === Employee::class) {
            $events = array_merge(
                $events,
                $this->assetMovementsForEmployee($allowedTypes),
                $this->productQuantityMovementsForEmployee($allowedTypes),
            );
        }

        return $events;
    }

    /**
     * @return list<TimelineEvent>
     */
    private function productQuantityMovements(?array $allowedTypes): array
    {
        $hasIn = $this->isTypeAllowed(TimelineEventType::PRODUCT_QTY_IN, $allowedTypes);
        $hasOut = $this->isTypeAllowed(TimelineEventType::PRODUCT_QTY_OUT, $allowedTypes);

        if (! $hasIn && ! $hasOut) {
            return [];
        }

        $query = ProductQuantityMovement::query()
            ->where('product_id', $this->entityId)
            ->with(['actorUser:id,name', 'employee:id,name,rpe'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::PER_SOURCE_LIMIT);

        if ($this->beforeCursor !== null) {
            $query->where('created_at', '<=', $this->beforeCursor);
        }

        $events = [];

        foreach ($query->get() as $m) {
            /** @var ProductQuantityMovement $m */
            $type = $m->direction === ProductQuantityMovement::DIRECTION_IN
                ? TimelineEventType::PRODUCT_QTY_IN
                : TimelineEventType::PRODUCT_QTY_OUT;

            if (! $this->isTypeAllowed($type, $allowedTypes)) {
                continue;
            }

            $dirLabel = $m->direction === ProductQuantityMovement::DIRECTION_IN ? 'Entrada' : 'Salida';
            $empLabel = $m->employee ? "{$m->employee->rpe} — {$m->employee->name}" : '—';

            $events[] = new TimelineEvent(
                type: $type,
                occurredAt: $m->created_at,
                source: 'product_quantity_movement',
                sourceId: $m->id,
                title: "{$dirLabel} x{$m->qty}",
                summary: "Empleado: {$empLabel}".($m->note ? " | {$m->note}" : ''),
                actorUserId: $m->actor_user_id,
                actorName: $m->actorUser?->name,
                meta: [
                    'direction' => $m->direction,
                    'qty' => $m->qty,
                    'qty_before' => $m->qty_before,
                    'qty_after' => $m->qty_after,
                ],
            );
        }

        return $events;
    }

    /**
     * @return list<TimelineEvent>
     */
    private function assetMovements(?array $allowedTypes): array
    {
        $relevantTypes = [
            AssetMovement::TYPE_ASSIGN => TimelineEventType::ASSET_ASSIGN,
            AssetMovement::TYPE_UNASSIGN => TimelineEventType::ASSET_UNASSIGN,
            AssetMovement::TYPE_LOAN => TimelineEventType::ASSET_LOAN,
            AssetMovement::TYPE_RETURN => TimelineEventType::ASSET_RETURN,
        ];

        // Check if any movement type is allowed
        $anyAllowed = false;
        foreach ($relevantTypes as $eventType) {
            if ($this->isTypeAllowed($eventType, $allowedTypes)) {
                $anyAllowed = true;
                break;
            }
        }

        if (! $anyAllowed) {
            return [];
        }

        $query = AssetMovement::query()
            ->where('asset_id', $this->entityId)
            ->with(['actor:id,name', 'employee:id,name,rpe'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::PER_SOURCE_LIMIT);

        if ($this->beforeCursor !== null) {
            $query->where('created_at', '<=', $this->beforeCursor);
        }

        return $this->mapAssetMovements($query->get(), $relevantTypes, $allowedTypes);
    }

    /**
     * @return list<TimelineEvent>
     */
    private function assetMovementsForEmployee(?array $allowedTypes): array
    {
        $relevantTypes = [
            AssetMovement::TYPE_ASSIGN => TimelineEventType::ASSET_ASSIGN,
            AssetMovement::TYPE_UNASSIGN => TimelineEventType::ASSET_UNASSIGN,
            AssetMovement::TYPE_LOAN => TimelineEventType::ASSET_LOAN,
            AssetMovement::TYPE_RETURN => TimelineEventType::ASSET_RETURN,
        ];

        $anyAllowed = false;
        foreach ($relevantTypes as $eventType) {
            if ($this->isTypeAllowed($eventType, $allowedTypes)) {
                $anyAllowed = true;
                break;
            }
        }

        if (! $anyAllowed) {
            return [];
        }

        $query = AssetMovement::query()
            ->where('employee_id', $this->entityId)
            ->with(['actor:id,name', 'employee:id,name,rpe', 'asset:id,serial,product_id', 'asset.product:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::PER_SOURCE_LIMIT);

        if ($this->beforeCursor !== null) {
            $query->where('created_at', '<=', $this->beforeCursor);
        }

        return $this->mapAssetMovements($query->get(), $relevantTypes, $allowedTypes, forEmployee: true);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, AssetMovement>  $movements
     * @param  array<string, string>  $relevantTypes
     * @return list<TimelineEvent>
     */
    private function mapAssetMovements($movements, array $relevantTypes, ?array $allowedTypes, bool $forEmployee = false): array
    {
        $events = [];

        foreach ($movements as $m) {
            /** @var AssetMovement $m */
            $eventType = $relevantTypes[$m->type] ?? null;
            if ($eventType === null || ! $this->isTypeAllowed($eventType, $allowedTypes)) {
                continue;
            }

            $typeLabel = TimelineEventType::label($eventType);
            $empLabel = $m->employee ? "{$m->employee->rpe} — {$m->employee->name}" : '—';

            $title = $typeLabel;
            $summary = "Empleado: {$empLabel}";

            if ($forEmployee && $m->asset) {
                $productName = $m->asset->product->name ?? '—';
                $title = "{$typeLabel}: {$m->asset->serial}";
                $summary = "Producto: {$productName}";
            }

            if ($m->note) {
                $summary .= " | {$m->note}";
            }

            $meta = ['movement_type' => $m->type];
            if ($forEmployee && $m->asset) {
                $meta['asset_serial'] = $m->asset->serial;
                $meta['product_id'] = $m->asset->product_id;
            }

            $events[] = new TimelineEvent(
                type: $eventType,
                occurredAt: $m->created_at,
                source: 'asset_movement',
                sourceId: $m->id,
                title: $title,
                summary: $summary,
                actorUserId: $m->actor_user_id,
                actorName: $m->actor?->name,
                meta: $meta,
            );
        }

        return $events;
    }

    /**
     * @return list<TimelineEvent>
     */
    private function productQuantityMovementsForEmployee(?array $allowedTypes): array
    {
        $hasIn = $this->isTypeAllowed(TimelineEventType::PRODUCT_QTY_IN, $allowedTypes);
        $hasOut = $this->isTypeAllowed(TimelineEventType::PRODUCT_QTY_OUT, $allowedTypes);

        if (! $hasIn && ! $hasOut) {
            return [];
        }

        $query = ProductQuantityMovement::query()
            ->where('employee_id', $this->entityId)
            ->with(['actorUser:id,name', 'product:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::PER_SOURCE_LIMIT);

        if ($this->beforeCursor !== null) {
            $query->where('created_at', '<=', $this->beforeCursor);
        }

        $events = [];

        foreach ($query->get() as $m) {
            /** @var ProductQuantityMovement $m */
            $type = $m->direction === ProductQuantityMovement::DIRECTION_IN
                ? TimelineEventType::PRODUCT_QTY_IN
                : TimelineEventType::PRODUCT_QTY_OUT;

            if (! $this->isTypeAllowed($type, $allowedTypes)) {
                continue;
            }

            $dirLabel = $m->direction === ProductQuantityMovement::DIRECTION_IN ? 'Entrada' : 'Salida';
            $productName = $m->product->name ?? '—';

            $events[] = new TimelineEvent(
                type: $type,
                occurredAt: $m->created_at,
                source: 'product_quantity_movement',
                sourceId: $m->id,
                title: "{$dirLabel} x{$m->qty}: {$productName}",
                summary: $m->note ? $m->note : '',
                actorUserId: $m->actor_user_id,
                actorName: $m->actorUser?->name,
                meta: [
                    'direction' => $m->direction,
                    'qty' => $m->qty,
                    'product_id' => $m->product_id,
                ],
            );
        }

        return $events;
    }

    /**
     * @return list<TimelineEvent>
     */
    private function adjustments(?array $allowedTypes): array
    {
        if (! $this->isTypeAllowed(TimelineEventType::ADJUSTMENT, $allowedTypes)) {
            return [];
        }

        if ($this->entityType === Employee::class || $this->entityType === PendingTask::class) {
            return [];
        }

        $fk = $this->entityType === Product::class ? 'product_id' : 'asset_id';

        $query = InventoryAdjustmentEntry::query()
            ->where($fk, $this->entityId)
            ->with(['adjustment:id,actor_user_id,reason', 'adjustment.actor:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::PER_SOURCE_LIMIT);

        if ($this->beforeCursor !== null) {
            $query->where('created_at', '<=', $this->beforeCursor);
        }

        $events = [];

        foreach ($query->get() as $entry) {
            /** @var InventoryAdjustmentEntry $entry */
            $adjustment = $entry->adjustment;
            $reason = $adjustment->reason ?? '—';
            $actorName = $adjustment?->actor?->name;
            $actorUserId = $adjustment?->actor_user_id;

            $beforeStr = json_encode($entry->before, JSON_UNESCAPED_UNICODE);
            $afterStr = json_encode($entry->after, JSON_UNESCAPED_UNICODE);

            $events[] = new TimelineEvent(
                type: TimelineEventType::ADJUSTMENT,
                occurredAt: $entry->created_at ?? now(),
                source: 'inventory_adjustment_entry',
                sourceId: $entry->id,
                title: 'Ajuste de inventario',
                summary: "Razon: {$reason}",
                actorUserId: $actorUserId,
                actorName: $actorName,
                meta: [
                    'before' => Str::limit($beforeStr, 100),
                    'after' => Str::limit($afterStr, 100),
                ],
            );
        }

        return $events;
    }

    /**
     * @return list<TimelineEvent>
     */
    private function notes(?array $allowedTypes): array
    {
        if (! $this->isTypeAllowed(TimelineEventType::NOTE_CREATE, $allowedTypes)) {
            return [];
        }

        // PendingTask doesn't have notes in the existing system
        if ($this->entityType === PendingTask::class) {
            return [];
        }

        $query = Note::query()
            ->where('noteable_type', $this->entityType)
            ->where('noteable_id', $this->entityId)
            ->with('author:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::PER_SOURCE_LIMIT);

        if ($this->beforeCursor !== null) {
            $query->where('created_at', '<=', $this->beforeCursor);
        }

        $events = [];

        foreach ($query->get() as $note) {
            /** @var Note $note */
            $events[] = new TimelineEvent(
                type: TimelineEventType::NOTE_CREATE,
                occurredAt: $note->created_at,
                source: 'note',
                sourceId: $note->id,
                title: 'Nota manual',
                summary: Str::limit($note->body, 120),
                actorUserId: $note->author_user_id,
                actorName: $note->author?->name,
                meta: [
                    'body' => Str::limit($note->body, 2000),
                ],
            );
        }

        return $events;
    }

    /**
     * @return list<TimelineEvent>
     */
    private function attachments(?array $allowedTypes): array
    {
        if (! $this->canViewAttachments) {
            return [];
        }

        $hasUpload = $this->isTypeAllowed(TimelineEventType::ATTACHMENT_UPLOAD, $allowedTypes);
        $hasDelete = $this->isTypeAllowed(TimelineEventType::ATTACHMENT_DELETE, $allowedTypes);

        if (! $hasUpload && ! $hasDelete) {
            return [];
        }

        // PendingTask doesn't have attachments in the existing system
        if ($this->entityType === PendingTask::class) {
            return [];
        }

        $events = [];

        // Existing attachments (uploads)
        if ($hasUpload) {
            $query = Attachment::query()
                ->where('attachable_type', $this->entityType)
                ->where('attachable_id', $this->entityId)
                ->with('uploader:id,name')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(self::PER_SOURCE_LIMIT);

            if ($this->beforeCursor !== null) {
                $query->where('created_at', '<=', $this->beforeCursor);
            }

            foreach ($query->get() as $attachment) {
                /** @var Attachment $attachment */
                $events[] = new TimelineEvent(
                    type: TimelineEventType::ATTACHMENT_UPLOAD,
                    occurredAt: $attachment->created_at,
                    source: 'attachment',
                    sourceId: $attachment->id,
                    title: 'Adjunto subido',
                    summary: $attachment->original_name,
                    actorUserId: $attachment->uploaded_by_user_id,
                    actorName: $attachment->uploader?->name,
                    meta: [
                        'attachment_id' => $attachment->id,
                        'mime_type' => $attachment->mime_type,
                    ],
                    routeName: 'attachments.download',
                    routeParams: ['id' => $attachment->id],
                );
            }
        }

        // Deleted attachments (from audit log)
        if ($hasDelete) {
            $deleteEvents = $this->attachmentDeletionsFromAudit();
            $events = array_merge($events, $deleteEvents);
        }

        return $events;
    }

    /**
     * @return list<TimelineEvent>
     */
    private function attachmentDeletionsFromAudit(): array
    {
        $query = AuditLog::query()
            ->where('action', AuditLog::ACTION_ATTACHMENT_DELETE)
            ->where('subject_type', $this->entityType)
            ->where('subject_id', $this->entityId)
            ->with('actor:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::PER_SOURCE_LIMIT);

        if ($this->beforeCursor !== null) {
            $query->where('created_at', '<=', $this->beforeCursor);
        }

        $events = [];

        foreach ($query->get() as $log) {
            /** @var AuditLog $log */
            $summary = is_array($log->context) && isset($log->context['summary'])
                ? (string) $log->context['summary']
                : 'Adjunto eliminado';

            $events[] = new TimelineEvent(
                type: TimelineEventType::ATTACHMENT_DELETE,
                occurredAt: $log->created_at,
                source: 'audit_log',
                sourceId: $log->id,
                title: 'Adjunto eliminado',
                summary: $summary,
                actorUserId: $log->actor_user_id,
                actorName: $log->actor?->name,
            );
        }

        return $events;
    }

    /**
     * Audit logs for events that don't have visible domain records.
     *
     * @return list<TimelineEvent>
     */
    private function auditLogs(?array $allowedTypes): array
    {
        // Determine which audit actions are relevant for this entity type
        $actionMap = $this->auditActionMap();

        if ($actionMap === []) {
            return [];
        }

        // Filter by allowed types
        $filteredActions = [];
        foreach ($actionMap as $action => $eventType) {
            if ($this->isTypeAllowed($eventType, $allowedTypes)) {
                $filteredActions[$action] = $eventType;
            }
        }

        if ($filteredActions === []) {
            return [];
        }

        $query = AuditLog::query()
            ->where('subject_type', $this->entityType)
            ->where('subject_id', $this->entityId)
            ->whereIn('action', array_keys($filteredActions))
            ->with('actor:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::PER_SOURCE_LIMIT);

        if ($this->beforeCursor !== null) {
            $query->where('created_at', '<=', $this->beforeCursor);
        }

        $events = [];

        foreach ($query->get() as $log) {
            /** @var AuditLog $log */
            $eventType = $filteredActions[$log->action] ?? TimelineEventType::AUDIT_GENERIC;

            // Skip attachment events (handled in attachments() method to avoid dupes)
            if (in_array($log->action, [AuditLog::ACTION_ATTACHMENT_UPLOAD, AuditLog::ACTION_ATTACHMENT_DELETE], true)) {
                continue;
            }

            $events[] = new TimelineEvent(
                type: $eventType,
                occurredAt: $log->created_at,
                source: 'audit_log',
                sourceId: $log->id,
                title: $log->action_label,
                summary: $this->auditSummary($log),
                actorUserId: $log->actor_user_id,
                actorName: $log->actor?->name,
            );
        }

        return $events;
    }

    /**
     * Map audit actions to timeline event types per entity.
     *
     * @return array<string, string>
     */
    private function auditActionMap(): array
    {
        $common = [
            AuditLog::ACTION_TRASH_SOFT_DELETE => TimelineEventType::AUDIT_TRASH_SOFT_DELETE,
            AuditLog::ACTION_TRASH_RESTORE => TimelineEventType::AUDIT_TRASH_RESTORE,
            AuditLog::ACTION_TRASH_PURGE => TimelineEventType::AUDIT_TRASH_PURGE,
        ];

        return match ($this->entityType) {
            PendingTask::class => [
                AuditLog::ACTION_LOCK_FORCE_RELEASE => TimelineEventType::AUDIT_LOCK_FORCE_RELEASE,
                AuditLog::ACTION_LOCK_FORCE_CLAIM => TimelineEventType::AUDIT_LOCK_FORCE_CLAIM,
            ] + $common,
            default => $common,
        };
    }

    /**
     * Create a safe summary from audit log context (sanitized, no full JSON dump).
     */
    private function auditSummary(AuditLog $log): string
    {
        if (! is_array($log->context)) {
            return $log->action_label;
        }

        $parts = [];

        if (isset($log->context['summary'])) {
            $parts[] = (string) $log->context['summary'];
        }

        if (isset($log->context['reason'])) {
            $parts[] = 'Razon: '.(string) $log->context['reason'];
        }

        return $parts === [] ? $log->action_label : implode(' | ', $parts);
    }
}
