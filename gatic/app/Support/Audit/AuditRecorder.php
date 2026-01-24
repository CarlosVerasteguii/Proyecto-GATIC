<?php

namespace App\Support\Audit;

use App\Jobs\RecordAuditLog;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Helper/service to standardize audit event recording.
 *
 * Use this class to record audit events in a best-effort manner.
 * The recording is dispatched as a queued job and executed
 * after the current transaction commits (when called within a transaction).
 *
 * @example
 * // After a successful operation:
 * AuditRecorder::record(
 *     action: AuditLog::ACTION_ASSET_ASSIGN,
 *     subjectType: AssetMovement::class,
 *     subjectId: $movement->id,
 *     actorUserId: auth()->id(),
 *     context: ['asset_id' => $asset->id, 'employee_id' => $employeeId]
 * );
 */
class AuditRecorder
{
    /**
     * Allowed context keys (MVP) to avoid over-auditing and storing sensitive data.
     *
     * Source of truth: docsBmad/product/audit-use-cases.md
     */
    private const ALLOWED_CONTEXT_KEYS = [
        'pending_task_id',
        'previous_locked_by_user_id',
        'new_locked_by_user_id',
        'asset_id',
        'product_id',
        'employee_id',
        'inventory_adjustment_id',
        'movement_id',
        'note_id',
        'summary',
        'reason',
    ];

    private const MAX_TEXT_LENGTH = 255;

    /**
     * Record an audit event (best-effort, non-blocking).
     *
     * @param  string  $action  One of AuditLog::ACTION_* constants
     * @param  string  $subjectType  The model class name (e.g., AssetMovement::class)
     * @param  int  $subjectId  The primary key of the subject
     * @param  int|null  $actorUserId  The user who performed the action
     * @param  array<string, mixed>|null  $context  Additional context for debugging
     */
    public static function record(
        string $action,
        string $subjectType,
        int $subjectId,
        ?int $actorUserId = null,
        ?array $context = null
    ): bool {
        try {
            $payload = [
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'actor_user_id' => $actorUserId,
                'context' => self::sanitizeContext($context),
                'created_at' => now()->toDateTimeString(),
            ];

            // Dispatch after commit to avoid recording events
            // from transactions that later rollback
            RecordAuditLog::dispatch($payload)->afterCommit();

            return true;
        } catch (\Throwable $e) {
            // Best-effort: if dispatch fails, log and continue (NFR8)
            try {
                Log::warning('AuditRecorder dispatch failed', [
                    'error' => $e->getMessage(),
                    'action' => $action,
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                ]);
            } catch (\Throwable) {
                // If even logging fails, silently ignore
            }

            return false;
        }
    }

    /**
     * Record an audit event synchronously (for testing or special cases).
     *
     * WARNING: Only use this for testing or when you explicitly need
     * synchronous recording. For production code, use record() instead.
     *
     * @param  string  $action  One of AuditLog::ACTION_* constants
     * @param  string  $subjectType  The model class name
     * @param  int  $subjectId  The primary key of the subject
     * @param  int|null  $actorUserId  The user who performed the action
     * @param  array<string, mixed>|null  $context  Additional context
     */
    public static function recordSync(
        string $action,
        string $subjectType,
        int $subjectId,
        ?int $actorUserId = null,
        ?array $context = null
    ): ?AuditLog {
        try {
            return AuditLog::create([
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'actor_user_id' => $actorUserId,
                'context' => self::sanitizeContext($context),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Best-effort: log and return null (NFR8)
            try {
                Log::warning('AuditRecorder sync recording failed', [
                    'error' => $e->getMessage(),
                    'action' => $action,
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                ]);
            } catch (\Throwable) {
                // Silently ignore
            }

            return null;
        }
    }

    /**
     * Reduce context to a scalar allowlist and trim overly-long text values.
     *
     * @param  array<string, mixed>|null  $context
     * @return array<string, int|string|bool|float|null>|null
     */
    private static function sanitizeContext(?array $context): ?array
    {
        if (! is_array($context) || $context === []) {
            return null;
        }

        $sanitized = [];

        foreach (self::ALLOWED_CONTEXT_KEYS as $key) {
            if (! array_key_exists($key, $context)) {
                continue;
            }

            /** @var mixed $value */
            $value = $context[$key];

            if (is_string($value)) {
                $value = trim($value);

                if ($value === '') {
                    $sanitized[$key] = '';

                    continue;
                }

                $value = Str::limit($value, self::MAX_TEXT_LENGTH, '...');

                $sanitized[$key] = $value;

                continue;
            }

            if (is_int($value) || is_bool($value) || is_float($value) || $value === null) {
                $sanitized[$key] = $value;

                continue;
            }
        }

        return $sanitized === [] ? null : $sanitized;
    }
}
