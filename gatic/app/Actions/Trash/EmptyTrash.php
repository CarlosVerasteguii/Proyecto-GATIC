<?php

namespace App\Actions\Trash;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use App\Support\Audit\AuditRecorder;
use App\Support\Errors\ErrorReporter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/**
 * Story 8.4: Empty trash (bulk purge) for a specific entity type.
 *
 * Uses best-effort approach: purges what's possible, reports failures.
 */
class EmptyTrash
{
    /**
     * Allowed entity types for trash operations.
     *
     * @var array<string, class-string<Model>>
     */
    private const ENTITY_TYPES = [
        'products' => Product::class,
        'assets' => Asset::class,
        'employees' => Employee::class,
        'categories' => Category::class,
        'brands' => Brand::class,
        'locations' => Location::class,
        'suppliers' => Supplier::class,
    ];

    /**
     * @return array{success: bool, purged: int, failed: int, message: string}
     */
    public function execute(string $type, ?int $actorUserId = null): array
    {
        $modelClass = self::ENTITY_TYPES[$type] ?? null;

        if ($modelClass === null) {
            return [
                'success' => false,
                'purged' => 0,
                'failed' => 0,
                'message' => 'Tipo de entidad no válido.',
            ];
        }

        $purged = 0;
        $failed = 0;
        $unexpectedFailed = 0;
        $firstErrorId = null;

        $modelClass::query()
            ->onlyTrashed()
            ->orderBy('id')
            ->chunkById(200, function ($trashedItems) use (
                $modelClass,
                $actorUserId,
                &$purged,
                &$failed,
                &$unexpectedFailed,
                &$firstErrorId
            ) {
                foreach ($trashedItems as $item) {
                    try {
                        /** @var int $itemId */
                        $itemId = $item->id;

                        $item->forceDelete();

                        AuditRecorder::record(
                            action: AuditLog::ACTION_TRASH_PURGE,
                            subjectType: $modelClass,
                            subjectId: $itemId,
                            actorUserId: $actorUserId
                        );

                        $purged++;
                    } catch (QueryException $e) {
                        $failed++;

                        if (! $this->isForeignKeyConstraintError($e)) {
                            $unexpectedFailed++;
                            $firstErrorId ??= app(ErrorReporter::class)->report($e, request());
                        }
                    } catch (\Throwable $e) {
                        $failed++;
                        $unexpectedFailed++;
                        $firstErrorId ??= app(ErrorReporter::class)->report($e, request());
                    }
                }
            }, column: 'id');

        if ($purged === 0 && $failed === 0) {
            return [
                'success' => true,
                'purged' => 0,
                'failed' => 0,
                'message' => 'La papelera ya estaba vacía.',
            ];
        }

        if ($failed === 0) {
            return [
                'success' => true,
                'purged' => $purged,
                'failed' => 0,
                'message' => "Se eliminaron {$purged} registro(s) permanentemente.",
            ];
        }

        if ($unexpectedFailed > 0 && is_string($firstErrorId)) {
            return [
                'success' => $purged > 0,
                'purged' => $purged,
                'failed' => $failed,
                'message' => "Se eliminaron {$purged} registro(s). {$failed} no pudieron eliminarse (dependencias u otros errores). ID: {$firstErrorId}",
            ];
        }

        return [
            'success' => $purged > 0,
            'purged' => $purged,
            'failed' => $failed,
            'message' => "Se eliminaron {$purged} registro(s). {$failed} no pudieron eliminarse por tener dependencias.",
        ];
    }

    private function isForeignKeyConstraintError(QueryException $e): bool
    {
        $errorInfo = $e->errorInfo;

        if (! is_array($errorInfo) || count($errorInfo) < 2) {
            return false;
        }

        // MySQL error 1451: Cannot delete or update a parent row: a foreign key constraint fails
        return ((int) ($errorInfo[1] ?? 0)) === 1451;
    }
}
