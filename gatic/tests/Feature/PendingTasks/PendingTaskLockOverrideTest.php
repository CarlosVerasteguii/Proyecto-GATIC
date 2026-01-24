<?php

namespace Tests\Feature\PendingTasks;

use App\Actions\PendingTasks\AcquirePendingTaskLock;
use App\Actions\PendingTasks\ForceClaimPendingTaskLock;
use App\Actions\PendingTasks\ForceReleasePendingTaskLock;
use App\Actions\PendingTasks\HeartbeatPendingTaskLock;
use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Jobs\RecordAuditLog;
use App\Livewire\PendingTasks\PendingTaskShow;
use App\Models\AuditLog;
use App\Models\PendingTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests for Admin override of pending task locks (Story 7.5)
 *
 * AC1: Admin can force-release a lock
 * AC2: Admin can force-claim a lock
 * AC3: Non-admin users cannot execute override actions (RBAC)
 * AC5: Editor loses lock when admin executes override
 * AC6: Idempotent behavior
 */
class PendingTaskLockOverrideTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    private User $lector;

    private PendingTask $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'Admin', 'name' => 'Admin User']);
        $this->editor = User::factory()->create(['role' => 'Editor', 'name' => 'Editor User']);
        $this->lector = User::factory()->create(['role' => 'Lector', 'name' => 'Lector User']);

        $this->task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockOut,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->editor->id,
        ]);
    }

    // =========================================================================
    // ForceReleasePendingTaskLock Tests (AC1, AC6)
    // =========================================================================

    public function test_force_release_clears_active_lock_from_another_user(): void
    {
        // Editor acquires lock
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->editor->id);

        $this->task->refresh();
        $this->assertTrue($this->task->hasActiveLock());
        $this->assertEquals($this->editor->id, $this->task->locked_by_user_id);

        // Admin force-releases
        $action = new ForceReleasePendingTaskLock;
        $result = $action->execute($this->task->id, $this->admin->id);

        $this->assertTrue($result['success']);
        $this->assertEquals($this->editor->id, $result['previous_locked_by']);
        $this->assertStringContainsString('liberado forzosamente', $result['message']);

        // Verify lock is cleared
        $this->task->refresh();
        $this->assertNull($this->task->locked_by_user_id);
        $this->assertNull($this->task->locked_at);
        $this->assertNull($this->task->heartbeat_at);
        $this->assertNull($this->task->expires_at);
        $this->assertFalse($this->task->hasActiveLock());
    }

    public function test_force_release_is_idempotent_when_no_lock_exists(): void
    {
        // Task has no lock
        $this->assertNull($this->task->locked_by_user_id);

        // Admin force-releases (should be idempotent)
        $action = new ForceReleasePendingTaskLock;
        $result = $action->execute($this->task->id, $this->admin->id);

        $this->assertTrue($result['success']);
        $this->assertNull($result['previous_locked_by']);
        $this->assertStringContainsString('No hay lock', $result['message']);
    }

    public function test_force_release_logs_audit_event(): void
    {
        Queue::fake();

        // Editor acquires lock
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->editor->id);

        $action = new ForceReleasePendingTaskLock;
        $action->execute($this->task->id, $this->admin->id);

        Queue::assertPushed(RecordAuditLog::class, function ($job) {
            return $job->payload['action'] === AuditLog::ACTION_LOCK_FORCE_RELEASE
                && $job->payload['subject_type'] === PendingTask::class
                && $job->payload['subject_id'] === $this->task->id
                && $job->payload['actor_user_id'] === $this->admin->id
                && $job->payload['context']['pending_task_id'] === $this->task->id
                && $job->payload['context']['previous_locked_by_user_id'] === $this->editor->id
                && $job->payload['context']['new_locked_by_user_id'] === null;
        });
    }

    // =========================================================================
    // ForceClaimPendingTaskLock Tests (AC2, AC6)
    // =========================================================================

    public function test_force_claim_takes_lock_from_another_user(): void
    {
        // Editor acquires lock
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->editor->id);

        $this->task->refresh();
        $this->assertEquals($this->editor->id, $this->task->locked_by_user_id);

        // Admin force-claims
        $action = new ForceClaimPendingTaskLock;
        $result = $action->execute($this->task->id, $this->admin->id);

        $this->assertTrue($result['success']);
        $this->assertEquals($this->admin->id, $result['locked_by']);
        $this->assertEquals($this->editor->id, $result['previous_locked_by']);
        $this->assertStringContainsString('reclamado forzosamente', $result['message']);

        // Verify lock is now owned by admin
        $this->task->refresh();
        $this->assertEquals($this->admin->id, $this->task->locked_by_user_id);
        $this->assertTrue($this->task->hasActiveLock());
        $this->assertTrue($this->task->isLockedBy($this->admin->id));
        $this->assertFalse($this->task->isLockedBy($this->editor->id));
    }

    public function test_force_claim_renews_lease_when_admin_already_owns_lock(): void
    {
        // Admin acquires lock first
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->admin->id);

        $this->task->refresh();
        $originalExpiresAt = $this->task->expires_at;

        // Advance time
        Carbon::setTestNow(now()->addSeconds(30));

        // Admin force-claims again (should renew)
        $action = new ForceClaimPendingTaskLock;
        $result = $action->execute($this->task->id, $this->admin->id);

        $this->assertTrue($result['success']);
        $this->assertEquals($this->admin->id, $result['locked_by']);
        $this->assertEquals($this->admin->id, $result['previous_locked_by']);
        $this->assertStringContainsString('renovado', $result['message']);

        // Verify lease was renewed
        $this->task->refresh();
        $this->assertTrue($this->task->expires_at->gt($originalExpiresAt));

        Carbon::setTestNow();
    }

    public function test_force_claim_creates_lock_on_free_task(): void
    {
        // Task has no lock
        $this->assertNull($this->task->locked_by_user_id);

        // Admin force-claims
        $action = new ForceClaimPendingTaskLock;
        $result = $action->execute($this->task->id, $this->admin->id);

        $this->assertTrue($result['success']);
        $this->assertEquals($this->admin->id, $result['locked_by']);
        $this->assertNull($result['previous_locked_by']);

        // Verify lock is created
        $this->task->refresh();
        $this->assertEquals($this->admin->id, $this->task->locked_by_user_id);
        $this->assertTrue($this->task->hasActiveLock());
    }

    public function test_force_claim_logs_audit_event(): void
    {
        Queue::fake();

        // Editor acquires lock
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->editor->id);

        $action = new ForceClaimPendingTaskLock;
        $action->execute($this->task->id, $this->admin->id);

        Queue::assertPushed(RecordAuditLog::class, function ($job) {
            return $job->payload['action'] === AuditLog::ACTION_LOCK_FORCE_CLAIM
                && $job->payload['subject_type'] === PendingTask::class
                && $job->payload['subject_id'] === $this->task->id
                && $job->payload['actor_user_id'] === $this->admin->id
                && $job->payload['context']['pending_task_id'] === $this->task->id
                && $job->payload['context']['previous_locked_by_user_id'] === $this->editor->id
                && $job->payload['context']['new_locked_by_user_id'] === $this->admin->id;
        });
    }

    // =========================================================================
    // Editor Lock Lost Tests (AC5)
    // =========================================================================

    public function test_editor_heartbeat_fails_after_admin_force_releases(): void
    {
        // Editor acquires lock
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->editor->id);

        // Admin force-releases
        $releaseAction = new ForceReleasePendingTaskLock;
        $releaseAction->execute($this->task->id, $this->admin->id);

        // Editor's heartbeat should fail (no lock)
        $heartbeatAction = new HeartbeatPendingTaskLock;
        $result = $heartbeatAction->execute($this->task->id, $this->editor->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No tienes el lock', $result['message']);
    }

    public function test_editor_heartbeat_fails_after_admin_force_claims(): void
    {
        // Editor acquires lock
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->editor->id);

        // Admin force-claims
        $claimAction = new ForceClaimPendingTaskLock;
        $claimAction->execute($this->task->id, $this->admin->id);

        // Editor's heartbeat should fail (owner mismatch)
        $heartbeatAction = new HeartbeatPendingTaskLock;
        $result = $heartbeatAction->execute($this->task->id, $this->editor->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No tienes el lock', $result['message']);
    }

    public function test_editor_is_locked_by_returns_false_after_admin_force_claims(): void
    {
        // Editor acquires lock
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->editor->id);

        $this->task->refresh();
        $this->assertTrue($this->task->isLockedBy($this->editor->id));

        // Admin force-claims
        $claimAction = new ForceClaimPendingTaskLock;
        $claimAction->execute($this->task->id, $this->admin->id);

        $this->task->refresh();
        $this->assertFalse($this->task->isLockedBy($this->editor->id));
        $this->assertTrue($this->task->isLockedByOther($this->editor->id));
    }

    // =========================================================================
    // Livewire RBAC Tests (AC3)
    // =========================================================================

    public function test_admin_can_access_force_release_via_livewire(): void
    {
        // Editor acquires lock
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->editor->id);

        Livewire::actingAs($this->admin)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->call('forceReleaseLock')
            ->assertHasNoErrors();

        $this->task->refresh();
        $this->assertNull($this->task->locked_by_user_id);
    }

    public function test_admin_can_access_force_claim_via_livewire(): void
    {
        // Editor acquires lock
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->editor->id);

        Livewire::actingAs($this->admin)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->call('forceClaimLock')
            ->assertHasNoErrors();

        $this->task->refresh();
        $this->assertEquals($this->admin->id, $this->task->locked_by_user_id);
    }

    public function test_editor_cannot_access_force_release_via_livewire(): void
    {
        // Another editor acquires lock
        $otherEditor = User::factory()->create(['role' => 'Editor', 'name' => 'Other Editor']);
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $otherEditor->id);

        Livewire::actingAs($this->editor)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->call('forceReleaseLock')
            ->assertForbidden();

        // Lock should remain unchanged
        $this->task->refresh();
        $this->assertEquals($otherEditor->id, $this->task->locked_by_user_id);
    }

    public function test_editor_cannot_access_force_claim_via_livewire(): void
    {
        // Another editor acquires lock
        $otherEditor = User::factory()->create(['role' => 'Editor', 'name' => 'Other Editor']);
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $otherEditor->id);

        Livewire::actingAs($this->editor)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->call('forceClaimLock')
            ->assertForbidden();

        // Lock should remain unchanged
        $this->task->refresh();
        $this->assertEquals($otherEditor->id, $this->task->locked_by_user_id);
    }

    public function test_lector_cannot_access_pending_task_show(): void
    {
        // Editor acquires lock
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->editor->id);

        // Lector doesn't even have inventory.manage, so they can't access the page at all
        Livewire::actingAs($this->lector)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->assertForbidden();
    }

    // =========================================================================
    // UI Visibility Tests (AC4)
    // =========================================================================

    public function test_admin_sees_override_buttons_when_lock_is_from_another_user(): void
    {
        // Editor acquires lock
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->editor->id);

        Livewire::actingAs($this->admin)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->assertSee('Forzar liberación')
            ->assertSee('Forzar reclamo')
            ->assertSee('Como Admin, puedes forzar acciones sobre este lock');
    }

    public function test_admin_does_not_see_override_buttons_when_lock_is_their_own(): void
    {
        // Admin acquires lock
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->admin->id);

        Livewire::actingAs($this->admin)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->assertDontSee('Forzar liberación')
            ->assertDontSee('Forzar reclamo');
    }

    public function test_admin_does_not_see_override_buttons_when_task_is_free(): void
    {
        // No lock on task
        $this->assertNull($this->task->locked_by_user_id);

        Livewire::actingAs($this->admin)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->assertDontSee('Forzar liberación')
            ->assertDontSee('Forzar reclamo');
    }

    public function test_editor_does_not_see_override_buttons(): void
    {
        // Another editor acquires lock
        $otherEditor = User::factory()->create(['role' => 'Editor', 'name' => 'Other Editor']);
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $otherEditor->id);

        Livewire::actingAs($this->editor)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->assertDontSee('Forzar liberación')
            ->assertDontSee('Forzar reclamo')
            ->assertDontSee('Como Admin');
    }

    public function test_editor_sees_lock_lost_banner_after_admin_force_releases(): void
    {
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->editor->id);

        $component = Livewire::actingAs($this->editor)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->call('enterProcessMode')
            ->assertSet('isProcessMode', true)
            ->assertSet('hasLock', true);

        $releaseAction = new ForceReleasePendingTaskLock;
        $releaseAction->execute($this->task->id, $this->admin->id);

        $component
            ->call('heartbeat')
            ->assertSet('lockLost', true)
            ->assertSee('Lock perdido')
            ->assertSee('Reintentar claim');
    }

    public function test_editor_sees_lock_lost_banner_after_admin_force_claims(): void
    {
        $acquireAction = new AcquirePendingTaskLock;
        $acquireAction->execute($this->task->id, $this->editor->id);

        $component = Livewire::actingAs($this->editor)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->call('enterProcessMode')
            ->assertSet('isProcessMode', true)
            ->assertSet('hasLock', true);

        $claimAction = new ForceClaimPendingTaskLock;
        $claimAction->execute($this->task->id, $this->admin->id);

        $component
            ->call('heartbeat')
            ->assertSet('lockLost', true)
            ->assertSee('Lock perdido')
            ->assertSee('Reintentar claim');
    }
}
