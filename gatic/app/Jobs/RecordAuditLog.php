<?php

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Best-effort audit log recorder.
 *
 * This job persists audit events to the database.
 * It swallows all exceptions to ensure the audit mechanism
 * never blocks or fails the primary operation (NFR8).
 *
 * @see \App\Support\Audit\AuditRecorder
 */
class RecordAuditLog implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{action: string, subject_type: string, subject_id: int, actor_user_id: int|null, context: array<string, mixed>|null, created_at: string|null}  $payload
     */
    public function __construct(
        public array $payload
    ) {
    }

    /**
     * Execute the job.
     *
     * Best-effort: catches ALL exceptions and logs them
     * without rethrowing to prevent job failures from
     * affecting the system.
     */
    public function handle(): void
    {
        try {
            $this->validatePayload();

            $createdAt = $this->payload['created_at'] ?? null;
            if (is_string($createdAt) && $createdAt !== '') {
                $createdAt = Carbon::parse($createdAt);
            } else {
                $createdAt = now();
            }

            AuditLog::create([
                'action' => $this->payload['action'],
                'subject_type' => $this->payload['subject_type'],
                'subject_id' => $this->payload['subject_id'],
                'actor_user_id' => $this->payload['actor_user_id'] ?? null,
                'context' => is_array($this->payload['context'] ?? null) ? $this->payload['context'] : null,
                'created_at' => $createdAt,
            ]);
        } catch (\Throwable $e) {
            // Best-effort: log the failure but never rethrow (NFR8)
            try {
                Log::warning('AuditLog recording failed', [
                    'error' => $e->getMessage(),
                    'payload' => $this->payload,
                ]);
            } catch (\Throwable) {
                // If even logging fails, silently ignore
            }
        }
    }

    /**
     * Validate payload has required fields.
     *
     * @throws \InvalidArgumentException
     */
    private function validatePayload(): void
    {
        $required = ['action', 'subject_type', 'subject_id'];

        foreach ($required as $field) {
            if (! array_key_exists($field, $this->payload) || $this->payload[$field] === '') {
                throw new \InvalidArgumentException("Missing required audit field: {$field}");
            }
        }

        /** @var mixed $subjectId */
        $subjectId = $this->payload['subject_id'];
        if (! is_int($subjectId) || $subjectId <= 0) {
            throw new \InvalidArgumentException('subject_id must be a positive integer');
        }
    }

    /**
     * Prevent job retries - best-effort means we don't retry failures.
     */
    public function tries(): int
    {
        return 1;
    }

    /**
     * Handle job failure - log and move on.
     */
    public function failed(?\Throwable $exception): void
    {
        try {
            Log::warning('AuditLog job failed permanently', [
                'error' => $exception?->getMessage(),
                'payload' => $this->payload,
            ]);
        } catch (\Throwable) {
            // Silently ignore
        }
    }
}
