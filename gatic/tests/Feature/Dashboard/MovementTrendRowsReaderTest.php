<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use App\Models\User;
use App\Support\Dashboard\MovementTrendRowsReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MovementTrendRowsReaderTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_reads_daily_aggregated_rows_for_asset_and_quantity_movements(): void
    {
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
        $actor = User::factory()->create();

        AssetMovement::factory()->create([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $actor->id,
            'type' => AssetMovement::TYPE_ASSIGN,
            'created_at' => Carbon::parse('2026-01-15 09:00:00'),
        ]);
        AssetMovement::factory()->create([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $actor->id,
            'type' => AssetMovement::TYPE_ASSIGN,
            'created_at' => Carbon::parse('2026-01-15 11:00:00'),
        ]);
        AssetMovement::factory()->create([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $actor->id,
            'type' => AssetMovement::TYPE_RETURN,
            'created_at' => Carbon::parse('2026-01-16 14:00:00'),
        ]);
        AssetMovement::factory()->create([
            'asset_id' => $asset->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $actor->id,
            'type' => AssetMovement::TYPE_LOAN,
            'created_at' => Carbon::parse('2026-01-18 10:00:00'),
        ]);

        ProductQuantityMovement::factory()->create([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $actor->id,
            'direction' => ProductQuantityMovement::DIRECTION_OUT,
            'created_at' => Carbon::parse('2026-01-16 08:00:00'),
        ]);
        ProductQuantityMovement::factory()->create([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $actor->id,
            'direction' => ProductQuantityMovement::DIRECTION_IN,
            'created_at' => Carbon::parse('2026-01-17 12:30:00'),
        ]);
        ProductQuantityMovement::factory()->create([
            'product_id' => $product->id,
            'employee_id' => $employee->id,
            'actor_user_id' => $actor->id,
            'direction' => ProductQuantityMovement::DIRECTION_OUT,
            'created_at' => Carbon::parse('2026-01-14 16:00:00'),
        ]);

        $rows = app(MovementTrendRowsReader::class)->get(
            assetMovementsQuery: AssetMovement::query(),
            quantityMovementsQuery: ProductQuantityMovement::query(),
            start: Carbon::parse('2026-01-15')->startOfDay(),
            end: Carbon::parse('2026-01-17')->endOfDay(),
        );

        $assetRows = $rows['assetRows'];
        usort($assetRows, static fn (array $left, array $right): int => strcmp(
            $left['day'].'|'.$left['type'],
            $right['day'].'|'.$right['type'],
        ));

        $quantityRows = $rows['quantityRows'];
        usort($quantityRows, static fn (array $left, array $right): int => strcmp(
            $left['day'].'|'.$left['direction'],
            $right['day'].'|'.$right['direction'],
        ));

        $this->assertSame([
            ['day' => '2026-01-15', 'type' => AssetMovement::TYPE_ASSIGN, 'total' => 2],
            ['day' => '2026-01-16', 'type' => AssetMovement::TYPE_RETURN, 'total' => 1],
        ], $assetRows);

        $this->assertSame([
            ['day' => '2026-01-16', 'direction' => ProductQuantityMovement::DIRECTION_OUT, 'total' => 1],
            ['day' => '2026-01-17', 'direction' => ProductQuantityMovement::DIRECTION_IN, 'total' => 1],
        ], $quantityRows);
    }
}
