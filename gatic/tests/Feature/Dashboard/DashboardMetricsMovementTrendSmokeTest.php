<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\UserRole;
use App\Livewire\Dashboard\DashboardMetrics;
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
use Livewire\Livewire;
use Tests\TestCase;

class DashboardMetricsMovementTrendSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_dispatches_movement_trend_chart_payload(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 17, 12, 0, 0));

        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Admin,
        ]);
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

        AssetMovement::factory()->create([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $user->id,
            'type' => AssetMovement::TYPE_ASSIGN,
            'created_at' => Carbon::today()->setTime(9, 0),
        ]);
        ProductQuantityMovement::factory()->create([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $user->id,
            'direction' => ProductQuantityMovement::DIRECTION_OUT,
            'created_at' => Carbon::today()->setTime(10, 0),
        ]);

        Livewire::actingAs($user)
            ->test(DashboardMetrics::class)
            ->set('trendRangeDays', 7)
            ->assertSet('trendRangeDays', 7)
            ->assertDispatched('dashboard:charts', function (string $name, array $params): bool {
                $charts = $params['charts'] ?? null;

                if (! is_array($charts) || ($charts['rangeDays'] ?? null) !== 7) {
                    return false;
                }

                $movementTrend = $charts['movementTrend'] ?? null;

                if (! is_array($movementTrend) || count($movementTrend['labels'] ?? []) !== 7) {
                    return false;
                }

                $datasets = [];

                foreach ($movementTrend['datasets'] ?? [] as $dataset) {
                    if (is_array($dataset) && isset($dataset['key']) && is_string($dataset['key'])) {
                        $datasets[$dataset['key']] = $dataset['data'] ?? null;
                    }
                }

                return $name === 'dashboard:charts'
                    && ($movementTrend['labels'][6] ?? null) === '2026-01-17'
                    && ($datasets['assets_assigned_ops'][6] ?? null) === 1
                    && ($datasets['qty_out_ops'][6] ?? null) === 1
                    && ($datasets['assets_loan_ops'][6] ?? null) === 0
                    && ($datasets['qty_in_ops'][6] ?? null) === 0;
            });
    }
}
