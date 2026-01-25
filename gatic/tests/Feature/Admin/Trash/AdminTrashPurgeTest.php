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
 * Story 8.4: Purge functionality tests.
 */
class AdminTrashPurgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_purge_soft_deleted_product(): void
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

        $this->assertSoftDeleted('products', ['id' => $productId]);

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->call('purge', 'products', $productId)
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertDatabaseMissing('products', ['id' => $productId]);
    }

    public function test_admin_can_purge_soft_deleted_asset(): void
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
        $assetId = $asset->id;
        $asset->delete();

        $this->assertSoftDeleted('assets', ['id' => $assetId]);

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->call('purge', 'assets', $assetId)
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertDatabaseMissing('assets', ['id' => $assetId]);
    }

    public function test_admin_can_purge_soft_deleted_employee(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = Employee::query()->create([
            'rpe' => 'ABC123',
            'name' => 'Juan Pérez',
        ]);
        $employeeId = $employee->id;
        $employee->delete();

        $this->assertSoftDeleted('employees', ['id' => $employeeId]);

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->call('setTab', 'employees')
            ->call('purge', 'employees', $employeeId)
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertDatabaseMissing('employees', ['id' => $employeeId]);
    }

    public function test_admin_can_empty_trash_for_products(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $product1 = Product::query()->create([
            'name' => 'Dell XPS 15',
            'category_id' => $category->id,
        ]);
        $product2 = Product::query()->create([
            'name' => 'MacBook Pro',
            'category_id' => $category->id,
        ]);
        $product1Id = $product1->id;
        $product2Id = $product2->id;
        $product1->delete();
        $product2->delete();

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->call('emptyTrash')
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertDatabaseMissing('products', ['id' => $product1Id]);
        $this->assertDatabaseMissing('products', ['id' => $product2Id]);
    }

    public function test_empty_trash_handles_empty_papelera(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->call('emptyTrash')
            ->assertDispatched('ui:toast', type: 'success');
    }
}
