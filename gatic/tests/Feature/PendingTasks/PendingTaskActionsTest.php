<?php

namespace Tests\Feature\PendingTasks;

use App\Actions\PendingTasks\AddLineToTask;
use App\Actions\PendingTasks\AddSerializedLinesToTask;
use App\Actions\PendingTasks\CreatePendingTask;
use App\Actions\PendingTasks\MarkTaskAsReady;
use App\Actions\PendingTasks\RemoveLineFromTask;
use App\Actions\PendingTasks\UpdateTaskLine;
use App\Enums\PendingTaskLineStatus;
use App\Enums\PendingTaskLineType;
use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Models\Category;
use App\Models\Employee;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PendingTaskActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    private Category $serializedCategory;

    private Category $quantityCategory;

    private Product $serializedProduct;

    private Product $quantityProduct;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'Admin']);
        $this->editor = User::factory()->create(['role' => 'Editor']);

        $this->serializedCategory = Category::factory()->create([
            'is_serialized' => true,
        ]);
        $this->quantityCategory = Category::factory()->create([
            'is_serialized' => false,
        ]);

        $this->serializedProduct = Product::factory()->create([
            'category_id' => $this->serializedCategory->id,
        ]);
        $this->quantityProduct = Product::factory()->create([
            'category_id' => $this->quantityCategory->id,
        ]);

        $this->employee = Employee::factory()->create();
    }

    // === CreatePendingTask Tests ===

    public function test_create_pending_task_success(): void
    {
        $action = new CreatePendingTask;

        $task = $action->execute([
            'type' => PendingTaskType::StockOut->value,
            'description' => 'Test description',
            'creator_user_id' => $this->admin->id,
        ]);

        $this->assertInstanceOf(PendingTask::class, $task);
        $this->assertEquals(PendingTaskType::StockOut, $task->type);
        $this->assertEquals(PendingTaskStatus::Draft, $task->status);
        $this->assertEquals('Test description', $task->description);
        $this->assertEquals($this->admin->id, $task->creator_user_id);
    }

    public function test_create_pending_task_without_description(): void
    {
        $action = new CreatePendingTask;

        $task = $action->execute([
            'type' => PendingTaskType::Assign->value,
            'creator_user_id' => $this->editor->id,
        ]);

        $this->assertNull($task->description);
        $this->assertEquals(PendingTaskStatus::Draft, $task->status);
    }

    public function test_create_pending_task_requires_type(): void
    {
        $action = new CreatePendingTask;

        $this->expectException(ValidationException::class);

        $action->execute([
            'type' => '',
            'creator_user_id' => $this->admin->id,
        ]);
    }

    // === AddLineToTask Tests ===

    public function test_add_serialized_line_success(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $this->admin->id,
        ]);

        $action = new AddLineToTask;

        $result = $action->execute([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized->value,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'ABC123',
            'asset_tag' => 'TAG001',
            'employee_id' => $this->employee->id,
            'note' => 'Test note',
        ]);

        $this->assertInstanceOf(PendingTaskLine::class, $result['line']);
        $this->assertFalse($result['has_duplicates']);
        $this->assertEquals(PendingTaskLineStatus::Pending, $result['line']->line_status);
        $this->assertEquals('ABC123', $result['line']->serial);
        $this->assertEquals('TAG001', $result['line']->asset_tag);
    }

    public function test_add_quantity_line_success(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $this->admin->id,
        ]);

        $action = new AddLineToTask;

        $result = $action->execute([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Quantity->value,
            'product_id' => $this->quantityProduct->id,
            'quantity' => 10,
            'employee_id' => $this->employee->id,
            'note' => 'Test note',
        ]);

        $this->assertEquals(10, $result['line']->quantity);
        $this->assertEquals(PendingTaskLineType::Quantity, $result['line']->line_type);
    }

    public function test_add_line_requires_employee_and_note(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $this->admin->id,
        ]);

        $action = new AddLineToTask;

        $this->expectException(ValidationException::class);

        $action->execute([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Quantity->value,
            'product_id' => $this->quantityProduct->id,
            'quantity' => 5,
            'employee_id' => 0,
            'note' => '',
        ]);
    }

    public function test_add_serialized_line_validates_minimum_length(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $this->admin->id,
        ]);

        $action = new AddLineToTask;

        $this->expectException(ValidationException::class);

        $action->execute([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized->value,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'AB', // Too short
            'employee_id' => $this->employee->id,
            'note' => 'Test note',
        ]);
    }

    public function test_add_line_detects_duplicates(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $this->admin->id,
        ]);

        $action = new AddLineToTask;

        // First line
        $action->execute([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized->value,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'ABC123',
            'employee_id' => $this->employee->id,
            'note' => 'First',
        ]);

        // Duplicate line
        $result = $action->execute([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized->value,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'ABC123',
            'employee_id' => $this->employee->id,
            'note' => 'Duplicate',
        ]);

        $this->assertTrue($result['has_duplicates']);
    }

    public function test_add_line_blocked_for_non_draft_task(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        $action = new AddLineToTask;

        $this->expectException(ValidationException::class);

        $action->execute([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Quantity->value,
            'product_id' => $this->quantityProduct->id,
            'quantity' => 5,
            'employee_id' => $this->employee->id,
            'note' => 'Test',
        ]);
    }

    // === Mismatch Tests (Code Review Fix M2) ===

    public function test_add_serialized_line_blocked_for_non_serialized_product(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $this->admin->id,
        ]);

        $action = new AddLineToTask;

        try {
            $action->execute([
                'pending_task_id' => $task->id,
                'line_type' => PendingTaskLineType::Serialized->value,
                'product_id' => $this->quantityProduct->id, // Mismatch (Product is quantity)
                'serial' => 'ABC123',
                'employee_id' => $this->employee->id,
                'note' => 'Test mismatch',
            ]);

            $this->fail('Expected ValidationException for line_type mismatch.');
        } catch (ValidationException $e) {
            $this->assertSame(
                ['El producto seleccionado no es de categoría serializada.'],
                $e->errors()['line_type'] ?? []
            );
        }
    }

    public function test_add_quantity_line_blocked_for_serialized_product(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $this->admin->id,
        ]);

        $action = new AddLineToTask;

        try {
            $action->execute([
                'pending_task_id' => $task->id,
                'line_type' => PendingTaskLineType::Quantity->value,
                'product_id' => $this->serializedProduct->id, // Mismatch (Product is serialized)
                'quantity' => 10,
                'employee_id' => $this->employee->id,
                'note' => 'Test mismatch',
            ]);

            $this->fail('Expected ValidationException for line_type mismatch.');
        } catch (ValidationException $e) {
            $this->assertSame(
                ['El producto seleccionado es de categoría serializada, no se puede usar tipo "Por cantidad".'],
                $e->errors()['line_type'] ?? []
            );
        }
    }

    // === AddSerializedLinesToTask Tests ===

    public function test_add_serialized_lines_batch_creates_lines_with_sequential_order(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $this->admin->id,
        ]);

        $existing = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'EXIST001',
            'employee_id' => $this->employee->id,
            'note' => 'Existing',
            'order' => 5,
        ]);

        $action = new AddSerializedLinesToTask;

        $result = $action->execute([
            'pending_task_id' => $task->id,
            'product_id' => $this->serializedProduct->id,
            'serials' => ['ABC123', 'ABC124', 'ABC125'],
            'employee_id' => $this->employee->id,
            'note' => 'Bulk note',
        ]);

        $this->assertSame(3, $result['lines_created']);
        $this->assertFalse($result['has_duplicates']);

        $lines = PendingTaskLine::query()
            ->where('pending_task_id', $task->id)
            ->where('id', '!=', $existing->id)
            ->orderBy('order')
            ->get();

        $this->assertCount(3, $lines);
        $this->assertSame([6, 7, 8], $lines->pluck('order')->all());
        $this->assertSame(['ABC123', 'ABC124', 'ABC125'], $lines->pluck('serial')->all());
        $this->assertSame([$this->employee->id, $this->employee->id, $this->employee->id], $lines->pluck('employee_id')->all());
        $this->assertSame(['Bulk note', 'Bulk note', 'Bulk note'], $lines->pluck('note')->all());
    }

    public function test_add_serialized_lines_batch_allows_duplicates(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $this->admin->id,
        ]);

        $action = new AddSerializedLinesToTask;

        $result = $action->execute([
            'pending_task_id' => $task->id,
            'product_id' => $this->serializedProduct->id,
            'serials' => ['DUP001', 'DUP001'],
            'employee_id' => $this->employee->id,
            'note' => 'Bulk note',
        ]);

        $this->assertSame(2, $result['lines_created']);
        $this->assertTrue($result['has_duplicates']);

        $this->assertDatabaseCount('pending_task_lines', 2);
        $this->assertDatabaseHas('pending_task_lines', [
            'pending_task_id' => $task->id,
            'serial' => 'DUP001',
        ]);
    }

    public function test_add_serialized_lines_batch_rejects_invalid_serial(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $this->admin->id,
        ]);

        $action = new AddSerializedLinesToTask;

        try {
            $action->execute([
                'pending_task_id' => $task->id,
                'product_id' => $this->serializedProduct->id,
                'serials' => ['INV@LID'],
                'employee_id' => $this->employee->id,
                'note' => 'Bulk note',
            ]);

            $this->fail('Expected ValidationException for invalid serials.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('serials', $e->errors());
        }
    }

    // === UpdateTaskLine Tests ===

    public function test_update_line_success(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $this->admin->id,
        ]);

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'OLD123',
            'employee_id' => $this->employee->id,
            'note' => 'Old note',
        ]);

        $action = new UpdateTaskLine;

        $result = $action->execute($line->id, [
            'serial' => 'NEW456',
            'note' => 'Updated note',
        ]);

        $this->assertEquals('NEW456', $result['line']->serial);
        $this->assertEquals('Updated note', $result['line']->note);
    }

    public function test_update_line_blocked_for_non_draft_task(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'ABC123',
            'employee_id' => $this->employee->id,
            'note' => 'Test',
        ]);

        $action = new UpdateTaskLine;

        $this->expectException(ValidationException::class);

        $action->execute($line->id, ['note' => 'Updated']);
    }

    // === RemoveLineFromTask Tests ===

    public function test_remove_line_success(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $this->admin->id,
        ]);

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'product_id' => $this->serializedProduct->id,
            'employee_id' => $this->employee->id,
        ]);

        $lineId = $line->id;

        $action = new RemoveLineFromTask;
        $action->execute($lineId);

        $this->assertDatabaseMissing('pending_task_lines', ['id' => $lineId]);
    }

    public function test_remove_line_blocked_for_non_draft_task(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'product_id' => $this->serializedProduct->id,
            'employee_id' => $this->employee->id,
        ]);

        $action = new RemoveLineFromTask;

        $this->expectException(ValidationException::class);

        $action->execute($line->id);
    }

    // === MarkTaskAsReady Tests ===

    public function test_mark_task_as_ready_success(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $this->admin->id,
        ]);

        PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_status' => PendingTaskLineStatus::Pending,
            'product_id' => $this->serializedProduct->id,
            'employee_id' => $this->employee->id,
        ]);

        $action = new MarkTaskAsReady;
        $updatedTask = $action->execute($task->id);

        $this->assertEquals(PendingTaskStatus::Ready, $updatedTask->status);
    }

    public function test_mark_task_as_ready_requires_at_least_one_line(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $this->admin->id,
        ]);

        $action = new MarkTaskAsReady;

        $this->expectException(ValidationException::class);

        $action->execute($task->id);
    }

    public function test_mark_task_as_ready_blocked_for_non_draft(): void
    {
        $task = PendingTask::factory()->create([
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'product_id' => $this->serializedProduct->id,
            'employee_id' => $this->employee->id,
        ]);

        $action = new MarkTaskAsReady;

        $this->expectException(ValidationException::class);

        $action->execute($task->id);
    }
}
