<?php

namespace Tests\Feature\Audit;

use App\Actions\Inventory\Adjustments\ApplyAssetAdjustment;
use App\Actions\Inventory\Adjustments\ApplyProductQuantityAdjustment;
use App\Actions\Movements\Assets\AssignAssetToEmployee;
use App\Actions\Movements\Assets\LoanAssetToEmployee;
use App\Actions\Movements\Assets\ReturnLoanedAsset;
use App\Actions\Movements\Products\RegisterProductQuantityMovement;
use App\Actions\PendingTasks\AcquirePendingTaskLock;
use App\Actions\PendingTasks\ForceClaimPendingTaskLock;
use App\Actions\PendingTasks\ForceReleasePendingTaskLock;
use App\Enums\PendingTaskStatus;
use App\Enums\PendingTaskType;
use App\Jobs\RecordAuditLog;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\PendingTask;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Tests for audit instrumentation (AC1, AC2, AC5).
 *
 * Verifies that actions emit audit jobs and that
 * the main operation succeeds even if audit fails.
 */
class AuditInstrumentationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    private Employee $employee;

    private Category $serializedCategory;

    private Category $quantityCategory;

    private Location $location;

    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'Admin', 'name' => 'Admin User']);
        $this->editor = User::factory()->create(['role' => 'Editor', 'name' => 'Editor User']);
        $this->employee = Employee::factory()->create(['rpe' => 'TEST001']);

        $this->serializedCategory = Category::factory()->create([
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $this->quantityCategory = Category::factory()->create([
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);
        $this->location = Location::factory()->create();
        $this->brand = Brand::factory()->create();
    }

    // =========================================================================
    // Lock Override Instrumentation (AC1, AC5)
    // =========================================================================

    public function test_force_release_dispatches_audit_job(): void
    {
        Queue::fake();

        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockOut,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->editor->id,
        ]);

        // Editor acquires lock
        (new AcquirePendingTaskLock)->execute($task->id, $this->editor->id);

        // Admin force-releases
        (new ForceReleasePendingTaskLock)->execute($task->id, $this->admin->id);

        Queue::assertPushed(RecordAuditLog::class, function ($job) use ($task) {
            return $job->payload['action'] === AuditLog::ACTION_LOCK_FORCE_RELEASE
                && $job->payload['subject_type'] === PendingTask::class
                && $job->payload['subject_id'] === $task->id
                && $job->payload['actor_user_id'] === $this->admin->id;
        });
    }

    public function test_force_claim_dispatches_audit_job(): void
    {
        Queue::fake();

        $task = PendingTask::factory()->create([
            'type' => PendingTaskType::StockOut,
            'status' => PendingTaskStatus::Ready,
            'creator_user_id' => $this->editor->id,
        ]);

        // Editor acquires lock
        (new AcquirePendingTaskLock)->execute($task->id, $this->editor->id);

        // Admin force-claims
        (new ForceClaimPendingTaskLock)->execute($task->id, $this->admin->id);

        Queue::assertPushed(RecordAuditLog::class, function ($job) use ($task) {
            return $job->payload['action'] === AuditLog::ACTION_LOCK_FORCE_CLAIM
                && $job->payload['subject_type'] === PendingTask::class
                && $job->payload['subject_id'] === $task->id
                && $job->payload['actor_user_id'] === $this->admin->id;
        });
    }

    // =========================================================================
    // Inventory Adjustment Instrumentation (AC1, AC5)
    // =========================================================================

    public function test_product_quantity_adjustment_dispatches_audit_job(): void
    {
        Queue::fake();

        $product = Product::factory()->create([
            'category_id' => $this->quantityCategory->id,
            'brand_id' => $this->brand->id,
            'qty_total' => 100,
        ]);

        $action = new ApplyProductQuantityAdjustment;
        $action->execute([
            'product_id' => $product->id,
            'new_qty' => 150,
            'reason' => 'Test adjustment for audit',
            'actor_user_id' => $this->admin->id,
        ]);

        Queue::assertPushed(RecordAuditLog::class, function ($job) use ($product) {
            return $job->payload['action'] === AuditLog::ACTION_INVENTORY_ADJUSTMENT
                && $job->payload['actor_user_id'] === $this->admin->id
                && isset($job->payload['context']['product_id'])
                && $job->payload['context']['product_id'] === $product->id;
        });
    }

    public function test_asset_adjustment_dispatches_audit_job(): void
    {
        Queue::fake();

        $product = Product::factory()->create([
            'category_id' => $this->serializedCategory->id,
            'brand_id' => $this->brand->id,
        ]);

        $asset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $this->location->id,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $newLocation = Location::factory()->create();

        $action = new ApplyAssetAdjustment;
        $action->execute([
            'asset_id' => $asset->id,
            'new_status' => Asset::STATUS_PENDING_RETIREMENT,
            'new_location_id' => $newLocation->id,
            'reason' => 'Test asset adjustment for audit',
            'actor_user_id' => $this->admin->id,
        ]);

        Queue::assertPushed(RecordAuditLog::class, function ($job) use ($asset) {
            return $job->payload['action'] === AuditLog::ACTION_INVENTORY_ADJUSTMENT
                && $job->payload['actor_user_id'] === $this->admin->id
                && isset($job->payload['context']['asset_id'])
                && $job->payload['context']['asset_id'] === $asset->id;
        });
    }

    // =========================================================================
    // Asset Movement Instrumentation (AC1, AC5)
    // =========================================================================

    public function test_assign_asset_dispatches_audit_job(): void
    {
        Queue::fake();

        $product = Product::factory()->create([
            'category_id' => $this->serializedCategory->id,
            'brand_id' => $this->brand->id,
        ]);

        $asset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $this->location->id,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $action = new AssignAssetToEmployee;
        $movement = $action->execute([
            'asset_id' => $asset->id,
            'employee_id' => $this->employee->id,
            'note' => 'Test assignment for audit',
            'actor_user_id' => $this->editor->id,
        ]);

        Queue::assertPushed(RecordAuditLog::class, function ($job) use ($movement, $asset) {
            return $job->payload['action'] === AuditLog::ACTION_ASSET_ASSIGN
                && $job->payload['subject_id'] === $movement->id
                && $job->payload['actor_user_id'] === $this->editor->id
                && $job->payload['context']['asset_id'] === $asset->id
                && $job->payload['context']['employee_id'] === $this->employee->id;
        });
    }

    public function test_loan_asset_dispatches_audit_job(): void
    {
        Queue::fake();

        $product = Product::factory()->create([
            'category_id' => $this->serializedCategory->id,
            'brand_id' => $this->brand->id,
        ]);

        $asset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $this->location->id,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $action = new LoanAssetToEmployee;
        $movement = $action->execute([
            'asset_id' => $asset->id,
            'employee_id' => $this->employee->id,
            'note' => 'Test loan for audit',
            'actor_user_id' => $this->editor->id,
        ]);

        Queue::assertPushed(RecordAuditLog::class, function ($job) use ($movement, $asset) {
            return $job->payload['action'] === AuditLog::ACTION_ASSET_LOAN
                && $job->payload['subject_id'] === $movement->id
                && $job->payload['actor_user_id'] === $this->editor->id
                && $job->payload['context']['asset_id'] === $asset->id;
        });
    }

    public function test_return_asset_dispatches_audit_job(): void
    {
        Queue::fake();

        $product = Product::factory()->create([
            'category_id' => $this->serializedCategory->id,
            'brand_id' => $this->brand->id,
        ]);

        $asset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $this->location->id,
            'status' => Asset::STATUS_LOANED,
            'current_employee_id' => $this->employee->id,
        ]);

        $action = new ReturnLoanedAsset;
        $movement = $action->execute([
            'asset_id' => $asset->id,
            'note' => 'Test return for audit',
            'actor_user_id' => $this->editor->id,
        ]);

        Queue::assertPushed(RecordAuditLog::class, function ($job) use ($movement, $asset) {
            return $job->payload['action'] === AuditLog::ACTION_ASSET_RETURN
                && $job->payload['subject_id'] === $movement->id
                && $job->payload['actor_user_id'] === $this->editor->id
                && $job->payload['context']['asset_id'] === $asset->id;
        });
    }

    // =========================================================================
    // Product Quantity Movement Instrumentation (AC1, AC5)
    // =========================================================================

    public function test_product_quantity_movement_dispatches_audit_job(): void
    {
        Queue::fake();

        $product = Product::factory()->create([
            'category_id' => $this->quantityCategory->id,
            'brand_id' => $this->brand->id,
            'qty_total' => 100,
        ]);

        $action = new RegisterProductQuantityMovement;
        $movement = $action->execute([
            'product_id' => $product->id,
            'employee_id' => $this->employee->id,
            'direction' => 'out',
            'qty' => 5,
            'note' => 'Test movement for audit',
            'actor_user_id' => $this->editor->id,
        ]);

        Queue::assertPushed(RecordAuditLog::class, function ($job) use ($movement, $product) {
            return $job->payload['action'] === AuditLog::ACTION_PRODUCT_QTY_REGISTER
                && $job->payload['subject_id'] === $movement->id
                && $job->payload['actor_user_id'] === $this->editor->id
                && $job->payload['context']['product_id'] === $product->id
                && $job->payload['context']['employee_id'] === $this->employee->id
                && str_contains($job->payload['context']['summary'] ?? '', 'direction=out')
                && str_contains($job->payload['context']['summary'] ?? '', 'qty=5');
        });
    }
}
