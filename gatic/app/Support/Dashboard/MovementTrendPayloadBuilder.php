<?php

declare(strict_types=1);

namespace App\Support\Dashboard;

use App\Models\AssetMovement;
use App\Models\ProductQuantityMovement;
use Illuminate\Support\Carbon;

/**
 * @phpstan-type MovementTrendAssetRow array{day: string, type: string, total: int}
 * @phpstan-type MovementTrendQuantityRow array{day: string, direction: string, total: int}
 * @phpstan-type MovementTrendDataset array{key: string, label: string, data: list<int>}
 * @phpstan-type MovementTrendPayload array{labels: list<string>, datasets: list<MovementTrendDataset>}
 */
final class MovementTrendPayloadBuilder
{
    /**
     * @param  list<MovementTrendAssetRow>  $assetRows
     * @param  list<MovementTrendQuantityRow>  $quantityRows
     * @return MovementTrendPayload
     */
    public function build(
        int $rangeDays,
        array $assetRows,
        array $quantityRows,
        ?Carbon $today = null,
    ): array {
        $today ??= Carbon::today();

        $start = $today->copy()->subDays($rangeDays - 1)->startOfDay();
        $end = $today->copy()->endOfDay();

        $days = [];
        $dayIndex = [];
        $cursor = $start->copy();
        $index = 0;

        while ($cursor->lte($end)) {
            $day = $cursor->toDateString();
            $days[] = $day;
            $dayIndex[$day] = $index;
            $index++;
            $cursor->addDay();
        }

        $series = [
            'assets_assigned_ops' => array_fill(0, count($days), 0),
            'assets_loan_ops' => array_fill(0, count($days), 0),
            'qty_out_ops' => array_fill(0, count($days), 0),
            'qty_in_ops' => array_fill(0, count($days), 0),
        ];

        foreach ($assetRows as $row) {
            $this->accumulateRow(
                series: $series,
                dayIndex: $dayIndex,
                seriesKey: $this->assetSeriesKey($row['type']),
                day: $row['day'],
                total: $row['total'],
            );
        }

        foreach ($quantityRows as $row) {
            $this->accumulateRow(
                series: $series,
                dayIndex: $dayIndex,
                seriesKey: $this->quantitySeriesKey($row['direction']),
                day: $row['day'],
                total: $row['total'],
            );
        }

        return [
            'labels' => $days,
            'datasets' => [
                ['key' => 'assets_assigned_ops', 'label' => 'Asignaciones', 'data' => $series['assets_assigned_ops']],
                ['key' => 'assets_loan_ops', 'label' => 'Préstamos', 'data' => $series['assets_loan_ops']],
                ['key' => 'qty_out_ops', 'label' => 'Cantidad salida', 'data' => $series['qty_out_ops']],
                ['key' => 'qty_in_ops', 'label' => 'Cantidad entrada', 'data' => $series['qty_in_ops']],
            ],
        ];
    }

    /**
     * @param  array<string, list<int>>  $series
     * @param  array<string, int>  $dayIndex
     */
    private function accumulateRow(array &$series, array $dayIndex, ?string $seriesKey, string $day, int $total): void
    {
        if ($seriesKey === null || $day === '' || ! array_key_exists($day, $dayIndex)) {
            return;
        }

        $series[$seriesKey][$dayIndex[$day]] += $total;
    }

    private function assetSeriesKey(string $type): ?string
    {
        return match ($type) {
            AssetMovement::TYPE_ASSIGN, AssetMovement::TYPE_UNASSIGN => 'assets_assigned_ops',
            AssetMovement::TYPE_LOAN, AssetMovement::TYPE_RETURN => 'assets_loan_ops',
            default => null,
        };
    }

    private function quantitySeriesKey(string $direction): ?string
    {
        return match ($direction) {
            ProductQuantityMovement::DIRECTION_OUT => 'qty_out_ops',
            ProductQuantityMovement::DIRECTION_IN => 'qty_in_ops',
            default => null,
        };
    }
}
