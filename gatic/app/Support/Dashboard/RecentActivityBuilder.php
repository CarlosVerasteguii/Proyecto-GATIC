<?php

namespace App\Support\Dashboard;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\InventoryAdjustmentEntry;
use App\Models\Note;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use App\Support\Timeline\TimelineEventType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Builds a global recent activity feed for the dashboard.
 *
 * Queries recent events from multiple domain sources,
 * respects RBAC gates, and returns a flat sorted array.
 */
final class RecentActivityBuilder
{
    private const PER_SOURCE_LIMIT = 10;

    private const TOTAL_LIMIT = 15;

    public function __construct(
        private readonly bool $canViewAttachments,
        private readonly bool $canManageInventory,
    ) {}

    /**
     * @return list<array{type: string, icon: string, label: string, title: string, summary: string, actor: string|null, occurred_at: string, occurred_at_human: string, route: string|null}>
     */
    public function build(): array
    {
        $events = array_merge(
            $this->assetMovements(),
            $this->productQuantityMovements(),
            $this->adjustments(),
            $this->notes(),
            $this->attachments(),
            $this->auditLogs(),
        );

        // Sort descending by timestamp, stable by sort_key
        usort($events, static function (array $a, array $b): int {
            $dateCmp = ($b['occurred_at_ts'] ?? 0) <=> ($a['occurred_at_ts'] ?? 0);
            if ($dateCmp !== 0) {
                return $dateCmp;
            }

            return strcmp($b['sort_key'], $a['sort_key']);
        });

        $events = array_slice($events, 0, self::TOTAL_LIMIT);

        return array_values(array_map(static function (array $event): array {
            unset($event['sort_key'], $event['occurred_at_ts']);

            return $event;
        }, $events));
    }

