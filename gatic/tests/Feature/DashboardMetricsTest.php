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
}
