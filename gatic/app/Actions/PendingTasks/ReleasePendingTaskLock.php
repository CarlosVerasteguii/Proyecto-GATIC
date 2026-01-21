<?php

namespace App\Actions\PendingTasks;

use App\Models\PendingTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Release a lock on a pending task.
 *
 * Only the lock owner can release. Clears all lock fields.
 */
class ReleasePendingTaskLock
{
    /**
     * @param  int  $pendingTaskId  The pending task to release
     * @param  int  $userId  The user releasing the lock
     * @return array{success: bool, message: string}
     *
     * @throws ValidationException
     */
    public function execute(int $pendingTaskId, int $userId): array
    {
        return DB::transaction(function () use ($pendingTaskId, $userId) {
            /** @var PendingTask|null $task */
            $task = PendingTask::query()
                ->lockForUpdate()
                ->find($pendingTaskId);

            if (! $task) {
                throw ValidationException::withMessages([
                    'pending_task_id' => ['La tarea no existe.'],
                ]);
            }

            // If no lock exists, nothing to do
            if ($task->locked_by_user_id === null) {
                Log::info('ReleasePendingTaskLock: no lock to release', [
                    'task_id' => $pendingTaskId,
                    'user_id' => $userId,
                ]);

                return [
                    'success' => true,
                    'message' => 'No hay lock que liberar.',
                ];
            }

            // Verify the user owns the lock
            if ($task->locked_by_user_id !== $userId) {
                Log::warning('ReleasePendingTaskLock: denied - not lock owner', [
                    'task_id' => $pendingTaskId,
                    'user_id' => $userId,
                    'locked_by' => $task->locked_by_user_id,
                ]);

                return [
                    'success' => false,
                    'message' => 'No puedes liberar un lock que no te pertenece.',
                ];
            }

            // Release the lock
            $task->locked_by_user_id = null;
            $task->locked_at = null;
            $task->heartbeat_at = null;
            $task->expires_at = null;
            $task->save();

            Log::info('ReleasePendingTaskLock: lock released', [
                'task_id' => $pendingTaskId,
                'user_id' => $userId,
            ]);

            return [
                'success' => true,
                'message' => 'Lock liberado.',
            ];
        });
    }
}
