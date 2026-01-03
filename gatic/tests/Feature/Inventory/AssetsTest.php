<?php

namespace Tests\Feature\Inventory;

use App\Enums\UserRole;
use App\Livewire\Inventory\Assets\AssetForm;
use App\Livewire\Inventory\Assets\AssetShow;
use App\Livewire\Inventory\Assets\AssetsIndex;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_editor_can_access_assets_pages_for_serialized_products(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);
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
        $asset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SER-1',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets")
            ->assertOk();

        $this->actingAs($editor)
            ->get("/inventory/products/{$product->id}/assets")
            ->assertOk();

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/create")
            ->assertOk();

        $this->actingAs($editor)
            ->get("/inventory/products/{$product->id}/assets/create")
            ->assertOk();

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/edit")
            ->assertOk();

        $this->actingAs($editor)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/edit")
            ->assertOk();
    }

    public function test_lector_can_view_assets_index_but_cannot_access_manage_routes(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
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
        $asset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SER-1',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $this->actingAs($lector)
            ->get("/inventory/products/{$product->id}/assets")
            ->assertOk();

        $this->actingAs($lector)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}")
            ->assertOk();

        $this->actingAs($lector)
            ->get("/inventory/products/{$product->id}/assets/create")
            ->assertForbidden();

        $this->actingAs($lector)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/edit")
            ->assertForbidden();
    }

    public function test_lector_cannot_execute_assets_manage_livewire_actions(): void
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

        Livewire::actingAs($lector)
            ->test(AssetForm::class, ['product' => (string) $product->id])
            ->assertForbidden();
    }

    public function test_non_serialized_products_show_guardrail_message(): void
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

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets")
            ->assertOk()
            ->assertSee('No hay activos para productos por cantidad.');

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/create")
            ->assertOk()
            ->assertSee('No hay activos para productos por cantidad.');
    }

    public function test_serial_is_unique_per_product_but_can_repeat_across_products(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Almacén']);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $product1 = Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);
        $product2 = Product::query()->create([
            'name' => 'Dell X2',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product1->id])
            ->set('serial', ' SER-1 ')
            ->set('asset_tag', null)
            ->set('location_id', $location->id)
            ->set('status', Asset::STATUS_AVAILABLE)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('assets', [
            'product_id' => $product1->id,
            'serial' => 'SER-1',
        ]);

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product2->id])
            ->set('serial', 'SER-1')
            ->set('asset_tag', null)
            ->set('location_id', $location->id)
            ->set('status', Asset::STATUS_AVAILABLE)
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product1->id])
            ->set('serial', 'SER-1')
            ->set('asset_tag', null)
            ->set('location_id', $location->id)
            ->set('status', Asset::STATUS_AVAILABLE)
            ->call('save')
            ->assertHasErrors(['serial']);
    }

    public function test_asset_tag_is_required_for_categories_that_require_it_and_unique_globally(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Almacén']);
        $category = Category::query()->create([
            'name' => 'Laptops corporativas',
            'is_serialized' => true,
            'requires_asset_tag' => true,
        ]);
        $product1 = Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);
        $product2 = Product::query()->create([
            'name' => 'Dell X2',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product1->id])
            ->set('serial', 'SER-1')
            ->set('asset_tag', null)
            ->set('location_id', $location->id)
            ->set('status', Asset::STATUS_AVAILABLE)
            ->call('save')
            ->assertHasErrors(['asset_tag']);

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product1->id])
            ->set('serial', 'SER-1')
            ->set('asset_tag', 'abc-123')
            ->set('location_id', $location->id)
            ->set('status', Asset::STATUS_AVAILABLE)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('assets', [
            'product_id' => $product1->id,
            'asset_tag' => 'ABC-123',
        ]);

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product2->id])
            ->set('serial', 'SER-2')
            ->set('asset_tag', 'abc-123')
            ->set('location_id', $location->id)
            ->set('status', Asset::STATUS_AVAILABLE)
            ->call('save')
            ->assertHasErrors(['asset_tag']);
    }

    public function test_location_must_exist_and_be_active(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $activeLocation = Location::query()->create(['name' => 'Almacén']);
        $deletedLocation = Location::query()->create(['name' => 'Bodega']);
        $deletedLocation->delete();

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

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id])
            ->set('serial', 'SER-1')
            ->set('asset_tag', null)
            ->set('location_id', $deletedLocation->id)
            ->set('status', Asset::STATUS_AVAILABLE)
            ->call('save')
            ->assertHasErrors(['location_id']);

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id])
            ->set('serial', 'SER-1')
            ->set('asset_tag', null)
            ->set('location_id', $activeLocation->id)
            ->set('status', Asset::STATUS_AVAILABLE)
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_assets_index_livewire_component_renders_for_lector(): void
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

        Livewire::actingAs($lector)
            ->test(AssetsIndex::class, ['product' => (string) $product->id])
            ->assertOk();
    }

    public function test_all_roles_can_view_asset_show_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $lector = User::factory()->create(['role' => UserRole::Lector]);
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
        $asset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SER-1',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}")
            ->assertOk();

        $this->actingAs($editor)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}")
            ->assertOk();

        $this->actingAs($lector)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}")
            ->assertOk();
    }

    public function test_asset_show_returns_404_if_asset_does_not_belong_to_product(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Almacén']);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);
        $product1 = Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);
        $product2 = Product::query()->create([
            'name' => 'Dell X2',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);
        $asset = Asset::query()->create([
            'product_id' => $product1->id,
            'location_id' => $location->id,
            'serial' => 'SER-1',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product2->id}/assets/{$asset->id}")
            ->assertNotFound();
    }

    public function test_asset_show_returns_404_if_asset_is_soft_deleted(): void
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
        $asset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SER-1',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);
        $asset->delete();

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}")
            ->assertNotFound();
    }

    public function test_asset_show_returns_404_for_non_serialized_products(): void
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

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/999")
            ->assertNotFound();
    }

    public function test_asset_show_displays_tenencia_na_message(): void
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
        $asset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SER-1',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}")
            ->assertOk()
            ->assertSee('N/A (se habilita en Épica 4/5)');
    }

    public function test_asset_show_livewire_component_renders(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
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
        $asset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SER-1',
            'asset_tag' => 'TAG-001',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        Livewire::actingAs($lector)
            ->test(AssetShow::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->assertOk()
            ->assertSee('SER-1')
            ->assertSee('TAG-001')
            ->assertSee('Disponible')
            ->assertSee('Almacén');
    }
}
