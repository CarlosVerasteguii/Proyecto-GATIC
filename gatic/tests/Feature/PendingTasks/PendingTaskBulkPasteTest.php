<?php

namespace Tests\Feature\PendingTasks;

use App\Enums\PendingTaskStatus;
use App\Enums\UserRole;
use App\Livewire\PendingTasks\PendingTaskShow;
use App\Models\Category;
use App\Models\Employee;
use App\Models\PendingTask;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PendingTaskBulkPasteTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_paste_blocks_save_when_any_serial_is_invalid(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $serializedCategory = Category::factory()->create(['is_serialized' => true]);
        $serializedProduct = Product::factory()->create(['category_id' => $serializedCategory->id]);
        $employee = Employee::factory()->create();

        $task = PendingTask::factory()->create([
            'creator_user_id' => $admin->id,
            'status' => PendingTaskStatus::Draft,
        ]);

        Livewire::actingAs($admin)
            ->test(PendingTaskShow::class, ['pendingTask' => $task->id])
            ->call('openAddLineModal')
            ->set('productId', $serializedProduct->id)
            ->set('employeeId', $employee->id)
            ->set('note', 'Test note')
            ->set('serializedBulkInput', "ABC123\nINV@LID\n")
            ->call('saveLine')
            ->assertHasErrors(['serializedBulkInput']);

        $this->assertDatabaseCount('pending_task_lines', 0);
    }

    public function test_bulk_paste_blocks_save_when_exceeds_max_lines(): void
    {
        config()->set('gatic.pending_tasks.bulk_paste.max_lines', 2);

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $serializedCategory = Category::factory()->create(['is_serialized' => true]);
        $serializedProduct = Product::factory()->create(['category_id' => $serializedCategory->id]);
        $employee = Employee::factory()->create();

        $task = PendingTask::factory()->create([
            'creator_user_id' => $admin->id,
            'status' => PendingTaskStatus::Draft,
        ]);

        Livewire::actingAs($admin)
            ->test(PendingTaskShow::class, ['pendingTask' => $task->id])
            ->call('openAddLineModal')
            ->set('productId', $serializedProduct->id)
            ->set('employeeId', $employee->id)
            ->set('note', 'Test note')
            ->set('serializedBulkInput', "ABC123\nABC124\nABC125\n")
            ->call('saveLine')
            ->assertHasErrors(['serializedBulkInput']);

        $this->assertDatabaseCount('pending_task_lines', 0);
    }

    public function test_bulk_paste_allows_duplicates_and_marks_them_in_ui(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $serializedCategory = Category::factory()->create(['is_serialized' => true]);
        $serializedProduct = Product::factory()->create(['category_id' => $serializedCategory->id]);
        $employee = Employee::factory()->create();

        $task = PendingTask::factory()->create([
            'creator_user_id' => $admin->id,
            'status' => PendingTaskStatus::Draft,
        ]);

        Livewire::actingAs($admin)
            ->test(PendingTaskShow::class, ['pendingTask' => $task->id])
            ->call('openAddLineModal')
            ->set('productId', $serializedProduct->id)
            ->set('employeeId', $employee->id)
            ->set('note', 'Test note')
            ->set('serializedBulkInput', "DUP001\nDUP001\n")
            ->call('saveLine')
            ->assertHasNoErrors()
            ->assertSee('Duplicado');

        $this->assertDatabaseCount('pending_task_lines', 2);
        $this->assertDatabaseHas('pending_task_lines', [
            'pending_task_id' => $task->id,
            'serial' => 'DUP001',
        ]);
    }

    public function test_quantity_line_blocks_save_when_quantity_is_not_positive(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $quantityCategory = Category::factory()->create(['is_serialized' => false]);
        $quantityProduct = Product::factory()->create(['category_id' => $quantityCategory->id]);
        $employee = Employee::factory()->create();

        $task = PendingTask::factory()->create([
            'creator_user_id' => $admin->id,
            'status' => PendingTaskStatus::Draft,
        ]);

        Livewire::actingAs($admin)
            ->test(PendingTaskShow::class, ['pendingTask' => $task->id])
            ->call('openAddLineModal')
            ->set('productId', $quantityProduct->id)
            ->set('quantity', '0')
            ->set('employeeId', $employee->id)
            ->set('note', 'Test note')
            ->call('saveLine')
            ->assertHasErrors(['quantity']);

        $this->assertDatabaseCount('pending_task_lines', 0);
    }

    public function test_quantity_line_blocks_save_when_quantity_is_not_integer(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $quantityCategory = Category::factory()->create(['is_serialized' => false]);
        $quantityProduct = Product::factory()->create(['category_id' => $quantityCategory->id]);
        $employee = Employee::factory()->create();

        $task = PendingTask::factory()->create([
            'creator_user_id' => $admin->id,
            'status' => PendingTaskStatus::Draft,
        ]);

        Livewire::actingAs($admin)
            ->test(PendingTaskShow::class, ['pendingTask' => $task->id])
            ->call('openAddLineModal')
            ->set('productId', $quantityProduct->id)
            ->set('quantity', '1.5')
            ->set('employeeId', $employee->id)
            ->set('note', 'Test note')
            ->call('saveLine')
            ->assertHasErrors(['quantity']);

        $this->assertDatabaseCount('pending_task_lines', 0);
    }
}
