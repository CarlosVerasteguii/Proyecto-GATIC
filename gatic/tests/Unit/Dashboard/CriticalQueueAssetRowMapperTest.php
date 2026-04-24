<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Models\Asset;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Product;
use App\Support\Dashboard\CriticalQueueAssetRowMapper;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CriticalQueueAssetRowMapperTest extends TestCase
{
    #[Test]
    public function it_maps_the_shared_fields_for_an_overdue_asset_row(): void
    {
        $asset = Asset::factory()->make([
            'id' => 44,
            'product_id' => 21,
            'serial' => 'LAP-001',
            'asset_tag' => 'TAG-001',
        ]);
        $asset->setRelation('product', Product::factory()->make(['name' => 'Laptop']));
        $asset->setRelation('location', Location::factory()->make(['name' => 'TI']));

        $dueDate = CarbonImmutable::parse('2026-01-12');
        $href = route('inventory.products.assets.show', [
            'product' => 21,
            'asset' => 44,
            'returnTo' => '/dashboard?location=3',
        ]);
        $actor = Employee::factory()->make([
            'rpe' => 'RPE-01',
            'name' => 'Ana Ruiz',
        ])->full_name;

        $row = app(CriticalQueueAssetRowMapper::class)->mapOverdueRow(
            asset: $asset,
            dueDate: $dueDate,
            overdueDays: 5,
            href: $href,
            actor: $actor,
        );

        $this->assertSame([
            'title' => 'LAP-001 (TAG-001)',
            'subtitle' => 'Laptop',
            'detail' => '12/01/2026',
            'detailHint' => 'Hace 5d',
            'location' => 'TI',
            'actor' => 'RPE-01 - Ana Ruiz',
            'href' => $href,
        ], $row);
    }

    #[Test]
    public function it_keeps_optional_shared_fields_nullable_and_uses_serial_when_asset_tag_is_blank(): void
    {
        $asset = new Asset([
            'serial' => 'LAP-002',
            'asset_tag' => null,
        ]);

        $row = app(CriticalQueueAssetRowMapper::class)->mapOverdueRow(
            asset: $asset,
            dueDate: null,
            overdueDays: null,
            href: '/inventory/products/21/assets/45',
        );

        $this->assertSame([
            'title' => 'LAP-002',
            'subtitle' => null,
            'detail' => null,
            'detailHint' => null,
            'location' => null,
            'actor' => null,
            'href' => '/inventory/products/21/assets/45',
        ], $row);
    }
}
