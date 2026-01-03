<?php

namespace Tests\Feature\Inventory;

use App\Enums\UserRole;
use App\Livewire\Inventory\Adjustments\AssetAdjustmentForm;
use App\Livewire\Inventory\Adjustments\ProductAdjustmentForm;
use App\Models\Asset;
use App\Models\Category;
use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentEntry;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryAdjustmentsTest extends TestCase
{
    use RefreshDatabase;

    // =====================
    // RBAC Tests
    // =====================

    public function test_admin_can_access_adjustments_index(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/inventory/adjustments')
            ->assertOk()
            ->assertSeeText('Historial de ajustes');
    }

    public function test_editor_cannot_access_adjustments_index(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($editor)
            ->get('/inventory/adjustments')
            ->assertForbidden();
    }

    public function test_lector_cannot_access_adjustments_index(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector)
            ->get('/inventory/adjustments')
            ->assertForbidden();
    }

    public function test_admin_can_access_product_adjustment_form(): void
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
            ->get("/inventory/products/{$product->id}/adjust")
            ->assertOk()
            ->assertSeeText('Ajustar inventario');
    }

    public function test_editor_cannot_access_product_adjustment_form(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
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

        $this->actingAs($editor)
            ->get("/inventory/products/{$product->id}/adjust")
            ->assertForbidden();
    }

    public function test_lector_cannot_access_product_adjustment_form(): void
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
            ->get("/inventory/products/{$product->id}/adjust")
            ->assertForbidden();
    }

    public function test_admin_can_access_asset_adjustment_form(): void
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
            'serial' => 'SN-001',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/adjust")
            ->assertOk()
            ->assertSeeText('Ajustar activo');
    }

    public function test_editor_cannot_access_asset_adjustment_form(): void
    {
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
            'serial' => 'SN-001',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $this->actingAs($editor)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/adjust")
            ->assertForbidden();
    }

    // =====================
    // Product Quantity Adjustment Tests
    // =====================

    public function test_admin_can_save_product_quantity_adjustment(): void
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

        Livewire::actingAs($admin)
            ->test(ProductAdjustmentForm::class, ['product' => (string) $product->id])
            ->set('newQty', 15)
            ->set('reason', 'Reposición de inventario físico')
            ->call('save')
            ->assertRedirect(route('inventory.products.show', ['product' => $product->id]));

        $product->refresh();
        $this->assertEquals(15, $product->qty_total);

        $this->assertDatabaseHas('inventory_adjustments', [
            'actor_user_id' => $admin->id,
            'reason' => 'Reposición de inventario físico',
        ]);

        $adjustment = InventoryAdjustment::query()->first();
        $this->assertNotNull($adjustment);

        $this->assertDatabaseHas('inventory_adjustment_entries', [
            'inventory_adjustment_id' => $adjustment->id,
            'subject_type' => Product::class,
            'subject_id' => $product->id,
            'product_id' => $product->id,
            'asset_id' => null,
        ]);

        $entry = InventoryAdjustmentEntry::query()->first();
        $this->assertEquals(['qty_total' => 10], $entry->before);
        $this->assertEquals(['qty_total' => 15], $entry->after);
    }

    public function test_product_adjustment_requires_reason(): void
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

        Livewire::actingAs($admin)
            ->test(ProductAdjustmentForm::class, ['product' => (string) $product->id])
            ->set('newQty', 15)
            ->set('reason', '')
            ->call('save')
            ->assertHasErrors(['reason' => 'required']);

        $this->assertDatabaseCount('inventory_adjustments', 0);
    }

    public function test_product_adjustment_requires_reason_min_length(): void
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

        Livewire::actingAs($admin)
            ->test(ProductAdjustmentForm::class, ['product' => (string) $product->id])
            ->set('newQty', 15)
            ->set('reason', 'Ok')
            ->call('save')
            ->assertHasErrors(['reason' => 'min']);
    }

    public function test_product_adjustment_rejects_negative_qty(): void
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

        Livewire::actingAs($admin)
            ->test(ProductAdjustmentForm::class, ['product' => (string) $product->id])
            ->set('newQty', -5)
            ->set('reason', 'Corrección de inventario')
            ->call('save')
            ->assertHasErrors(['newQty' => 'min']);

        $this->assertDatabaseCount('inventory_adjustments', 0);
    }

    public function test_product_adjustment_404_for_serialized_products(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
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
            ->get("/inventory/products/{$product->id}/adjust")
            ->assertNotFound();
    }

    // =====================
    // Asset Adjustment Tests
    // =====================

    public function test_admin_can_save_asset_adjustment(): void
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
        $asset = Asset::query()->create([
            'product_id' => $product->id,
            'location_id' => $location1->id,
            'serial' => 'SN-001',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetAdjustmentForm::class, [
                'product' => (string) $product->id,
                'asset' => (string) $asset->id,
            ])
            ->set('newStatus', Asset::STATUS_ASSIGNED)
            ->set('newLocationId', $location2->id)
            ->set('reason', 'Corrección de estado por auditoría')
            ->call('save')
            ->assertRedirect(route('inventory.products.assets.show', [
                'product' => $product->id,
                'asset' => $asset->id,
            ]));

        $asset->refresh();
        $this->assertEquals(Asset::STATUS_ASSIGNED, $asset->status);
        $this->assertEquals($location2->id, $asset->location_id);

        $this->assertDatabaseHas('inventory_adjustments', [
            'actor_user_id' => $admin->id,
            'reason' => 'Corrección de estado por auditoría',
        ]);

        $adjustment = InventoryAdjustment::query()->first();
        $this->assertNotNull($adjustment);

        $entry = InventoryAdjustmentEntry::query()->first();
        $this->assertEquals([
            'status' => Asset::STATUS_AVAILABLE,
            'location_id' => $location1->id,
        ], $entry->before);
        $this->assertEquals([
            'status' => Asset::STATUS_ASSIGNED,
            'location_id' => $location2->id,
        ], $entry->after);
    }

    public function test_asset_adjustment_requires_reason(): void
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
            'serial' => 'SN-001',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetAdjustmentForm::class, [
                'product' => (string) $product->id,
                'asset' => (string) $asset->id,
            ])
            ->set('newStatus', Asset::STATUS_ASSIGNED)
            ->set('newLocationId', $location->id)
            ->set('reason', '')
            ->call('save')
            ->assertHasErrors(['reason' => 'required']);

        $this->assertDatabaseCount('inventory_adjustments', 0);
    }

    public function test_asset_adjustment_rejects_invalid_status(): void
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
            'serial' => 'SN-001',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetAdjustmentForm::class, [
                'product' => (string) $product->id,
                'asset' => (string) $asset->id,
            ])
            ->set('newStatus', 'InvalidStatus')
            ->set('newLocationId', $location->id)
            ->set('reason', 'Corrección de inventario')
            ->call('save')
            ->assertHasErrors(['newStatus' => 'in']);
    }

    public function test_asset_adjustment_rejects_soft_deleted_location(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $location1 = Location::query()->create(['name' => 'Almacén']);
        $location2 = Location::query()->create(['name' => 'Bodega vieja']);
        $location2->delete();

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
            'location_id' => $location1->id,
            'serial' => 'SN-001',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        Livewire::actingAs($admin)
            ->test(AssetAdjustmentForm::class, [
                'product' => (string) $product->id,
                'asset' => (string) $asset->id,
            ])
            ->set('newStatus', Asset::STATUS_AVAILABLE)
            ->set('newLocationId', $location2->id)
            ->set('reason', 'Corrección de inventario')
            ->call('save')
            ->assertHasErrors(['newLocationId' => 'exists']);
    }

    public function test_asset_adjustment_404_for_qty_products(): void
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
            ->get("/inventory/products/{$product->id}/assets/1/adjust")
            ->assertNotFound();
    }

    public function test_asset_adjustment_404_for_asset_not_belonging_to_product(): void
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
            'name' => 'HP Z2',
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);
        $asset = Asset::query()->create([
            'product_id' => $product2->id,
            'location_id' => $location->id,
            'serial' => 'SN-001',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $this->actingAs($admin)
            ->get("/inventory/products/{$product1->id}/assets/{$asset->id}/adjust")
            ->assertNotFound();
    }

    // =====================
    // Soft-delete handling
    // =====================

    public function test_product_adjustment_404_for_soft_deleted_product(): void
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
        $product->delete();

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/adjust")
            ->assertNotFound();
    }

    public function test_asset_adjustment_404_for_soft_deleted_asset(): void
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
            'serial' => 'SN-001',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);
        $asset->delete();

        $this->actingAs($admin)
            ->get("/inventory/products/{$product->id}/assets/{$asset->id}/adjust")
            ->assertNotFound();
    }
}
