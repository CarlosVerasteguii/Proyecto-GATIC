<?php

namespace Tests\Feature\Alerts;

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

class AlertsUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_access_all_alert_routes(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Editor,
        ]);

        foreach ($this->alertRoutes() as $route) {
            $this->actingAs($user)
                ->get($route)
                ->assertOk();
        }
    }

    public function test_lector_cannot_access_alert_routes(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Lector,
        ]);

        foreach ($this->alertRoutes() as $route) {
            $this->actingAs($user)
                ->get($route)
                ->assertForbidden();
        }
    }

    public function test_alert_routes_render_expected_empty_states(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Editor,
        ]);

        $expectations = [
            route('alerts.loans.index') => 'Sin préstamos vencidos',
            route('alerts.warranties.index') => 'Sin garantías vencidas',
            route('alerts.renewals.index') => 'Sin renovaciones vencidas',
            route('alerts.stock.index') => 'Sin alertas de stock bajo',
        ];

        foreach ($expectations as $route => $expectedText) {
            $this->actingAs($user)
                ->get($route)
                ->assertOk()
                ->assertSee($expectedText);
        }
    }

    public function test_loan_alerts_show_active_filter_context_for_due_soon_mode(): void
    {
        Carbon::setTestNow('2026-03-06');

        try {
            $user = User::factory()->create([
                'is_active' => true,
                'role' => UserRole::Editor,
            ]);

            $location = Location::factory()->create(['name' => 'Centro']);
            $category = Category::factory()->create([
                'name' => 'Laptops',
                'is_serialized' => true,
                'requires_asset_tag' => true,
            ]);
            $brand = Brand::factory()->create(['name' => 'Lenovo']);
            $product = Product::factory()->create([
                'name' => 'ThinkPad T14',
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'qty_total' => null,
                'low_stock_threshold' => null,
            ]);

            Asset::factory()->create([
                'product_id' => $product->id,
                'location_id' => $location->id,
                'serial' => 'LN-001',
                'asset_tag' => 'GATIC-001',
                'status' => Asset::STATUS_LOANED,
                'loan_due_date' => Carbon::today()->addDays(5),
            ]);

            $this->actingAs($user)
                ->get(route('alerts.loans.index', [
                    'type' => 'due-soon',
                    'windowDays' => 7,
                    'location' => $location->id,
                    'category' => $category->id,
                    'brand' => $brand->id,
                ]))
                ->assertOk()
                ->assertSee('Por vencer')
                ->assertSee('Ventana')
                ->assertSee('Filtros activos:')
                ->assertSee('Ubicación: Centro')
                ->assertSee('Categoría: Laptops')
                ->assertSee('Marca: Lenovo')
                ->assertSee('ThinkPad T14');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_low_stock_alerts_show_active_filter_context(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Editor,
        ]);

        $category = Category::factory()->create([
            'name' => 'Consumibles',
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);
        $brand = Brand::factory()->create(['name' => 'Brother']);
        Product::factory()->create([
            'name' => 'Toner TN-760',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'qty_total' => 2,
            'low_stock_threshold' => 5,
        ]);

        $this->actingAs($user)
            ->get(route('alerts.stock.index', [
                'category' => $category->id,
                'brand' => $brand->id,
            ]))
            ->assertOk()
            ->assertSee('Alertas de stock bajo')
            ->assertSee('Filtros activos:')
            ->assertSee('Categoría: Consumibles')
            ->assertSee('Marca: Brother')
            ->assertSee('Toner TN-760');
    }

    /**
     * @return list<string>
     */
    private function alertRoutes(): array
    {
        return [
            route('alerts.loans.index'),
            route('alerts.warranties.index'),
            route('alerts.renewals.index'),
            route('alerts.stock.index'),
        ];
    }
}
