<?php

namespace App\Actions\PendingTasks;

use App\Models\PendingTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Renew the lock lease via heartbeat.
 *
 * Only the lock owner can renew. Updates heartbeat_at and extends expires_at.
 */
class HeartbeatPendingTaskLock
{
    /**
     * @param  int  $pendingTaskId  The pending task to heartbeat
     * @param  int  $userId  The user sending the heartbeat
     * @return array{success: bool, message: string, expires_at: string|null}
     *
     * @throws ValidationException
     */
    public function execute(int $pendingTaskId, int $userId): array
    {
        $leaseTtlSeconds = (int) config('gatic.pending_tasks.locks.lease_ttl_s', 180);

        return DB::transaction(function () use ($pendingTaskId, $userId, $leaseTtlSeconds) {
            /** @var PendingTask|null $task */
            $task = PendingTask::query()
                ->lockForUpdate()
                ->find($pendingTaskId);

            if (! $task) {
                throw ValidationException::withMessages([
                    'pending_task_id' => ['La tarea no existe.'],
                ]);
            }

            $now = now();

            // Verify the user owns the lock
            if ($task->locked_by_user_id !== $userId) {
                Log::warning('HeartbeatPendingTaskLock: denied - not lock owner', [
                    'task_id' => $pendingTaskId,
                    'user_id' => $userId,
                    'locked_by' => $task->locked_by_user_id,
                ]);

                return [
                    'success' => false,
                    'message' => 'No tienes el lock de esta tarea.',
                    'expires_at' => null,
                ];
            }

            // Verify lock hasn't expired
            if ($task->expires_at === null || $task->expires_at->lte($now)) {
                Log::warning('HeartbeatPendingTaskLock: denied - lock expired', [
                    'task_id' => $pendingTaskId,
                    'user_id' => $userId,
                    'expires_at' => $task->expires_at?->toIso8601String(),
                ]);

                // Clear the expired lock
                $task->locked_by_user_id = null;
                $task->locked_at = null;
                $task->heartbeat_at = null;
                $task->expires_at = null;
                $task->save();

                return [
                    'success' => false,
                    'message' => 'Tu lock ha expirado.',
                    'expires_at' => null,
                ];
            }

            // Renew the lock
            $task->heartbeat_at = $now;
            $task->expires_at = $now->copy()->addSeconds($leaseTtlSeconds);
            $task->save();

            Log::debug('HeartbeatPendingTaskLock: renewed', [
                'task_id' => $pendingTaskId,
                'user_id' => $userId,
                'expires_at' => $task->expires_at->toIso8601String(),
            ]);

            return [
                'success' => true,
                'message' => 'Lock renovado.',
                'expires_at' => $task->expires_at->toIso8601String(),
            ];
        });
    }
}
