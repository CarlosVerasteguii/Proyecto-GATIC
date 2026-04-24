<?php

declare(strict_types=1);

namespace App\Support\Dashboard;

use App\Models\AssetMovement;
use App\Models\ProductQuantityMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * @phpstan-import-type MovementTrendAssetRow from \App\Support\Dashboard\MovementTrendPayloadBuilder
 * @phpstan-import-type MovementTrendQuantityRow from \App\Support\Dashboard\MovementTrendPayloadBuilder
 *
 * @phpstan-type MovementTrendRows array{
 *     assetRows: list<MovementTrendAssetRow>,
 *     quantityRows: list<MovementTrendQuantityRow>
 * }
 */
final class MovementTrendRowsReader
{
    /**
     * @param  Builder<AssetMovement>  $assetMovementsQuery
     * @param  Builder<ProductQuantityMovement>  $quantityMovementsQuery
     * @return MovementTrendRows
     */
    public function get(
        Builder $assetMovementsQuery,
        Builder $quantityMovementsQuery,
        Carbon $start,
        Carbon $end,
    ): array {
        return [
            'assetRows' => $this->getAssetRows($assetMovementsQuery, $start, $end),
            'quantityRows' => $this->getQuantityRows($quantityMovementsQuery, $start, $end),
        ];
    }

    /**
     * @param  Builder<AssetMovement>  $query
     * @return list<MovementTrendAssetRow>
     */
    private function getAssetRows(Builder $query, Carbon $start, Carbon $end): array
    {
        return (clone $query)
            ->selectRaw('DATE(asset_movements.created_at) as day, asset_movements.type as type, COUNT(*) as total')
            ->whereBetween('asset_movements.created_at', [$start, $end])
            ->whereIn('asset_movements.type', AssetMovement::TYPES)
            ->groupByRaw('DATE(asset_movements.created_at), asset_movements.type')
            ->get()
            ->map(static fn (object $row): array => [
                'day' => (string) ($row->day ?? ''),
                'type' => (string) ($row->type ?? ''),
                'total' => (int) ($row->total ?? 0),
            ])
            ->all();
    }

    /**
     * @param  Builder<ProductQuantityMovement>  $query
     * @return list<MovementTrendQuantityRow>
     */
    private function getQuantityRows(Builder $query, Carbon $start, Carbon $end): array
    {
        return (clone $query)
            ->selectRaw('DATE(product_quantity_movements.created_at) as day, product_quantity_movements.direction as direction, COUNT(*) as total')
            ->whereBetween('product_quantity_movements.created_at', [$start, $end])
            ->whereIn('product_quantity_movements.direction', ProductQuantityMovement::DIRECTIONS)
            ->groupByRaw('DATE(product_quantity_movements.created_at), product_quantity_movements.direction')
            ->get()
            ->map(static fn (object $row): array => [
                'day' => (string) ($row->day ?? ''),
                'direction' => (string) ($row->direction ?? ''),
                'total' => (int) ($row->total ?? 0),
            ])
            ->all();
    }
}
