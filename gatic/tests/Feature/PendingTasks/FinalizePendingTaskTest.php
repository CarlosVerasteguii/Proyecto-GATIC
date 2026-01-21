<?php

namespace Tests\Feature\PendingTasks;

use App\Actions\PendingTasks\FinalizePendingTask;
use App\Actions\PendingTasks\ValidatePendingTaskLine;
use App\Enums\PendingTaskLineStatus;
use App\Enums\PendingTaskLineType;
use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PendingTask;
use App\Models\PendingTaskLine;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinalizePendingTaskTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Category $serializedCategory;

    private Category $quantityCategory;

    private Product $serializedProduct;

    private Product $quantityProduct;

    private Employee $employee;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'Admin']);

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
            'qty_total' => 100,
        ]);

        $this->employee = Employee::factory()->create();
        $this->location = Location::factory()->create();
    }

    // === Partial Finalization Tests (AC3) ===

    public function test_partial_finalization_applies_valid_lines_and_marks_invalid_as_error(): void
    {
        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::Assign,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        // Valid line - asset exists and is available
        $validAsset = Asset::factory()->create([
            'product_id' => $this->serializedProduct->id,
            'location_id' => $this->location->id,
            'serial' => 'VALID001',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $validLine = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'VALID001',
            'employee_id' => $this->employee->id,
            'note' => 'Valid line',
            'line_status' => PendingTaskLineStatus::Pending,
            'order' => 1,
        ]);

        // Invalid line - asset does not exist
        $invalidLine = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'NOTEXIST001',
            'employee_id' => $this->employee->id,
            'note' => 'Invalid line',
            'line_status' => PendingTaskLineStatus::Pending,
            'order' => 2,
        ]);

        $action = new FinalizePendingTask;
        $result = $action->execute($task->id, $this->admin->id);

        // Verify results
        $this->assertEquals(1, $result['applied_count']);
        $this->assertEquals(1, $result['error_count']);
        $this->assertEquals(0, $result['skipped_count']);
        $this->assertEquals(PendingTaskStatus::PartiallyCompleted, $result['task_status']);

        // Verify valid line was applied
        $validLine->refresh();
        $this->assertEquals(PendingTaskLineStatus::Applied, $validLine->line_status);
        $this->assertNull($validLine->error_message);

        // Verify asset was assigned
        $validAsset->refresh();
        $this->assertEquals(Asset::STATUS_ASSIGNED, $validAsset->status);
        $this->assertEquals($this->employee->id, $validAsset->current_employee_id);

        // Verify invalid line has error
        $invalidLine->refresh();
        $this->assertEquals(PendingTaskLineStatus::Error, $invalidLine->line_status);
        $this->assertNotNull($invalidLine->error_message);
        $this->assertStringContainsString('No se encontró', $invalidLine->error_message);
    }

    public function test_all_lines_valid_results_in_completed_status(): void
    {
        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::Assign,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        $asset = Asset::factory()->create([
            'product_id' => $this->serializedProduct->id,
            'location_id' => $this->location->id,
            'serial' => 'ASSET001',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'ASSET001',
            'employee_id' => $this->employee->id,
            'note' => 'Test note',
            'line_status' => PendingTaskLineStatus::Pending,
        ]);

        $action = new FinalizePendingTask;
        $result = $action->execute($task->id, $this->admin->id);

        $this->assertEquals(1, $result['applied_count']);
        $this->assertEquals(0, $result['error_count']);
        $this->assertEquals(PendingTaskStatus::Completed, $result['task_status']);

        $task->refresh();
        $this->assertEquals(PendingTaskStatus::Completed, $task->status);
    }

    // === Duplicate Detection Tests (AC4) ===

    public function test_duplicates_in_task_are_marked_as_error(): void
    {
        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::Assign,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        // Create asset
        Asset::factory()->create([
            'product_id' => $this->serializedProduct->id,
            'location_id' => $this->location->id,
            'serial' => 'DUP001',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        // Create two lines with same serial (duplicate)
        $line1 = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'DUP001',
            'employee_id' => $this->employee->id,
            'note' => 'First duplicate',
            'line_status' => PendingTaskLineStatus::Pending,
            'order' => 1,
        ]);

        $line2 = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'DUP001',
            'employee_id' => $this->employee->id,
            'note' => 'Second duplicate',
            'line_status' => PendingTaskLineStatus::Pending,
            'order' => 2,
        ]);

        $action = new FinalizePendingTask;
        $result = $action->execute($task->id, $this->admin->id);

        // Both lines should be marked as error
        $this->assertEquals(0, $result['applied_count']);
        $this->assertEquals(2, $result['error_count']);

        $line1->refresh();
        $line2->refresh();

        $this->assertEquals(PendingTaskLineStatus::Error, $line1->line_status);
        $this->assertEquals(PendingTaskLineStatus::Error, $line2->line_status);
        $this->assertStringContainsString('Duplicado', $line1->error_message);
        $this->assertStringContainsString('Duplicado', $line2->error_message);
    }

    public function test_duplicates_in_task_by_asset_tag_are_marked_as_error(): void
    {
        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::Assign,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        // Create asset with asset_tag
        Asset::factory()->create([
            'product_id' => $this->serializedProduct->id,
            'location_id' => $this->location->id,
            'serial' => 'ASSET-TAG-001',
            'asset_tag' => 'TAG001',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        // Create two lines with same asset_tag (duplicate)
        $line1 = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $this->serializedProduct->id,
            'serial' => null,
            'asset_tag' => 'TAG001',
            'employee_id' => $this->employee->id,
            'note' => 'First duplicate',
            'line_status' => PendingTaskLineStatus::Pending,
            'order' => 1,
        ]);

        $line2 = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $this->serializedProduct->id,
            'serial' => null,
            'asset_tag' => 'TAG001',
            'employee_id' => $this->employee->id,
            'note' => 'Second duplicate',
            'line_status' => PendingTaskLineStatus::Pending,
            'order' => 2,
        ]);

        $action = new FinalizePendingTask;
        $result = $action->execute($task->id, $this->admin->id);

        // Both lines should be marked as error
        $this->assertEquals(0, $result['applied_count']);
        $this->assertEquals(2, $result['error_count']);

        $line1->refresh();
        $line2->refresh();

        $this->assertEquals(PendingTaskLineStatus::Error, $line1->line_status);
        $this->assertEquals(PendingTaskLineStatus::Error, $line2->line_status);
        $this->assertStringContainsString('Duplicado', $line1->error_message);
        $this->assertStringContainsString('Duplicado', $line2->error_message);
    }

    // === Serialized Line Transition Tests (AC2) ===

    public function test_serialized_assign_requires_available_status(): void
    {
        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::Assign,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        // Asset is already assigned
        Asset::factory()->create([
            'product_id' => $this->serializedProduct->id,
            'location_id' => $this->location->id,
            'serial' => 'ASSIGNED001',
            'status' => Asset::STATUS_ASSIGNED,
            'current_employee_id' => $this->employee->id,
        ]);

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'ASSIGNED001',
            'employee_id' => $this->employee->id,
            'note' => 'Test note',
            'line_status' => PendingTaskLineStatus::Pending,
        ]);

        $action = new FinalizePendingTask;
        $result = $action->execute($task->id, $this->admin->id);

        $this->assertEquals(0, $result['applied_count']);
        $this->assertEquals(1, $result['error_count']);

        $line->refresh();
        $this->assertEquals(PendingTaskLineStatus::Error, $line->line_status);
        $this->assertStringContainsString('ya está asignado', $line->error_message);
    }

    public function test_serialized_return_requires_loaned_status(): void
    {
        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::Return,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        // Asset is available, not loaned
        Asset::factory()->create([
            'product_id' => $this->serializedProduct->id,
            'location_id' => $this->location->id,
            'serial' => 'AVAIL001',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'AVAIL001',
            'employee_id' => $this->employee->id,
            'note' => 'Test note',
            'line_status' => PendingTaskLineStatus::Pending,
        ]);

        $action = new FinalizePendingTask;
        $result = $action->execute($task->id, $this->admin->id);

        $this->assertEquals(0, $result['applied_count']);
        $this->assertEquals(1, $result['error_count']);

        $line->refresh();
        $this->assertEquals(PendingTaskLineStatus::Error, $line->line_status);
    }

    // === Quantity Line Tests (AC2) ===

    public function test_quantity_line_insufficient_stock_error(): void
    {
        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockOut,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        // Product has qty_total = 100 (from setUp)
        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Quantity,
            'product_id' => $this->quantityProduct->id,
            'quantity' => 150, // More than available
            'employee_id' => $this->employee->id,
            'note' => 'Test note',
            'line_status' => PendingTaskLineStatus::Pending,
        ]);

        $action = new FinalizePendingTask;
        $result = $action->execute($task->id, $this->admin->id);

        $this->assertEquals(0, $result['applied_count']);
        $this->assertEquals(1, $result['error_count']);

        $line->refresh();
        $this->assertEquals(PendingTaskLineStatus::Error, $line->line_status);
        $this->assertStringContainsString('Stock insuficiente', $line->error_message);

        // Stock should not change
        $this->quantityProduct->refresh();
        $this->assertEquals(100, $this->quantityProduct->qty_total);
    }

    public function test_quantity_line_success_updates_stock(): void
    {
        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockOut,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Quantity,
            'product_id' => $this->quantityProduct->id,
            'quantity' => 30,
            'employee_id' => $this->employee->id,
            'note' => 'Test note',
            'line_status' => PendingTaskLineStatus::Pending,
        ]);

        $action = new FinalizePendingTask;
        $result = $action->execute($task->id, $this->admin->id);

        $this->assertEquals(1, $result['applied_count']);
        $this->assertEquals(0, $result['error_count']);

        // Stock should be reduced
        $this->quantityProduct->refresh();
        $this->assertEquals(70, $this->quantityProduct->qty_total);

        // Movement should be created
        $this->assertDatabaseHas('product_quantity_movements', [
            'product_id' => $this->quantityProduct->id,
            'direction' => 'out',
            'qty' => 30,
        ]);
    }

    // === Never Re-Apply Already Applied Lines ===

    public function test_already_applied_lines_are_skipped(): void
    {
        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockOut,
            'status' => PendingTaskStatus::PartiallyCompleted,
            'creator_user_id' => $this->admin->id,
        ]);

        // Already applied line
        $appliedLine = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Quantity,
            'product_id' => $this->quantityProduct->id,
            'quantity' => 10,
            'employee_id' => $this->employee->id,
            'note' => 'Applied',
            'line_status' => PendingTaskLineStatus::Applied,
            'order' => 1,
        ]);

        // Pending line
        $pendingLine = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Quantity,
            'product_id' => $this->quantityProduct->id,
            'quantity' => 20,
            'employee_id' => $this->employee->id,
            'note' => 'Pending',
            'line_status' => PendingTaskLineStatus::Pending,
            'order' => 2,
        ]);

        $action = new FinalizePendingTask;
        $result = $action->execute($task->id, $this->admin->id);

        $this->assertEquals(1, $result['applied_count']);
        $this->assertEquals(0, $result['error_count']);
        $this->assertEquals(1, $result['skipped_count']);

        // Applied line should remain applied
        $appliedLine->refresh();
        $this->assertEquals(PendingTaskLineStatus::Applied, $appliedLine->line_status);
    }

    // === Task Type/Line Type Matrix Tests ===

    public function test_serialized_line_blocked_for_stock_in_task(): void
    {
        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockIn,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        Asset::factory()->create([
            'product_id' => $this->serializedProduct->id,
            'location_id' => $this->location->id,
            'serial' => 'ASSET001',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'ASSET001',
            'employee_id' => $this->employee->id,
            'note' => 'Test note',
            'line_status' => PendingTaskLineStatus::Pending,
        ]);

        $action = new FinalizePendingTask;
        $result = $action->execute($task->id, $this->admin->id);

        $this->assertEquals(0, $result['applied_count']);
        $this->assertEquals(1, $result['error_count']);

        $line->refresh();
        $this->assertStringContainsString('no admite renglones serializados', $line->error_message);
    }

    // === Validation Tests ===

    public function test_cannot_finalize_draft_task(): void
    {
        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::Assign,
            'status' => PendingTaskStatus::Draft,
            'creator_user_id' => $this->admin->id,
        ]);

        PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'product_id' => $this->serializedProduct->id,
            'employee_id' => $this->employee->id,
        ]);

        $action = new FinalizePendingTask;

        $this->expectException(ValidationException::class);

        $action->execute($task->id, $this->admin->id);
    }

    public function test_cannot_finalize_task_without_lines(): void
    {
        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::Assign,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        $action = new FinalizePendingTask;

        $this->expectException(ValidationException::class);

        $action->execute($task->id, $this->admin->id);
    }

    // === ValidatePendingTaskLine Tests ===

    public function test_validate_line_clears_error_on_valid(): void
    {
        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::Assign,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        Asset::factory()->create([
            'product_id' => $this->serializedProduct->id,
            'location_id' => $this->location->id,
            'serial' => 'ASSET001',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'ASSET001',
            'employee_id' => $this->employee->id,
            'note' => 'Test note',
            'line_status' => PendingTaskLineStatus::Error,
            'error_message' => 'Previous error',
        ]);

        $action = new ValidatePendingTaskLine;
        $result = $action->execute($line->id);

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error_message']);

        $line->refresh();
        $this->assertEquals(PendingTaskLineStatus::Pending, $line->line_status);
        $this->assertNull($line->error_message);
    }

    public function test_validate_line_sets_error_on_invalid(): void
    {
        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::Assign,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        // No asset exists for this serial
        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'NOTEXIST001',
            'employee_id' => $this->employee->id,
            'note' => 'Test note',
            'line_status' => PendingTaskLineStatus::Pending,
        ]);

        $action = new ValidatePendingTaskLine;
        $result = $action->execute($line->id);

        $this->assertFalse($result['valid']);
        $this->assertNotNull($result['error_message']);

        $line->refresh();
        $this->assertEquals(PendingTaskLineStatus::Error, $line->line_status);
        $this->assertStringContainsString('No se encontró', $line->error_message);
    }

    // === Soft-Delete Regression Test (Dev Notes 4.1) ===

    public function test_soft_deleted_assets_are_not_found(): void
    {
        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::Assign,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        // Create and soft-delete an asset
        $asset = Asset::factory()->create([
            'product_id' => $this->serializedProduct->id,
            'location_id' => $this->location->id,
            'serial' => 'DELETED001',
            'status' => Asset::STATUS_AVAILABLE,
        ]);
        $asset->delete();

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Serialized,
            'product_id' => $this->serializedProduct->id,
            'serial' => 'DELETED001',
            'employee_id' => $this->employee->id,
            'note' => 'Test note',
            'line_status' => PendingTaskLineStatus::Pending,
        ]);

        $action = new FinalizePendingTask;
        $result = $action->execute($task->id, $this->admin->id);

        // Asset should not be found (is soft-deleted)
        $this->assertEquals(0, $result['applied_count']);
        $this->assertEquals(1, $result['error_count']);

        $line->refresh();
        $this->assertEquals(PendingTaskLineStatus::Error, $line->line_status);
        $this->assertStringContainsString('No se encontró', $line->error_message);
    }

    public function test_soft_deleted_products_are_not_applicable(): void
    {
        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockOut,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->admin->id,
        ]);

        // Create a product and soft-delete it
        $product = Product::factory()->create([
            'category_id' => $this->quantityCategory->id,
            'qty_total' => 100,
        ]);

        $line = PendingTaskLine::factory()->create([
            'pending_task_id' => $task->id,
            'line_type' => PendingTaskLineType::Quantity,
            'product_id' => $product->id,
            'quantity' => 10,
            'employee_id' => $this->employee->id,
            'note' => 'Test note',
            'line_status' => PendingTaskLineStatus::Pending,
        ]);

        // Soft-delete the product
        $product->delete();

        $action = new FinalizePendingTask;
        $result = $action->execute($task->id, $this->admin->id);

        $this->assertEquals(0, $result['applied_count']);
        $this->assertEquals(1, $result['error_count']);

        $line->refresh();
        $this->assertEquals(PendingTaskLineStatus::Error, $line->line_status);
        $this->assertStringContainsString('no existe o fue eliminado', $line->error_message);
    }
}
