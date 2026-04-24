<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\UserRole;
use App\Livewire\Dashboard\DashboardMetrics;
use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardMetricsCriticalQueueSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function it_populates_the_critical_queue_preview_in_ranked_order(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 17, 12, 0, 0));

        $user = User::factory()->create([
            'is_active' => true,
            'role' => UserRole::Admin,
        ]);
        $category = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create(['name' => 'TI']);
        $employee = Employee::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => 'Laptop',
        ]);

        $recentlyOverdueAsset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'current_employee_id' => $employee->id,
            'status' => Asset::STATUS_LOANED,
            'serial' => 'LAP-001',
            'asset_tag' => 'TAG-001',
            'loan_due_date' => Carbon::today()->subDay(),
        ]);

        $olderOverdueAsset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'current_employee_id' => $employee->id,
            'status' => Asset::STATUS_LOANED,
            'serial' => 'LAP-002',
            'asset_tag' => 'TAG-002',
            'loan_due_date' => Carbon::today()->subDays(5),
        ]);

        $component = Livewire::actingAs($user)->test(DashboardMetrics::class);

        $this->assertSame([
            [
                'type' => 'Préstamo vencido',
                'variant' => 'danger',
                'icon' => 'bi-clock-history',
                'title' => 'LAP-002 (TAG-002)',
                'subtitle' => 'Laptop',
                'detail' => '12/01/2026',
                'detailHint' => 'Hace 5d',
                'location' => 'TI',
                'actor' => $employee->full_name,
                'href' => route('inventory.products.assets.show', [
                    'product' => $product->id,
                    'asset' => $olderOverdueAsset->id,
                    'returnTo' => '/dashboard',
                ]),
            ],
            [
                'type' => 'Préstamo vencido',
                'variant' => 'danger',
                'icon' => 'bi-clock-history',
                'title' => 'LAP-001 (TAG-001)',
                'subtitle' => 'Laptop',
                'detail' => '16/01/2026',
                'detailHint' => 'Hace 1d',
                'location' => 'TI',
                'actor' => $employee->full_name,
                'href' => route('inventory.products.assets.show', [
                    'product' => $product->id,
                    'asset' => $recentlyOverdueAsset->id,
                    'returnTo' => '/dashboard',
                ]),
            ],
        ], $component->get('criticalQueue'));
    }
}
