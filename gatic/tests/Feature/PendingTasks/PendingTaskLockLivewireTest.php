<?php

namespace Tests\Feature\PendingTasks;

use App\Enums\PendingTaskLineStatus;
use App\Enums\PendingTaskLineType;
use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Livewire\PendingTasks\PendingTaskShow;
use App\Models\Category;
use App\Models\Employee;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PendingTaskLockLivewireTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private PendingTask $task;

    private PendingTaskLine $line;

    private Product $product;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create(['role' => 'Editor', 'name' => 'Usuario A']);
        $this->userB = User::factory()->create(['role' => 'Editor', 'name' => 'Usuario B']);

        $category = Category::factory()->create(['is_serialized' => true]);
        $this->product = Product::factory()->create(['category_id' => $category->id]);
        $this->employee = Employee::factory()->create();

        $this->task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockOut,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->userA->id,
        ]);

        $this->line = PendingTaskLine::factory()->create([
            'pending_task_id' => $this->task->id,
            'product_id' => $this->product->id,
            'employee_id' => $this->employee->id,
            'line_type' => PendingTaskLineType::Serialized,
            'serial' => 'TEST123',
            'line_status' => PendingTaskLineStatus::Pending,
        ]);
    }

    public function test_enter_process_mode_acquires_lock(): void
    {
        Livewire::actingAs($this->userA)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->call('enterProcessMode')
            ->assertSet('isProcessMode', true)
            ->assertSet('hasLock', true)
            ->assertSet('lockLost', false);

        $this->task->refresh();
        $this->assertEquals($this->userA->id, $this->task->locked_by_user_id);
        $this->assertNotNull($this->task->expires_at);
    }

    public function test_page_refresh_with_own_active_lock_resumes_process_mode(): void
    {
        // Simulate the user already has the lock and refreshes the page
        $this->task->locked_by_user_id = $this->userA->id;
        $this->task->locked_at = now();
        $this->task->heartbeat_at = now();
        $this->task->expires_at = now()->addMinutes(3);
        $this->task->save();

        Livewire::actingAs($this->userA)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->assertSet('hasLock', true)
            ->assertSet('isProcessMode', true)
            ->assertSet('lockLost', false);

        // Also ensure status is consistent for process mode
        $this->task->refresh();
        $this->assertEquals(PendingTaskStatus::Processing, $this->task->status);
    }

    public function test_enter_process_mode_fails_when_locked_by_other(): void
    {
        // User A enters process mode first
        Livewire::actingAs($this->userA)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->call('enterProcessMode');

        // User B tries to enter - should fail
        Livewire::actingAs($this->userB)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->call('enterProcessMode')
            ->assertSet('isProcessMode', false)
            ->assertSet('hasLock', false);
    }

    public function test_exit_process_mode_releases_lock(): void
    {
        $component = Livewire::actingAs($this->userA)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->call('enterProcessMode')
            ->assertSet('hasLock', true);

        $component->call('exitProcessMode')
            ->assertSet('isProcessMode', false)
            ->assertSet('hasLock', false);

        $this->task->refresh();
        $this->assertNull($this->task->locked_by_user_id);
    }

    public function test_validate_line_blocked_without_lock(): void
    {
        // User A enters process mode
        Livewire::actingAs($this->userA)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->call('enterProcessMode');

        // Simulate lock expiration
        $this->task->expires_at = now()->subMinutes(1);
        $this->task->save();

        // Try to validate - should fail
        Livewire::actingAs($this->userA)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->set('isProcessMode', true)
            ->set('hasLock', false)
            ->call('validateLine', $this->line->id);

        // Line should remain pending (not validated)
        $this->line->refresh();
        $this->assertEquals(PendingTaskLineStatus::Pending, $this->line->line_status);
    }

    public function test_finalize_task_blocked_without_lock(): void
    {
        // User A enters process mode
        $component = Livewire::actingAs($this->userA)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->call('enterProcessMode');

        // Simulate lock expiration by clearing it
        $this->task->locked_by_user_id = null;
        $this->task->locked_at = null;
        $this->task->expires_at = null;
        $this->task->save();

        // Try to finalize - should fail
        $component->call('finalizeTask');

        // Task should remain in Ready/Processing state
        $this->task->refresh();
        $this->assertNotEquals(PendingTaskStatus::Completed, $this->task->status);
    }

    public function test_retry_lock_reacquires_when_available(): void
    {
        // User A enters process mode
        $component = Livewire::actingAs($this->userA)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->call('enterProcessMode')
            ->assertSet('hasLock', true);

        // Simulate lock lost (expired)
        $this->task->locked_by_user_id = null;
        $this->task->locked_at = null;
        $this->task->expires_at = null;
        $this->task->save();

        $component->set('hasLock', false)
            ->set('lockLost', true);

        // Retry should succeed
        $component->call('retryLock')
            ->assertSet('hasLock', true)
            ->assertSet('lockLost', false);

        $this->task->refresh();
        $this->assertEquals($this->userA->id, $this->task->locked_by_user_id);
    }

    public function test_retry_lock_fails_when_held_by_other(): void
    {
        // User B has the lock
        $this->task->locked_by_user_id = $this->userB->id;
        $this->task->locked_at = now();
        $this->task->heartbeat_at = now();
        $this->task->expires_at = now()->addMinutes(3);
        $this->task->save();

        // User A tries to retry
        Livewire::actingAs($this->userA)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->set('isProcessMode', true)
            ->set('lockLost', true)
            ->call('retryLock')
            ->assertSet('hasLock', false)
            ->assertSet('lockLost', true);

        // Lock should remain with User B
        $this->task->refresh();
        $this->assertEquals($this->userB->id, $this->task->locked_by_user_id);
    }

    public function test_heartbeat_renews_lock(): void
    {
        $component = Livewire::actingAs($this->userA)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->call('enterProcessMode');

        $this->task->refresh();
        $originalExpires = $this->task->expires_at;

        // Advance time
        Carbon::setTestNow(now()->addSeconds(30));

        // Call heartbeat
        $component->call('heartbeat');

        $this->task->refresh();
        $this->assertTrue($this->task->expires_at->gt($originalExpires));

        Carbon::setTestNow();
    }

    public function test_user_without_permission_cannot_acquire_lock(): void
    {
        $reader = User::factory()->create(['role' => 'Lector']);

        Livewire::actingAs($reader)
            ->test(PendingTaskShow::class, ['pendingTask' => $this->task->id])
            ->assertForbidden();
    }
}
