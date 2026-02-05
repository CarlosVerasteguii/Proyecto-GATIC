<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_contains_polling_markup_when_polling_enabled(): void
    {
        config(['gatic.ui.polling.enabled' => true]);
        $interval = config('gatic.ui.polling.metrics_interval_s');

        $user = User::factory()->create(['is_active' => true]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee("wire:poll.visible.{$interval}s=\"poll\"", false);
    }

    public function test_dashboard_contains_freshness_indicator(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('data-gatic-freshness', false);
    }

    public function test_dashboard_displays_metric_cards(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Activos Prestados');
        $response->assertSee('Pendientes de Retiro');
        $response->assertSee('Activos Asignados');
        $response->assertSee('Activos No Disponibles');
        $response->assertSee('Movimientos Hoy');
        $response->assertSee('Vencidos');
        $response->assertSee('Por vencer');
        $response->assertSee('Stock Bajo');
    }

    public function test_dashboard_shows_overdue_and_due_soon_counts(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 17, 12, 0, 0));

        config([
            'gatic.alerts.loans.due_soon_window_days_default' => 7,
            'gatic.alerts.loans.due_soon_window_days_options' => [7, 14, 30],
        ]);

        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        Asset::factory()->count(2)->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_LOANED,
            'loan_due_date' => Carbon::today()->subDay(),
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_LOANED,
            'loan_due_date' => Carbon::today(),
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_LOANED,
            'loan_due_date' => Carbon::today()->addDays(3),
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_LOANED,
            'loan_due_date' => Carbon::today()->addDays(8),
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_LOANED,
            'loan_due_date' => null,
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_ASSIGNED,
            'loan_due_date' => Carbon::today()->subDay(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();

        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/data-testid="dashboard-metric-loans-overdue"[^>]*>\\s*2\\s*</', $content);
        $this->assertMatchesRegularExpression('/data-testid="dashboard-metric-loans-due-soon"[^>]*>\\s*2\\s*</', $content);
    }

    public function test_dashboard_shows_correct_asset_counts(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 17, 12, 0, 0));

        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        Asset::factory()->count(2)->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_LOANED,
        ]);

        Asset::factory()->count(3)->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_ASSIGNED,
        ]);

        Asset::factory()->count(1)->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_PENDING_RETIREMENT,
        ]);

        Asset::factory()->count(4)->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();

        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/data-testid="dashboard-metric-assets-loaned"[^>]*>\\s*2\\s*</', $content);
        $this->assertMatchesRegularExpression('/data-testid="dashboard-metric-assets-assigned"[^>]*>\\s*3\\s*</', $content);
        $this->assertMatchesRegularExpression('/data-testid="dashboard-metric-assets-pending-retirement"[^>]*>\\s*1\\s*</', $content);
        $this->assertMatchesRegularExpression('/data-testid="dashboard-metric-assets-unavailable"[^>]*>\\s*6\\s*</', $content);
    }

    public function test_dashboard_shows_movements_today_count(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 17, 12, 0, 0));

        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
        $asset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
        ]);
        $employee = Employee::factory()->create();

        AssetMovement::factory()->count(2)->create([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $user->id,
        ]);

        ProductQuantityMovement::factory()->count(3)->create([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();

        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/data-testid="dashboard-metric-movements-today"[^>]*>\\s*5\\s*</', $content);
    }

    public function test_dashboard_has_refresh_button(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('wire:click="refreshNow"', false);
        $response->assertSee('Actualizar');
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $this
            ->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_dashboard_shows_low_stock_products_count(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $categoryQuantity = Category::factory()->create(['is_serialized' => false]);
        $categorySerialized = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();

        // Product with low stock (qty_total <= low_stock_threshold)
        Product::factory()->create([
            'category_id' => $categoryQuantity->id,
            'brand_id' => $brand->id,
            'qty_total' => 5,
            'low_stock_threshold' => 10,
        ]);

        // Product at threshold (should count)
        Product::factory()->create([
            'category_id' => $categoryQuantity->id,
            'brand_id' => $brand->id,
            'qty_total' => 10,
            'low_stock_threshold' => 10,
        ]);

        // Product above threshold (should NOT count)
        Product::factory()->create([
            'category_id' => $categoryQuantity->id,
            'brand_id' => $brand->id,
            'qty_total' => 15,
            'low_stock_threshold' => 10,
        ]);

        // Product with no threshold configured (should NOT count)
        Product::factory()->create([
            'category_id' => $categoryQuantity->id,
            'brand_id' => $brand->id,
            'qty_total' => 3,
            'low_stock_threshold' => null,
        ]);

        // Serialized product (should NOT count)
        Product::factory()->create([
            'category_id' => $categorySerialized->id,
            'brand_id' => $brand->id,
            'qty_total' => null,
            'low_stock_threshold' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();

        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/data-testid="dashboard-metric-products-low-stock"[^>]*>\\s*2\\s*</', $content);
    }

    public function test_dashboard_low_stock_count_excludes_soft_deleted_products(): void
    {
        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $categoryQuantity = Category::factory()->create(['is_serialized' => false]);
        $brand = Brand::factory()->create();

        // Active low stock product (should count)
        Product::factory()->create([
            'category_id' => $categoryQuantity->id,
            'brand_id' => $brand->id,
            'qty_total' => 5,
            'low_stock_threshold' => 10,
        ]);

        // Soft-deleted low stock product (should NOT count)
        $deletedProduct = Product::factory()->create([
            'category_id' => $categoryQuantity->id,
            'brand_id' => $brand->id,
            'qty_total' => 3,
            'low_stock_threshold' => 10,
        ]);
        $deletedProduct->delete();

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();

        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/data-testid="dashboard-metric-products-low-stock"[^>]*>\\s*1\\s*</', $content);
    }

    public function test_dashboard_shows_total_inventory_value(): void
    {
        config(['gatic.inventory.money.default_currency' => 'MXN']);
        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '10000.00',
            'acquisition_currency' => 'MXN',
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_ASSIGNED,
            'acquisition_cost' => '5000.50',
            'acquisition_currency' => 'MXN',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Valor del Inventario');
        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/data-testid="dashboard-metric-total-inventory-value"[^>]*>[^<]*15,000\.50 MXN/', $content);
    }

    public function test_dashboard_total_value_excludes_retired_assets(): void
    {
        config(['gatic.inventory.money.default_currency' => 'MXN']);
        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        // Active asset (should count)
        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '10000.00',
            'acquisition_currency' => 'MXN',
        ]);

        // Retired asset (should NOT count by default)
        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_RETIRED,
            'acquisition_cost' => '5000.00',
            'acquisition_currency' => 'MXN',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();

        $content = $response->getContent();
        // Should be 10000.00, not 15000.00
        $this->assertMatchesRegularExpression('/data-testid="dashboard-metric-total-inventory-value"[^>]*>[^<]*10,000\.00 MXN/', $content);
    }

    public function test_dashboard_total_value_excludes_soft_deleted_assets(): void
    {
        config(['gatic.inventory.money.default_currency' => 'MXN']);
        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        // Active asset (should count)
        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '8000.00',
            'acquisition_currency' => 'MXN',
        ]);

        // Soft-deleted asset (should NOT count)
        $deletedAsset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '7000.00',
            'acquisition_currency' => 'MXN',
        ]);
        $deletedAsset->delete();

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();

        $content = $response->getContent();
        // Should be 8000.00, not 15000.00
        $this->assertMatchesRegularExpression('/data-testid="dashboard-metric-total-inventory-value"[^>]*>[^<]*8,000\.00 MXN/', $content);
    }

    public function test_dashboard_shows_value_breakdown_by_category(): void
    {
        config(['gatic.inventory.money.default_currency' => 'MXN']);
        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $categoryLaptops = Category::factory()->create(['name' => 'Laptops', 'is_serialized' => true]);
        $categoryMonitors = Category::factory()->create(['name' => 'Monitors', 'is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();

        $productLaptop = Product::factory()->create([
            'category_id' => $categoryLaptops->id,
            'brand_id' => $brand->id,
        ]);
        $productMonitor = Product::factory()->create([
            'category_id' => $categoryMonitors->id,
            'brand_id' => $brand->id,
        ]);

        Asset::factory()->create([
            'product_id' => $productLaptop->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '20000.00',
            'acquisition_currency' => 'MXN',
        ]);

        Asset::factory()->create([
            'product_id' => $productMonitor->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '5000.00',
            'acquisition_currency' => 'MXN',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Valor por Categoría');
        $response->assertSee('Laptops');
        $response->assertSee('Monitors');
        $response->assertSee('data-testid="dashboard-value-by-category"', false);
    }

    public function test_dashboard_shows_value_breakdown_by_brand(): void
    {
        config(['gatic.inventory.money.default_currency' => 'MXN']);
        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $brandDell = Brand::factory()->create(['name' => 'Dell']);
        $brandHP = Brand::factory()->create(['name' => 'HP']);
        $location = Location::factory()->create();

        $productDell = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brandDell->id,
        ]);
        $productHP = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brandHP->id,
        ]);

        Asset::factory()->create([
            'product_id' => $productDell->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '18000.00',
            'acquisition_currency' => 'MXN',
        ]);

        Asset::factory()->create([
            'product_id' => $productHP->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '12000.00',
            'acquisition_currency' => 'MXN',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Valor por Marca');
        $response->assertSee('Dell');
        $response->assertSee('HP');
        $response->assertSee('data-testid="dashboard-value-by-brand"', false);
    }

    public function test_dashboard_value_breakdown_handles_null_brand(): void
    {
        config(['gatic.inventory.money.default_currency' => 'MXN']);
        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $location = Location::factory()->create();

        $productNoBrand = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => null,
        ]);

        Asset::factory()->create([
            'product_id' => $productNoBrand->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '5000.00',
            'acquisition_currency' => 'MXN',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Sin marca');
    }

    public function test_dashboard_hides_inventory_value_for_lector(): void
    {
        config(['gatic.inventory.money.default_currency' => 'MXN']);
        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Lector]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('Valor del Inventario');
        $response->assertDontSee('dashboard-metric-total-inventory-value');
    }

    public function test_dashboard_value_breakdown_adds_otros_when_more_than_top_n(): void
    {
        config([
            'gatic.inventory.money.default_currency' => 'MXN',
            'gatic.dashboard.value.top_n' => 1,
        ]);

        $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Admin]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();
        $categoryA = Category::factory()->create(['name' => 'Cat A', 'is_serialized' => true]);
        $categoryB = Category::factory()->create(['name' => 'Cat B', 'is_serialized' => true]);

        $productA = Product::factory()->create(['category_id' => $categoryA->id, 'brand_id' => $brand->id]);
        $productB = Product::factory()->create(['category_id' => $categoryB->id, 'brand_id' => $brand->id]);

        Asset::factory()->create([
            'product_id' => $productA->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '20000.00',
            'acquisition_currency' => 'MXN',
        ]);

        Asset::factory()->create([
            'product_id' => $productB->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '5000.00',
            'acquisition_currency' => 'MXN',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Otros');
        $response->assertSee('data-testid="dashboard-value-by-category"', false);
    }
}
