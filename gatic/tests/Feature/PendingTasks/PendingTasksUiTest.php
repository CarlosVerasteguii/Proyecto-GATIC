<?php

namespace Tests\Feature\PendingTasks;

use App\Enums\PendingTaskLineStatus;
use App\Enums\PendingTaskLineType;
use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Employee;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingTasksUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_tasks_index_filters_by_status_and_type(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $draftStockOut = PendingTask::factory()->create([
            'creator_user_id' => $admin->id,
            'status' => PendingTaskStatus::Draft,
            'type' => PendingTaskType::StockOut,
        ]);

        $readyAssign = PendingTask::factory()->create([
            'creator_user_id' => $admin->id,
            'status' => PendingTaskStatus::Ready,
            'type' => PendingTaskType::Assign,
        ]);

        $this->actingAs($admin)
            ->get(route('pending-tasks.index'))
            ->assertOk()
            ->assertSee(route('pending-tasks.show', $draftStockOut->id))
            ->assertSee(route('pending-tasks.show', $readyAssign->id));

        $this->actingAs($admin)
            ->get(route('pending-tasks.index', ['status' => PendingTaskStatus::Draft->value]))
            ->assertOk()
            ->assertSee(route('pending-tasks.show', $draftStockOut->id))
            ->assertDontSee(route('pending-tasks.show', $readyAssign->id));

        $this->actingAs($admin)
            ->get(route('pending-tasks.index', ['type' => PendingTaskType::Assign->value]))
            ->assertOk()
            ->assertSee(route('pending-tasks.show', $readyAssign->id))
            ->assertDontSee(route('pending-tasks.show', $draftStockOut->id));

        $this->actingAs($admin)
            ->get(route('pending-tasks.index', [
                'status' => PendingTaskStatus::Draft->value,
                'type' => PendingTaskType::Assign->value,
            ]))
            ->assertOk()
            ->assertDontSee(route('pending-tasks.show', $draftStockOut->id))
            ->assertDontSee(route('pending-tasks.show', $readyAssign->id));
    }

    public function test_pending_task_show_displays_employee_and_identifier_in_lines_table(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $serializedCategory = Category::factory()->create(['is_serialized' => true]);
        $serializedProduct = Product::factory()->create(['category_id' => $serializedCategory->id]);

        $employee = Employee::factory()->create([
            'rpe' => 'EMP001',
            'name' => 'Juan Perez',
        ]);

        $task = PendingTask::factory()->create([
            'creator_user_id' => $admin->id,
            'status' => PendingTaskStatus::Draft,
            'type' => PendingTaskType::StockOut,
        ]);

        PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $serializedProduct->id,
            'serial' => 'ABC123',
            'asset_tag' => 'TAG001',
            'quantity' => null,
            'employee_id' => $employee->id,
            'note' => 'Nota de prueba',
            'line_status' => PendingTaskLineStatus::Pending,
            'order' => 1,
        ]);

        PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $serializedProduct->id,
            'serial' => 'ABC123',
            'asset_tag' => null,
            'quantity' => null,
            'employee_id' => $employee->id,
            'note' => 'Duplicado',
            'line_status' => PendingTaskLineStatus::Pending,
            'order' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('pending-tasks.show', $task->id))
            ->assertOk()
            ->assertSee('EMP001 - Juan Perez')
            ->assertSee('S/N: ABC123')
            ->assertSee('Tag: TAG001')
            ->assertSee('Nota de prueba')
            ->assertSee('Duplicado')
            ->assertSee('Añadir renglón');
    }

    public function test_pending_task_show_hides_line_actions_when_task_is_not_draft(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $task = PendingTask::factory()->create([
            'creator_user_id' => $admin->id,
            'status' => PendingTaskStatus::Ready,
            'type' => PendingTaskType::StockOut,
        ]);

        $this->actingAs($admin)
            ->get(route('pending-tasks.show', $task->id))
            ->assertOk()
            ->assertDontSee('Añadir renglón')
            ->assertDontSee('Editar')
            ->assertDontSee('Eliminar')
            ->assertDontSee('Marcar como lista');
    }
}
