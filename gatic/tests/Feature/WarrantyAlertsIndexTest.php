<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WarrantyAlertsIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_unauthenticated_user_cannot_access_warranty_alerts(): void
    {
        $this
            ->get('/alerts/warranties')
            ->assertRedirect('/login');
    }

    public function test_user_without_inventory_manage_cannot_access_warranty_alerts(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Lector]);

        $this
            ->actingAs($user)
            ->get('/alerts/warranties')
            ->assertForbidden();
    }

    public function test_expired_filter_shows_only_expired_warranties(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 3, 12, 0, 0));

        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $expired = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'serial' => 'SER-EXPIRED',
            'warranty_end_date' => Carbon::today()->subDay(),
        ]);

        $dueSoon = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'serial' => 'SER-DUE-SOON',
            'warranty_end_date' => Carbon::today()->addDays(10),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/alerts/warranties?type=expired');

        $response->assertOk();
        $response->assertSee($expired->serial);
        $response->assertDontSee($dueSoon->serial);
    }

    public function test_due_soon_filter_respects_window_days(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 3, 12, 0, 0));

        config([
            'gatic.alerts.warranties.due_soon_window_days_default' => 30,
            'gatic.alerts.warranties.due_soon_window_days_options' => [7, 14, 30, 60, 90],
        ]);

        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $within = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'serial' => 'SER-WITHIN',
            'warranty_end_date' => Carbon::today()->addDays(14),
        ]);

        $outside = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'serial' => 'SER-OUTSIDE',
            'warranty_end_date' => Carbon::today()->addDays(15),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/alerts/warranties?type=due-soon&windowDays=14');

        $response->assertOk();
        $response->assertSee($within->serial);
        $response->assertDontSee($outside->serial);
    }

    public function test_soft_deleted_assets_are_excluded_from_warranty_alerts(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 3, 12, 0, 0));

        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $activeAsset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'serial' => 'SER-ACTIVE',
            'warranty_end_date' => Carbon::today()->subDay(),
        ]);

        $deletedAsset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'serial' => 'SER-DELETED',
            'warranty_end_date' => Carbon::today()->subDay(),
        ]);
        $deletedAsset->delete();

        $response = $this
            ->actingAs($user)
            ->get('/alerts/warranties?type=expired');

        $response->assertOk();
        $response->assertSee($activeAsset->serial);
        $response->assertDontSee($deletedAsset->serial);
    }

    public function test_retired_assets_are_excluded_from_warranty_alerts(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 3, 12, 0, 0));

        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $activeAsset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'serial' => 'SER-ACTIVE',
            'warranty_end_date' => Carbon::today()->subDay(),
        ]);

        $retiredAsset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_RETIRED,
            'serial' => 'SER-RETIRED',
            'warranty_end_date' => Carbon::today()->subDay(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/alerts/warranties?type=expired');

        $response->assertOk();
        $response->assertSee($activeAsset->serial);
        $response->assertDontSee($retiredAsset->serial);
    }

    public function test_warranty_supplier_is_displayed_in_alerts(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 3, 12, 0, 0));

        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
        $supplier = Supplier::factory()->create(['name' => 'Garantías Acme S.A.']);

        $asset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'serial' => 'SER-WITH-SUPPLIER',
            'warranty_end_date' => Carbon::today()->subDay(),
            'warranty_supplier_id' => $supplier->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/alerts/warranties?type=expired');

        $response->assertOk();
        $response->assertSee($asset->serial);
        $response->assertSee('Garantías Acme S.A.');
    }

    public function test_editor_can_access_warranty_alerts(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 3, 12, 0, 0));

        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Editor]);

        $response = $this
            ->actingAs($user)
            ->get('/alerts/warranties');

        $response->assertOk();
    }
}
