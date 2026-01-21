<?php

namespace Tests\Feature\PendingTasks;

use App\Actions\PendingTasks\AcquirePendingTaskLock;
use App\Actions\PendingTasks\HeartbeatPendingTaskLock;
use App\Actions\PendingTasks\ReleasePendingTaskLock;
use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Models\PendingTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PendingTaskLockTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private PendingTask $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create(['role' => 'Editor', 'name' => 'Usuario A']);
        $this->userB = User::factory()->create(['role' => 'Editor', 'name' => 'Usuario B']);

        $this->task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockOut,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->userA->id,
        ]);
    }

    // === AcquirePendingTaskLock Tests ===

    public function test_acquire_lock_success_on_free_task(): void
    {
        $action = new AcquirePendingTaskLock;

        $result = $action->execute($this->task->id, $this->userA->id);

        $this->assertTrue($result['success']);
        $this->assertEquals($this->userA->id, $result['locked_by']);
        $this->assertNotNull($result['locked_at']);

        $this->task->refresh();
        $this->assertEquals($this->userA->id, $this->task->locked_by_user_id);
        $this->assertNotNull($this->task->locked_at);
        $this->assertNotNull($this->task->heartbeat_at);
        $this->assertNotNull($this->task->expires_at);
        $this->assertTrue($this->task->expires_at->gt(now()));
    }

    public function test_acquire_lock_denied_when_another_user_has_active_lock(): void
    {
        // User A acquires lock first
        $action = new AcquirePendingTaskLock;
        $action->execute($this->task->id, $this->userA->id);

        // User B tries to acquire
        $result = $action->execute($this->task->id, $this->userB->id);

        $this->assertFalse($result['success']);
        $this->assertEquals($this->userA->id, $result['locked_by']);
        $this->assertStringContainsString('Usuario A', $result['message']);

        // Verify lock unchanged
        $this->task->refresh();
        $this->assertEquals($this->userA->id, $this->task->locked_by_user_id);
    }

    public function test_acquire_lock_renews_own_lock(): void
    {
        $action = new AcquirePendingTaskLock;

        // First acquire
        $action->execute($this->task->id, $this->userA->id);
        $this->task->refresh();
        $originalLockedAt = $this->task->locked_at;
        $originalExpiresAt = $this->task->expires_at;

        // Advance time slightly
        Carbon::setTestNow(now()->addSeconds(5));

        // Second acquire by same user - should renew
        $result = $action->execute($this->task->id, $this->userA->id);

        $this->assertTrue($result['success']);

        $this->task->refresh();
        $this->assertEquals($this->userA->id, $this->task->locked_by_user_id);
        // locked_at should remain same (original claim time)
        $this->assertEquals($originalLockedAt->timestamp, $this->task->locked_at->timestamp);
        // expires_at should be extended
        $this->assertTrue($this->task->expires_at->gt($originalExpiresAt));

        Carbon::setTestNow(); // Reset
    }

    public function test_acquire_lock_succeeds_when_previous_lock_expired(): void
    {
        $action = new AcquirePendingTaskLock;

        // User A acquires lock
        $action->execute($this->task->id, $this->userA->id);

        // Advance time past TTL (3 minutes default + buffer)
        Carbon::setTestNow(now()->addMinutes(5));

        // User B should now be able to claim
        $result = $action->execute($this->task->id, $this->userB->id);

        $this->assertTrue($result['success']);
        $this->assertEquals($this->userB->id, $result['locked_by']);

        $this->task->refresh();
        $this->assertEquals($this->userB->id, $this->task->locked_by_user_id);

        Carbon::setTestNow(); // Reset
    }

    public function test_acquire_lock_fails_for_completed_task(): void
    {
        $this->task->update(['status' => PendingTaskStatus::Completed]);

        $action = new AcquirePendingTaskLock;

        $this->expectException(ValidationException::class);
        $action->execute($this->task->id, $this->userA->id);
    }

    public function test_acquire_lock_fails_for_draft_task(): void
    {
        $this->task->update(['status' => PendingTaskStatus::Draft]);

        $action = new AcquirePendingTaskLock;

        $this->expectException(ValidationException::class);
        $action->execute($this->task->id, $this->userA->id);
    }

    public function test_acquire_lock_works_for_processing_task(): void
    {
        $this->task->update(['status' => PendingTaskStatus::Processing]);

        $action = new AcquirePendingTaskLock;
        $result = $action->execute($this->task->id, $this->userA->id);

        $this->assertTrue($result['success']);
    }

    public function test_acquire_lock_works_for_partially_completed_task(): void
    {
        $this->task->update(['status' => PendingTaskStatus::PartiallyCompleted]);

        $action = new AcquirePendingTaskLock;
        $result = $action->execute($this->task->id, $this->userA->id);

        $this->assertTrue($result['success']);
    }

    // === HeartbeatPendingTaskLock Tests ===

    public function test_heartbeat_renews_lock_for_owner(): void
    {
        // First acquire the lock
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->userA->id);

        $this->task->refresh();
        $originalExpiresAt = $this->task->expires_at;

        // Advance time
        Carbon::setTestNow(now()->addSeconds(30));

        // Send heartbeat
        $heartbeatAction = new HeartbeatPendingTaskLock;
        $result = $heartbeatAction->execute($this->task->id, $this->userA->id);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['expires_at']);

        $this->task->refresh();
        $this->assertTrue($this->task->expires_at->gt($originalExpiresAt));

        Carbon::setTestNow(); // Reset
    }

    public function test_heartbeat_denied_for_non_owner(): void
    {
        // User A acquires lock
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->userA->id);

        // User B tries to heartbeat
        $heartbeatAction = new HeartbeatPendingTaskLock;
        $result = $heartbeatAction->execute($this->task->id, $this->userB->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No tienes el lock', $result['message']);
    }

    public function test_heartbeat_fails_when_lock_expired(): void
    {
        // User A acquires lock
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->userA->id);

        // Advance time past TTL
        Carbon::setTestNow(now()->addMinutes(5));

        // Heartbeat should fail
        $heartbeatAction = new HeartbeatPendingTaskLock;
        $result = $heartbeatAction->execute($this->task->id, $this->userA->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('expirado', $result['message']);

        // Lock should be cleared
        $this->task->refresh();
        $this->assertNull($this->task->locked_by_user_id);

        Carbon::setTestNow(); // Reset
    }

    // === ReleasePendingTaskLock Tests ===

    public function test_release_lock_success_for_owner(): void
    {
        // Acquire lock first
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->userA->id);

        // Release
        $releaseAction = new ReleasePendingTaskLock;
        $result = $releaseAction->execute($this->task->id, $this->userA->id);

        $this->assertTrue($result['success']);

        $this->task->refresh();
        $this->assertNull($this->task->locked_by_user_id);
        $this->assertNull($this->task->locked_at);
        $this->assertNull($this->task->heartbeat_at);
        $this->assertNull($this->task->expires_at);
    }

    public function test_release_lock_denied_for_non_owner(): void
    {
        // User A acquires lock
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->userA->id);

        // User B tries to release
        $releaseAction = new ReleasePendingTaskLock;
        $result = $releaseAction->execute($this->task->id, $this->userB->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('no te pertenece', $result['message']);

        // Lock should remain
        $this->task->refresh();
        $this->assertEquals($this->userA->id, $this->task->locked_by_user_id);
    }

    public function test_release_lock_noop_when_no_lock(): void
    {
        $releaseAction = new ReleasePendingTaskLock;
        $result = $releaseAction->execute($this->task->id, $this->userA->id);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('No hay lock', $result['message']);
    }

    // === Model Helper Tests ===

    public function test_has_active_lock_returns_true_when_not_expired(): void
    {
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->userA->id);

        $this->task->refresh();
        $this->assertTrue($this->task->hasActiveLock());
    }

    public function test_has_active_lock_returns_false_when_expired(): void
    {
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->userA->id);

        Carbon::setTestNow(now()->addMinutes(5));

        $this->task->refresh();
        $this->assertFalse($this->task->hasActiveLock());

        Carbon::setTestNow();
    }

    public function test_is_locked_by_returns_correct_values(): void
    {
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->userA->id);

        $this->task->refresh();
        $this->assertTrue($this->task->isLockedBy($this->userA->id));
        $this->assertFalse($this->task->isLockedBy($this->userB->id));
    }

    public function test_is_locked_by_other_returns_correct_values(): void
    {
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->userA->id);

        $this->task->refresh();
        $this->assertFalse($this->task->isLockedByOther($this->userA->id));
        $this->assertTrue($this->task->isLockedByOther($this->userB->id));
    }
}
