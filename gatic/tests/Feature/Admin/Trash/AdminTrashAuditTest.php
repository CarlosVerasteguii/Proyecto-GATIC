<?php

namespace Tests\Feature\Admin\Trash;

use App\Enums\UserRole;
use App\Livewire\Admin\Trash\TrashIndex;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Story 8.4 AC5: Audit log tests for trash operations.
 */
class AdminTrashAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run jobs synchronously for testing
        config(['queue.default' => 'sync']);
    }

    public function test_restore_action_creates_audit_log(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = Employee::query()->create([
            'rpe' => 'ABC123',
            'name' => 'Juan Pérez',
        ]);
        $employeeId = $employee->id;
        $employee->delete();

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->call('setTab', 'employees')
            ->call('restore', 'employees', $employeeId);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_TRASH_RESTORE,
            'subject_type' => Employee::class,
            'subject_id' => $employeeId,
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_purge_action_creates_audit_log(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $product = Product::query()->create([
            'name' => 'Dell XPS 15',
            'category_id' => $category->id,
        ]);
        $productId = $product->id;
        $product->delete();

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->call('purge', 'products', $productId);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_TRASH_PURGE,
            'subject_type' => Product::class,
            'subject_id' => $productId,
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_empty_trash_creates_audit_logs_for_each_item(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee1 = Employee::query()->create([
            'rpe' => 'EMP001',
            'name' => 'Employee 1',
        ]);
        $employee2 = Employee::query()->create([
            'rpe' => 'EMP002',
            'name' => 'Employee 2',
        ]);
        $employee1Id = $employee1->id;
        $employee2Id = $employee2->id;
        $employee1->delete();
        $employee2->delete();

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->call('setTab', 'employees')
            ->call('emptyTrash');

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_TRASH_PURGE,
            'subject_type' => Employee::class,
            'subject_id' => $employee1Id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_TRASH_PURGE,
            'subject_type' => Employee::class,
            'subject_id' => $employee2Id,
        ]);
    }
}
