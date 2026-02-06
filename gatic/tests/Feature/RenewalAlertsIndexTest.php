<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RenewalAlertsIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_unauthenticated_user_cannot_access_renewal_alerts(): void
    {
        $this
            ->get('/alerts/renewals')
            ->assertRedirect('/login');
    }

    public function test_user_without_inventory_manage_cannot_access_renewal_alerts(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Lector]);

        $this
            ->actingAs($user)
            ->get('/alerts/renewals')
            ->assertForbidden();
    }

    public function test_overdue_filter_shows_only_overdue_replacements(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 12, 0, 0));

        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        $overdue = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'serial' => 'SER-RENEWAL-OVERDUE',
            'expected_replacement_date' => Carbon::today()->subDay()->toDateString(),
        ]);

        $dueSoon = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'serial' => 'SER-RENEWAL-DUE-SOON',
            'expected_replacement_date' => Carbon::today()->addDays(10)->toDateString(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/alerts/renewals?type=overdue');

        $response->assertOk();
        $response->assertSee($overdue->serial);
        $response->assertDontSee($dueSoon->serial);
    }

    public function test_due_soon_filter_respects_window_days(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 12, 0, 0));

        config([
            'gatic.alerts.renewals.due_soon_window_days_default' => 90,
            'gatic.alerts.renewals.due_soon_window_days_options' => [30, 60, 90, 180],
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
            'serial' => 'SER-RENEWAL-WITHIN',
            'expected_replacement_date' => Carbon::today()->addDays(60)->toDateString(),
        ]);

        $outside = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'serial' => 'SER-RENEWAL-OUTSIDE',
            'expected_replacement_date' => Carbon::today()->addDays(61)->toDateString(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/alerts/renewals?type=due-soon&windowDays=60');

        $response->assertOk();
        $response->assertSee($within->serial);
        $response->assertDontSee($outside->serial);
    }

    public function test_soft_deleted_assets_are_excluded_from_renewal_alerts(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 12, 0, 0));

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
            'serial' => 'SER-RENEWAL-ACTIVE',
            'expected_replacement_date' => Carbon::today()->subDay()->toDateString(),
        ]);

        $deletedAsset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'serial' => 'SER-RENEWAL-DELETED',
            'expected_replacement_date' => Carbon::today()->subDay()->toDateString(),
        ]);
        $deletedAsset->delete();

        $response = $this
            ->actingAs($user)
            ->get('/alerts/renewals?type=overdue');

        $response->assertOk();
        $response->assertSee($activeAsset->serial);
        $response->assertDontSee($deletedAsset->serial);
    }

    public function test_retired_assets_are_excluded_from_renewal_alerts(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 6, 12, 0, 0));

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
            'serial' => 'SER-RENEWAL-ACTIVE',
            'expected_replacement_date' => Carbon::today()->subDay()->toDateString(),
        ]);

        $retiredAsset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_RETIRED,
            'serial' => 'SER-RENEWAL-RETIRED',
            'expected_replacement_date' => Carbon::today()->subDay()->toDateString(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/alerts/renewals?type=overdue');

        $response->assertOk();
        $response->assertSee($activeAsset->serial);
        $response->assertDontSee($retiredAsset->serial);
    }
}
