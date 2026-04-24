<?php

namespace App\Livewire\PendingTasks;

use App\Actions\PendingTasks\AcquirePendingTaskLock;
use App\Actions\PendingTasks\AddSerializedLinesToTask;
use App\Actions\PendingTasks\ClearLineError;
use App\Actions\PendingTasks\FinalizePendingTask;
use App\Actions\PendingTasks\ForceClaimPendingTaskLock;
use App\Actions\PendingTasks\ForceReleasePendingTaskLock;
use App\Actions\PendingTasks\HeartbeatPendingTaskLock;
use App\Actions\PendingTasks\MarkTaskAsReady;
use App\Actions\PendingTasks\ProcessQuickCapturePendingTask;
use App\Actions\PendingTasks\ReleasePendingTaskLock;
use App\Actions\PendingTasks\RemoveLineFromTask;
use App\Actions\PendingTasks\ValidatePendingTaskLine;
use App\Enums\PendingTaskLineType;
use App\Enums\PendingTaskStatus;
use App\Enums\UserRole;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\PendingTask;
use App\Models\Product;
use App\Support\Errors\ErrorReporter;
use App\Support\PendingTasks\PendingTaskDraftLineSaver;
use App\Support\PendingTasks\PendingTaskDraftNewLineSaver;
use App\Support\PendingTasks\PendingTaskEditableLineResolver;
use App\Support\PendingTasks\PendingTaskLineEditorStateFactory;
use App\Support\PendingTasks\PendingTaskLinePayloadNormalizer;
use App\Support\PendingTasks\PendingTaskLineValidationErrorMapper;
use App\Support\PendingTasks\PendingTaskProcessLineSaver;
use App\Support\PendingTasks\SerializedBulkPasteAnalysis;
use App\Support\PendingTasks\SerializedBulkPasteAnalyzer;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PendingTaskShow extends Component
{
    use InteractsWithToasts;

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

    /** @var array<string, list<int>> */
    public array $duplicates = [];

    // Process mode state
    public bool $isProcessMode = false;

    /**
     * When entering process mode we render a lightweight shell first and
     * lazy-render the heavy table on a follow-up request (wire:init).
     */
    public bool $processModeReady = true;

    public bool $showFinalizeConfirmModal = false;

    /** @var array{applied_count: int, error_count: int, skipped_count: int}|null */
    public ?array $finalizeResult = null;

    // Lock state
    public bool $hasLock = false;

    public bool $lockLost = false;

    // Edit line in process mode
    public bool $showProcessLineModal = false;

    public ?int $editingProcessLineId = null;

    public string $processLineSerial = '';

    public string $processLineAssetTag = '';

    public ?string $processLineQuantity = null;

    public ?int $processLineEmployeeId = null;

    public string $processLineNote = '';

    // Quick Capture processing (FP-03 follow-up)
    public bool $showQuickProcessModal = false;

    public ?int $quickProcessEmployeeId = null;

    public ?int $quickProcessProductId = null;

    public ?int $quickProcessLocationId = null;

    public string $quickProcessNote = '';

    public function mount(int $pendingTask): void
    {
        Gate::authorize('inventory.manage');

        $this->pendingTask = $pendingTask;
        $this->serializedBulkMaxLines = (int) config('gatic.pending_tasks.bulk_paste.max_lines', 200);
        $this->loadTask();
        $this->resumeProcessModeIfOwnLock();
    }

    private function isQuickCaptureTask(): bool
    {
        return $this->task?->isQuickCaptureTask() ?? false;
    }

    private function abortIfQuickCapture(string $action): bool
    {
        if (! $this->isQuickCaptureTask()) {
            return false;
        }

        $this->toast(type: 'error', message: "Esta tarea fue creada como captura rápida y no permite {$action}.");

        return true;
    }

    private function resumeProcessModeIfOwnLock(): void
    {
        if (! $this->task) {
            return;
        }

        if (! $this->hasLock) {
            return;
        }

        if (! $this->canProcess()) {
            return;
        }

        $this->isProcessMode = true;
        $this->processModeReady = true;
        $this->lockLost = false;
        $this->finalizeResult = null;

        if ($this->task->status === PendingTaskStatus::Ready) {
            $this->task->status = PendingTaskStatus::Processing;
            $this->task->save();
            $this->loadTask();
        }
    }

    /**
     * Refresh only lock-related state without reloading task lines.
     *
     * This avoids re-querying potentially large `lines` collections on every
     * process-mode action (validate/clear/edit/finalize), while still ensuring
     * correctness for the lock owner checks.
     */
    private function refreshTaskLockState(): void
    {
        $fresh = PendingTask::query()
            ->select([
                'id',
                'status',
                'locked_by_user_id',
                'locked_at',
                'heartbeat_at',
                'expires_at',
            ])
            ->with(['lockedBy'])
            ->find($this->pendingTask);

        if (! $fresh) {
            return;
        }

        if ($this->task) {
            $this->task->status = $fresh->status;
            $this->task->locked_by_user_id = $fresh->locked_by_user_id;
            $this->task->locked_at = $fresh->locked_at;
            $this->task->heartbeat_at = $fresh->heartbeat_at;
            $this->task->expires_at = $fresh->expires_at;
            $this->task->setRelation('lockedBy', $fresh->lockedBy);
        } else {
            $this->task = $fresh;
        }

        $userId = Auth::id();
        if (! is_int($userId)) {
            $this->hasLock = false;

            return;
        }

        $this->hasLock = $fresh->isLockedBy($userId);

        if ($this->hasLock) {
            $this->lockLost = false;
        }

        if ($this->isProcessMode && ! $this->hasLock && $fresh->hasActiveLock()) {
            $this->lockLost = true;
        }
    }

    private function loadTask(): void
    {
        $this->task = PendingTask::with(['creator', 'lockedBy', 'lines.product', 'lines.employee'])
            ->findOrFail($this->pendingTask);

        $this->duplicates = $this->task->getDuplicateIdentifiers();

        // Update lock state
        /** @var int $userId */
        $userId = Auth::id();
        $this->hasLock = $this->task->isLockedBy($userId);

        // If we were in process mode but lost the lock, set lockLost flag
        if ($this->isProcessMode && ! $this->hasLock && $this->task->hasActiveLock()) {
            $this->lockLost = true;
        }
    }

    public function openAddLineModal(): void
    {
        if ($this->abortIfQuickCapture('agregar renglones')) {
            return;
        }

        if (! $this->task?->isDraft()) {
            return;
        }

        $this->resetForm();
        $this->showLineModal = true;
    }

    public function openEditLineModal(int $lineId): void
    {
        if ($this->abortIfQuickCapture('editar renglones')) {
            return;
        }

        if (! $this->task?->isDraft()) {
            return;
        }

        $line = (new PendingTaskEditableLineResolver)->resolveDraftEditorLine($this->pendingTask, $lineId);
        if (! $line) {
            return;
        }

        $this->applyState((new PendingTaskLineEditorStateFactory)->hydrateDraftEditor($line));
        $this->showLineModal = true;
    }

    public function closeModal(): void
    {
        $this->showLineModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->applyState((new PendingTaskLineEditorStateFactory)->resetDraftEditor());
        $this->resetErrorBag();
    }

    public function openQuickProcessModal(): void
    {
        Gate::authorize('inventory.manage');

        if (! $this->task || ! $this->task->isQuickCaptureTask()) {
            return;
        }

        $payload = is_array($this->task->payload) ? $this->task->payload : [];
        $product = is_array($payload['product'] ?? null) ? $payload['product'] : null;
        $kind = is_string($payload['kind'] ?? null) ? (string) $payload['kind'] : '';
        $reason = is_string($payload['reason'] ?? null) ? trim((string) $payload['reason']) : '';
        $note = is_string($payload['note'] ?? null) ? trim((string) $payload['note']) : '';

        $this->quickProcessEmployeeId = null;
        $this->quickProcessProductId = is_array($product) && is_int($product['id'] ?? null) ? (int) $product['id'] : null;
        $this->quickProcessLocationId = null;

        $this->quickProcessNote = $note !== '' ? $note : 'Procesado desde captura rapida';
        if ($kind === 'quick_retirement' && $reason !== '') {
            $this->quickProcessNote = "Motivo: {$reason}".($note !== '' ? ". {$note}" : '');
        }

        $this->showQuickProcessModal = true;
        $this->resetErrorBag();
    }

    public function closeQuickProcessModal(): void
    {
        $this->showQuickProcessModal = false;
        $this->resetQuickProcessForm();
    }

    private function resetQuickProcessForm(): void
    {
        $this->quickProcessEmployeeId = null;
        $this->quickProcessProductId = null;
        $this->quickProcessLocationId = null;
        $this->quickProcessNote = '';
        $this->resetErrorBag();
    }

    public function processQuickCapture(): void
    {
        Gate::authorize('inventory.manage');

        if (! $this->task) {
            $this->toastError('La tarea no está disponible para procesarse.');

            return;
        }

        if (! $this->task->hasQuickCapturePayload()) {
            $this->toastError('Esta tarea no es una captura rápida.');

            return;
        }

        if (! $this->task->isQuickCaptureTask()) {
            $this->toastError('Esta captura rápida ya fue procesada y no puede procesarse de nuevo.');

            return;
        }

        /** @var int $actorUserId */
        $actorUserId = (int) Auth::id();

        $payload = is_array($this->task->payload) ? $this->task->payload : [];
        $product = is_array($payload['product'] ?? null) ? $payload['product'] : null;
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : null;
        $itemsType = is_array($items) && is_string($items['type'] ?? null) ? (string) $items['type'] : '';

        $resolvedProductId = null;
        if (! (is_array($product) && is_int($product['id'] ?? null))) {
            $resolvedProductId = $this->quickProcessProductId;
        }

        $employeeId = $itemsType === 'quantity' ? $this->quickProcessEmployeeId : null;
        $locationId = $itemsType === 'serialized' ? $this->quickProcessLocationId : null;

        $note = trim($this->quickProcessNote);

        try {
            $result = (new ProcessQuickCapturePendingTask)->execute([
                'task_id' => $this->task->id,
                'actor_user_id' => $actorUserId,
                'employee_id' => $employeeId,
                'resolved_product_id' => $resolvedProductId,
                'location_id' => $locationId,
                'note' => $note !== '' ? $note : null,
            ]);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->errors());

            return;
        }

        $this->closeQuickProcessModal();
        $this->loadTask();
        $this->dispatch('pending-tasks:refresh');

        $messagesByMode = [
            'lines' => 'Se genero el renglon. Ya puedes marcar la tarea como Lista y procesarla.',
            'assets_stock_in' => 'Se crearon activos desde la captura rapida.',
            'assets_retirement' => 'Se marcaron activos como Pendiente de Retiro.',
        ];
        $message = $messagesByMode[$result['mode']];

        $hasErrors = $result['errors'] !== [];
        if ($hasErrors) {
            $message .= ' (con alertas)';
        }

        $this->toast(
            type: $hasErrors ? 'warning' : 'success',
            message: $message,
        );
    }

    public function updatedProductId(): void
    {
        // Auto-set line type based on product category
        if ($this->productId) {
            $product = $this->findSelectableProduct($this->productId);
            if ($product !== null) {
                $this->lineType = (bool) $product->category->is_serialized
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

        if ($this->abortIfQuickCapture('guardar renglones')) {
            return;
        }

        if (! $this->task?->isDraft()) {
            $this->toast(type: 'error', message: 'La tarea no está en estado borrador.');

            return;
        }

        try {
            $draftLineInput = [
                'serial' => $this->serial,
                'asset_tag' => $this->assetTag,
                'quantity' => $this->quantity,
                'employee_id' => $this->employeeId,
                'note' => $this->note,
            ];

            if ($this->editingLineId) {
                $result = (new PendingTaskDraftLineSaver)->save(
                    $this->pendingTask,
                    $this->editingLineId,
                    $this->lineType,
                    $this->productId,
                    $draftLineInput,
                );

                if (! $result) {
                    return;
                }

                $message = 'Renglón actualizado.';
            } elseif ($this->lineType === PendingTaskLineType::Serialized->value) {
                $linePayload = (new PendingTaskLinePayloadNormalizer)->normalize(
                    $this->lineType,
                    $this->productId,
                    $draftLineInput,
                );

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
                    'product_id' => $linePayload['product_id'],
                    'serials' => $this->extractSerializedBulkSerials(),
                    'employee_id' => $linePayload['employee_id'],
                    'note' => $linePayload['note'],
                ]);

                $message = "Renglones añadidos: {$result['lines_created']}.";
            } else {
                $result = (new PendingTaskDraftNewLineSaver)->save(
                    $this->pendingTask,
                    $this->lineType,
                    $this->productId,
                    $draftLineInput,
                );

                $message = 'Renglón añadido.';
            }

            if ($result['has_duplicates'] || $this->serializedBulkDuplicateCount > 0) {
                $message .= ' (Duplicado detectado)';
            }

            $this->closeModal();
            $this->loadTask();

            $this->toast(type: 'success', message: $message);
        } catch (ValidationException $e) {
            foreach ((new PendingTaskLineValidationErrorMapper)->forDraftEditor($e) as $field => $message) {
                $this->addError($field, $message);
            }
        }
    }

    public function removeLine(int $lineId): void
    {
        Gate::authorize('inventory.manage');

        if ($this->abortIfQuickCapture('eliminar renglones')) {
            return;
        }

        if (! $this->task?->isDraft()) {
            $this->toast(type: 'error', message: 'La tarea no está en estado borrador.');

            return;
        }

        try {
            $action = new RemoveLineFromTask;
            $action->execute($lineId);

            $this->loadTask();

            $this->toast(type: 'success', message: 'Renglón eliminado.');
        } catch (ValidationException $e) {
            $this->toast(type: 'error', message: $e->errors()['pending_task_id'][0] ?? $e->getMessage());
        }
    }

    public function markAsReady(): void
    {
        Gate::authorize('inventory.manage');

        if ($this->abortIfQuickCapture('marcarla como lista')) {
            return;
        }

        try {
            $action = new MarkTaskAsReady;
            $action->execute($this->pendingTask);

            $this->loadTask();

            $this->toast(type: 'success', message: 'Tarea marcada como lista. Ya no puedes editar renglones.');
        } catch (ValidationException $e) {
            $this->toast(type: 'error', message: $e->errors()['lines'][0] ?? $e->errors()['status'][0] ?? $e->getMessage());
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

        if ($this->isQuickCaptureTask()) {
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

        if ($this->abortIfQuickCapture('procesarla')) {
            return;
        }

        if (! $this->canProcess()) {
            $this->toast(type: 'error', message: 'La tarea no está en un estado que permita procesarla.');

            return;
        }

        // Try to acquire lock before entering process mode
        /** @var int $userId */
        $userId = Auth::id();

        try {
            $action = new AcquirePendingTaskLock;
            $result = $action->execute($this->pendingTask, $userId);

            if (! $result['success']) {
                // Lock is held by another user
                $this->loadTask();
                $this->toast(type: 'error', message: $result['message']);

                return;
            }

            // Lock acquired successfully
            $this->hasLock = true;
            $this->lockLost = false;
            $this->isProcessMode = true;
            $this->processModeReady = false;
            $this->finalizeResult = null;

            // Update task status to processing if it was ready
            if ($this->task && $this->task->status === PendingTaskStatus::Ready) {
                $this->task->status = PendingTaskStatus::Processing;
                $this->task->save();
            }

            // Keep local task lock fields in sync without forcing an eager reload.
            if ($this->task) {
                $now = now();
                $leaseTtlSeconds = (int) config('gatic.pending_tasks.locks.lease_ttl_s', 180);

                $this->task->locked_by_user_id = $userId;
                $this->task->locked_at = $now;
                $this->task->heartbeat_at = $now;
                $this->task->expires_at = $now->copy()->addSeconds($leaseTtlSeconds);
                $this->task->setRelation('lockedBy', Auth::user());
            }
        } catch (ValidationException $e) {
            $this->toast(type: 'error', message: $e->errors()['status'][0] ?? $e->errors()['pending_task_id'][0] ?? $e->getMessage());
        }
    }

    /**
     * Exit process mode
     */
    public function exitProcessMode(): void
    {
        Gate::authorize('inventory.manage');

        // Release lock if we have it
        if ($this->hasLock) {
            /** @var int $userId */
            $userId = Auth::id();

            try {
                $action = new ReleasePendingTaskLock;
                $action->execute($this->pendingTask, $userId);
            } catch (\Throwable $e) {
                // Best effort - log but don't fail
                Log::warning('exitProcessMode: failed to release lock', [
                    'task_id' => $this->pendingTask,
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->isProcessMode = false;
        $this->processModeReady = true;
        $this->hasLock = false;
        $this->lockLost = false;
        $this->finalizeResult = null;
        $this->closeProcessLineModal();
        $this->loadTask();
    }

    public function initProcessModeUi(): void
    {
        Gate::authorize('inventory.manage');

        if ($this->abortIfQuickCapture('procesarla')) {
            return;
        }

        if (! $this->isProcessMode) {
            return;
        }

        $this->processModeReady = true;
    }

    /**
     * Check if we have an active lock - if not, block the action
     */
    private function requireActiveLock(): bool
    {
        $this->refreshTaskLockState();

        if (! $this->hasLock) {
            if ($this->isProcessMode) {
                $this->lockLost = true;
            }

            if ($this->task?->hasActiveLock()) {
                $this->toast(type: 'error', message: 'Has perdido el lock. Otro usuario lo tiene.');
            } else {
                $this->toast(type: 'error', message: 'Tu lock ya no está activo. Haz clic en "Reintentar claim".');
            }

            return false;
        }

        return true;
    }

    /**
     * Retry acquiring the lock (from lock lost state)
     */
    public function retryLock(): void
    {
        Gate::authorize('inventory.manage');

        if ($this->abortIfQuickCapture('procesarla')) {
            return;
        }

        /** @var int $userId */
        $userId = Auth::id();

        try {
            $action = new AcquirePendingTaskLock;
            $result = $action->execute($this->pendingTask, $userId);

            if ($result['success']) {
                $this->hasLock = true;
                $this->lockLost = false;
                $this->loadTask();

                $this->toast(type: 'success', message: 'Lock adquirido. Puedes continuar procesando.');
            } else {
                $this->loadTask();
                $this->toast(type: 'error', message: $result['message']);
            }
        } catch (ValidationException $e) {
            $this->toast(type: 'error', message: $e->errors()['pending_task_id'][0] ?? $e->getMessage());
        }
    }

    /**
     * Heartbeat to renew lock lease
     */
    public function heartbeat(): void
    {
        Gate::authorize('inventory.manage');

        if ($this->isQuickCaptureTask()) {
            return;
        }

        if (! $this->isProcessMode || ! $this->hasLock) {
            return;
        }

        /** @var int $userId */
        $userId = Auth::id();

        try {
            $action = new HeartbeatPendingTaskLock;
            $result = $action->execute($this->pendingTask, $userId);

            if (! $result['success']) {
                // Lock expired or lost
                $this->hasLock = false;
                $this->lockLost = true;
                $this->refreshTaskLockState();
            }
        } catch (\Throwable $e) {
            Log::warning('heartbeat failed', [
                'task_id' => $this->pendingTask,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate a single line
     */
    public function validateLine(int $lineId): void
    {
        Gate::authorize('inventory.manage');

        if ($this->abortIfQuickCapture('validar renglones')) {
            return;
        }

        // Require lock for process mode actions
        if ($this->isProcessMode && ! $this->requireActiveLock()) {
            return;
        }

        try {
            $action = new ValidatePendingTaskLine;
            $result = $action->execute($lineId);

            $this->loadTask();

            if ($result['valid']) {
                $this->toast(type: 'success', message: 'Renglón validado correctamente.');
            } else {
                $this->toast(type: 'error', message: $result['error_message'] ?? 'Error de validación.');
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

        if ($this->abortIfQuickCapture('modificar renglones')) {
            return;
        }

        // Require lock for process mode actions
        if ($this->isProcessMode && ! $this->requireActiveLock()) {
            return;
        }

        try {
            $action = new ClearLineError;
            $action->execute($lineId);

            $this->loadTask();

            $this->toast(type: 'success', message: 'Error limpiado. El renglón está pendiente de validación.');
        } catch (ValidationException $e) {
            $this->toast(type: 'error', message: $e->errors()['line_status'][0] ?? $e->getMessage());
        }
    }

    /**
     * Open modal to edit a line in process mode
     */
    public function openProcessLineModal(int $lineId): void
    {
        Gate::authorize('inventory.manage');

        if ($this->abortIfQuickCapture('editar renglones')) {
            return;
        }

        // Require lock for process mode actions
        if (! $this->requireActiveLock()) {
            return;
        }

        try {
            $line = (new PendingTaskEditableLineResolver)->resolveProcessEditorLine($this->pendingTask, $lineId);
        } catch (DomainException $e) {
            $this->toast(type: 'error', message: $e->getMessage());

            return;
        }

        if (! $line) {
            return;
        }

        $this->applyState((new PendingTaskLineEditorStateFactory)->hydrateProcessEditor($line));
        $this->showProcessLineModal = true;
    }

    /**
     * Close process line modal
     */
    public function closeProcessLineModal(): void
    {
        $this->showProcessLineModal = false;
        $this->resetProcessLineForm();
    }

    /**
     * Save line edit in process mode
     */
    public function saveProcessLine(): void
    {
        Gate::authorize('inventory.manage');

        if ($this->abortIfQuickCapture('guardar cambios')) {
            $this->closeProcessLineModal();

            return;
        }

        // Require lock for process mode actions
        if (! $this->requireActiveLock()) {
            $this->closeProcessLineModal();

            return;
        }

        if (! $this->editingProcessLineId) {
            return;
        }

        try {
            $line = (new PendingTaskEditableLineResolver)->resolveProcessEditorLine($this->pendingTask, $this->editingProcessLineId);
        } catch (DomainException $e) {
            $this->toast(type: 'error', message: $e->getMessage());

            return;
        }

        if (! $line) {
            return;
        }

        try {
            (new PendingTaskProcessLineSaver)->save(
                $line,
                [
                    'serial' => $this->processLineSerial,
                    'asset_tag' => $this->processLineAssetTag,
                    'quantity' => $this->processLineQuantity,
                    'employee_id' => $this->processLineEmployeeId,
                    'note' => $this->processLineNote,
                ],
            );

            $this->closeProcessLineModal();
            $this->loadTask();

            $this->toast(type: 'success', message: 'Renglón actualizado. Re-valida para verificar.');
        } catch (ValidationException $e) {
            foreach ((new PendingTaskLineValidationErrorMapper)->forProcessEditor($e) as $field => $message) {
                $this->addError($field, $message);
            }
        }
    }

    /**
     * Show finalize confirmation modal
     */
    public function showFinalizeConfirm(): void
    {
        Gate::authorize('inventory.manage');

        if ($this->abortIfQuickCapture('finalizarla')) {
            return;
        }

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

        if ($this->abortIfQuickCapture('finalizarla')) {
            $this->hideFinalizeConfirm();

            return;
        }

        $this->hideFinalizeConfirm();

        // Require lock for finalization
        if (! $this->requireActiveLock()) {
            return;
        }

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

            // Release lock after finalization (task may be completed or partially completed)
            if ($this->hasLock) {
                try {
                    $releaseAction = new ReleasePendingTaskLock;
                    $releaseAction->execute($this->pendingTask, $user->id);
                    $this->hasLock = false;
                } catch (\Throwable $e) {
                    // Best effort
                    Log::warning('finalizeTask: failed to release lock after finalization', [
                        'task_id' => $this->pendingTask,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Exit process mode if task is completed
            if ($this->task && in_array($this->task->status, [PendingTaskStatus::Completed, PendingTaskStatus::Cancelled], true)) {
                $this->isProcessMode = false;
            }

            $this->toast(type: $result['error_count'] > 0 ? 'warning' : 'success', message: $message);
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first();
            $this->toast(type: 'error', message: $firstError ?? 'Error al finalizar la tarea.');
        } catch (\Throwable $e) {
            $this->flashUnexpectedError($e, 'finalizar la tarea');
        }
    }

    private function flashUnexpectedError(\Throwable $e, string $action): void
    {
        $errorId = app(ErrorReporter::class)->report($e, request());

        $message = "Error inesperado al {$action} (ID: {$errorId}). Contacta a soporte.";

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user && $user->role === UserRole::Admin) {
            $message .= ' '.$e->getMessage();
        }

        $this->toast(type: 'error', message: $message);
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

    // =========================================================================
    // Admin Override Methods (Story 7.5)
    // =========================================================================

    /**
     * Check if current user is Admin
     */
    public function isAdmin(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user?->isAdmin() ?? false;
    }

    /**
     * Admin-only: Force release the lock (AC1)
     */
    public function forceReleaseLock(): void
    {
        Gate::authorize('admin-only');

        if (! $this->task) {
            return;
        }

        /** @var int $userId */
        $userId = Auth::id();

        try {
            $action = new ForceReleasePendingTaskLock;
            $result = $action->execute($this->pendingTask, $userId);

            $this->loadTask();

            $this->toast(type: 'success', message: $result['message']);
        } catch (ValidationException $e) {
            $this->toast(type: 'error', message: $e->errors()['pending_task_id'][0] ?? $e->getMessage());
        } catch (\Throwable $e) {
            $this->flashUnexpectedError($e, 'forzar liberacion del lock');
        }
    }

    /**
     * Admin-only: Force claim the lock (AC2)
     */
    public function forceClaimLock(): void
    {
        Gate::authorize('admin-only');

        if ($this->abortIfQuickCapture('procesarla')) {
            return;
        }

        if (! $this->task) {
            return;
        }

        /** @var int $userId */
        $userId = Auth::id();

        try {
            $action = new ForceClaimPendingTaskLock;
            $result = $action->execute($this->pendingTask, $userId);

            // Update local state
            $this->hasLock = true;
            $this->lockLost = false;
            $this->loadTask();

            $this->toast(type: 'success', message: $result['message']);
        } catch (ValidationException $e) {
            $this->toast(type: 'error', message: $e->errors()['pending_task_id'][0] ?? $e->getMessage());
        } catch (\Throwable $e) {
            $this->flashUnexpectedError($e, 'forzar reclamo del lock');
        }
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        return view('livewire.pending-tasks.pending-task-show', [
            'lineTypes' => PendingTaskLineType::cases(),
            'lineStatusSummary' => $this->getLineStatusSummary(),
        ]);
    }

    private function findSelectableProduct(?int $productId): ?Product
    {
        if ($productId === null) {
            return null;
        }

        return Product::query()
            ->with('category')
            ->whereNull('products.deleted_at')
            ->whereHas('category', static fn ($query) => $query->whereNull('categories.deleted_at'))
            ->find($productId);
    }

    /**
     * @return list<string>
     */
    private function extractSerializedBulkSerials(): array
    {
        return $this->analyzeSerializedBulkInput()->serials;
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

        $this->applySerializedBulkAnalysis($this->analyzeSerializedBulkInput());
    }

    private function analyzeSerializedBulkInput(): SerializedBulkPasteAnalysis
    {
        $existingSerials = [];

        if ($this->task) {
            foreach ($this->task->lines as $line) {
                if ($line->serial !== null && $line->serial !== '') {
                    $existingSerials[] = $line->serial;
                }
            }
        }

        return app(SerializedBulkPasteAnalyzer::class)->analyze(
            input: $this->serializedBulkInput,
            maxLines: $this->serializedBulkMaxLines,
            existingSerials: $existingSerials,
        );
    }

    private function applySerializedBulkAnalysis(SerializedBulkPasteAnalysis $analysis): void
    {
        $this->serializedBulkPreview = $analysis->preview;
        $this->serializedBulkCount = $analysis->count;
        $this->serializedBulkOkCount = $analysis->okCount;
        $this->serializedBulkDuplicateCount = $analysis->duplicateCount;
        $this->serializedBulkInvalidCount = $analysis->invalidCount;
        $this->serializedBulkLimitError = $analysis->limitError;
    }

    private function resetProcessLineForm(): void
    {
        $this->applyState((new PendingTaskLineEditorStateFactory)->resetProcessEditor());
        $this->resetErrorBag();
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function applyState(array $state): void
    {
        foreach ($state as $property => $value) {
            $this->{$property} = $value;
        }
    }
}
