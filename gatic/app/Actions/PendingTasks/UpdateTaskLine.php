<?php

namespace App\Actions\PendingTasks;

use App\Actions\PendingTasks\Concerns\ValidatesTaskLines;
use App\Enums\PendingTaskLineType;
use App\Enums\PendingTaskStatus;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateTaskLine
{
    use ValidatesTaskLines;

    /**
     * @param  array{
     *     line_type?: string,
     *     product_id?: int,
     *     serial?: string|null,
     *     asset_tag?: string|null,
     *     quantity?: int|null,
     *     employee_id?: int,
     *     note?: string
     * }  $data
     * @return array{line: PendingTaskLine, has_duplicates: bool}
     */
    public function execute(int $lineId, array $data): array
    {
        $line = PendingTaskLine::with('pendingTask')->findOrFail($lineId);
        $task = $line->pendingTask;

        if ($task->isQuickCaptureTask()) {
            throw ValidationException::withMessages([
                'pending_task_id' => ['Esta tarea fue creada como captura rápida y no permite editar renglones.'],
            ]);
        }

        // Task must be in draft status
        if ($task->status !== PendingTaskStatus::Draft) {
            throw ValidationException::withMessages([
                'pending_task_id' => ['La tarea no está en estado borrador. No se pueden editar renglones.'],
            ]);
        }

        // Validate provided fields
        Validator::make($data, [
            'line_type' => ['sometimes', 'string', Rule::in(PendingTaskLineType::values())],
            'product_id' => ['sometimes', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'employee_id' => ['sometimes', 'integer', Rule::exists('employees', 'id')],
            'note' => ['sometimes', 'string', 'min:1', 'max:5000'],
        ])->validate();

        // If changing line_type or product, validate compatibility
        $newLineType = isset($data['line_type'])
            ? PendingTaskLineType::from($data['line_type'])
            : $line->line_type;

        $productId = $data['product_id'] ?? $line->product_id;

        if (isset($data['line_type']) || isset($data['product_id'])) {
            $product = \App\Models\Product::with('category')->findOrFail($productId);
            $isSerialized = $product->category->is_serialized;

            if ($newLineType === PendingTaskLineType::Serialized && ! $isSerialized) {
                throw ValidationException::withMessages([
                    'line_type' => ['El producto seleccionado no es de categoría serializada.'],
                ]);
            }

            if ($newLineType === PendingTaskLineType::Quantity && $isSerialized) {
                throw ValidationException::withMessages([
                    'line_type' => ['El producto seleccionado es de categoría serializada, no se puede usar tipo "Por cantidad".'],
                ]);
            }
        }

        // Merge data for validation
        $mergedData = [
            'serial' => $data['serial'] ?? $line->serial,
            'asset_tag' => $data['asset_tag'] ?? $line->asset_tag,
            'quantity' => $data['quantity'] ?? $line->quantity,
        ];

        // Validate based on line type
        if ($newLineType === PendingTaskLineType::Serialized) {
            $this->validateSerializedLine($mergedData);
        } else {
            $this->validateQuantityLine($mergedData);
        }

        // Update line
        $updateData = [];

        if (isset($data['line_type'])) {
            $updateData['line_type'] = $newLineType;
        }
        if (isset($data['product_id'])) {
            $updateData['product_id'] = $data['product_id'];
        }
        if (array_key_exists('serial', $data)) {
            $updateData['serial'] = $data['serial'];
        }
        if (array_key_exists('asset_tag', $data)) {
            $updateData['asset_tag'] = $data['asset_tag'];
        }
        if (array_key_exists('quantity', $data)) {
            $updateData['quantity'] = $data['quantity'];
        }
        if (isset($data['employee_id'])) {
            $updateData['employee_id'] = $data['employee_id'];
        }
        if (isset($data['note'])) {
            $updateData['note'] = $data['note'];
        }

        $line->update($updateData);
        $line->refresh();

        // Check for duplicates
        $hasDuplicates = $this->checkForDuplicates($task, $line);

        return [
            'line' => $line,
            'has_duplicates' => $hasDuplicates,
        ];
    }

    private function checkForDuplicates(PendingTask $task, PendingTaskLine $line): bool
    {
        if ($line->line_type !== PendingTaskLineType::Serialized) {
            return false;
        }

        $query = PendingTaskLine::where('pending_task_id', $task->id)
            ->where('id', '!=', $line->id);

        $hasDuplicate = false;

        if ($line->serial !== null && $line->serial !== '') {
            $hasDuplicate = $query->clone()->where('serial', $line->serial)->exists();
        }

        if (! $hasDuplicate && $line->asset_tag !== null && $line->asset_tag !== '') {
            $hasDuplicate = $query->clone()->where('asset_tag', $line->asset_tag)->exists();
        }

        return $hasDuplicate;
    }
}
