<?php

namespace App\Actions\PendingTasks;

use App\Enums\PendingTaskStatus;
use App\Models\PendingTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Acquire an exclusive lock on a pending task.
 *
 * Claim is atomic (transaction + row lock) to avoid race conditions.
 * Only tasks in Ready, Processing, or PartiallyCompleted status can be locked.
 * Lock is considered active when expires_at > now().
 */
class AcquirePendingTaskLock
{
    /**
     * @param  int  $pendingTaskId  The pending task to lock
     * @param  int  $userId  The user attempting to acquire the lock
     * @return array{success: bool, message: string, locked_by: int|null, locked_at: string|null}
     *
     * @throws ValidationException
     */
    public function execute(int $pendingTaskId, int $userId): array
    {
        $leaseTtlSeconds = (int) config('gatic.pending_tasks.locks.lease_ttl_s', 180);

        return DB::transaction(function () use ($pendingTaskId, $userId, $leaseTtlSeconds) {
            // Lock the row for update to prevent race conditions
            /** @var PendingTask|null $task */
            $task = PendingTask::query()
                ->lockForUpdate()
                ->find($pendingTaskId);

            if (! $task) {
                throw ValidationException::withMessages([
                    'pending_task_id' => ['La tarea no existe.'],
                ]);
            }

            // Check task is in a status that allows processing
            $allowedStatuses = [
                PendingTaskStatus::Ready,
                PendingTaskStatus::Processing,
                PendingTaskStatus::PartiallyCompleted,
            ];

            if (! in_array($task->status, $allowedStatuses, true)) {
                Log::warning('AcquirePendingTaskLock: task not in allowed status', [
                    'task_id' => $pendingTaskId,
                    'user_id' => $userId,
                    'status' => $task->status->value,
                ]);

                throw ValidationException::withMessages([
                    'status' => ['La tarea no está en un estado que permita procesarla.'],
                ]);
            }

            $now = now();

            // Check if there's an active lock by another user
            if ($task->locked_by_user_id !== null && $task->expires_at !== null) {
                if ($task->expires_at->gt($now)) {
                    // Lock is still active
                    if ($task->locked_by_user_id === $userId) {
                        // User already owns the lock - renew it
                        $task->heartbeat_at = $now;
                        $task->expires_at = $now->copy()->addSeconds($leaseTtlSeconds);
                        $task->save();

                        Log::info('AcquirePendingTaskLock: renewed existing lock', [
                            'task_id' => $pendingTaskId,
                            'user_id' => $userId,
                        ]);

                        return [
                            'success' => true,
                            'message' => 'Lock renovado.',
                            'locked_by' => $userId,
                            'locked_at' => $task->locked_at?->toIso8601String(),
                        ];
                    }

                    // Another user has the lock
                    Log::info('AcquirePendingTaskLock: denied - locked by another user', [
                        'task_id' => $pendingTaskId,
                        'user_id' => $userId,
                        'locked_by' => $task->locked_by_user_id,
                        'expires_at' => $task->expires_at->toIso8601String(),
                    ]);

                    $lockedByName = $task->lockedBy->name ?? 'otro usuario';

                    return [
                        'success' => false,
                        'message' => 'La tarea está bloqueada por '.$lockedByName.'.',
                        'locked_by' => $task->locked_by_user_id,
                        'locked_at' => $task->locked_at?->toIso8601String(),
                    ];
                }

                // Lock has expired - we can claim it
                Log::info('AcquirePendingTaskLock: previous lock expired, claiming', [
                    'task_id' => $pendingTaskId,
                    'user_id' => $userId,
                    'previous_owner' => $task->locked_by_user_id,
                    'expired_at' => $task->expires_at->toIso8601String(),
                ]);
            }

            // Acquire the lock
            $task->locked_by_user_id = $userId;
            $task->locked_at = $now;
            $task->heartbeat_at = $now;
            $task->expires_at = $now->copy()->addSeconds($leaseTtlSeconds);
            $task->save();

            Log::info('AcquirePendingTaskLock: lock acquired', [
                'task_id' => $pendingTaskId,
                'user_id' => $userId,
                'expires_at' => $task->expires_at->toIso8601String(),
            ]);

            return [
                'success' => true,
                'message' => 'Lock adquirido.',
                'locked_by' => $userId,
                'locked_at' => $task->locked_at->toIso8601String(),
            ];
        });
    }
}
