<?php

namespace App\Actions\PendingTasks;

use App\Models\PendingTask;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Admin-only: Force claim a lock on a pending task.
 *
 * This takes ownership of the lock (or creates one) regardless of the current owner.
 * If Admin is already the owner, it just renews the lease.
 * The operation is audited (best-effort) for compliance.
 */
class ForceClaimPendingTaskLock
{
    /**
     * @param  int  $pendingTaskId  The pending task to force-claim
     * @param  int  $adminUserId  The admin performing the action
     * @return array{success: bool, message: string, locked_by: int, locked_at: string, expires_at: string, previous_locked_by: int|null}
     *
     * @throws ValidationException
     */
    public function execute(int $pendingTaskId, int $adminUserId): array
    {
        $leaseTtlSeconds = (int) config('gatic.pending_tasks.locks.lease_ttl_s', 180);

        return DB::transaction(function () use ($pendingTaskId, $adminUserId, $leaseTtlSeconds) {
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

            $now = now();
            $isRenewal = ($task->locked_by_user_id === $adminUserId);

            // Set admin as owner and reinitialize lease
            $task->locked_by_user_id = $adminUserId;
            $task->locked_at = $now;
            $task->heartbeat_at = $now;
            $task->expires_at = $now->copy()->addSeconds($leaseTtlSeconds);
            $task->save();

            // Audit best-effort
            $this->auditOverride([
                'action' => 'force_claim',
                'pending_task_id' => $pendingTaskId,
                'actor_user_id' => $adminUserId,
                'previous_locked_by_user_id' => $previousLockedBy,
                'previous_locked_at' => $previousLockedAt,
                'previous_expires_at' => $previousExpiresAt,
                'new_locked_by_user_id' => $adminUserId,
                'new_locked_at' => $task->locked_at->toIso8601String(),
                'new_expires_at' => $task->expires_at->toIso8601String(),
                'timestamp' => $timestamp,
            ]);

            $message = $isRenewal
                ? 'Lock renovado (ya eras el propietario).'
                : 'Lock reclamado forzosamente.';

            try {
                Log::info('ForceClaimPendingTaskLock: lock acquired', [
                    'task_id' => $pendingTaskId,
                    'admin_user_id' => $adminUserId,
                    'was_renewal' => $isRenewal,
                    'previous_owner' => $previousLockedBy,
                    'expires_at' => $task->expires_at->toIso8601String(),
                ]);
            } catch (\Throwable) {
                // Best-effort: never block the override if logging fails.
            }

            return [
                'success' => true,
                'message' => $message,
                'locked_by' => $adminUserId,
                'locked_at' => $task->locked_at->toIso8601String(),
                'expires_at' => $task->expires_at->toIso8601String(),
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
