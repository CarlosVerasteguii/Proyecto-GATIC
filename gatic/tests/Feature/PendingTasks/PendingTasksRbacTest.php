<?php

namespace Tests\Feature\PendingTasks;

use App\Models\PendingTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PendingTasksRbacTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    private User $lector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->editor = User::factory()->create(['role' => 'Editor']);
        $this->lector = User::factory()->create(['role' => 'Lector']);
    }

    // === Route Access Tests ===

    public function test_admin_can_access_pending_tasks_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('pending-tasks.index'));

        $response->assertStatus(200);
    }

    public function test_editor_can_access_pending_tasks_index(): void
    {
        $response = $this->actingAs($this->editor)->get(route('pending-tasks.index'));

        $response->assertStatus(200);
    }

    public function test_lector_cannot_access_pending_tasks_index(): void
    {
        $response = $this->actingAs($this->lector)->get(route('pending-tasks.index'));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_pending_tasks_index(): void
    {
        $response = $this->get(route('pending-tasks.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_access_create_pending_task(): void
    {
        $response = $this->actingAs($this->admin)->get(route('pending-tasks.create'));

        $response->assertStatus(200);
    }

    public function test_editor_can_access_create_pending_task(): void
    {
        $response = $this->actingAs($this->editor)->get(route('pending-tasks.create'));

        $response->assertStatus(200);
    }

    public function test_lector_cannot_access_create_pending_task(): void
    {
        $response = $this->actingAs($this->lector)->get(route('pending-tasks.create'));

        $response->assertStatus(403);
    }

    public function test_admin_can_access_pending_task_show(): void
    {
        $task = PendingTask::factory()->create([
            'creator_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('pending-tasks.show', $task->id));

        $response->assertStatus(200);
    }

    public function test_editor_can_access_pending_task_show(): void
    {
        $task = PendingTask::factory()->create([
            'creator_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->editor)->get(route('pending-tasks.show', $task->id));

        $response->assertStatus(200);
    }

    public function test_lector_cannot_access_pending_task_show(): void
    {
        $task = PendingTask::factory()->create([
            'creator_user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->lector)->get(route('pending-tasks.show', $task->id));

        $response->assertStatus(403);
    }

    // === Livewire Component Tests ===

    public function test_admin_can_create_pending_task_via_livewire(): void
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\PendingTasks\CreatePendingTask::class)
            ->set('type', 'stock_out')
            ->set('description', 'Test task')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pending_tasks', [
            'type' => 'stock_out',
            'description' => 'Test task',
            'creator_user_id' => $this->admin->id,
        ]);
    }

    public function test_editor_can_create_pending_task_via_livewire(): void
    {
        Livewire::actingAs($this->editor)
            ->test(\App\Livewire\PendingTasks\CreatePendingTask::class)
            ->set('type', 'assign')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pending_tasks', [
            'type' => 'assign',
            'creator_user_id' => $this->editor->id,
        ]);
    }
}
