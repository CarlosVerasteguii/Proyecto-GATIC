<?php

namespace Tests\Feature\Audit;

use App\Jobs\RecordAuditLog;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Audit\AuditRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests for audit best-effort behavior (AC2).
 *
 * - Audit never blocks main operation
 * - Failures are logged but don't throw
 */
class AuditBestEffortTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'Editor']);
    }

    public function test_audit_recorder_dispatches_job_after_commit(): void
    {
        Queue::fake();

        AuditRecorder::record(
            action: AuditLog::ACTION_ASSET_ASSIGN,
            subjectType: 'App\\Models\\AssetMovement',
            subjectId: 123,
            actorUserId: 1,
            context: ['summary' => 'test data']
        );

        Queue::assertPushed(RecordAuditLog::class, function ($job) {
            return $job->payload['action'] === AuditLog::ACTION_ASSET_ASSIGN
                && $job->payload['subject_id'] === 123
                && $job->payload['context']['summary'] === 'test data';
        });
    }

    public function test_audit_recorder_sync_creates_record_directly(): void
    {
        $log = AuditRecorder::recordSync(
            action: AuditLog::ACTION_ASSET_LOAN,
            subjectType: 'App\\Models\\AssetMovement',
            subjectId: 456,
            actorUserId: $this->user->id,
            context: ['sync' => 'test']
        );

        $this->assertNotNull($log);
        $this->assertEquals(AuditLog::ACTION_ASSET_LOAN, $log->action);
        $this->assertEquals(456, $log->subject_id);
        $this->assertEquals($this->user->id, $log->actor_user_id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_ASSET_LOAN,
            'subject_id' => 456,
            'actor_user_id' => $this->user->id,
        ]);
    }

    public function test_audit_job_creates_record_in_database(): void
    {
        $payload = [
            'action' => AuditLog::ACTION_INVENTORY_ADJUSTMENT,
            'subject_type' => 'App\\Models\\InventoryAdjustmentEntry',
            'subject_id' => 789,
            'actor_user_id' => $this->user->id,
            'context' => ['summary' => 'before=10; after=20'],
            'created_at' => now()->toDateTimeString(),
        ];

        $job = new RecordAuditLog($payload);
        $job->handle();

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_INVENTORY_ADJUSTMENT,
            'subject_id' => 789,
            'actor_user_id' => $this->user->id,
        ]);
    }

    public function test_audit_job_swallows_exceptions_and_logs_warning(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'AuditLog recording failed')
                    && isset($context['error']);
            });

        // Invalid payload (missing required field)
        $payload = [
            'action' => AuditLog::ACTION_ASSET_ASSIGN,
            'subject_type' => '', // Invalid - empty
            'subject_id' => 123,
            'actor_user_id' => 1,
            'context' => null,
            'created_at' => now()->toIso8601String(),
        ];

        $job = new RecordAuditLog($payload);
        $job->handle(); // Should not throw

        // No record should be created
        $this->assertDatabaseMissing('audit_logs', [
            'subject_id' => 123,
        ]);
    }

    public function test_audit_job_validates_subject_id_must_be_positive(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'AuditLog recording failed')
                    && str_contains($context['error'] ?? '', 'positive integer');
            });

        $payload = [
            'action' => AuditLog::ACTION_ASSET_ASSIGN,
            'subject_type' => 'App\\Models\\Test',
            'subject_id' => 0, // Invalid - not positive
            'actor_user_id' => 1,
            'context' => null,
            'created_at' => now()->toIso8601String(),
        ];

        $job = new RecordAuditLog($payload);
        $job->handle(); // Should not throw

        // No record should be created
        $this->assertDatabaseMissing('audit_logs', [
            'action' => AuditLog::ACTION_ASSET_ASSIGN,
        ]);
    }

    public function test_audit_job_has_single_try(): void
    {
        $job = new RecordAuditLog([
            'action' => AuditLog::ACTION_ASSET_ASSIGN,
            'subject_type' => 'App\\Models\\Test',
            'subject_id' => 1,
            'actor_user_id' => 1,
            'context' => null,
            'created_at' => now()->toIso8601String(),
        ]);

        $this->assertEquals(1, $job->tries());
    }

    public function test_audit_model_has_correct_action_labels(): void
    {
        $log = new AuditLog([
            'action' => AuditLog::ACTION_LOCK_FORCE_RELEASE,
            'subject_type' => 'App\\Models\\PendingTask',
            'subject_id' => 1,
        ]);

        $this->assertEquals('Lock liberado (admin)', $log->action_label);

        $log->action = AuditLog::ACTION_ASSET_ASSIGN;
        $this->assertEquals('Asignación de activo', $log->action_label);

        // Unknown action returns action string itself
        $log->action = 'unknown.action';
        $this->assertEquals('unknown.action', $log->action_label);
    }

    public function test_audit_model_subject_type_short_returns_class_basename(): void
    {
        $log = new AuditLog([
            'action' => AuditLog::ACTION_ASSET_ASSIGN,
            'subject_type' => 'App\\Models\\AssetMovement',
            'subject_id' => 1,
        ]);

        $this->assertEquals('AssetMovement', $log->subject_type_short);
    }
}
