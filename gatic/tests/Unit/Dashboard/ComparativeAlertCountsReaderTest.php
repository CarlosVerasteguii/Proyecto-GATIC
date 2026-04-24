<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Support\Dashboard\AlertComparativePeriodResolver;
use App\Support\Dashboard\ComparativeAlertCountsReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ComparativeAlertCountsReaderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function it_reads_the_four_comparative_counts_from_the_received_base_query(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 17, 12, 0, 0));

        [$product, $location] = $this->createSerializedAssetContext();

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_LOANED,
            'loan_due_date' => Carbon::create(2026, 1, 15),
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_LOANED,
            'loan_due_date' => Carbon::create(2026, 1, 16),
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_LOANED,
            'loan_due_date' => Carbon::create(2026, 1, 17),
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_LOANED,
            'loan_due_date' => Carbon::create(2026, 1, 23),
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_LOANED,
            'loan_due_date' => Carbon::create(2026, 1, 24),
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_LOANED,
            'loan_due_date' => Carbon::create(2026, 1, 25),
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_ASSIGNED,
            'loan_due_date' => Carbon::create(2026, 1, 15),
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_LOANED,
            'loan_due_date' => null,
        ]);

        $counts = app(ComparativeAlertCountsReader::class)->get(
            baseQuery: Asset::query()
                ->where('status', Asset::STATUS_LOANED)
                ->whereNotNull('loan_due_date'),
            dateColumn: 'loan_due_date',
            comparativePeriod: (new AlertComparativePeriodResolver)->resolve(windowDays: 7),
        );

        $this->assertSame([
            'currentOverdue' => 2,
            'previousOverdue' => 1,
            'currentDueSoon' => 3,
            'previousDueSoon' => 3,
        ], $counts);
    }

    #[Test]
    public function it_uses_the_received_date_column_for_the_comparative_counts(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 17, 12, 0, 0));

        [$product, $location] = $this->createSerializedAssetContext();

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'loan_due_date' => Carbon::create(2026, 1, 1),
            'expected_replacement_date' => Carbon::create(2026, 1, 15),
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'loan_due_date' => Carbon::create(2026, 1, 1),
            'expected_replacement_date' => Carbon::create(2026, 1, 17),
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'loan_due_date' => Carbon::create(2026, 1, 1),
            'expected_replacement_date' => Carbon::create(2026, 1, 24),
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_RETIRED,
            'loan_due_date' => Carbon::create(2026, 1, 1),
            'expected_replacement_date' => Carbon::create(2026, 1, 15),
        ]);

        $counts = app(ComparativeAlertCountsReader::class)->get(
            baseQuery: Asset::query()
                ->whereNotNull('expected_replacement_date')
                ->where('status', '!=', Asset::STATUS_RETIRED),
            dateColumn: 'expected_replacement_date',
            comparativePeriod: (new AlertComparativePeriodResolver)->resolve(windowDays: 7),
        );

        $this->assertSame([
            'currentOverdue' => 1,
            'previousOverdue' => 1,
            'currentDueSoon' => 2,
            'previousDueSoon' => 1,
        ], $counts);
    }

    /**
     * @return array{0: Product, 1: Location}
     */
    private function createSerializedAssetContext(): array
    {
        $category = Category::factory()->create(['is_serialized' => true]);
        $brand = Brand::factory()->create();
        $location = Location::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);

        return [$product, $location];
    }
}
