<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardGlobalFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_filters_metrics_by_location(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 17, 12, 0, 0));

        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $locationA = Location::factory()->create();
        $locationB = Location::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        Asset::factory()->count(2)->create([
            'product_id' => $product->id,
            'location_id' => $locationA->id,
            'status' => Asset::STATUS_LOANED,
            'loan_due_date' => Carbon::today()->subDay(),
        ]);

        Asset::factory()->count(3)->create([
            'product_id' => $product->id,
            'location_id' => $locationB->id,
            'status' => Asset::STATUS_LOANED,
            'loan_due_date' => Carbon::today()->subDay(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard?location='.$locationA->id);

        $response->assertOk();

        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/data-testid="dashboard-metric-loans-overdue"[^>]*>\\s*2\\s*</', $content);
    }

    public function test_assets_global_index_filters_by_category(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);

        $categoryA = Category::factory()->create(['is_serialized' => true]);
        $categoryB = Category::factory()->create(['is_serialized' => true]);
        $location = Location::factory()->create();

        $productA = Product::factory()->create(['category_id' => $categoryA->id, 'name' => 'Producto A']);
        $productB = Product::factory()->create(['category_id' => $categoryB->id, 'name' => 'Producto B']);

        Asset::factory()->create([
            'product_id' => $productA->id,
            'location_id' => $location->id,
            'serial' => 'SER-A-001',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        Asset::factory()->create([
            'product_id' => $productB->id,
            'location_id' => $location->id,
            'serial' => 'SER-B-001',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/inventory/assets?category='.$categoryA->id);

        $response->assertOk();
        $response->assertSee('SER-A-001');
        $response->assertDontSee('SER-B-001');
    }
}
