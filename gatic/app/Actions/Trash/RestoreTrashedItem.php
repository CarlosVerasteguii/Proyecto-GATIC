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
use Illuminate\Database\QueryException;

/**
 * Story 8.4: Restore a soft-deleted item.
 *
 * Only operates on trashed items. Returns success/failure with message.
 */
class RestoreTrashedItem
{
    /**
     * Allowed entity types for trash operations.
     *
     * @var array<string, class-string>
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
        if (! array_key_exists($type, self::ENTITY_TYPES)) {
            return ['success' => false, 'message' => 'Tipo de entidad no válido.'];
        }

        $modelClass = self::ENTITY_TYPES[$type];

        /** @var (Product|Asset|Employee|Category|Brand|Location|Supplier)|null $item */
        $item = $modelClass::query()->onlyTrashed()->find($id);

        if ($item === null) {
            return ['success' => false, 'message' => 'Registro no encontrado en papelera.'];
        }

        // Check dependencies for assets (Product must not be deleted)
        if ($item instanceof Asset) {
            $product = Product::withTrashed()->find($item->product_id);
            if ($product !== null && $product->trashed()) {
                return [
                    'success' => false,
                    'message' => 'No se puede restaurar el activo porque su producto está eliminado. Restaure el producto primero.',
                ];
            }
        }

        try {
            $item->restore();

            AuditRecorder::record(
                action: AuditLog::ACTION_TRASH_RESTORE,
                subjectType: $modelClass,
                subjectId: $id,
                actorUserId: $actorUserId
            );

            return ['success' => true, 'message' => 'Registro restaurado.'];
        } catch (QueryException $e) {
            if ($this->isDuplicateKeyError($e)) {
                return [
                    'success' => false,
                    'message' => 'No se puede restaurar porque ya existe un registro activo con los mismos datos.',
                ];
            }

            $errorId = app(ErrorReporter::class)->report($e, request());

            return [
                'success' => false,
                'message' => "Ocurrió un error inesperado al restaurar. ID: {$errorId}",
            ];
        } catch (\Throwable $e) {
            $errorId = app(ErrorReporter::class)->report($e, request());

            return [
                'success' => false,
                'message' => "Ocurrió un error inesperado al restaurar. ID: {$errorId}",
            ];
        }
    }

    private function isDuplicateKeyError(QueryException $e): bool
    {
        $errorInfo = $e->errorInfo;

        if (! is_array($errorInfo) || count($errorInfo) < 2) {
            return false;
        }

        // MySQL error 1062: Duplicate entry
        return ((int) ($errorInfo[1] ?? 0)) === 1062;
    }
}
