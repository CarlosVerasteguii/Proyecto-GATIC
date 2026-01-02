<?php

namespace Tests\Feature\Inventory;

use App\Enums\UserRole;
use App\Livewire\Inventory\Products\ProductForm;
use App\Livewire\Inventory\Products\ProductsIndex;
use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_editor_can_access_products_pages(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $product = Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        $this->actingAs($admin)
            ->get('/inventory/products')
            ->assertOk();

        $this->actingAs($editor)
            ->get('/inventory/products')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/inventory/products/create')
            ->assertOk();

        $this->actingAs($editor)
            ->get('/inventory/products/create')
            ->assertOk();

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/edit")
            ->assertOk();

        $this->actingAs($editor)
            ->get("/inventory/products/{$product->id}/edit")
            ->assertOk();
    }

    public function test_lector_can_view_products_index_but_cannot_access_manage_routes(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
        $category = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);
        $product = Product::query()->create([
            'name' => 'Cables HDMI',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => 10,
        ]);

        $this->actingAs($lector)
            ->get('/inventory/products')
            ->assertOk();

        $this->actingAs($lector)
            ->get('/inventory/products/create')
            ->assertForbidden();

        $this->actingAs($lector)
            ->get("/inventory/products/{$product->id}/edit")
            ->assertForbidden();
    }

    public function test_qty_total_is_required_for_non_serialized_products(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductForm::class)
            ->set('name', 'Cables HDMI')
            ->set('category_id', $category->id)
            ->set('qty_total', null)
            ->call('save')
            ->assertHasErrors(['qty_total']);
    }

    public function test_qty_total_is_forced_to_null_for_serialized_products_even_if_user_provides_one(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductForm::class)
            ->set('name', 'Dell X1')
            ->set('category_id', $category->id)
            ->set('qty_total', 5)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Dell X1',
            'category_id' => $category->id,
            'qty_total' => null,
        ]);
    }

    public function test_category_is_immutable_when_editing(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $originalCategory = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);
        $newCategory = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $brand = Brand::query()->create(['name' => 'Acme']);
        $product = Product::query()->create([
            'name' => 'Cables HDMI',
            'category_id' => $originalCategory->id,
            'brand_id' => $brand->id,
            'qty_total' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductForm::class, ['product' => (string) $product->id])
            ->set('name', 'Cables HDMI')
            ->set('category_id', $newCategory->id)
            ->set('qty_total', 1)
            ->call('save');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'category_id' => $originalCategory->id,
        ]);
    }

    public function test_lector_cannot_access_products_manage_livewire_component(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        Livewire::actingAs($lector)
            ->test(ProductForm::class)
            ->assertForbidden();
    }

    public function test_lector_can_render_products_index_livewire_component(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        Livewire::actingAs($lector)
            ->test(ProductsIndex::class)
            ->assertOk();
    }

    public function test_products_search_escapes_like_wildcards(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);

        Product::query()->create([
            'name' => 'Dell_1',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => 10,
        ]);

        Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductsIndex::class)
            ->set('search', 'Dell_1')
            ->assertSee('Dell_1')
            ->assertDontSee('Dell X1');

        Livewire::actingAs($admin)
            ->test(ProductsIndex::class)
            ->set('search', 'Dell%')
            ->assertSee('No hay productos.')
            ->assertDontSee('Dell_1')
            ->assertDontSee('Dell X1');
    }

    public function test_products_index_shows_availability_counts_for_qty_products(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);

        Product::query()->create([
            'name' => 'Cables HDMI',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => 10,
        ]);

        $response = $this->actingAs($admin)
            ->get('/inventory/products')
            ->assertOk();

        $response->assertSeeTextInOrder([
            'Cables HDMI',
            'Por cantidad',
            '10',
            '10',
            '0',
        ]);
    }

    public function test_products_index_shows_availability_counts_for_serialized_products_excluding_retired(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Almacén']);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);

        $product = Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'A-1',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);
        Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'A-2',
            'asset_tag' => null,
            'status' => Asset::STATUS_ASSIGNED,
        ]);
        Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'A-3',
            'asset_tag' => null,
            'status' => Asset::STATUS_LOANED,
        ]);
        Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'A-4',
            'asset_tag' => null,
            'status' => Asset::STATUS_PENDING_RETIREMENT,
        ]);
        Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'A-5',
            'asset_tag' => null,
            'status' => Asset::STATUS_RETIRED,
        ]);

        $response = $this->actingAs($admin)
            ->get('/inventory/products')
            ->assertOk();

        $response->assertSeeTextInOrder([
            'Dell X1',
            'Serializado',
            '4',
            '1',
            '3',
        ]);
    }

    public function test_products_index_highlights_products_with_no_available_stock(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);

        Product::query()->create([
            'name' => 'Baterías AAA',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => 0,
        ]);

        $response = $this->actingAs($admin)
            ->get('/inventory/products')
            ->assertOk();

        $response
            ->assertSeeText('Baterías AAA')
            ->assertSeeText('Sin disponibles')
            ->assertSee('table-warning', false);
    }
}
