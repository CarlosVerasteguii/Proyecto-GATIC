<?php

namespace App\Actions\PendingTasks;

use App\Actions\PendingTasks\Concerns\ValidatesTaskLines;
use App\Enums\PendingTaskLineStatus;
use App\Enums\PendingTaskLineType;
use App\Enums\PendingTaskStatus;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AddLineToTask
{
    use ValidatesTaskLines;

    /**
     * @param  array{
     *     pending_task_id: int,
     *     line_type: string,
     *     product_id: int,
     *     serial?: string|null,
     *     asset_tag?: string|null,
     *     quantity?: int|null,
     *     employee_id: int,
     *     note: string
     * }  $data
     * @return array{line: PendingTaskLine, has_duplicates: bool}
     */
    public function execute(array $data): array
    {
        // Basic validation
        Validator::make($data, [
            'pending_task_id' => ['required', 'integer', Rule::exists('pending_tasks', 'id')->whereNull('deleted_at')],
            'line_type' => ['required', 'string', Rule::in(PendingTaskLineType::values())],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'note' => ['required', 'string', 'min:1', 'max:5000'],
        ])->validate();

        return DB::transaction(function () use ($data): array {
            $task = PendingTask::query()
                ->whereKey($data['pending_task_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Task must be in draft status
            if ($task->status !== PendingTaskStatus::Draft) {
                throw ValidationException::withMessages([
                    'pending_task_id' => ['La tarea no está en estado borrador. No se pueden añadir renglones.'],
                ]);
            }

            // Load product to validate line_type matches category
            $product = \App\Models\Product::with('category')->findOrFail($data['product_id']);
            $isSerialized = $product->category->is_serialized;
            $lineType = PendingTaskLineType::from($data['line_type']);

            if ($lineType === PendingTaskLineType::Serialized && ! $isSerialized) {
                throw ValidationException::withMessages([
                    'line_type' => ['El producto seleccionado no es de categoría serializada.'],
                ]);
            }

            if ($lineType === PendingTaskLineType::Quantity && $isSerialized) {
                throw ValidationException::withMessages([
                    'line_type' => ['El producto seleccionado es de categoría serializada, no se puede usar tipo "Por cantidad".'],
                ]);
            }

            // Validate based on line type
            if ($lineType === PendingTaskLineType::Serialized) {
                $this->validateSerializedLine($data);
            } else {
                $this->validateQuantityLine($data);
            }

            // Calculate next order (serialized by locking pending_tasks row in this transaction)
            $maxOrder = PendingTaskLine::where('pending_task_id', $task->id)->max('order') ?? 0;

            // Create line
            $line = PendingTaskLine::create([
                'pending_task_id' => $task->id,
                'line_type' => $lineType,
                'product_id' => $data['product_id'],
                'serial' => $data['serial'] ?? null,
                'asset_tag' => $data['asset_tag'] ?? null,
                'quantity' => $data['quantity'] ?? null,
                'employee_id' => $data['employee_id'],
                'note' => $data['note'],
                'line_status' => PendingTaskLineStatus::Pending,
                'order' => $maxOrder + 1,
            ]);

            // Check for duplicates
            $hasDuplicates = $this->checkForDuplicates($task, $line);

            return [
                'line' => $line,
                'has_duplicates' => $hasDuplicates,
            ];
        });
    }

    private function checkForDuplicates(PendingTask $task, PendingTaskLine $newLine): bool
    {
        if ($newLine->line_type !== PendingTaskLineType::Serialized) {
            return false;
        }

        $query = PendingTaskLine::where('pending_task_id', $task->id)
            ->where('id', '!=', $newLine->id);

        $hasDuplicate = false;

        if ($newLine->serial !== null && $newLine->serial !== '') {
            $hasDuplicate = $query->clone()->where('serial', $newLine->serial)->exists();
        }

        if (! $hasDuplicate && $newLine->asset_tag !== null && $newLine->asset_tag !== '') {
            $hasDuplicate = $query->clone()->where('asset_tag', $newLine->asset_tag)->exists();
        }

        return $hasDuplicate;
    }
}
