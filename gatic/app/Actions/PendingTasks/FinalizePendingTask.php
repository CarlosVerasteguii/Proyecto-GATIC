<?php

declare(strict_types=1);

namespace App\Actions\PendingTasks;

use App\Actions\Movements\Assets\AssignAssetToEmployee;
use App\Actions\Movements\Assets\LoanAssetToEmployee;
use App\Actions\Movements\Assets\ReturnLoanedAsset;
use App\Actions\Movements\Products\RegisterProductQuantityMovement;
use App\Enums\PendingTaskLineStatus;
use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Models\Asset;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Finalize a PendingTask by applying all valid lines.
 *
 * Features:
 * - Partial finalization: applies valid lines, marks errors on invalid ones
 * - Duplicate detection within task (blocks application)
 * - Per-line transactions for isolation
 * - Never re-applies already applied lines
 */
class FinalizePendingTask
{
    /**
     * Result structure returned by execute().
     *
     * @var array{
     *     applied_count: int,
     *     error_count: int,
     *     skipped_count: int,
     *     task_status: PendingTaskStatus,
     *     errors: array<int, string>
     * }
     */
    private array $result;

    /**
     * @param  int  $taskId  The PendingTask ID
     * @param  int  $actorUserId  The user performing the action
     * @return array{applied_count: int, error_count: int, skipped_count: int, task_status: PendingTaskStatus, errors: array<int, string>}
     *
     * @throws ValidationException
     */
    public function execute(int $taskId, int $actorUserId): array
    {
        $task = PendingTask::with('lines')->findOrFail($taskId);

        if ($task->isQuickCaptureTask()) {
            throw ValidationException::withMessages([
                'status' => ['Esta tarea fue creada como captura rápida y no se puede finalizar en esta versión.'],
            ]);
        }

        $this->validateTaskCanBeFinalized($task);

        $this->result = [
            'applied_count' => 0,
            'error_count' => 0,
            'skipped_count' => 0,
            'task_status' => $task->status,
            'errors' => [],
        ];

        // Get lines that can be processed (pending/processing/error, never applied)
        $linesToProcess = $task->lines->filter(
            fn (PendingTaskLine $line) => in_array(
                $line->line_status,
                [PendingTaskLineStatus::Pending, PendingTaskLineStatus::Processing, PendingTaskLineStatus::Error],
                true
            )
        );

        // Count already applied lines as skipped
        $this->result['skipped_count'] = $task->lines->filter(
            fn (PendingTaskLine $line) => $line->line_status === PendingTaskLineStatus::Applied
        )->count();

        // Detect duplicates within the task for serialized lines
        $duplicates = $this->detectDuplicatesInTask($task);

        // Process each line
        foreach ($linesToProcess as $line) {
            $this->processLine($line, $task->type, $actorUserId, $duplicates);
        }

        // Update task status based on results
        $this->updateTaskStatus($task);

        return $this->result;
    }

    private function validateTaskCanBeFinalized(PendingTask $task): void
    {
        $allowedStatuses = [
            PendingTaskStatus::Ready,
            PendingTaskStatus::Processing,
            PendingTaskStatus::PartiallyCompleted,
        ];

        if (! in_array($task->status, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => "La tarea debe estar en estado Listo, Procesando o Parcialmente completado para finalizar. Estado actual: {$task->status->label()}.",
            ]);
        }

