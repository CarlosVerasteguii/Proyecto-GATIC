<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Support\Dashboard\InventoryValueReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryValueReaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reads_inventory_value_with_dashboard_filters_and_default_currency_scope(): void
    {
        $locationIncluded = Location::factory()->create();
        $locationExcluded = Location::factory()->create();
        $categoryIncluded = Category::factory()->create(['name' => 'Laptops', 'is_serialized' => true]);
        $categoryExcluded = Category::factory()->create(['name' => 'Monitors', 'is_serialized' => true]);
        $brandIncluded = Brand::factory()->create(['name' => 'Dell']);
        $brandExcluded = Brand::factory()->create(['name' => 'HP']);

        $includedProduct = Product::factory()->create([
            'category_id' => $categoryIncluded->id,
            'brand_id' => $brandIncluded->id,
        ]);
        $otherCategoryProduct = Product::factory()->create([
            'category_id' => $categoryExcluded->id,
            'brand_id' => $brandIncluded->id,
        ]);
        $otherBrandProduct = Product::factory()->create([
            'category_id' => $categoryIncluded->id,
            'brand_id' => $brandExcluded->id,
        ]);

        Asset::factory()->create([
            'product_id' => $includedProduct->id,
            'location_id' => $locationIncluded->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '100.00',
            'acquisition_currency' => 'MXN',
        ]);
        Asset::factory()->create([
            'product_id' => $includedProduct->id,
            'location_id' => $locationIncluded->id,
            'status' => Asset::STATUS_ASSIGNED,
            'acquisition_cost' => '50.00',
            'acquisition_currency' => null,
        ]);

        Asset::factory()->create([
            'product_id' => $includedProduct->id,
            'location_id' => $locationIncluded->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '999.00',
            'acquisition_currency' => 'USD',
        ]);
        Asset::factory()->create([
            'product_id' => $includedProduct->id,
            'location_id' => $locationIncluded->id,
            'status' => Asset::STATUS_RETIRED,
            'acquisition_cost' => '500.00',
            'acquisition_currency' => 'MXN',
        ]);
        $deletedAsset = Asset::factory()->create([
            'product_id' => $includedProduct->id,
            'location_id' => $locationIncluded->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '400.00',
            'acquisition_currency' => 'MXN',
        ]);
        $deletedAsset->delete();

        Asset::factory()->create([
            'product_id' => $includedProduct->id,
            'location_id' => $locationExcluded->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '300.00',
            'acquisition_currency' => 'MXN',
        ]);
        Asset::factory()->create([
            'product_id' => $otherCategoryProduct->id,
            'location_id' => $locationIncluded->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '200.00',
            'acquisition_currency' => 'MXN',
        ]);
        Asset::factory()->create([
            'product_id' => $otherBrandProduct->id,
            'location_id' => $locationIncluded->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '150.00',
            'acquisition_currency' => 'MXN',
        ]);

        $result = app(InventoryValueReader::class)->get(
            locationId: $locationIncluded->id,
            categoryId: $categoryIncluded->id,
            brandId: $brandIncluded->id,
            defaultCurrency: 'MXN',
            topN: 5,
        );

        $this->assertSame(150.0, $result['totalValue']);
        $this->assertSame([
            ['id' => $categoryIncluded->id, 'name' => 'Laptops', 'total' => 150.0],
        ], $result['categoryRows']);
        $this->assertSame(1, $result['categoriesCount']);
        $this->assertSame([
            ['id' => $brandIncluded->id, 'name' => 'Dell', 'total' => 150.0],
        ], $result['brandRows']);
        $this->assertSame(1, $result['brandsCount']);
    }

    public function test_it_groups_missing_brands_as_sin_marca_and_returns_distinct_breakdown_counts(): void
    {
        $location = Location::factory()->create();
        $categoryLaptops = Category::factory()->create(['name' => 'Laptops', 'is_serialized' => true]);
        $categoryMonitors = Category::factory()->create(['name' => 'Monitors', 'is_serialized' => true]);
        $categoryDocks = Category::factory()->create(['name' => 'Docks', 'is_serialized' => true]);
        $categoryKeyboards = Category::factory()->create(['name' => 'Keyboards', 'is_serialized' => true]);
        $brandDell = Brand::factory()->create(['name' => 'Dell']);
        $brandHp = Brand::factory()->create(['name' => 'HP']);

        $productDell = Product::factory()->create([
            'category_id' => $categoryLaptops->id,
            'brand_id' => $brandDell->id,
        ]);
        $productNoBrandMonitors = Product::factory()->create([
            'category_id' => $categoryMonitors->id,
            'brand_id' => null,
        ]);
        $productNoBrandDocks = Product::factory()->create([
            'category_id' => $categoryDocks->id,
            'brand_id' => null,
        ]);
        $productHp = Product::factory()->create([
            'category_id' => $categoryKeyboards->id,
            'brand_id' => $brandHp->id,
        ]);

        Asset::factory()->create([
            'product_id' => $productDell->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '200.00',
            'acquisition_currency' => 'MXN',
        ]);
        Asset::factory()->create([
            'product_id' => $productNoBrandMonitors->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '70.00',
            'acquisition_currency' => 'MXN',
        ]);
        Asset::factory()->create([
            'product_id' => $productNoBrandDocks->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '30.00',
            'acquisition_currency' => 'MXN',
        ]);
        Asset::factory()->create([
            'product_id' => $productHp->id,
            'location_id' => $location->id,
            'status' => Asset::STATUS_AVAILABLE,
            'acquisition_cost' => '50.00',
            'acquisition_currency' => 'MXN',
        ]);

        $result = app(InventoryValueReader::class)->get(
            locationId: null,
            categoryId: null,
            brandId: null,
            defaultCurrency: 'MXN',
            topN: 2,
        );

        $this->assertSame(350.0, $result['totalValue']);
        $this->assertSame([
            ['id' => $categoryLaptops->id, 'name' => 'Laptops', 'total' => 200.0],
            ['id' => $categoryMonitors->id, 'name' => 'Monitors', 'total' => 70.0],
        ], $result['categoryRows']);
        $this->assertSame(4, $result['categoriesCount']);
        $this->assertSame([
            ['id' => $brandDell->id, 'name' => 'Dell', 'total' => 200.0],
            ['id' => null, 'name' => 'Sin marca', 'total' => 100.0],
        ], $result['brandRows']);
        $this->assertSame(3, $result['brandsCount']);
    }
}
