<?php

namespace App\Livewire\PendingTasks;

use App\Actions\PendingTasks\AddLineToTask;
use App\Actions\PendingTasks\AddSerializedLinesToTask;
use App\Actions\PendingTasks\ClearLineError;
use App\Actions\PendingTasks\Concerns\ValidatesTaskLines;
use App\Actions\PendingTasks\FinalizePendingTask;
use App\Actions\PendingTasks\MarkTaskAsReady;
use App\Actions\PendingTasks\RemoveLineFromTask;
use App\Actions\PendingTasks\UpdateTaskLine;
use App\Actions\PendingTasks\ValidatePendingTaskLine;
use App\Enums\PendingTaskLineStatus;
use App\Enums\PendingTaskLineType;
use App\Enums\PendingTaskStatus;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class PendingTaskShow extends Component
{
    use ValidatesTaskLines;

    public int $pendingTask;

    public ?PendingTask $task = null;

    // Modal state
    public bool $showLineModal = false;

    public ?int $editingLineId = null;

    // Form fields
    public string $lineType = '';

    public ?int $productId = null;

    public string $serial = '';

    public string $assetTag = '';

    public ?string $quantity = null;

    public string $serializedBulkInput = '';

    /** @var array<int, array{line: int, value: string, status: string, status_label: string, message: string|null}> */
    public array $serializedBulkPreview = [];

    public int $serializedBulkCount = 0;

    public int $serializedBulkOkCount = 0;

    public int $serializedBulkDuplicateCount = 0;

    public int $serializedBulkInvalidCount = 0;

    public ?string $serializedBulkLimitError = null;

    public int $serializedBulkMaxLines = 200;

    public ?int $employeeId = null;

    public string $note = '';

    // Product selection
    /** @var array<int, array{id: int, name: string, is_serialized: bool}> */
    public array $products = [];

    /** @var array<string, list<int>> */
    public array $duplicates = [];

    // Process mode state
    public bool $isProcessMode = false;

    public bool $showFinalizeConfirmModal = false;

    /** @var array{applied_count: int, error_count: int, skipped_count: int}|null */
    public ?array $finalizeResult = null;

    // Edit line in process mode
    public bool $showProcessLineModal = false;

    public ?int $editingProcessLineId = null;

    public string $processLineSerial = '';

    public string $processLineAssetTag = '';

    public ?string $processLineQuantity = null;

    public ?int $processLineEmployeeId = null;

    public string $processLineNote = '';

    public function mount(int $pendingTask): void
    {
        Gate::authorize('inventory.manage');

        $this->pendingTask = $pendingTask;
        $this->serializedBulkMaxLines = (int) config('gatic.pending_tasks.bulk_paste.max_lines', 200);
        $this->loadTask();
        $this->loadProducts();
    }

    private function loadTask(): void
    {
        $this->task = PendingTask::with(['creator', 'lines.product', 'lines.employee'])
            ->findOrFail($this->pendingTask);

        $this->duplicates = $this->task->getDuplicateIdentifiers();
    }

    private function loadProducts(): void
    {
        $this->products = Product::query()
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereNull('products.deleted_at')
            ->whereNull('categories.deleted_at')
            ->select('products.id', 'products.name', 'categories.is_serialized')
            ->orderBy('products.name')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'is_serialized' => (bool) $p->getAttribute('is_serialized'),
            ])
            ->toArray();
    }

    public function openAddLineModal(): void
    {
        if (! $this->task?->isDraft()) {
            return;
        }

        $this->resetForm();
        $this->editingLineId = null;
        $this->showLineModal = true;
    }

    public function openEditLineModal(int $lineId): void
    {
        if (! $this->task?->isDraft()) {
            return;
        }

        $line = PendingTaskLine::find($lineId);
        if (! $line || $line->pending_task_id !== $this->pendingTask) {
            return;
        }

        $this->editingLineId = $lineId;
        $this->lineType = $line->line_type->value;
        $this->productId = $line->product_id;
        $this->serial = $line->serial ?? '';
        $this->assetTag = $line->asset_tag ?? '';
        $this->quantity = $line->quantity !== null ? (string) $line->quantity : null;
        $this->employeeId = $line->employee_id;
        $this->note = $line->note;
        $this->showLineModal = true;
    }

    public function closeModal(): void
    {
        $this->showLineModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->lineType = '';
        $this->productId = null;
        $this->serial = '';
        $this->assetTag = '';
        $this->quantity = null;
        $this->serializedBulkInput = '';
        $this->serializedBulkPreview = [];
        $this->serializedBulkCount = 0;
        $this->serializedBulkOkCount = 0;
        $this->serializedBulkDuplicateCount = 0;
        $this->serializedBulkInvalidCount = 0;
        $this->serializedBulkLimitError = null;
        $this->employeeId = null;
        $this->note = '';
        $this->editingLineId = null;
        $this->resetErrorBag();
    }

    #[On('employee-selected')]
    public function onEmployeeSelected(?int $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    public function updatedProductId(): void
    {
        // Auto-set line type based on product category
        if ($this->productId) {
            $product = collect($this->products)->firstWhere('id', $this->productId);
            if ($product) {
                $this->lineType = $product['is_serialized']
                    ? PendingTaskLineType::Serialized->value
                    : PendingTaskLineType::Quantity->value;
            }
        }

        $this->rebuildSerializedBulkPreview();
    }

    public function updatedLineType(): void
    {
        if ($this->lineType !== PendingTaskLineType::Serialized->value) {
            $this->serializedBulkInput = '';
        }

        $this->rebuildSerializedBulkPreview();
    }

    public function updatedSerializedBulkInput(): void
    {
        $this->rebuildSerializedBulkPreview();
    }

    public function saveLine(): void
    {
        Gate::authorize('inventory.manage');

        if (! $this->task?->isDraft()) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => 'La tarea no está en estado borrador.',
            ]);

            return;
        }

        try {
            $quantity = null;
            if ($this->lineType === PendingTaskLineType::Quantity->value) {
                $validator = Validator::make(
                    ['quantity' => $this->quantity],
                    ['quantity' => ['required', 'integer', 'min:1']],
                );

                if ($validator->fails()) {
                    $this->addError('quantity', $validator->errors()->first('quantity'));

                    return;
                }

                $quantity = (int) $validator->validated()['quantity'];
            }

            if ($this->editingLineId) {
                $action = new UpdateTaskLine;
                $result = $action->execute($this->editingLineId, [
                    'line_type' => $this->lineType,
                    'product_id' => $this->productId,
                    'serial' => $this->lineType === PendingTaskLineType::Serialized->value ? $this->serial : null,
                    'asset_tag' => $this->lineType === PendingTaskLineType::Serialized->value ? $this->assetTag : null,
                    'quantity' => $quantity,
                    'employee_id' => $this->employeeId,
                    'note' => $this->note,
                ]);

                $message = 'Renglón actualizado.';
            } else {
                if ($this->lineType === PendingTaskLineType::Serialized->value) {
                    $this->rebuildSerializedBulkPreview();

                    if ($this->serializedBulkCount < 1) {
                        $this->addError('serializedBulkInput', 'Pega al menos una serie.');

                        return;
                    }

                    if ($this->serializedBulkInvalidCount > 0 || $this->serializedBulkLimitError !== null) {
                        $this->addError('serializedBulkInput', 'Corrige las líneas inválidas antes de guardar.');

                        return;
                    }

                    $action = new AddSerializedLinesToTask;
                    $result = $action->execute([
                        'pending_task_id' => $this->pendingTask,
                        'product_id' => $this->productId,
                        'serials' => $this->extractSerializedBulkSerials(),
                        'employee_id' => $this->employeeId,
                        'note' => $this->note,
                    ]);

                    $message = "Renglones añadidos: {$result['lines_created']}.";
                } else {
                    $action = new AddLineToTask;
                    $result = $action->execute([
                        'pending_task_id' => $this->pendingTask,
                        'line_type' => $this->lineType,
                        'product_id' => $this->productId,
                        'serial' => $this->lineType === PendingTaskLineType::Serialized->value ? $this->serial : null,
                        'asset_tag' => $this->lineType === PendingTaskLineType::Serialized->value ? $this->assetTag : null,
                        'quantity' => $quantity,
                        'employee_id' => $this->employeeId,
                        'note' => $this->note,
                    ]);

                    $message = 'Renglón añadido.';
                }
            }

            if ($result['has_duplicates'] || $this->serializedBulkDuplicateCount > 0) {
                $message .= ' (Duplicado detectado)';
            }

            $this->closeModal();
            $this->loadTask();

            session()->flash('toast', [
                'type' => 'success',
                'message' => $message,
            ]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field === 'serials' ? 'serializedBulkInput' : $field, $messages[0]);
            }
        }
    }

    public function removeLine(int $lineId): void
    {
        Gate::authorize('inventory.manage');

        if (! $this->task?->isDraft()) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => 'La tarea no está en estado borrador.',
            ]);

            return;
        }

        try {
            $action = new RemoveLineFromTask;
            $action->execute($lineId);

            $this->loadTask();

            session()->flash('toast', [
                'type' => 'success',
                'message' => 'Renglón eliminado.',
            ]);
        } catch (ValidationException $e) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => $e->errors()['pending_task_id'][0] ?? $e->getMessage(),
            ]);
        }
    }

    public function markAsReady(): void
    {
        Gate::authorize('inventory.manage');

        try {
            $action = new MarkTaskAsReady;
            $action->execute($this->pendingTask);

            $this->loadTask();

            session()->flash('toast', [
                'type' => 'success',
                'message' => 'Tarea marcada como lista. Ya no puedes editar renglones.',
            ]);
        } catch (ValidationException $e) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => $e->errors()['lines'][0] ?? $e->errors()['status'][0] ?? $e->getMessage(),
            ]);
        }
    }

    public function isDuplicate(int $lineId): bool
    {
        foreach ($this->duplicates as $lineIds) {
            if (in_array($lineId, $lineIds, true)) {
                return true;
            }
        }

        return false;
    }

    // =========================================================================
    // Process Mode Methods
    // =========================================================================

    /**
     * Check if task can enter process mode
     */
    public function canProcess(): bool
    {
        if (! $this->task) {
            return false;
        }

        return in_array($this->task->status, [
            PendingTaskStatus::Ready,
            PendingTaskStatus::Processing,
            PendingTaskStatus::PartiallyCompleted,
        ], true);
    }

    /**
     * Enter process mode
     */
    public function enterProcessMode(): void
    {
        Gate::authorize('inventory.manage');

        if (! $this->canProcess()) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => 'La tarea no está en un estado que permita procesarla.',
            ]);

            return;
        }

        $this->isProcessMode = true;
        $this->finalizeResult = null;

        // Update task status to processing if it was ready
        if ($this->task && $this->task->status === PendingTaskStatus::Ready) {
            $this->task->status = PendingTaskStatus::Processing;
            $this->task->save();
            $this->loadTask();
        }
    }

    /**
     * Exit process mode
     */
    public function exitProcessMode(): void
    {
        $this->isProcessMode = false;
        $this->finalizeResult = null;
        $this->closeProcessLineModal();
    }

    /**
     * Validate a single line
     */
    public function validateLine(int $lineId): void
    {
        Gate::authorize('inventory.manage');

        try {
            $action = new ValidatePendingTaskLine;
            $result = $action->execute($lineId);

            $this->loadTask();

            if ($result['valid']) {
                session()->flash('toast', [
                    'type' => 'success',
                    'message' => 'Renglón validado correctamente.',
                ]);
            } else {
                session()->flash('toast', [
                    'type' => 'error',
                    'message' => $result['error_message'] ?? 'Error de validación.',
                ]);
            }
        } catch (\Throwable $e) {
            $this->flashUnexpectedError($e, 'validar el renglón');
        }
    }

    /**
     * Clear error from a line
     */
    public function clearLineError(int $lineId): void
    {
        Gate::authorize('inventory.manage');

        try {
            $action = new ClearLineError;
            $action->execute($lineId);

            $this->loadTask();

            session()->flash('toast', [
                'type' => 'success',
                'message' => 'Error limpiado. El renglón está pendiente de validación.',
            ]);
        } catch (ValidationException $e) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => $e->errors()['line_status'][0] ?? $e->getMessage(),
            ]);
        }
    }

    /**
     * Open modal to edit a line in process mode
     */
    public function openProcessLineModal(int $lineId): void
    {
        Gate::authorize('inventory.manage');

        $line = PendingTaskLine::find($lineId);
        if (! $line || $line->pending_task_id !== $this->pendingTask) {
            return;
        }

        // Can't edit applied lines
        if ($line->line_status === PendingTaskLineStatus::Applied) {
            session()->flash('toast', [
                'type' => 'error',
                'message' => 'No se puede editar un renglón ya aplicado.',
            ]);

            return;
        }

        $this->editingProcessLineId = $lineId;
        $this->processLineSerial = $line->serial ?? '';
        $this->processLineAssetTag = $line->asset_tag ?? '';
        $this->processLineQuantity = $line->quantity !== null ? (string) $line->quantity : null;
        $this->processLineEmployeeId = $line->employee_id;
        $this->processLineNote = $line->note;
        $this->showProcessLineModal = true;
    }

    /**
     * Close process line modal
     */
    public function closeProcessLineModal(): void
    {
        $this->showProcessLineModal = false;
        $this->editingProcessLineId = null;
        $this->processLineSerial = '';
        $this->processLineAssetTag = '';
        $this->processLineQuantity = null;
        $this->processLineEmployeeId = null;
        $this->processLineNote = '';
        $this->resetErrorBag();
    }

    #[On('process-employee-selected')]
    public function onProcessEmployeeSelected(?int $employeeId): void
    {
        $this->processLineEmployeeId = $employeeId;
    }

    /**
     * Save line edit in process mode
     */
    public function saveProcessLine(): void
    {
        Gate::authorize('inventory.manage');

        if (! $this->editingProcessLineId) {
            return;
        }

        $line = PendingTaskLine::find($this->editingProcessLineId);
        if (! $line || $line->pending_task_id !== $this->pendingTask) {
            return;
        }

        try {
            $quantity = null;
            if ($line->line_type === PendingTaskLineType::Quantity) {
                $validator = Validator::make(
                    ['quantity' => $this->processLineQuantity],
                    ['quantity' => ['required', 'integer', 'min:1']],
                );

                if ($validator->fails()) {
                    $this->addError('processLineQuantity', $validator->errors()->first('quantity'));

                    return;
                }

                $quantity = (int) $validator->validated()['quantity'];
            }

            $action = new UpdateTaskLine;
            $action->execute($this->editingProcessLineId, [
                'line_type' => $line->line_type->value,
                'product_id' => $line->product_id,
                'serial' => $line->isSerialized() ? $this->processLineSerial : null,
                'asset_tag' => $line->isSerialized() ? $this->processLineAssetTag : null,
                'quantity' => $quantity,
                'employee_id' => $this->processLineEmployeeId,
                'note' => $this->processLineNote,
            ]);

            // Clear any previous error and set to pending
            $line->line_status = PendingTaskLineStatus::Pending;
            $line->error_message = null;
            $line->save();

            $this->closeProcessLineModal();
            $this->loadTask();

            session()->flash('toast', [
                'type' => 'success',
                'message' => 'Renglón actualizado. Re-valida para verificar.',
            ]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $mappedField = match ($field) {
                    'serial' => 'processLineSerial',
                    'asset_tag' => 'processLineAssetTag',
                    'quantity' => 'processLineQuantity',
                    'employee_id' => 'processLineEmployeeId',
                    'note' => 'processLineNote',
                    default => $field,
                };
                $this->addError($mappedField, $messages[0]);
            }
        }
    }

    /**
     * Show finalize confirmation modal
     */
    public function showFinalizeConfirm(): void
    {
        Gate::authorize('inventory.manage');

        $this->showFinalizeConfirmModal = true;
    }

    /**
     * Hide finalize confirmation modal
     */
    public function hideFinalizeConfirm(): void
    {
        $this->showFinalizeConfirmModal = false;
    }

    /**
     * Execute finalize task
     */
    public function finalizeTask(): void
    {
        Gate::authorize('inventory.manage');

        $this->hideFinalizeConfirm();

        if (! $this->task) {
            return;
        }

        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $action = new FinalizePendingTask;
            $result = $action->execute($this->pendingTask, $user->id);

            $this->loadTask();

            $this->finalizeResult = [
                'applied_count' => $result['applied_count'],
                'error_count' => $result['error_count'],
                'skipped_count' => $result['skipped_count'],
            ];

            $message = "Finalización completada: {$result['applied_count']} aplicados";
            if ($result['error_count'] > 0) {
                $message .= ", {$result['error_count']} errores";
            }
            if ($result['skipped_count'] > 0) {
                $message .= ", {$result['skipped_count']} ya aplicados";
            }

            session()->flash('toast', [
                'type' => $result['error_count'] > 0 ? 'warning' : 'success',
                'message' => $message,
            ]);
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            session()->flash('toast', [
                'type' => 'error',
                'message' => $firstError ?? 'Error al finalizar la tarea.',
            ]);
        } catch (\Throwable $e) {
            $this->flashUnexpectedError($e, 'finalizar la tarea');
        }
    }

    private function flashUnexpectedError(\Throwable $e, string $action): void
    {
        $errorId = uniqid('ERR-');

        Log::error("PendingTaskShow unexpected error [{$errorId}] ({$action})", [
            'pending_task_id' => $this->pendingTask,
            'user_id' => Auth::id(),
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        $message = "Error inesperado al {$action} (ID: {$errorId}). Contacta a soporte.";

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user && ($user->role ?? null) === 'Admin') {
            $message .= ' '.$e->getMessage();
        }

        session()->flash('toast', [
            'type' => 'error',
            'message' => $message,
        ]);
    }

    /**
     * Get summary of line statuses for process mode
     *
     * @return array{pending: int, processing: int, applied: int, error: int}
     */
    public function getLineStatusSummary(): array
    {
        if (! $this->task) {
            return ['pending' => 0, 'processing' => 0, 'applied' => 0, 'error' => 0];
        }

        $summary = ['pending' => 0, 'processing' => 0, 'applied' => 0, 'error' => 0];

        foreach ($this->task->lines as $line) {
            $key = $line->line_status->value;
            if (array_key_exists($key, $summary)) {
                $summary[$key]++;
            }
        }

        return $summary;
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        return view('livewire.pending-tasks.pending-task-show', [
            'lineTypes' => PendingTaskLineType::cases(),
            'lineStatusSummary' => $this->getLineStatusSummary(),
        ]);
    }

    /**
     * @return list<array{line: int, value: string}>
     */
    private function parseSerializedBulkInput(): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $this->serializedBulkInput) ?: [];
        $parsed = [];

        foreach ($lines as $index => $line) {
            $value = trim($line);
            if ($value === '') {
                continue;
            }

            $parsed[] = [
                'line' => $index + 1,
                'value' => $value,
            ];
        }

        return $parsed;
    }

    /**
     * @return list<string>
     */
    private function extractSerializedBulkSerials(): array
    {
        return array_map(
            fn (array $item): string => $item['value'],
            $this->parseSerializedBulkInput(),
        );
    }

    private function rebuildSerializedBulkPreview(): void
    {
        $this->serializedBulkPreview = [];
        $this->serializedBulkCount = 0;
        $this->serializedBulkOkCount = 0;
        $this->serializedBulkDuplicateCount = 0;
        $this->serializedBulkInvalidCount = 0;
        $this->serializedBulkLimitError = null;

        if ($this->editingLineId !== null) {
            return;
        }

        if ($this->lineType !== PendingTaskLineType::Serialized->value) {
            return;
        }

        $parsed = $this->parseSerializedBulkInput();
        if ($parsed === []) {
            return;
        }

        $this->serializedBulkCount = count($parsed);

        $max = max(1, $this->serializedBulkMaxLines);
        if ($this->serializedBulkCount > $max) {
            $this->serializedBulkLimitError = "Límite máximo: {$max} líneas. Reduce el pegado para poder guardar.";
        }

        $values = array_map(fn (array $item): string => $item['value'], $parsed);
        $counts = array_count_values($values);

        $existingSerialsSet = [];
        if ($this->task) {
            foreach ($this->task->lines as $line) {
                if ($line->serial !== null && $line->serial !== '') {
                    $existingSerialsSet[$line->serial] = true;
                }
            }
        }

        foreach ($parsed as $index => $item) {
            $value = $item['value'];
            $status = 'ok';
            $statusLabel = 'OK';
            $message = null;

            if ($index >= $max) {
                $status = 'invalid';
                $statusLabel = 'Inválida';
                $message = "Excede el límite de {$max} líneas.";
            } else {
                $validationError = null;
                $status = 'invalid';
                $statusLabel = 'Inválida';

                try {
                    $this->validateSerializedLine([
                        'serial' => $value,
                        'asset_tag' => null,
                    ]);
                } catch (ValidationException $e) {
                    $errors = $e->errors();
                    $validationError = $errors['serial'][0] ?? $errors['asset_tag'][0] ?? $e->getMessage();
                }

                if ($validationError !== null) {
                    $message = $validationError;
                } else {
                    $status = 'ok';
                    $statusLabel = 'OK';

                    $duplicateReasons = [];

                    if (($counts[$value] ?? 0) > 1) {
                        $duplicateReasons[] = 'Repetida en el pegado';
                    }

                    if (isset($existingSerialsSet[$value])) {
                        $duplicateReasons[] = 'Ya existe en la tarea';
                    }

                    if ($duplicateReasons !== []) {
                        $status = 'duplicate';
                        $statusLabel = 'Duplicada';
                        $message = implode(' · ', $duplicateReasons);
                    }
                }
            }

            $this->serializedBulkPreview[] = [
                'line' => $item['line'],
                'value' => $value,
                'status' => $status,
                'status_label' => $statusLabel,
                'message' => $message,
            ];

            if ($status === 'ok') {
                $this->serializedBulkOkCount++;
            } elseif ($status === 'duplicate') {
                $this->serializedBulkDuplicateCount++;
            } else {
                $this->serializedBulkInvalidCount++;
            }
        }
    }
}