        if ($task->lines->isEmpty()) {
            throw ValidationException::withMessages([
                'lines' => 'La tarea no tiene renglones para procesar.',
            ]);
        }
    }

    /**
     * Detect duplicate serial/asset_tag within the task.
     *
     * @return array<string, list<int>> Map of identifier => line IDs
     */
    private function detectDuplicatesInTask(PendingTask $task): array
    {
        return $task->getDuplicateIdentifiers();
    }

    /**
     * Check if a line is part of a duplicate group.
     *
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
     * Get duplicate details for error message.
     *
     * @param  array<string, list<int>>  $duplicates
     */
    private function getDuplicateDetails(PendingTaskLine $line, array $duplicates): string
    {
        $details = [];

        foreach ($duplicates as $key => $lineIds) {
            if (in_array($line->id, $lineIds, true)) {
                $otherIds = array_filter($lineIds, fn ($id) => $id !== $line->id);
                $details[] = "{$key} (también en renglones: ".implode(', ', $otherIds).')';
            }
        }

        return implode('; ', $details);
    }

    /**
     * Process a single line within its own transaction.
     *
     * @param  array<string, list<int>>  $duplicates
     */
    private function processLine(
        PendingTaskLine $line,
        PendingTaskType $taskType,
        int $actorUserId,
        array $duplicates
    ): void {
        try {
            $status = DB::transaction(function () use ($line, $taskType, $actorUserId, $duplicates): string {
                // Lock the line to prevent double-apply in concurrent finalizations
                $lockedLine = PendingTaskLine::query()
                    ->whereKey($line->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Never re-apply already applied lines
                if ($lockedLine->line_status === PendingTaskLineStatus::Applied) {
                    return 'skipped';
                }

                // Check for duplicates first (before any processing)
                if ($lockedLine->isSerialized() && $this->isLineDuplicate($lockedLine, $duplicates)) {
                    $details = $this->getDuplicateDetails($lockedLine, $duplicates);

                    throw ValidationException::withMessages([
                        'duplicate' => "Duplicado en la tarea: {$details}. Corrige o elimina el duplicado antes de aplicar.",
                    ]);
                }

                // Validate line type is supported by task type
                if ($lockedLine->isSerialized() && ! $taskType->supportsSerialized()) {
                    throw ValidationException::withMessages([
                        'line_type' => $taskType->unsupportedLineTypeMessage($lockedLine->line_type),
                    ]);
                }

                if ($lockedLine->isQuantity() && ! $taskType->supportsQuantity()) {
                    throw ValidationException::withMessages([
                        'line_type' => $taskType->unsupportedLineTypeMessage($lockedLine->line_type),
                    ]);
                }

                // Mark as processing
                $lockedLine->line_status = PendingTaskLineStatus::Processing;
                $lockedLine->save();

                if ($lockedLine->isSerialized()) {
                    $this->applySerializedLine($lockedLine, $taskType, $actorUserId);
                } else {
                    $this->applyQuantityLine($lockedLine, $taskType, $actorUserId);
                }

                // Mark as applied
                $lockedLine->line_status = PendingTaskLineStatus::Applied;
                $lockedLine->error_message = null;
                $lockedLine->save();

                return 'applied';
            });

            if ($status === 'applied') {
                $this->result['applied_count']++;
            } else {
                // If another concurrent finalization applied it first
                $this->result['skipped_count']++;
            }
        } catch (ValidationException $e) {
            $errorMessage = $this->extractErrorMessage($e);
            $freshLine = PendingTaskLine::find($line->id);
            if ($freshLine) {
                $this->markLineAsError($freshLine, $errorMessage);
            }
        } catch (\Throwable $e) {
            // Unexpected error - log and provide error_id
            $errorId = uniqid('ERR-');
            Log::error("FinalizePendingTask unexpected error [{$errorId}]", [
                'line_id' => $line->id,
                'task_id' => $line->pending_task_id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->markLineAsError($line, "Error inesperado (ID: {$errorId}). Contacta a soporte.");
        }
    }

    private function markLineAsError(PendingTaskLine $line, string $message): void
    {
        $line->line_status = PendingTaskLineStatus::Error;
        $line->error_message = $message;
        $line->save();

        $this->result['error_count']++;
        $this->result['errors'][$line->id] = $message;
    }

    private function extractErrorMessage(ValidationException $e): string
    {
        $errors = $e->errors();
        $firstField = array_key_first($errors);

        return $firstField !== null ? $errors[$firstField][0] : $e->getMessage();
    }

    /**
     * Apply a serialized line (Asset movement).
     */
    private function applySerializedLine(
        PendingTaskLine $line,
        PendingTaskType $taskType,
        int $actorUserId
    ): void {
        // Find the asset by product_id + (serial OR asset_tag)
        $asset = $this->findAssetForLine($line);

        if ($asset === null) {
            throw ValidationException::withMessages([
                'asset' => $this->buildAssetNotFoundMessage($line),
            ]);
        }

        // Build note from line note or generate default
        $note = $line->note !== '' ? $line->note : "Aplicado desde tarea pendiente #{$line->pending_task_id}";

        // Apply the movement based on task type
        match ($taskType) {
            PendingTaskType::Assign => (new AssignAssetToEmployee)->execute([
                'asset_id' => $asset->id,
                'employee_id' => $line->employee_id,
                'note' => $note,
                'actor_user_id' => $actorUserId,
            ]),
            PendingTaskType::Loan => (new LoanAssetToEmployee)->execute([
                'asset_id' => $asset->id,
                'employee_id' => $line->employee_id,
                'note' => $note,
                'actor_user_id' => $actorUserId,
            ]),
            PendingTaskType::Return => (new ReturnLoanedAsset)->execute([
                'asset_id' => $asset->id,
                'employee_id' => $line->employee_id,
                'note' => $note,
                'actor_user_id' => $actorUserId,
            ]),
            default => throw ValidationException::withMessages([
                'task_type' => "El tipo de tarea \"{$taskType->label()}\" no soporta renglones serializados.",
            ]),
        };
    }

    /**
     * Find an asset by product_id + (serial OR asset_tag).
     * Respects soft-delete: only finds non-deleted assets.
     */
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

    /**
     * Apply a quantity line (Product quantity movement).
     */
    private function applyQuantityLine(
        PendingTaskLine $line,
        PendingTaskType $taskType,
        int $actorUserId
    ): void {
        // Verify product exists and is not soft-deleted
        $product = Product::query()
            ->whereNull('deleted_at')
            ->find($line->product_id);

        if ($product === null) {
            throw ValidationException::withMessages([
                'product' => 'El producto no existe o fue eliminado.',
            ]);
        }

        // Build note from line note or generate default
        $note = $line->note !== '' ? $line->note : "Aplicado desde tarea pendiente #{$line->pending_task_id}";

        // Apply the quantity movement
        (new RegisterProductQuantityMovement)->execute([
            'product_id' => $line->product_id,
            'employee_id' => $line->employee_id,
            'direction' => $taskType->quantityDirection(),
            'qty' => $line->quantity,
            'note' => $note,
            'actor_user_id' => $actorUserId,
        ]);
    }

    /**
     * Update task status based on processing results.
     */
    private function updateTaskStatus(PendingTask $task): void
    {
        // Reload lines to get fresh status
        $task->refresh();

        $appliedCount = $task->lines->filter(
            fn (PendingTaskLine $line) => $line->line_status === PendingTaskLineStatus::Applied
        )->count();

        $errorCount = $task->lines->filter(
            fn (PendingTaskLine $line) => $line->line_status === PendingTaskLineStatus::Error
        )->count();

        $totalCount = $task->lines->count();

        if ($appliedCount === $totalCount) {
            $task->status = PendingTaskStatus::Completed;
        } elseif ($errorCount > 0 || $appliedCount > 0) {
            $task->status = PendingTaskStatus::PartiallyCompleted;
        } else {
            // All still pending/processing - shouldn't happen, but safe default
            $task->status = PendingTaskStatus::Processing;
        }

        $task->save();
        $this->result['task_status'] = $task->status;
    }
}
