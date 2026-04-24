<?php

declare(strict_types=1);

namespace App\Support\Dashboard;

use App\Models\Asset;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-import-type InventoryValueBreakdownSourceRow from \App\Support\Dashboard\InventoryValuePayloadBuilder
 *
 * @phpstan-type InventoryValueReadResult array{
 *     totalValue: float,
 *     categoryRows: list<InventoryValueBreakdownSourceRow>,
 *     categoriesCount: int,
 *     brandRows: list<InventoryValueBreakdownSourceRow>,
 *     brandsCount: int
 * }
 */
final class InventoryValueReader
{
    /**
     * @return InventoryValueReadResult
     */
    public function get(
        ?int $locationId,
        ?int $categoryId,
        ?int $brandId,
        string $defaultCurrency,
        int $topN,
    ): array {
        $baseDefaultCurrencyQuery = $this->baseDefaultCurrencyQuery(
            locationId: $locationId,
            categoryId: $categoryId,
            brandId: $brandId,
            defaultCurrency: $defaultCurrency,
        );

        return [
            'totalValue' => (float) (clone $baseDefaultCurrencyQuery)->sum('assets.acquisition_cost'),
            'categoryRows' => $this->getCategoryRows(clone $baseDefaultCurrencyQuery, $topN),
            'categoriesCount' => (int) (clone $baseDefaultCurrencyQuery)->distinct()->count('categories.id'),
            'brandRows' => $this->getBrandRows(clone $baseDefaultCurrencyQuery, $topN),
            'brandsCount' => $this->getBrandsCount(clone $baseDefaultCurrencyQuery),
        ];
    }

    private function baseDefaultCurrencyQuery(
        ?int $locationId,
        ?int $categoryId,
        ?int $brandId,
        string $defaultCurrency,
    ): QueryBuilder {
        $query = DB::table('assets')
            ->join('products', 'assets.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('assets.status', '!=', Asset::STATUS_RETIRED)
            ->whereNotNull('assets.acquisition_cost')
            ->whereNull('assets.deleted_at')
            ->whereNull('products.deleted_at')
            ->whereNull('categories.deleted_at');

        if ($locationId !== null) {
            $query->where('assets.location_id', '=', $locationId);
        }

        if ($categoryId !== null) {
            $query->where('categories.id', '=', $categoryId);
        }

        if ($brandId !== null) {
            $query->where('products.brand_id', '=', $brandId);
        }

        return $query->where(static function (QueryBuilder $query) use ($defaultCurrency): void {
            $query->where('assets.acquisition_currency', '=', $defaultCurrency)
                ->orWhereNull('assets.acquisition_currency');
        });
    }

    /**
     * @return list<InventoryValueBreakdownSourceRow>
     */
    private function getCategoryRows(QueryBuilder $query, int $topN): array
    {
        return $query
            ->selectRaw('categories.id as category_id, categories.name as category_name, SUM(assets.acquisition_cost) as total_value')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_value')
            ->limit($topN)
            ->get()
            ->map(static fn (object $row): array => [
                'id' => (int) $row->category_id,
                'name' => (string) $row->category_name,
                'total' => (float) $row->total_value,
            ])
            ->all();
    }

    /**
     * @return list<InventoryValueBreakdownSourceRow>
     */
    private function getBrandRows(QueryBuilder $query, int $topN): array
    {
        return $query
            ->selectRaw('brands.id as brand_id, COALESCE(brands.name, \'Sin marca\') as brand_name, SUM(assets.acquisition_cost) as total_value')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->groupByRaw('brands.id, COALESCE(brands.name, \'Sin marca\')')
            ->orderByDesc('total_value')
            ->limit($topN)
            ->get()
            ->map(static fn (object $row): array => [
                'id' => $row->brand_id !== null ? (int) $row->brand_id : null,
                'name' => (string) $row->brand_name,
                'total' => (float) $row->total_value,
            ])
            ->all();
    }

    private function getBrandsCount(QueryBuilder $query): int
    {
        return (int) $query
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->distinct()
            ->count(DB::raw('COALESCE(brands.id, 0)'));
    }
}
