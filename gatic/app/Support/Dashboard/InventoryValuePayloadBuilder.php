<?php

declare(strict_types=1);

namespace App\Support\Dashboard;

/**
 * @phpstan-type InventoryValueBreakdownSourceRow array{id: int|null, name: string, total: float}
 * @phpstan-type InventoryValueBreakdownItem array{name: string, value: string, id: int|null}
 * @phpstan-type InventoryValuePayload array{
 *     totalInventoryValue: string,
 *     valueByCategory: list<InventoryValueBreakdownItem>,
 *     valueByBrand: list<InventoryValueBreakdownItem>
 * }
 */
final class InventoryValuePayloadBuilder
{
    /**
     * @param  list<InventoryValueBreakdownSourceRow>  $categoryRows
     * @param  list<InventoryValueBreakdownSourceRow>  $brandRows
     * @return InventoryValuePayload
     */
    public function build(
        float $totalValue,
        array $categoryRows,
        int $categoriesCount,
        array $brandRows,
        int $brandsCount,
        int $topN,
    ): array {
        $formattedTotalValue = $this->formatAmount($totalValue);
        $roundedTotalValue = (float) $formattedTotalValue;
        $normalizedTopN = $this->normalizeTopN($topN);

        return [
            'totalInventoryValue' => $formattedTotalValue,
            'valueByCategory' => $this->buildBreakdown(
                rows: $categoryRows,
                totalCount: $categoriesCount,
                topN: $normalizedTopN,
                roundedTotalValue: $roundedTotalValue,
            ),
            'valueByBrand' => $this->buildBreakdown(
                rows: $brandRows,
                totalCount: $brandsCount,
                topN: $normalizedTopN,
                roundedTotalValue: $roundedTotalValue,
            ),
        ];
    }

    /**
     * @param  list<InventoryValueBreakdownSourceRow>  $rows
     * @return list<InventoryValueBreakdownItem>
     */
    private function buildBreakdown(array $rows, int $totalCount, int $topN, float $roundedTotalValue): array
    {
        $items = array_map(
            fn (array $row): array => [
                'name' => $row['name'],
                'value' => $this->formatAmount($row['total']),
                'id' => $row['id'],
            ],
            $rows,
        );

        $sumTopItems = array_reduce(
            $items,
            static fn (float $carry, array $item): float => $carry + (float) $item['value'],
            0.0,
        );

        $otherValue = $roundedTotalValue - $sumTopItems;

        if ($totalCount > $topN && $otherValue > 0.004) {
            $items[] = [
                'name' => 'Otros',
                'value' => $this->formatAmount($otherValue),
                'id' => null,
            ];
        }

        return $items;
    }

    private function normalizeTopN(int $topN): int
    {
        return $topN > 0 ? $topN : 5;
    }

    private function formatAmount(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
