<?php

namespace App\Actions\PendingTasks;

use App\Models\PendingTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Admin-only: Force release a lock on a pending task.
 *
 * This clears all lock fields regardless of the current owner.
 * The operation is audited (best-effort) for compliance.
 */
class ForceReleasePendingTaskLock
{
    /**
     * @param  int  $pendingTaskId  The pending task to force-release
     * @param  int  $adminUserId  The admin performing the action
     * @return array{success: bool, message: string, previous_locked_by: int|null}
     *
     * @throws ValidationException
     */
    public function execute(int $pendingTaskId, int $adminUserId): array
    {
        return DB::transaction(function () use ($pendingTaskId, $adminUserId) {
            $timestamp = now()->toIso8601String();

            /** @var PendingTask|null $task */
            $task = PendingTask::query()
                ->lockForUpdate()
                ->find($pendingTaskId);

            if (! $task) {
                throw ValidationException::withMessages([
                    'pending_task_id' => ['La tarea no existe.'],
                ]);
            }

            // Capture previous state for audit
            $previousLockedBy = $task->locked_by_user_id;
            $previousLockedAt = $task->locked_at?->toIso8601String();
            $previousExpiresAt = $task->expires_at?->toIso8601String();

            // If no lock exists, respond OK (idempotent)
            if ($task->locked_by_user_id === null) {
                $this->auditOverride([
                    'action' => 'force_release',
                    'pending_task_id' => $pendingTaskId,
                    'actor_user_id' => $adminUserId,
                    'previous_locked_by_user_id' => null,
                    'previous_locked_at' => null,
                    'previous_expires_at' => null,
                    'new_locked_by_user_id' => null,
                    'new_locked_at' => null,
                    'new_expires_at' => null,
                    'timestamp' => $timestamp,
                    'result' => 'no_lock',
                ]);

                return [
                    'success' => true,
                    'message' => 'No hay lock que liberar.',
                    'previous_locked_by' => null,
                ];
            }

            // Clear all lock fields
            $task->locked_by_user_id = null;
            $task->locked_at = null;
            $task->heartbeat_at = null;
            $task->expires_at = null;
            $task->save();

            // Audit best-effort
            $this->auditOverride([
                'action' => 'force_release',
                'pending_task_id' => $pendingTaskId,
                'actor_user_id' => $adminUserId,
                'previous_locked_by_user_id' => $previousLockedBy,
                'previous_locked_at' => $previousLockedAt,
                'previous_expires_at' => $previousExpiresAt,
                'new_locked_by_user_id' => null,
                'new_locked_at' => null,
                'new_expires_at' => null,
                'timestamp' => $timestamp,
            ]);

            return [
                'success' => true,
                'message' => 'Lock liberado forzosamente.',
                'previous_locked_by' => $previousLockedBy,
            ];
        });
    }

    private function auditOverride(array $context): void
    {
        try {
            Log::info('PendingTaskLockOverride', $context);
        } catch (\Throwable) {
            // Best-effort: never block the override if audit fails.
        }
    }
}
