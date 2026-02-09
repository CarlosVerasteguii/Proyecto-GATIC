<?php

declare(strict_types=1);

namespace App\Actions\PendingTasks;

use App\Enums\PendingTaskLineStatus;
use App\Enums\PendingTaskLineType;
use App\Enums\PendingTaskStatus;
use App\Models\Asset;
use App\Models\Location;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProcessQuickCapturePendingTask
{
    /**
     * @param  array{
     *   task_id: int,
     *   actor_user_id: int,
     *   employee_id?: int|null,
     *   resolved_product_id?: int|null,
     *   location_id?: int|null,
     *   note?: string|null
     * }  $data
     * @return array{
     *   mode: 'lines'|'assets_stock_in'|'assets_retirement',
     *   created_lines: int,
     *   created_assets: int,
     *   updated_assets: int,
     *   skipped: int,
     *   errors: list<string>
     * }
     *
     * @throws ValidationException
     */
    public function execute(array $data): array
    {
        Validator::make($data, [
            'task_id' => ['required', 'integer', Rule::exists('pending_tasks', 'id')->whereNull('deleted_at')],
            'actor_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'resolved_product_id' => [
                'nullable',
                'integer',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
            ],
            'location_id' => [
                'nullable',
                'integer',
                Rule::exists('locations', 'id')->whereNull('deleted_at'),
            ],
            'note' => ['nullable', 'string', 'min:5', 'max:1000'],
        ], [
            'task_id.required' => 'La tarea es obligatoria.',
            'task_id.exists' => 'La tarea no existe o fue eliminada.',
            'employee_id.exists' => 'El empleado seleccionado no existe.',
            'resolved_product_id.exists' => 'El producto seleccionado no existe o fue eliminado.',
            'location_id.exists' => 'La ubicacion seleccionada no existe o fue eliminada.',
            'note.min' => 'La nota debe tener al menos :min caracteres.',
            'note.max' => 'La nota no puede exceder :max caracteres.',
        ])->validate();

        return DB::transaction(function () use ($data): array {
            /** @var PendingTask $task */
            $task = PendingTask::query()
                ->lockForUpdate()
                ->findOrFail($data['task_id']);

            if (! $task->hasQuickCapturePayload()) {
                throw ValidationException::withMessages([
                    'status' => ['Esta tarea no es una captura rapida.'],
                ]);
            }

            if (! $task->isQuickCaptureTask()) {
                throw ValidationException::withMessages([
                    'status' => ['Esta captura rapida ya fue procesada o convertida.'],
                ]);
            }

            if ($task->status !== PendingTaskStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => ['Solo se pueden procesar capturas rapidas en estado Borrador.'],
                ]);
            }

            if (PendingTaskLine::query()->where('pending_task_id', $task->id)->exists()) {
                throw ValidationException::withMessages([
                    'lines' => ['Esta captura rapida ya tiene renglones.'],
                ]);
            }

            $payload = is_array($task->payload) ? $task->payload : [];
            $kind = is_string($payload['kind'] ?? null) ? (string) $payload['kind'] : '';
            $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
            $itemsType = is_string($items['type'] ?? null) ? (string) $items['type'] : '';

            $result = [
                'mode' => 'lines',
                'created_lines' => 0,
                'created_assets' => 0,
                'updated_assets' => 0,
                'skipped' => 0,
                'errors' => [],
            ];

            $note = is_string($data['note'] ?? null) ? trim((string) $data['note']) : '';
            if ($note === '') {
                $note = 'Procesado desde captura rapida';
            }

            $nowIso = CarbonImmutable::now()->toIso8601String();

            if ($kind === 'quick_stock_in' && $itemsType === 'quantity') {
                $result = $this->convertQuantityToLines(
                    task: $task,
                    payload: $payload,
                    productIdOverride: isset($data['resolved_product_id']) ? (int) $data['resolved_product_id'] : null,
                    employeeId: isset($data['employee_id']) ? (int) $data['employee_id'] : null,
                    note: $note,
                );
            } elseif ($kind === 'quick_retirement' && $itemsType === 'quantity') {
                $result = $this->convertQuantityToLines(
                    task: $task,
                    payload: $payload,
                    productIdOverride: null,
                    employeeId: isset($data['employee_id']) ? (int) $data['employee_id'] : null,
                    note: $note,
                );
            } elseif ($kind === 'quick_stock_in' && $itemsType === 'serialized') {
                $result = $this->applyStockInSerializedAsAssets(
                    task: $task,
                    payload: $payload,
                    productIdOverride: isset($data['resolved_product_id']) ? (int) $data['resolved_product_id'] : null,
                    locationId: isset($data['location_id']) ? (int) $data['location_id'] : null,
                );
            } elseif ($kind === 'quick_retirement' && $itemsType === 'serialized') {
                $result = $this->applyRetirementSerializedAsAssets(
                    task: $task,
                    payload: $payload,
                );
            } else {
                throw ValidationException::withMessages([
                    'payload' => ['Esta captura rapida no es valida o no es soportada.'],
                ]);
            }

            if (($result['created_lines'] + $result['created_assets'] + $result['updated_assets'] + $result['skipped']) <= 0) {
                throw ValidationException::withMessages([
                    'status' => ['No se realizaron cambios al procesar la captura rapida.'],
                ]);
            }

            $task->payload = array_merge($payload, [
                'converted_at' => $nowIso,
                'converted_by_user_id' => (int) $data['actor_user_id'],
                'conversion' => [
                    'mode' => $result['mode'],
                    'created_lines' => $result['created_lines'],
                    'created_assets' => $result['created_assets'],
                    'updated_assets' => $result['updated_assets'],
                    'skipped' => $result['skipped'],
                    'errors' => $result['errors'],
                ],
            ]);

            if ($result['mode'] !== 'lines') {
                $hasErrors = $result['errors'] !== [];
                $task->status = $hasErrors ? PendingTaskStatus::PartiallyCompleted : PendingTaskStatus::Completed;
            }

            $task->save();

            return $result;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *   mode: 'lines',
     *   created_lines: int,
     *   created_assets: int,
     *   updated_assets: int,
     *   skipped: int,
     *   errors: list<string>
     * }
     */
    private function convertQuantityToLines(
        PendingTask $task,
        array &$payload,
        ?int $productIdOverride,
        ?int $employeeId,
        string $note,
    ): array {
        $productFromPayload = is_array($payload['product'] ?? null) ? $payload['product'] : null;
        $payloadProductId = is_int($productFromPayload['id'] ?? null) ? $productFromPayload['id'] : null;

        $resolvedProductId = $payloadProductId ?? $productIdOverride;
        if (! is_int($resolvedProductId)) {
            throw ValidationException::withMessages([
                'resolved_product_id' => ['Selecciona un producto para procesar esta captura.'],
            ]);
        }

        if ($employeeId === null) {
            throw ValidationException::withMessages([
                'employee_id' => ['El empleado es obligatorio para generar renglones.'],
            ]);
        }

        /** @var Product $product */
        $product = Product::query()
            ->with('category')
            ->whereNull('deleted_at')
            ->findOrFail($resolvedProductId);

        $isSerializedProduct = (bool) ($product->category?->is_serialized ?? false);

        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $qty = isset($items['quantity']) ? (int) $items['quantity'] : 0;
        if ($qty < 1) {
            throw ValidationException::withMessages([
                'quantity' => ['La cantidad debe ser al menos 1.'],
            ]);
        }

        if ($isSerializedProduct) {
            throw ValidationException::withMessages([
                'resolved_product_id' => ['El producto es serializado. Esta captura debe procesarse por seriales.'],
            ]);
        }

        $maxOrder = PendingTaskLine::where('pending_task_id', $task->id)->max('order') ?? 0;

        PendingTaskLine::create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Quantity,
            'product_id' => $product->id,
            'serial' => null,
            'asset_tag' => null,
            'quantity' => $qty,
            'employee_id' => $employeeId,
            'note' => $note,
            'line_status' => PendingTaskLineStatus::Pending,
            'error_message' => null,
            'order' => $maxOrder + 1,
        ]);

        if (is_array($productFromPayload) && ($productFromPayload['mode'] ?? null) === 'placeholder') {
            $payload['product'] = [
                'mode' => 'existing',
                'id' => (int) $product->id,
                'name' => (string) $product->name,
                'is_serialized' => false,
            ];
        }

        return [
            'mode' => 'lines',
            'created_lines' => 1,
            'created_assets' => 0,
            'updated_assets' => 0,
            'skipped' => 0,
            'errors' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *   mode: 'assets_stock_in',
     *   created_lines: int,
     *   created_assets: int,
     *   updated_assets: int,
     *   skipped: int,
     *   errors: list<string>
     * }
     */
    private function applyStockInSerializedAsAssets(
        PendingTask $task,
        array $payload,
        ?int $productIdOverride,
        ?int $locationId,
    ): array {
        $productFromPayload = is_array($payload['product'] ?? null) ? $payload['product'] : null;
        $payloadProductId = is_int($productFromPayload['id'] ?? null) ? $productFromPayload['id'] : null;

        $resolvedProductId = $payloadProductId ?? $productIdOverride;
        if (! is_int($resolvedProductId)) {
            throw ValidationException::withMessages([
                'resolved_product_id' => ['Selecciona un producto para procesar esta captura.'],
            ]);
        }

        if ($locationId === null) {
            throw ValidationException::withMessages([
                'location_id' => ['Selecciona una ubicacion para crear los activos.'],
            ]);
        }

        /** @var Product $product */
        $product = Product::query()
            ->with('category')
            ->whereNull('deleted_at')
            ->findOrFail($resolvedProductId);

        $isSerializedProduct = (bool) ($product->category?->is_serialized ?? false);
        if (! $isSerializedProduct) {
            throw ValidationException::withMessages([
                'resolved_product_id' => ['El producto no es serializado.'],
            ]);
        }

        Location::query()
            ->whereNull('deleted_at')
            ->findOrFail($locationId);

        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $serials = is_array($items['serials'] ?? null) ? $items['serials'] : [];
        /** @var list<string> $normalizedSerials */
        $normalizedSerials = [];
        foreach ($serials as $serial) {
            if (! is_string($serial)) {
                continue;
            }
            $normalized = Asset::normalizeSerial($serial);
            if ($normalized !== null) {
                $normalizedSerials[] = $normalized;
            }
        }

        if ($normalizedSerials === []) {
            throw ValidationException::withMessages([
                'serials' => ['No hay seriales validos para procesar.'],
            ]);
        }

        $existing = Asset::query()
            ->where('product_id', $product->id)
            ->whereIn('serial', $normalizedSerials)
            ->pluck('serial')
            ->all();

        $existingMap = [];
        foreach ($existing as $s) {
            if (is_string($s)) {
                $existingMap[$s] = true;
            }
        }

        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($normalizedSerials as $serial) {
            if (isset($existingMap[$serial])) {
                $errors[] = "Ya existe un activo con serial {$serial} para este producto.";

                continue;
            }

            try {
                Asset::query()->create([
                    'product_id' => $product->id,
                    'location_id' => $locationId,
                    'serial' => $serial,
                    'asset_tag' => null,
                    'status' => Asset::STATUS_AVAILABLE,
                    'current_employee_id' => null,
                ]);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = "No se pudo crear el activo con serial {$serial}.";
            }
        }

        return [
            'mode' => 'assets_stock_in',
            'created_lines' => 0,
            'created_assets' => $created,
            'updated_assets' => 0,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *   mode: 'assets_retirement',
     *   created_lines: int,
     *   created_assets: int,
     *   updated_assets: int,
     *   skipped: int,
     *   errors: list<string>
     * }
     */
    private function applyRetirementSerializedAsAssets(
        PendingTask $task,
        array $payload,
    ): array {
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $serials = is_array($items['serials'] ?? null) ? $items['serials'] : [];

        /** @var list<string> $normalizedSerials */
        $normalizedSerials = [];
        foreach ($serials as $serial) {
            if (! is_string($serial)) {
                continue;
            }
            $normalized = Asset::normalizeSerial($serial);
            if ($normalized !== null) {
                $normalizedSerials[] = $normalized;
            }
        }

        if ($normalizedSerials === []) {
            throw ValidationException::withMessages([
                'serials' => ['No hay seriales validos para procesar.'],
            ]);
        }

        $assets = Asset::query()
            ->whereNull('deleted_at')
            ->whereIn('serial', $normalizedSerials)
            ->get(['id', 'serial', 'product_id', 'status']);

        /** @var array<string, list<Asset>> $bySerial */
        $bySerial = [];
        foreach ($assets as $asset) {
            $bySerial[$asset->serial][] = $asset;
        }

        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($normalizedSerials as $serial) {
            $matches = $bySerial[$serial] ?? [];

            if (count($matches) === 0) {
                $errors[] = "No se encontro ningun activo con serial {$serial}.";

                continue;
            }

            if (count($matches) > 1) {
                $errors[] = "El serial {$serial} es ambiguo (hay multiples activos).";

                continue;
            }

            $asset = $matches[0];

            if ($asset->status === Asset::STATUS_PENDING_RETIREMENT || $asset->status === Asset::STATUS_RETIRED) {
                $skipped++;

                continue;
            }

            if ($asset->status !== Asset::STATUS_AVAILABLE) {
                $errors[] = "El activo {$serial} no esta Disponible (estado actual: {$asset->status}).";

                continue;
            }

            $asset->status = Asset::STATUS_PENDING_RETIREMENT;
            $asset->save();
            $updated++;
        }

        return [
            'mode' => 'assets_retirement',
            'created_lines' => 0,
            'created_assets' => 0,
            'updated_assets' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }
}
