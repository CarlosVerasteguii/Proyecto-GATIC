<?php

declare(strict_types=1);

namespace Tests\Unit\Search;

use App\Actions\Search\SearchInventory;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SearchInventoryTest extends TestCase
{
    private string $connectionName = 'search_inventory_sqlite';

    private string $databasePath = '';

    private string $originalDefaultConnection = '';

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('SearchInventoryTest requiere pdo_sqlite o un entorno con MySQL/Sail disponible.');
        }

        $this->databasePath = tempnam(sys_get_temp_dir(), 'gatic-search-');
        $this->originalDefaultConnection = (string) config('database.default');

        Config::set("database.connections.{$this->connectionName}", [
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        Config::set('database.default', $this->connectionName);

        DB::purge($this->connectionName);
        DB::reconnect($this->connectionName);

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        DB::disconnect($this->connectionName);
        DB::purge($this->connectionName);
        Config::set('database.default', $this->originalDefaultConnection);

        if ($this->databasePath !== '' && file_exists($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    public function test_empty_query_returns_empty_result_contract(): void
    {
        $result = app(SearchInventory::class)->execute('   ');

        $this->assertTrue($result['products']->isEmpty());
        $this->assertTrue($result['assets']->isEmpty());
        $this->assertNull($result['exactMatch']);
    }

    public function test_exact_asset_tag_match_takes_precedence_over_serial_matches(): void
    {
        $productWithTag = $this->createSerializedProduct('Laptop con tag');
        $productWithSerial = $this->createSerializedProduct('Laptop con serial');
        $location = Location::factory()->create();

        $taggedAsset = Asset::factory()->create([
            'product_id' => $productWithTag->id,
            'location_id' => $location->id,
            'serial' => 'SER-001',
            'asset_tag' => 'GATIC-001',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        Asset::factory()->create([
            'product_id' => $productWithSerial->id,
            'location_id' => $location->id,
            'serial' => 'GATIC-001',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $result = app(SearchInventory::class)->execute('  gatic-001  ');

        $this->assertTrue($result['products']->isEmpty());
        $this->assertCount(1, $result['assets']);
        $this->assertSame($taggedAsset->id, $result['exactMatch']?->id);
        $this->assertSame($taggedAsset->id, $result['assets']->first()?->id);
    }

    public function test_ambiguous_serial_match_returns_assets_without_exact_match(): void
    {
        $firstProduct = $this->createSerializedProduct('Laptop A');
        $secondProduct = $this->createSerializedProduct('Laptop B');
        $location = Location::factory()->create();

        $firstAsset = Asset::factory()->create([
            'product_id' => $firstProduct->id,
            'location_id' => $location->id,
            'serial' => 'SHARED-SERIAL',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $secondAsset = Asset::factory()->create([
            'product_id' => $secondProduct->id,
            'location_id' => $location->id,
            'serial' => 'SHARED-SERIAL',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $result = app(SearchInventory::class)->execute('SHARED-SERIAL');
        $matchedIds = $result['assets']->pluck('id')->all();
        sort($matchedIds);
        $expectedIds = [$firstAsset->id, $secondAsset->id];
        sort($expectedIds);

        $this->assertNull($result['exactMatch']);
        $this->assertTrue($result['products']->isEmpty());
        $this->assertSame($expectedIds, $matchedIds);
    }

    public function test_product_search_returns_operational_asset_counts(): void
    {
        $product = $this->createSerializedProduct('Dell Latitude 5520');
        $location = Location::factory()->create();

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'LAT-001',
            'asset_tag' => 'LAT-001',
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'LAT-002',
            'asset_tag' => 'LAT-002',
            'status' => Asset::STATUS_ASSIGNED,
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'LAT-003',
            'asset_tag' => 'LAT-003',
            'status' => Asset::STATUS_PENDING_RETIREMENT,
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'LAT-004',
            'asset_tag' => 'LAT-004',
            'status' => Asset::STATUS_RETIRED,
        ]);

        $result = app(SearchInventory::class)->execute('Dell   Latitude');
        $matchedProduct = $result['products']->sole();

        $this->assertTrue($result['assets']->isEmpty());
        $this->assertNull($result['exactMatch']);
        $this->assertSame($product->id, $matchedProduct->id);
        $this->assertSame(3, (int) $matchedProduct->assets_total);
        $this->assertSame(2, (int) $matchedProduct->assets_unavailable);
    }

    public function test_partial_asset_search_escapes_like_wildcards(): void
    {
        $product = $this->createSerializedProduct('Scanner Zebra');
        $location = Location::factory()->create();

        $matchingAsset = Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SN_ABC_001',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        Asset::factory()->create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'serial' => 'SNXABCX001',
            'asset_tag' => null,
            'status' => Asset::STATUS_AVAILABLE,
        ]);

        $result = app(SearchInventory::class)->execute('SN_ABC');

        $this->assertTrue($result['products']->isEmpty());
        $this->assertNull($result['exactMatch']);
        $this->assertCount(1, $result['assets']);
        $this->assertSame($matchingAsset->id, $result['assets']->sole()->id);
    }

    private function createSchema(): void
    {
        $schema = Schema::connection($this->connectionName);

        $schema->create('brands', function ($table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('categories', function ($table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_serialized')->default(false);
            $table->boolean('requires_asset_tag')->default(false);
            $table->unsignedInteger('default_useful_life_months')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('locations', function ($table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('employees', function ($table): void {
            $table->id();
            $table->string('rpe');
            $table->string('name');
            $table->string('department')->nullable();
            $table->string('job_title')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('products', function ($table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedInteger('qty_total')->nullable();
            $table->unsignedInteger('low_stock_threshold')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $schema->create('assets', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('current_employee_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('warranty_supplier_id')->nullable();
            $table->string('serial');
            $table->string('asset_tag')->nullable();
            $table->string('status');
            $table->date('loan_due_date')->nullable();
            $table->date('warranty_start_date')->nullable();
            $table->date('warranty_end_date')->nullable();
            $table->text('warranty_notes')->nullable();
            $table->decimal('acquisition_cost', 12, 2)->nullable();
            $table->string('acquisition_currency')->nullable();
            $table->unsignedInteger('useful_life_months')->nullable();
            $table->date('expected_replacement_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'serial']);
        });
    }

    private function createSerializedProduct(string $name): Product
    {
        $category = Category::factory()->create([
            'is_serialized' => true,
            'requires_asset_tag' => false,
        ]);

        return Product::factory()->create([
            'name' => $name,
            'category_id' => $category->id,
            'brand_id' => null,
            'qty_total' => null,
        ]);
    }
}