    /**
     * @return list<array{type: string, icon: string, label: string, title: string, summary: string, actor: string|null, occurred_at: string, occurred_at_human: string, route: string|null, sort_key: string, occurred_at_ts: int}>
     */
    private function assetMovements(): array
    {
        $relevantTypes = [
            AssetMovement::TYPE_ASSIGN => TimelineEventType::ASSET_ASSIGN,
            AssetMovement::TYPE_UNASSIGN => TimelineEventType::ASSET_UNASSIGN,
            AssetMovement::TYPE_LOAN => TimelineEventType::ASSET_LOAN,
            AssetMovement::TYPE_RETURN => TimelineEventType::ASSET_RETURN,
        ];

        $query = AssetMovement::query()
            ->whereIn('type', array_keys($relevantTypes))
            ->with(['actor:id,name', 'employee:id,name,rpe', 'asset:id,serial,product_id', 'asset.product:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::PER_SOURCE_LIMIT);

        $events = [];

        foreach ($query->get() as $m) {
            /** @var AssetMovement $m */
            $eventType = $relevantTypes[$m->type] ?? null;
            if ($eventType === null) {
                continue;
            }

            $typeLabel = TimelineEventType::label($eventType);
            $empLabel = $m->employee ? "{$m->employee->rpe} — {$m->employee->name}" : '—';
            $assetLabel = $m->asset ? $m->asset->serial : '—';
            $productName = $m->asset?->product?->name ?? '—';

            $route = null;
            if ($m->asset && $m->asset->product_id) {
                $route = $this->entityRoute('Asset', $m->asset->id, $m->asset->product_id);
            }

            $events[] = $this->makeEvent(
                type: $eventType,
                title: "{$typeLabel}: {$assetLabel}",
                summary: "Producto: {$productName} | Empleado: {$empLabel}",
                actor: $m->actor?->name,
                occurredAt: $m->created_at,
                source: 'asset_movement',
                sourceId: $m->id,
                route: $route,
            );
        }

        return $events;
    }

    /**
     * @return list<array{type: string, icon: string, label: string, title: string, summary: string, actor: string|null, occurred_at: string, occurred_at_human: string, route: string|null, sort_key: string, occurred_at_ts: int}>
     */
    private function productQuantityMovements(): array
    {
        $query = ProductQuantityMovement::query()
            ->with(['actorUser:id,name', 'employee:id,name,rpe', 'product:id,name'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::PER_SOURCE_LIMIT);

        $events = [];

        foreach ($query->get() as $m) {
            /** @var ProductQuantityMovement $m */
            $type = $m->direction === ProductQuantityMovement::DIRECTION_IN
                ? TimelineEventType::PRODUCT_QTY_IN
                : TimelineEventType::PRODUCT_QTY_OUT;

            $dirLabel = $m->direction === ProductQuantityMovement::DIRECTION_IN ? 'Entrada' : 'Salida';
            $productName = $m->product->name ?? '—';
            $empLabel = $m->employee ? "{$m->employee->rpe} — {$m->employee->name}" : '—';

            $route = $m->product_id
                ? $this->entityRoute('Product', $m->product_id)
                : null;

            $events[] = $this->makeEvent(
                type: $type,
                title: "{$dirLabel} x{$m->qty}: {$productName}",
                summary: "Empleado: {$empLabel}",
                actor: $m->actorUser?->name,
                occurredAt: $m->created_at,
                source: 'product_quantity_movement',
                sourceId: $m->id,
                route: $route,
            );
        }

        return $events;
    }

    /**
     * @return list<array{type: string, icon: string, label: string, title: string, summary: string, actor: string|null, occurred_at: string, occurred_at_human: string, route: string|null, sort_key: string, occurred_at_ts: int}>
     */
    private function adjustments(): array
    {
        $query = InventoryAdjustmentEntry::query()
            ->with(['adjustment:id,actor_user_id,reason', 'adjustment.actor:id,name', 'product:id,name', 'asset:id,serial,product_id'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::PER_SOURCE_LIMIT);

        $events = [];

        foreach ($query->get() as $entry) {
            /** @var InventoryAdjustmentEntry $entry */
            $adjustment = $entry->adjustment;
            $reason = $adjustment?->reason ?? '—';
            $actorName = $adjustment?->actor?->name;

            $entityLabel = '';
            $route = null;
            if ($entry->product_id) {
                $entityLabel = $entry->product?->name ?? '—';
                $route = $this->entityRoute('Product', $entry->product_id);
            } elseif ($entry->asset_id) {
                $entityLabel = $entry->asset?->serial ?? '—';
                if ($entry->asset?->product_id) {
                    $route = $this->entityRoute('Asset', $entry->asset_id, $entry->asset->product_id);
                }
            }

            $events[] = $this->makeEvent(
                type: TimelineEventType::ADJUSTMENT,
                title: "Ajuste: {$entityLabel}",
                summary: "Razón: {$reason}",
                actor: $actorName,
                occurredAt: $entry->created_at ?? now(),
                source: 'inventory_adjustment_entry',
                sourceId: $entry->id,
                route: $route,
            );
        }

        return $events;
    }

    /**
     * @return list<array{type: string, icon: string, label: string, title: string, summary: string, actor: string|null, occurred_at: string, occurred_at_human: string, route: string|null, sort_key: string, occurred_at_ts: int}>
     */
    private function notes(): array
    {
        $allowedNoteableTypes = [Product::class, Asset::class];
        if ($this->canManageInventory) {
            $allowedNoteableTypes[] = Employee::class;
        }

        $query = Note::query()
            ->whereIn('noteable_type', $allowedNoteableTypes)
            ->with(['author:id,name', 'noteable'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::PER_SOURCE_LIMIT);

        $events = [];

        foreach ($query->get() as $note) {
            /** @var Note $note */
            $route = $this->noteableRoute($note);

            $events[] = $this->makeEvent(
                type: TimelineEventType::NOTE_CREATE,
                title: 'Nota manual',
                summary: Str::limit($note->body, 120),
                actor: $note->author?->name,
                occurredAt: $note->created_at,
                source: 'note',
                sourceId: $note->id,
                route: $route,
            );
        }

        return $events;
    }

    /**
     * @return list<array{type: string, icon: string, label: string, title: string, summary: string, actor: string|null, occurred_at: string, occurred_at_human: string, route: string|null, sort_key: string, occurred_at_ts: int}>
     */
    private function attachments(): array
    {
        if (! $this->canViewAttachments) {
            return [];
        }

        $allowedAttachableTypes = [Product::class, Asset::class];
        if ($this->canManageInventory) {
            $allowedAttachableTypes[] = Employee::class;
        }

        $query = Attachment::query()
            ->whereIn('attachable_type', $allowedAttachableTypes)
            ->with('uploader:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::PER_SOURCE_LIMIT);

        $events = [];

        foreach ($query->get() as $attachment) {
            /** @var Attachment $attachment */
            $events[] = $this->makeEvent(
                type: TimelineEventType::ATTACHMENT_UPLOAD,
                title: 'Adjunto subido',
                summary: $attachment->original_name,
                actor: $attachment->uploader?->name,
                occurredAt: $attachment->created_at,
                source: 'attachment',
                sourceId: $attachment->id,
                route: route('attachments.download', ['id' => $attachment->id]),
            );
        }

        return $events;
    }

    /**
     * @return list<array{type: string, icon: string, label: string, title: string, summary: string, actor: string|null, occurred_at: string, occurred_at_human: string, route: string|null, sort_key: string, occurred_at_ts: int}>
     */
    private function auditLogs(): array
    {
        $allowedActions = [
            AuditLog::ACTION_LOCK_FORCE_RELEASE,
            AuditLog::ACTION_LOCK_FORCE_CLAIM,
            AuditLog::ACTION_TRASH_SOFT_DELETE,
            AuditLog::ACTION_TRASH_RESTORE,
            AuditLog::ACTION_TRASH_PURGE,
            AuditLog::ACTION_ATTACHMENT_DELETE,
        ];

        $query = AuditLog::query()
            ->whereIn('action', $allowedActions)
            ->with('actor:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::PER_SOURCE_LIMIT);

        $events = [];

        foreach ($query->get() as $log) {
            /** @var AuditLog $log */
            if (! $this->canIncludeAuditLog($log)) {
                continue;
            }

            $type = match ($log->action) {
                AuditLog::ACTION_LOCK_FORCE_RELEASE => TimelineEventType::AUDIT_LOCK_FORCE_RELEASE,
                AuditLog::ACTION_LOCK_FORCE_CLAIM => TimelineEventType::AUDIT_LOCK_FORCE_CLAIM,
                AuditLog::ACTION_TRASH_SOFT_DELETE => TimelineEventType::AUDIT_TRASH_SOFT_DELETE,
                AuditLog::ACTION_TRASH_RESTORE => TimelineEventType::AUDIT_TRASH_RESTORE,
                AuditLog::ACTION_TRASH_PURGE => TimelineEventType::AUDIT_TRASH_PURGE,
                AuditLog::ACTION_ATTACHMENT_DELETE => TimelineEventType::ATTACHMENT_DELETE,
                default => TimelineEventType::AUDIT_GENERIC,
            };

            $route = $this->auditSubjectRoute($log);

            $summary = $this->auditSummary($log);
            $subjectShort = class_basename($log->subject_type);
            $title = $log->action_label;

            $events[] = $this->makeEvent(
                type: $type,
                title: $title,
                summary: $summary !== '' ? $summary : "{$subjectShort} #{$log->subject_id}",
                actor: $log->actor?->name,
                occurredAt: $log->created_at ?? now(),
                source: 'audit_log',
                sourceId: $log->id,
                route: $route,
            );
        }

        return $events;
    }

    private function canIncludeAuditLog(AuditLog $log): bool
    {
        // Hide attachment audit details unless attachments.view is allowed
        if ($log->action === AuditLog::ACTION_ATTACHMENT_DELETE && ! $this->canViewAttachments) {
            return false;
        }

        // Hide employee / pending task entities unless inventory.manage is allowed
        $entityLabel = class_basename($log->subject_type);
        if (($entityLabel === 'Employee' || $entityLabel === 'PendingTask') && ! $this->canManageInventory) {
            return false;
        }

        // Allowlist of subject types we are willing to show in the global feed
        return in_array($entityLabel, ['Product', 'Asset', 'Employee', 'PendingTask'], true);
    }

    private function auditSubjectRoute(AuditLog $log): ?string
    {
        $entityLabel = class_basename($log->subject_type);

        return match ($entityLabel) {
            'Product' => $this->entityRoute('Product', (int) $log->subject_id),
            'Employee' => $this->entityRoute('Employee', (int) $log->subject_id),
            'PendingTask' => $this->entityRoute('PendingTask', (int) $log->subject_id),
            default => null,
        };
    }

    private function auditSummary(AuditLog $log): string
    {
        if (! is_array($log->context) || $log->context === []) {
            return '';
        }

        $summary = $log->context['summary'] ?? null;

        return is_string($summary) ? (string) $summary : '';
    }

    /**
     * @return array{type: string, icon: string, label: string, title: string, summary: string, actor: string|null, occurred_at: string, occurred_at_human: string, route: string|null, sort_key: string, occurred_at_ts: int}
     */
    private function makeEvent(
        string $type,
        string $title,
        string $summary,
        ?string $actor,
        Carbon $occurredAt,
        string $source,
        int $sourceId,
        ?string $route,
    ): array {
        $occurredAtIso = $occurredAt->toIso8601String();

        return [
            'type' => $type,
            'icon' => TimelineEventType::icon($type),
            'label' => TimelineEventType::label($type),
            'title' => $title,
            'summary' => $summary,
            'actor' => $actor,
            'occurred_at' => $occurredAtIso,
            'occurred_at_human' => $occurredAt->diffForHumans(),
            'route' => $route,
            'sort_key' => $occurredAtIso.'|'.$source.'|'.sprintf('%020d', $sourceId),
            'occurred_at_ts' => $occurredAt->timestamp,
        ];
    }

    private function entityRoute(string $entityLabel, int $entityId, ?int $productId = null): ?string
    {
        return match ($entityLabel) {
            'Product' => route('inventory.products.show', ['product' => $entityId]),
            'Asset' => $productId !== null
                ? route('inventory.products.assets.show', ['product' => $productId, 'asset' => $entityId])
                : null,
            'Employee' => $this->canManageInventory
                ? route('employees.show', ['employee' => $entityId])
                : null,
            'PendingTask' => $this->canManageInventory
                ? route('pending-tasks.show', ['pendingTask' => $entityId])
                : null,
            default => null,
        };
    }

    private function noteableRoute(Note $note): ?string
    {
        $noteable = $note->noteable;
        if ($noteable === null) {
            return null;
        }

        return match ($note->noteable_type) {
            Product::class => $this->entityRoute('Product', $noteable->id),
            Asset::class => $this->entityRoute('Asset', $noteable->id, $noteable->product_id ?? null),
            Employee::class => $this->entityRoute('Employee', $noteable->id),
            default => null,
        };
    }
}
