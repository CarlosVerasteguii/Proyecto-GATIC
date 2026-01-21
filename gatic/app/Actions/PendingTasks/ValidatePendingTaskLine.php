<?php

declare(strict_types=1);

namespace App\Actions\PendingTasks;

use App\Enums\PendingTaskLineStatus;
use App\Enums\PendingTaskType;
use App\Models\Asset;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use App\Models\Product;
use App\Support\Assets\AssetStatusTransitions;

/**
 * Validate a single PendingTaskLine against current inventory state.
 *
 * This is a "dry-run" validation that checks if the line CAN be applied
 * without actually applying it. Used in the "Process" mode UI.
 */
class ValidatePendingTaskLine
{
    /**
     * Validate a line and update its status/error_message.
     *
     * @param  int  $lineId  The PendingTaskLine ID
     * @return array{valid: bool, error_message: string|null}
     */
    public function execute(int $lineId): array
    {
        $line = PendingTaskLine::with('pendingTask')->findOrFail($lineId);
        $task = $line->pendingTask;

        // Check if already applied - don't re-validate
        if ($line->line_status === PendingTaskLineStatus::Applied) {
            return [
                'valid' => true,
                'error_message' => null,
            ];
        }

        $error = $this->validateLine($line, $task);

        if ($error === null) {
            // Valid - clear any previous error
            $line->line_status = PendingTaskLineStatus::Pending;
            $line->error_message = null;
            $line->save();

            return [
                'valid' => true,
                'error_message' => null,
            ];
        }

        // Invalid - mark as error
        $line->line_status = PendingTaskLineStatus::Error;
        $line->error_message = $error;
        $line->save();

        return [
            'valid' => false,
            'error_message' => $error,
        ];
    }

    /**
     * Validate line without saving (for batch validation).
     *
     * @return string|null Error message or null if valid
     */
    public function validateLine(PendingTaskLine $line, PendingTask $task): ?string
    {
        // Check line type compatibility
        if ($line->isSerialized() && ! $task->type->supportsSerialized()) {
            return $task->type->unsupportedLineTypeMessage($line->line_type);
        }

        if ($line->isQuantity() && ! $task->type->supportsQuantity()) {
            return $task->type->unsupportedLineTypeMessage($line->line_type);
        }

        // Check for duplicates within task
        $duplicates = $task->getDuplicateIdentifiers();
        if ($line->isSerialized() && $this->isLineDuplicate($line, $duplicates)) {
            $details = $this->getDuplicateDetails($line, $duplicates);

            return "Duplicado en la tarea: {$details}. Corrige o elimina el duplicado.";
        }

        // Validate based on line type
        if ($line->isSerialized()) {
            return $this->validateSerializedLine($line, $task->type);
        }

        return $this->validateQuantityLine($line, $task->type);
    }

    /**
     * @param  array<string, list<int>>  $duplicates
     */
    private function isLineDuplicate(PendingTaskLine $line, array $duplicates): bool
    {
        foreach ($duplicates as $lineIds) {
            if (in_array($line->id, $lineIds, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, list<int>>  $duplicates
     */
    private function getDuplicateDetails(PendingTaskLine $line, array $duplicates): string
    {
        $details = [];

        foreach ($duplicates as $key => $lineIds) {
            if (in_array($line->id, $lineIds, true)) {
                $otherIds = array_filter($lineIds, fn ($id) => $id !== $line->id);
                if ($otherIds !== []) {
                    $details[] = "{$key} (renglones: ".implode(', ', $otherIds).')';
                }
            }
        }

        return implode('; ', $details);
    }

    private function validateSerializedLine(PendingTaskLine $line, PendingTaskType $taskType): ?string
    {
        // Find the asset
        $asset = $this->findAssetForLine($line);

        if ($asset === null) {
            return $this->buildAssetNotFoundMessage($line);
        }

        // Validate transition based on task type
        $action = match ($taskType) {
            PendingTaskType::Assign => 'assign',
            PendingTaskType::Loan => 'loan',
            PendingTaskType::Return => 'return',
            default => null,
        };

        if ($action === null) {
            return "El tipo de tarea \"{$taskType->label()}\" no soporta activos serializados.";
        }

        $blockingReason = AssetStatusTransitions::getBlockingReason($asset->status, $action);

        if ($blockingReason !== null) {
            return "Estado actual del activo: {$asset->status}. {$blockingReason}";
        }

        return null;
    }

    private function findAssetForLine(PendingTaskLine $line): ?Asset
    {
        $query = Asset::query()
            ->where('product_id', $line->product_id)
            ->whereNull('deleted_at');

        // Try to find by serial first, then by asset_tag
        if ($line->serial !== null && $line->serial !== '') {
            $asset = (clone $query)->where('serial', $line->serial)->first();
            if ($asset !== null) {
                return $asset;
            }
        }

        if ($line->asset_tag !== null && $line->asset_tag !== '') {
            return (clone $query)->where('asset_tag', $line->asset_tag)->first();
        }

        // Fallback: try serial-only match
        if ($line->serial !== null && $line->serial !== '') {
            return $query->where('serial', $line->serial)->first();
        }

        return null;
    }

    private function buildAssetNotFoundMessage(PendingTaskLine $line): string
    {
        $identifiers = [];
        if ($line->serial !== null && $line->serial !== '') {
            $identifiers[] = "S/N: {$line->serial}";
        }
        if ($line->asset_tag !== null && $line->asset_tag !== '') {
            $identifiers[] = "Tag: {$line->asset_tag}";
        }

        $idStr = $identifiers !== [] ? implode(', ', $identifiers) : 'sin identificador';

        return "No se encontró el activo ({$idStr}) para el producto seleccionado.";
    }

    private function validateQuantityLine(PendingTaskLine $line, PendingTaskType $taskType): ?string
    {
        // Verify product exists and is not soft-deleted
        $product = Product::query()
            ->whereNull('deleted_at')
            ->find($line->product_id);

        if ($product === null) {
            return 'El producto no existe o fue eliminado.';
        }

        // Check if stock is initialized
        if ($product->qty_total === null) {
            return 'El stock de este producto no está inicializado. Ajusta el inventario (Admin) antes de procesar.';
        }

        // For outbound movements, check stock availability
        if ($taskType->quantityDirection() === 'out') {
            $available = (int) $product->qty_total;
            $requested = (int) $line->quantity;

            if ($available < $requested) {
                return "Stock insuficiente. Disponible: {$available}, solicitado: {$requested}.";
            }
        }

        return null;
    }
}
