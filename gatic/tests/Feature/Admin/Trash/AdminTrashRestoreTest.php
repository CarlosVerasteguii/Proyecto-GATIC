<?php

namespace Tests\Feature\Admin\Trash;

use App\Enums\UserRole;
use App\Livewire\Admin\Trash\TrashIndex;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Story 8.4: Restore functionality tests.
 */
class AdminTrashRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_restore_soft_deleted_product(): void
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
        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $product->id]);

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->call('restore', 'products', $product->id)
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_restore_soft_deleted_asset(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $location = Location::query()->create(['name' => 'Bodega']);
        $product = Product::query()->create([
            'name' => 'Dell XPS 15',
            'category_id' => $category->id,
        ]);
        $asset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SN12345',
            'status' => Asset::STATUS_AVAILABLE,
        ]);
        $asset->delete();

        $this->assertSoftDeleted('assets', ['id' => $asset->id]);

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->call('setTab', 'assets')
            ->call('restore', 'assets', $asset->id)
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_restore_soft_deleted_employee(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = Employee::query()->create([
            'rpe' => 'ABC123',
            'name' => 'Juan Pérez',
        ]);
        $employee->delete();

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->call('setTab', 'employees')
            ->call('restore', 'employees', $employee->id)
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'deleted_at' => null,
        ]);
    }

    public function test_restore_asset_blocked_when_product_is_deleted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $location = Location::query()->create(['name' => 'Bodega']);
        $product = Product::query()->create([
            'name' => 'Dell XPS 15',
            'category_id' => $category->id,
        ]);
        $asset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SN12345',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        // Delete both product and asset
        $asset->delete();
        $product->delete();

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->call('setTab', 'assets')
            ->call('restore', 'assets', $asset->id)
            ->assertDispatched('ui:toast', type: 'error');

        // Asset should still be deleted
        $this->assertSoftDeleted('assets', ['id' => $asset->id]);
    }
}
