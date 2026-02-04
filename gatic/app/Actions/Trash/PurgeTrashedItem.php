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
 * Story 8.4: Permanently delete (purge) a soft-deleted item.
 *
 * Only operates on trashed items. Handles FK constraint errors gracefully.
 */
class PurgeTrashedItem
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
     * @return array{success: bool, message: string}
     */
    public function execute(string $type, int $id, ?int $actorUserId = null): array
    {
        $modelClass = self::ENTITY_TYPES[$type] ?? null;

        if ($modelClass === null) {
            return ['success' => false, 'message' => 'Tipo de entidad no válido.'];
        }

        /** @var Model|null $item */
        $item = $modelClass::query()->onlyTrashed()->find($id);

        if ($item === null) {
            return ['success' => false, 'message' => 'Registro no encontrado en papelera.'];
        }

        try {
            $item->forceDelete();

            AuditRecorder::record(
                action: AuditLog::ACTION_TRASH_PURGE,
                subjectType: $modelClass,
                subjectId: $id,
                actorUserId: $actorUserId
            );

            return ['success' => true, 'message' => 'Registro eliminado permanentemente.'];
        } catch (QueryException $e) {
            // Handle FK constraint violations (MySQL error 1451)
            if ($this->isForeignKeyConstraintError($e)) {
                return [
                    'success' => false,
                    'message' => $this->buildForeignKeyConstraintMessage($type),
                ];
            }

            $errorId = app(ErrorReporter::class)->report($e, request());

            return [
                'success' => false,
                'message' => "Ocurrió un error inesperado al purgar. ID: {$errorId}",
            ];
        } catch (\Throwable $e) {
            $errorId = app(ErrorReporter::class)->report($e, request());

            return [
                'success' => false,
                'message' => "Ocurrió un error inesperado al purgar. ID: {$errorId}",
            ];
        }
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

    private function buildForeignKeyConstraintMessage(string $type): string
    {
        return match ($type) {
            'categories' => 'No se puede eliminar porque la categoría está en uso por productos.',
            'brands' => 'No se puede eliminar porque la marca está en uso por productos.',
            'locations' => 'No se puede eliminar porque la ubicación está en uso por activos u otros registros.',
            'suppliers' => 'No se puede eliminar porque el proveedor está asociado a uno o más productos.',
            default => 'No se puede eliminar porque tiene registros dependientes.',
        };
    }
}
