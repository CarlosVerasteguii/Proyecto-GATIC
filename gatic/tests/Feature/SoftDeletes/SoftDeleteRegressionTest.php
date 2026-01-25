<?php

namespace Tests\Feature\SoftDeletes;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Story 8.4 AC7: Regression tests for soft-delete exclusion from normal queries.
 *
 * Ensures soft-deleted records do NOT appear in normal listings.
 */
class SoftDeleteRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleted_products_are_excluded_from_products_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $activeProduct = Product::query()->create([
            'name' => 'Active Product',
            'category_id' => $category->id,
        ]);
        $deletedProduct = Product::query()->create([
            'name' => 'Deleted Product',
            'category_id' => $category->id,
        ]);
        $deletedProduct->delete();

        $this->actingAs($admin)
            ->get('/inventory/products')
            ->assertOk()
            ->assertSee('Active Product')
            ->assertDontSee('Deleted Product');
    }

    public function test_soft_deleted_assets_are_excluded_from_assets_index(): void
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
        $activeAsset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'ACTIVE123',
            'status' => Asset::STATUS_AVAILABLE,
        ]);
        $deletedAsset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'DELETED456',
            'status' => Asset::STATUS_AVAILABLE,
        ]);
        $deletedAsset->delete();

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets")
            ->assertOk()
            ->assertSee('ACTIVE123')
            ->assertDontSee('DELETED456');
    }

    public function test_soft_deleted_employees_are_excluded_from_employees_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $activeEmployee = Employee::query()->create([
            'rpe' => 'ACTIVE123',
            'name' => 'Active Employee',
        ]);
        $deletedEmployee = Employee::query()->create([
            'rpe' => 'DELETED456',
            'name' => 'Deleted Employee',
        ]);
        $deletedEmployee->delete();

        $this->actingAs($admin)
            ->get('/employees')
            ->assertOk()
            ->assertSee('Active Employee')
            ->assertDontSee('Deleted Employee');
    }

    public function test_soft_deleted_categories_are_excluded_from_categories_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $activeCategory = Category::query()->create([
            'name' => 'Active Category',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);
        $deletedCategory = Category::query()->create([
            'name' => 'Deleted Category',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);
        $deletedCategory->delete();

        $this->actingAs($admin)
            ->get('/catalogs/categories')
            ->assertOk()
            ->assertSee('Active Category')
            ->assertDontSee('Deleted Category');
    }
}
