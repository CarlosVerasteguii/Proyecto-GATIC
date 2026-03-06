<?php

namespace Tests\Feature\PendingTasks;

use App\Enums\PendingTaskStatus;
use App\Enums\UserRole;
use App\Livewire\PendingTasks\PendingTaskShow;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PendingTaskShowMarkupTest extends TestCase
{
    use RefreshDatabase;

    public function test_line_modal_includes_dialog_semantics_and_close_label(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $task = PendingTask::factory()->create([
            'creator_user_id' => $admin->id,
            'status' => PendingTaskStatus::Draft,
        ]);

        Livewire::actingAs($admin)
            ->test(PendingTaskShow::class, ['pendingTask' => $task->id])
            ->call('openAddLineModal')
            ->assertSeeHtml('aria-labelledby="pendingTaskLineModalTitle"')
            ->assertSeeHtml('aria-modal="true"')
            ->assertSeeHtml('role="dialog"')
            ->assertSeeHtml('aria-label="Cerrar"');
    }

    public function test_finalize_confirm_modal_includes_dialog_semantics_and_close_label(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $task = PendingTask::factory()->create([
            'creator_user_id' => $admin->id,
            'status' => PendingTaskStatus::Ready,
        ]);

        Livewire::actingAs($admin)
            ->test(PendingTaskShow::class, ['pendingTask' => $task->id])
            ->call('showFinalizeConfirm')
            ->assertSeeHtml('aria-labelledby="pendingTaskFinalizeConfirmTitle"')
            ->assertSeeHtml('aria-modal="true"')
            ->assertSeeHtml('role="dialog"')
            ->assertSeeHtml('aria-label="Cerrar"');
    }

    public function test_lines_table_rows_include_wire_key_for_stable_morphing(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $task = PendingTask::factory()->create([
            'creator_user_id' => $admin->id,
            'status' => PendingTaskStatus::Draft,
        ]);

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
        ]);

        Livewire::actingAs($admin)
            ->test(PendingTaskShow::class, ['pendingTask' => $task->id])
            ->assertSeeHtml('wire:key="pending-task-line-'.$line->id.'"');
    }
}
