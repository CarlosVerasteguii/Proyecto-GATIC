<?php

namespace App\Actions\PendingTasks;

use App\Actions\PendingTasks\Concerns\ValidatesTaskLines;
use App\Enums\PendingTaskLineStatus;
use App\Enums\PendingTaskLineType;
use App\Enums\PendingTaskStatus;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AddSerializedLinesToTask
{
    use ValidatesTaskLines;

    /**
     * @param  array{
     *     pending_task_id: int,
     *     product_id: int,
     *     serials: list<string>,
     *     employee_id: int,
     *     note: string
     * }  $data
     * @return array{lines_created: int, has_duplicates: bool}
     */
    public function execute(array $data): array
    {
        Validator::make($data, [
            'pending_task_id' => ['required', 'integer', Rule::exists('pending_tasks', 'id')->whereNull('deleted_at')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'serials' => ['required', 'array', 'min:1'],
            'serials.*' => ['required', 'string'],
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'note' => ['required', 'string', 'min:1', 'max:5000'],
        ])->validate();

        $maxLines = (int) config('gatic.pending_tasks.bulk_paste.max_lines', 200);

        $serials = array_values(array_filter(array_map(
            fn (string $serial): string => trim($serial),
            $data['serials'],
        ), fn (string $serial): bool => $serial !== ''));

        if ($serials === []) {
            throw ValidationException::withMessages([
                'serials' => ['Debe proporcionar al menos una serie.'],
            ]);
        }

        if (count($serials) > $maxLines) {
            throw ValidationException::withMessages([
                'serials' => ["El máximo permitido es {$maxLines} líneas."],
            ]);
        }

        return DB::transaction(function () use ($data, $serials): array {
            $task = PendingTask::query()
                ->whereKey($data['pending_task_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($task->isQuickCaptureTask()) {
                throw ValidationException::withMessages([
                    'pending_task_id' => ['Esta tarea fue creada como captura rápida y no permite añadir renglones.'],
                ]);
            }

            if ($task->status !== PendingTaskStatus::Draft) {
                throw ValidationException::withMessages([
                    'pending_task_id' => ['La tarea no está en estado borrador. No se pueden añadir renglones.'],
                ]);
            }

            $product = Product::with('category')->findOrFail($data['product_id']);
            if (! $product->category->is_serialized) {
                throw ValidationException::withMessages([
                    'product_id' => ['El producto seleccionado no es de categoría serializada.'],
                ]);
            }

            foreach ($serials as $serial) {
                try {
                    $this->validateSerializedLine([
                        'serial' => $serial,
                        'asset_tag' => null,
                    ]);
                } catch (ValidationException $e) {
                    $errors = $e->errors();
                    $reason = $errors['serial'][0] ?? $errors['asset_tag'][0] ?? $e->getMessage();
                    throw ValidationException::withMessages([
                        'serials' => ["Serie inválida: \"{$serial}\". Motivo: {$reason}"],
                    ]);
                }
            }

            $serialCounts = array_count_values($serials);
            $duplicatesInInput = array_filter($serialCounts, fn (int $count): bool => $count > 1);

            $uniqueSerials = array_values(array_unique($serials));
            $existingSerials = PendingTaskLine::query()
                ->where('pending_task_id', $task->id)
                ->whereIn('serial', $uniqueSerials)
                ->pluck('serial')
                ->all();

            $maxOrder = PendingTaskLine::where('pending_task_id', $task->id)->max('order') ?? 0;
            $nextOrder = $maxOrder + 1;

            foreach ($serials as $serial) {
                PendingTaskLine::create([
                    'pending_task_id' => $task->id,
                    'line_type' => PendingTaskLineType::Serialized,
                    'product_id' => $product->id,
                    'serial' => $serial,
                    'asset_tag' => null,
                    'quantity' => null,
                    'employee_id' => $data['employee_id'],
                    'note' => $data['note'],
                    'line_status' => PendingTaskLineStatus::Pending,
                    'order' => $nextOrder,
                ]);

                $nextOrder++;
            }

            return [
                'lines_created' => count($serials),
                'has_duplicates' => count($duplicatesInInput) > 0 || count($existingSerials) > 0,
            ];
        });
    }
}
