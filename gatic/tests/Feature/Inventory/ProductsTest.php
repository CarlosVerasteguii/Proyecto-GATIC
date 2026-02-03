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
use Illuminate\Support\Facades\Gate;
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
            ->assertSee('Sin resultados')
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
        $softDeletedUnavailable = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'A-DELETED',
            'asset_tag' => null,
            'status' => Asset::STATUS_ASSIGNED,
        ]);
        $softDeletedUnavailable->delete();
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

    public function test_all_roles_can_access_product_show_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $lector = User::factory()->create(['role' => UserRole::Lector]);
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
            ->get("/inventory/products/{$product->id}")
            ->assertOk()
            ->assertSeeText('Dell X1');

        $this->actingAs($editor)
            ->get("/inventory/products/{$product->id}")
            ->assertOk()
            ->assertSeeText('Dell X1');

        $this->actingAs($lector)
            ->get("/inventory/products/{$product->id}")
            ->assertOk()
            ->assertSeeText('Dell X1');
    }

    public function test_product_show_is_forbidden_when_inventory_view_is_denied(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
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

        Gate::define('inventory.view', static fn (User $user): bool => false);

        $this->actingAs($lector)
            ->get("/inventory/products/{$product->id}")
            ->assertForbidden();
    }

    public function test_product_show_shows_availability_counts_for_serialized_products_with_breakdown(): void
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
        $softDeletedAsset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'A-6',
            'asset_tag' => null,
            'status' => Asset::STATUS_ASSIGNED,
        ]);
        $softDeletedAsset->delete();

        $response = $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}")
            ->assertOk();

        $response->assertSeeTextInOrder([
            'Total',
            '4',
            'Disponibles',
            '1',
            'No disponibles',
            '3',
        ]);

        $response->assertSeeTextInOrder([
            Asset::STATUS_AVAILABLE,
            '1',
            Asset::STATUS_ASSIGNED,
            '1',
            Asset::STATUS_LOANED,
            '1',
            Asset::STATUS_PENDING_RETIREMENT,
            '1',
            Asset::STATUS_RETIRED,
            '1',
        ]);
    }

    public function test_product_show_shows_qty_summary_for_qty_products(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
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

        $response = $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}")
            ->assertOk();

        $response->assertSeeTextInOrder([
            'Total',
            '10',
            'Disponibles',
            '10',
            'No disponibles',
            '0',
        ]);
    }

    public function test_products_index_can_filter_by_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $categoryLaptops = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $categoryConsumibles = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);

        Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $categoryLaptops->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        Product::query()->create([
            'name' => 'Cables HDMI',
            'category_id' => $categoryConsumibles->id,
            'brand_id' => null,
            'qty_total' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductsIndex::class)
            ->assertSee('Dell X1')
            ->assertSee('Cables HDMI')
            ->set('categoryId', $categoryLaptops->id)
            ->assertSee('Dell X1')
            ->assertDontSee('Cables HDMI');
    }

    public function test_products_index_can_filter_by_brand(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $brandDell = Brand::query()->create(['name' => 'Dell']);
        $brandHp = Brand::query()->create(['name' => 'HP']);

        Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $category->id,
            'brand_id' => $brandDell->id,
            'qty_total' => null,
        ]);

        Product::query()->create([
            'name' => 'HP Pavilion',
            'category_id' => $category->id,
            'brand_id' => $brandHp->id,
            'qty_total' => null,
        ]);

        Product::query()->create([
            'name' => 'Genérico',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductsIndex::class)
            ->assertSee('Dell X1')
            ->assertSee('HP Pavilion')
            ->assertSee('Genérico')
            ->set('brandId', $brandDell->id)
            ->assertSee('Dell X1')
            ->assertDontSee('HP Pavilion')
            ->assertDontSee('Genérico');
    }

    public function test_products_index_can_filter_by_availability_with_available(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Almacén']);
        $categorySerialized = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $categoryQty = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);

        $productWithAvailable = Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $categorySerialized->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);
        Asset::query()->create([
            'product_id' => $productWithAvailable->id,
            'location_id' => $location->id,
            'serial' => 'A-1',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $productWithoutAvailable = Product::query()->create([
            'name' => 'HP Z1',
            'category_id' => $categorySerialized->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);
        Asset::query()->create([
            'product_id' => $productWithoutAvailable->id,
            'location_id' => $location->id,
            'serial' => 'B-1',
            'status' => Asset::STATUS_ASSIGNED,
        ]);

        Product::query()->create([
            'name' => 'Cables HDMI',
            'category_id' => $categoryQty->id,
            'brand_id' => null,
            'qty_total' => 10,
        ]);

        Product::query()->create([
            'name' => 'Cables VGA',
            'category_id' => $categoryQty->id,
            'brand_id' => null,
            'qty_total' => 0,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductsIndex::class)
            ->set('availability', 'with_available')
            ->assertSee('Dell X1')
            ->assertDontSee('HP Z1')
            ->assertSee('Cables HDMI')
            ->assertDontSee('Cables VGA');
    }

    public function test_products_index_can_filter_by_availability_without_available(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Almacén']);
        $categorySerialized = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $categoryQty = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);

        $productWithAvailable = Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $categorySerialized->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);
        Asset::query()->create([
            'product_id' => $productWithAvailable->id,
            'location_id' => $location->id,
            'serial' => 'A-1',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $productWithoutAvailable = Product::query()->create([
            'name' => 'HP Z1',
            'category_id' => $categorySerialized->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);
        Asset::query()->create([
            'product_id' => $productWithoutAvailable->id,
            'location_id' => $location->id,
            'serial' => 'B-1',
            'status' => Asset::STATUS_ASSIGNED,
        ]);

        Product::query()->create([
            'name' => 'Cables HDMI',
            'category_id' => $categoryQty->id,
            'brand_id' => null,
            'qty_total' => 10,
        ]);

        Product::query()->create([
            'name' => 'Cables VGA',
            'category_id' => $categoryQty->id,
            'brand_id' => null,
            'qty_total' => 0,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductsIndex::class)
            ->set('availability', 'without_available')
            ->assertDontSee('Dell X1')
            ->assertSee('HP Z1')
            ->assertDontSee('Cables HDMI')
            ->assertSee('Cables VGA');
    }

    public function test_products_index_resets_pagination_when_filter_changes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);

        for ($i = 1; $i <= 20; $i++) {
            Product::query()->create([
                'name' => "Producto {$i}",
                'category_id' => $category->id,
                'brand_id' => null,
                'qty_total' => 10,
            ]);
        }

        Livewire::actingAs($admin)
            ->test(ProductsIndex::class)
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('categoryId', $category->id)
            ->assertSet('paginators.page', 1);
    }

    public function test_low_stock_threshold_validation_accepts_valid_values(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductForm::class)
            ->set('name', 'Test Product')
            ->set('category_id', $category->id)
            ->set('qty_total', 50)
            ->set('low_stock_threshold', 10)
            ->call('save')
            ->assertHasNoErrors(['low_stock_threshold']);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'low_stock_threshold' => 10,
        ]);
    }

    public function test_low_stock_threshold_validation_accepts_null(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductForm::class)
            ->set('name', 'Test Product Null')
            ->set('category_id', $category->id)
            ->set('qty_total', 50)
            ->set('low_stock_threshold', null)
            ->call('save')
            ->assertHasNoErrors(['low_stock_threshold']);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product Null',
            'low_stock_threshold' => null,
        ]);
    }

    public function test_low_stock_threshold_validation_accepts_zero(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductForm::class)
            ->set('name', 'Test Product Zero')
            ->set('category_id', $category->id)
            ->set('qty_total', 50)
            ->set('low_stock_threshold', 0)
            ->call('save')
            ->assertHasNoErrors(['low_stock_threshold']);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product Zero',
            'low_stock_threshold' => 0,
        ]);
    }

    public function test_low_stock_threshold_validation_rejects_negative_values(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductForm::class)
            ->set('name', 'Test Product Negative')
            ->set('category_id', $category->id)
            ->set('qty_total', 50)
            ->set('low_stock_threshold', -5)
            ->call('save')
            ->assertHasErrors(['low_stock_threshold']);
    }

    public function test_low_stock_threshold_is_forced_to_null_for_serialized_products(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(ProductForm::class)
            ->set('name', 'Serialized Product')
            ->set('category_id', $category->id)
            ->set('qty_total', 5)
            ->set('low_stock_threshold', 10)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'Serialized Product',
            'low_stock_threshold' => null,
        ]);
    }

    public function test_products_index_shows_low_stock_badge(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);

        // Low stock product
        Product::query()->create([
            'name' => 'Low Stock Product',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => 5,
            'low_stock_threshold' => 10,
        ]);

        // Normal stock product
        Product::query()->create([
            'name' => 'Normal Stock Product',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => 15,
            'low_stock_threshold' => 10,
        ]);

        $response = $this->actingAs($admin)
            ->get('/inventory/products')
            ->assertOk();

        $response->assertSee('Stock bajo');
    }

    public function test_product_show_displays_low_stock_alert_for_qty_product(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);

        $product = Product::query()->create([
            'name' => 'Low Stock Test Product',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => 5,
            'low_stock_threshold' => 10,
        ]);

        $response = $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}")
            ->assertOk();

        $response->assertSee('Stock bajo:');
        $response->assertSee('Umbral de stock bajo');
    }

    public function test_product_show_does_not_display_low_stock_alert_when_above_threshold(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);

        $product = Product::query()->create([
            'name' => 'Normal Stock Test Product',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => 15,
            'low_stock_threshold' => 10,
        ]);

        $response = $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}")
            ->assertOk();

        $response->assertDontSee('Stock bajo:');
        $response->assertSee('Umbral de stock bajo');
    }

    public function test_product_show_does_not_display_threshold_for_serialized_products(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);

        $product = Product::query()->create([
            'name' => 'Serialized Test Product',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
            'low_stock_threshold' => null,
        ]);

        $response = $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}")
            ->assertOk();

        $response->assertDontSee('Umbral de stock bajo');
    }
}
