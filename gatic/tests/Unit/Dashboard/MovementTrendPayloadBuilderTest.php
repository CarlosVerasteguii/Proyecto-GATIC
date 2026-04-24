<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Models\AssetMovement;
use App\Models\ProductQuantityMovement;
use App\Support\Dashboard\MovementTrendPayloadBuilder;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MovementTrendPayloadBuilderTest extends TestCase
{
    #[Test]
    public function it_builds_a_zero_filled_payload_for_every_day_in_the_requested_range(): void
    {
        $payload = app(MovementTrendPayloadBuilder::class)->build(
            rangeDays: 3,
            assetRows: [],
            quantityRows: [],
            today: Carbon::create(2026, 1, 17, 12, 0, 0),
        );

        $this->assertSame([
            'labels' => ['2026-01-15', '2026-01-16', '2026-01-17'],
            'datasets' => [
                ['key' => 'assets_assigned_ops', 'label' => 'Asignaciones', 'data' => [0, 0, 0]],
                ['key' => 'assets_loan_ops', 'label' => 'Préstamos', 'data' => [0, 0, 0]],
                ['key' => 'qty_out_ops', 'label' => 'Cantidad salida', 'data' => [0, 0, 0]],
                ['key' => 'qty_in_ops', 'label' => 'Cantidad entrada', 'data' => [0, 0, 0]],
            ],
        ], $payload);
    }

    #[Test]
    public function it_maps_aggregated_rows_into_the_existing_chart_series(): void
    {
        $payload = app(MovementTrendPayloadBuilder::class)->build(
            rangeDays: 3,
            assetRows: [
                ['day' => '2026-01-15', 'type' => AssetMovement::TYPE_ASSIGN, 'total' => 2],
                ['day' => '2026-01-15', 'type' => AssetMovement::TYPE_UNASSIGN, 'total' => 1],
                ['day' => '2026-01-16', 'type' => AssetMovement::TYPE_LOAN, 'total' => 4],
                ['day' => '2026-01-16', 'type' => AssetMovement::TYPE_RETURN, 'total' => 1],
                ['day' => '2026-01-16', 'type' => 'unexpected', 'total' => 99],
                ['day' => '2026-01-18', 'type' => AssetMovement::TYPE_ASSIGN, 'total' => 99],
            ],
            quantityRows: [
                ['day' => '2026-01-16', 'direction' => ProductQuantityMovement::DIRECTION_OUT, 'total' => 6],
                ['day' => '2026-01-17', 'direction' => ProductQuantityMovement::DIRECTION_IN, 'total' => 7],
                ['day' => '2026-01-17', 'direction' => 'unexpected', 'total' => 99],
                ['day' => '2026-01-14', 'direction' => ProductQuantityMovement::DIRECTION_OUT, 'total' => 99],
            ],
            today: Carbon::create(2026, 1, 17, 12, 0, 0),
        );

        $this->assertSame([
            'labels' => ['2026-01-15', '2026-01-16', '2026-01-17'],
            'datasets' => [
                ['key' => 'assets_assigned_ops', 'label' => 'Asignaciones', 'data' => [3, 0, 0]],
                ['key' => 'assets_loan_ops', 'label' => 'Préstamos', 'data' => [0, 5, 0]],
                ['key' => 'qty_out_ops', 'label' => 'Cantidad salida', 'data' => [0, 6, 0]],
                ['key' => 'qty_in_ops', 'label' => 'Cantidad entrada', 'data' => [0, 0, 7]],
            ],
        ], $payload);
    }
}
