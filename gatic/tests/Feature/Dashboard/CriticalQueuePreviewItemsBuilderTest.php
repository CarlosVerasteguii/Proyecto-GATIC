<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Support\Dashboard\CriticalQueuePreviewItemsBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CriticalQueuePreviewItemsBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function it_builds_ranked_critical_queue_rows_from_filtered_dashboard_queries(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 17, 12, 0, 0));

        $location = Location::factory()->create(['name' => 'TI']);
        $employee = Employee::factory()->create([
            'rpe' => 'RPE-01',
            'name' => 'Ana Ruiz',
        ]);
        $serializedCategory = Category::factory()->create(['is_serialized' => true]);
        $quantityCategory = Category::factory()->create([
            'is_serialized' => false,
            'requires_asset_tag' => false,
        ]);
        $brand = Brand::factory()->create(['name' => 'Dell']);

        $assetProduct = Product::factory()->create([
            'category_id' => $serializedCategory->id,
            'brand_id' => $brand->id,
            'name' => 'Laptop',
        ]);

        $asset = Asset::factory()->create([
            'product_id' => $assetProduct->id,
            'location_id' => $location->id,
            'current_employee_id' => $employee->id,
            'status' => Asset::STATUS_LOANED,
            'serial' => 'LAP-001',
            'asset_tag' => 'TAG-001',
            'loan_due_date' => Carbon::today()->subDays(2),
        ]);

        $lowStockProduct = Product::factory()->create([
            'category_id' => $quantityCategory->id,
            'brand_id' => $brand->id,
            'name' => 'Mouse',
            'qty_total' => 1,
            'low_stock_threshold' => 4,
        ]);

        $preview = app(CriticalQueuePreviewItemsBuilder::class)->build(
            assetQuery: Asset::query()->where('location_id', $location->id),
            productQuery: Product::query()->where('brand_id', $brand->id),
            today: Carbon::today(),
            returnTo: '/dashboard?location='.$location->id,
            limit: 10,
            loansOverdueCount: 1,
            warrantiesExpiredCount: 0,
            renewalsOverdueCount: 0,
            lowStockProductsCount: 1,
        );

        $this->assertSame([
            [
                'type' => 'Préstamo vencido',
                'variant' => 'danger',
                'icon' => 'bi-clock-history',
                'title' => 'LAP-001 (TAG-001)',
                'subtitle' => 'Laptop',
                'detail' => '15/01/2026',
                'detailHint' => 'Hace 2d',
                'location' => 'TI',
                'actor' => 'RPE-01 - Ana Ruiz',
                'href' => route('inventory.products.assets.show', [
                    'product' => $assetProduct->id,
                    'asset' => $asset->id,
                    'returnTo' => '/dashboard?location='.$location->id,
                ]),
            ],
            [
                'type' => 'Stock bajo',
                'variant' => 'warning',
                'icon' => 'bi-box-seam',
                'title' => 'Mouse',
                'subtitle' => 'Dell',
                'detail' => '1 / 4',
                'detailHint' => 'Faltan 3',
                'location' => null,
                'actor' => null,
                'href' => route('inventory.products.show', [
                    'product' => $lowStockProduct->id,
                ]),
            ],
        ], $preview);
    }
}
