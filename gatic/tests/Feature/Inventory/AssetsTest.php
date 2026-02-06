<?php

namespace Tests\Feature\Inventory;

use App\Enums\UserRole;
use App\Livewire\Inventory\Assets\AssetForm;
use App\Livewire\Inventory\Assets\AssetShow;
use App\Livewire\Inventory\Assets\AssetsIndex;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AssetsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

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

    public function test_asset_form_requires_employee_when_status_is_assigned_or_loaned(): void
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

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id])
            ->set('serial', 'SER-EMP')
            ->set('asset_tag', null)
            ->set('location_id', $location->id)
            ->set('status', Asset::STATUS_ASSIGNED)
            ->call('save')
            ->assertHasErrors(['current_employee_id']);

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id])
            ->set('serial', 'SER-EMP-2')
            ->set('asset_tag', null)
            ->set('location_id', $location->id)
            ->set('status', Asset::STATUS_LOANED)
            ->call('save')
            ->assertHasErrors(['current_employee_id']);
    }

    public function test_asset_form_persists_employee_on_edit_and_clears_it_when_status_no_longer_requires_holder(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = Employee::factory()->create();
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
            'serial' => 'SER-EDIT',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
            'current_employee_id' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->set('status', Asset::STATUS_ASSIGNED)
            ->set('current_employee_id', $employee->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => Asset::STATUS_ASSIGNED,
            'current_employee_id' => $employee->id,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->set('status', Asset::STATUS_AVAILABLE)
            ->set('current_employee_id', $employee->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => Asset::STATUS_AVAILABLE,
            'current_employee_id' => null,
        ]);
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

    public function test_asset_show_displays_tenencia_na_for_available_asset(): void
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
            ->assertSee('El activo está disponible');
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

    public function test_assets_index_can_filter_by_location(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location1 = Location::query()->create(['name' => 'Almacén']);
        $location2 = Location::query()->create(['name' => 'Oficina']);
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
            'location_id' => $location1->id,
            'serial' => 'SER-1',
            'status' => Asset::STATUS_AVAILABLE,
        ]);
        Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location2->id,
            'serial' => 'SER-2',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetsIndex::class, ['product' => (string) $product->id])
            ->assertSee('SER-1')
            ->assertSee('SER-2')
            ->set('locationId', $location1->id)
            ->assertSee('SER-1')
            ->assertDontSee('SER-2');
    }

    public function test_assets_index_can_filter_by_status(): void
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
            'serial' => 'SER-1',
            'status' => Asset::STATUS_AVAILABLE,
        ]);
        Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SER-2',
            'status' => Asset::STATUS_ASSIGNED,
        ]);
        Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SER-3',
            'status' => Asset::STATUS_RETIRED,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetsIndex::class, ['product' => (string) $product->id])
            ->assertSee('SER-1')
            ->assertSee('SER-2')
            ->assertDontSee('SER-3')
            ->set('status', Asset::STATUS_AVAILABLE)
            ->assertSee('SER-1')
            ->assertDontSee('SER-2')
            ->assertDontSee('SER-3');
    }

    public function test_assets_index_shows_retired_only_when_explicitly_filtered(): void
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
            'serial' => 'SER-AVAILABLE',
            'status' => Asset::STATUS_AVAILABLE,
        ]);
        Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SER-RETIRED',
            'status' => Asset::STATUS_RETIRED,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetsIndex::class, ['product' => (string) $product->id])
            ->assertSee('SER-AVAILABLE')
            ->assertDontSee('SER-RETIRED')
            ->set('status', Asset::STATUS_RETIRED)
            ->assertDontSee('SER-AVAILABLE')
            ->assertSee('SER-RETIRED');
    }

    public function test_assets_index_resets_pagination_when_filter_changes(): void
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

        for ($i = 1; $i <= 20; $i++) {
            Asset::query()->create([
                'product_id' => $product->id,
                'location_id' => $location->id,
                'serial' => "SER-{$i}",
                'status' => Asset::STATUS_AVAILABLE,
            ]);
        }

        Livewire::actingAs($admin)
            ->test(AssetsIndex::class, ['product' => (string) $product->id])
            ->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('locationId', $location->id)
            ->assertSet('paginators.page', 1);
    }

    public function test_asset_form_can_save_acquisition_cost_and_currency(): void
    {
        config(['gatic.inventory.money.allowed_currencies' => ['MXN']]);
        config(['gatic.inventory.money.default_currency' => 'MXN']);

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

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id])
            ->set('serial', 'SER-COST-1')
            ->set('location_id', $location->id)
            ->set('status', Asset::STATUS_AVAILABLE)
            ->set('acquisitionCost', '15000.50')
            ->set('acquisitionCurrency', 'MXN')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('assets', [
            'serial' => 'SER-COST-1',
            'acquisition_cost' => '15000.50',
            'acquisition_currency' => 'MXN',
        ]);
    }

    public function test_asset_form_defaults_currency_to_mxn_when_cost_present(): void
    {
        config(['gatic.inventory.money.allowed_currencies' => ['MXN']]);
        config(['gatic.inventory.money.default_currency' => 'MXN']);

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

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id])
            ->set('serial', 'SER-COST-DEFAULT-CUR')
            ->set('location_id', $location->id)
            ->set('status', Asset::STATUS_AVAILABLE)
            ->set('acquisitionCost', '1.00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('assets', [
            'serial' => 'SER-COST-DEFAULT-CUR',
            'acquisition_cost' => '1.00',
            'acquisition_currency' => 'MXN',
        ]);
    }

    public function test_asset_form_validates_invalid_currency(): void
    {
        config(['gatic.inventory.money.allowed_currencies' => ['MXN']]);
        config(['gatic.inventory.money.default_currency' => 'MXN']);

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

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id])
            ->set('serial', 'SER-COST-2')
            ->set('location_id', $location->id)
            ->set('status', Asset::STATUS_AVAILABLE)
            ->set('acquisitionCost', '1000.00')
            ->set('acquisitionCurrency', 'EUR')
            ->call('save')
            ->assertHasErrors(['acquisitionCurrency']);
    }

    public function test_asset_form_validates_negative_cost(): void
    {
        config(['gatic.inventory.money.allowed_currencies' => ['MXN']]);

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

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id])
            ->set('serial', 'SER-COST-3')
            ->set('location_id', $location->id)
            ->set('status', Asset::STATUS_AVAILABLE)
            ->set('acquisitionCost', '-100.00')
            ->call('save')
            ->assertHasErrors(['acquisitionCost']);
    }

    public function test_asset_form_validates_cost_with_more_than_two_decimals(): void
    {
        config(['gatic.inventory.money.allowed_currencies' => ['MXN']]);

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

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id])
            ->set('serial', 'SER-COST-4')
            ->set('location_id', $location->id)
            ->set('status', Asset::STATUS_AVAILABLE)
            ->set('acquisitionCost', '100.999')
            ->call('save')
            ->assertHasErrors(['acquisitionCost']);
    }

    public function test_asset_show_displays_acquisition_cost(): void
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
            'serial' => 'SER-SHOW-1',
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '25000.00',
            'acquisition_currency' => 'MXN',
        ]);

        Livewire::actingAs($admin)
            ->test(AssetShow::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->assertSee('Costo de adquisición')
            ->assertSee('25,000.00')
            ->assertSee('MXN');
    }

    public function test_asset_show_displays_dash_when_no_cost(): void
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
            'serial' => 'SER-SHOW-2',
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => null,
            'acquisition_currency' => null,
        ]);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}")
            ->assertOk()
            ->assertSee('Costo de adquisición')
            ->assertSee('—');
    }

    public function test_asset_show_displays_default_currency_when_currency_is_null(): void
    {
        config(['gatic.inventory.money.default_currency' => 'MXN']);

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
            'serial' => 'SER-SHOW-3',
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '1234.00',
            'acquisition_currency' => null,
        ]);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}")
            ->assertOk()
            ->assertSee('1,234.00')
            ->assertSee('MXN');
    }

    public function test_asset_form_can_edit_acquisition_cost(): void
    {
        config(['gatic.inventory.money.allowed_currencies' => ['MXN']]);

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
            'serial' => 'SER-EDIT-COST',
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '10000.00',
            'acquisition_currency' => 'MXN',
        ]);

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->assertSet('acquisitionCost', '10000.00')
            ->assertSet('acquisitionCurrency', 'MXN')
            ->set('acquisitionCost', '20000.00')
            ->set('acquisitionCurrency', 'MXN')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'acquisition_cost' => '20000.00',
            'acquisition_currency' => 'MXN',
        ]);
    }

    public function test_asset_form_preloads_useful_life_from_category_default(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
            'default_useful_life_months' => 60,
        ]);
        $product = Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id])
            ->assertSet('usefulLifeMonths', '60');
    }

    public function test_asset_form_calculates_expected_replacement_date_when_manual_date_is_empty(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 10, 0, 0));

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Almacén']);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
            'default_useful_life_months' => null,
        ]);
        $product = Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id])
            ->set('serial', 'SER-REPLACEMENT-1')
            ->set('location_id', $location->id)
            ->set('status', Asset::STATUS_AVAILABLE)
            ->set('usefulLifeMonths', '12')
            ->set('expectedReplacementDate', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('assets', [
            'serial' => 'SER-REPLACEMENT-1',
            'useful_life_months' => 12,
            'expected_replacement_date' => Carbon::today()->addMonthsNoOverflow(12)->toDateString(),
        ]);
    }

    public function test_asset_form_respects_manual_expected_replacement_date(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 10, 0, 0));

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Almacén']);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
            'default_useful_life_months' => 24,
        ]);
        $product = Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id])
            ->set('serial', 'SER-REPLACEMENT-2')
            ->set('location_id', $location->id)
            ->set('status', Asset::STATUS_AVAILABLE)
            ->set('usefulLifeMonths', '36')
            ->set('expectedReplacementDate', '2030-01-15')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('assets', [
            'serial' => 'SER-REPLACEMENT-2',
            'useful_life_months' => 36,
            'expected_replacement_date' => '2030-01-15',
        ]);
    }

    public function test_asset_form_uses_category_default_when_useful_life_is_empty(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 10, 0, 0));

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Almacén']);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
            'default_useful_life_months' => 48,
        ]);
        $product = Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id])
            ->set('serial', 'SER-REPLACEMENT-3')
            ->set('location_id', $location->id)
            ->set('status', Asset::STATUS_AVAILABLE)
            ->set('usefulLifeMonths', '')
            ->set('expectedReplacementDate', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('assets', [
            'serial' => 'SER-REPLACEMENT-3',
            'useful_life_months' => null,
            'expected_replacement_date' => Carbon::today()->addMonthsNoOverflow(48)->toDateString(),
        ]);
    }

    public function test_asset_form_does_not_persist_category_default_as_override_when_editing_existing_asset(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 10, 0, 0));

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Almacén']);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
            'default_useful_life_months' => 48,
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
            'serial' => 'SER-REPLACEMENT-EXISTING',
            'status' => Asset::STATUS_AVAILABLE,
            'useful_life_months' => null,
            'expected_replacement_date' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id, 'asset' => (string) $asset->id])
            ->assertSet('usefulLifeMonths', '48')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'useful_life_months' => null,
        ]);
    }

    public function test_asset_show_normalizes_renewal_window_default_to_allowed_options(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 10, 0, 0));
        config([
            'gatic.alerts.renewals.due_soon_window_days_default' => 999,
            'gatic.alerts.renewals.due_soon_window_days_options' => [30, 60],
        ]);

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
            'serial' => 'SER-SHOW-RENEWAL-DEFAULT-INVALID',
            'status' => Asset::STATUS_AVAILABLE,
            'expected_replacement_date' => Carbon::today()->addDays(40)->toDateString(),
        ]);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}")
            ->assertOk()
            ->assertSee('Fecha estimada de reemplazo')
            ->assertSee(Carbon::today()->addDays(40)->format('d/m/Y'))
            ->assertSee('En tiempo')
            ->assertDontSee('Por vencer');
    }

    public function test_asset_form_validates_useful_life_range(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Almacén']);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
            'default_useful_life_months' => null,
        ]);
        $product = Product::query()->create([
            'name' => 'Dell X1',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetForm::class, ['product' => (string) $product->id])
            ->set('serial', 'SER-REPLACEMENT-4')
            ->set('location_id', $location->id)
            ->set('status', Asset::STATUS_AVAILABLE)
            ->set('usefulLifeMonths', '601')
            ->call('save')
            ->assertHasErrors(['usefulLifeMonths']);
    }

    public function test_asset_show_displays_replacement_fields_and_due_soon_badge(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 10, 0, 0));
        config(['gatic.alerts.renewals.due_soon_window_days_default' => 30]);

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location = Location::query()->create(['name' => 'Almacén']);
        $category = Category::query()->create([
            'name' => 'Laptops',
            'is_serialized' => true,
            'requires_asset_tag' => false,
            'default_useful_life_months' => 36,
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
            'serial' => 'SER-SHOW-RENEWAL',
            'status' => Asset::STATUS_AVAILABLE,
            'useful_life_months' => 36,
            'expected_replacement_date' => Carbon::today()->addDays(10)->toDateString(),
        ]);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}")
            ->assertOk()
            ->assertSee('Vida útil (meses)')
            ->assertSee('36')
            ->assertSee('Fecha estimada de reemplazo')
            ->assertSee(Carbon::today()->addDays(10)->format('d/m/Y'))
            ->assertSee('Por vencer');
    }
}
