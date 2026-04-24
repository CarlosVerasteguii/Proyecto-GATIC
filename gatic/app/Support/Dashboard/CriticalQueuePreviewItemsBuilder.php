<?php

declare(strict_types=1);

namespace App\Support\Dashboard;

use App\Models\Asset;
use App\Models\Product;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * @phpstan-import-type CriticalQueuePreviewRow from \App\Support\Dashboard\CriticalQueuePreviewBuilder
 */
final class CriticalQueuePreviewItemsBuilder
{
    public function __construct(
        private readonly CriticalQueueAssetRowMapper $assetRowMapper,
        private readonly CriticalQueuePreviewBuilder $previewBuilder,
    ) {}

    /**
     * @param  Builder<Asset>  $assetQuery
     * @param  Builder<Product>  $productQuery
     * @return list<CriticalQueuePreviewRow>
     */
    public function build(
        Builder $assetQuery,
        Builder $productQuery,
        CarbonInterface $today,
        string $returnTo,
        int $limit,
        int $loansOverdueCount,
        int $warrantiesExpiredCount,
        int $renewalsOverdueCount,
        int $lowStockProductsCount,
    ): array {
        $items = array_merge(
            $this->overdueLoans($assetQuery, $today, $returnTo, $loansOverdueCount),
            $this->expiredWarranties($assetQuery, $today, $returnTo, $warrantiesExpiredCount),
            $this->overdueRenewals($assetQuery, $today, $returnTo, $renewalsOverdueCount),
            $this->lowStockProducts($productQuery, $lowStockProductsCount),
        );

        return $this->previewBuilder->build(
            items: $items,
            limit: $limit,
        );
    }

    /**
     * @param  Builder<Asset>  $assetQuery
     * @return list<array{priority: int, score: int, type: string, variant: string, icon: string, title: string, subtitle: string|null, detail: string|null, detailHint: string|null, location: string|null, actor: string|null, href: string}>
     */
    private function overdueLoans(Builder $assetQuery, CarbonInterface $today, string $returnTo, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $assets = (clone $assetQuery)
            ->with([
                'product:id,name',
                'location:id,name',
                'currentEmployee:id,rpe,name',
            ])
            ->where('status', Asset::STATUS_LOANED)
            ->whereNotNull('loan_due_date')
            ->where('loan_due_date', '<', $today->toDateString())
            ->orderBy('loan_due_date')
            ->orderBy('serial')
            ->limit(5)
            ->get([
                'id',
                'product_id',
                'location_id',
                'current_employee_id',
                'serial',
                'asset_tag',
                'loan_due_date',
            ]);

        $items = [];

        foreach ($assets as $asset) {
            $due = $asset->loan_due_date;
            $days = $due ? (int) $due->diffInDays($today) : null;

            $items[] = array_merge([
                'priority' => 100,
                'score' => $days ?? 0,
                'type' => 'Préstamo vencido',
                'variant' => 'danger',
                'icon' => 'bi-clock-history',
            ], $this->assetRowMapper->mapOverdueRow(
                asset: $asset,
                dueDate: $due,
                overdueDays: $days,
                actor: $asset->currentEmployee?->full_name,
                href: $this->assetRoute($asset, $returnTo),
            ));
        }

        return $items;
    }

    /**
     * @param  Builder<Asset>  $assetQuery
     * @return list<array{priority: int, score: int, type: string, variant: string, icon: string, title: string, subtitle: string|null, detail: string|null, detailHint: string|null, location: string|null, actor: string|null, href: string}>
     */
    private function expiredWarranties(Builder $assetQuery, CarbonInterface $today, string $returnTo, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $assets = (clone $assetQuery)
            ->with([
                'product:id,name',
                'location:id,name',
                'warrantySupplier:id,name',
            ])
            ->whereNotNull('warranty_end_date')
            ->where('status', '!=', Asset::STATUS_RETIRED)
            ->where('warranty_end_date', '<', $today->toDateString())
            ->orderBy('warranty_end_date')
            ->orderBy('serial')
            ->limit(5)
            ->get([
                'id',
                'product_id',
                'location_id',
                'warranty_supplier_id',
                'serial',
                'asset_tag',
                'warranty_end_date',
            ]);

        $items = [];

        foreach ($assets as $asset) {
            $due = $asset->warranty_end_date;
            $days = $due ? (int) $due->diffInDays($today) : null;

            $items[] = array_merge([
                'priority' => 90,
                'score' => $days ?? 0,
                'type' => 'Garantía vencida',
                'variant' => 'danger',
                'icon' => 'bi-shield-x',
            ], $this->assetRowMapper->mapOverdueRow(
                asset: $asset,
                dueDate: $due,
                overdueDays: $days,
                actor: $asset->warrantySupplier?->name,
                href: $this->assetRoute($asset, $returnTo),
            ));
        }

        return $items;
    }

    /**
     * @param  Builder<Asset>  $assetQuery
     * @return list<array{priority: int, score: int, type: string, variant: string, icon: string, title: string, subtitle: string|null, detail: string|null, detailHint: string|null, location: string|null, actor: string|null, href: string}>
     */
    private function overdueRenewals(Builder $assetQuery, CarbonInterface $today, string $returnTo, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $assets = (clone $assetQuery)
            ->with([
                'product:id,name',
                'location:id,name',
            ])
            ->whereNotNull('expected_replacement_date')
            ->where('status', '!=', Asset::STATUS_RETIRED)
            ->where('expected_replacement_date', '<', $today->toDateString())
            ->orderBy('expected_replacement_date')
            ->orderBy('serial')
            ->limit(5)
            ->get([
                'id',
                'product_id',
                'location_id',
                'serial',
                'asset_tag',
                'expected_replacement_date',
            ]);

        $items = [];

        foreach ($assets as $asset) {
            $due = $asset->expected_replacement_date;
            $days = $due ? (int) $due->diffInDays($today) : null;

            $items[] = array_merge([
                'priority' => 80,
                'score' => $days ?? 0,
                'type' => 'Renovación vencida',
                'variant' => 'danger',
                'icon' => 'bi-arrow-repeat',
            ], $this->assetRowMapper->mapOverdueRow(
                asset: $asset,
                dueDate: $due,
                overdueDays: $days,
                href: $this->assetRoute($asset, $returnTo),
            ));
        }

        return $items;
    }

    /**
     * @param  Builder<Product>  $productQuery
     * @return list<array{priority: int, score: int, type: string, variant: string, icon: string, title: string, subtitle: string|null, detail: string, detailHint: string|null, location: null, actor: null, href: string}>
     */
    private function lowStockProducts(Builder $productQuery, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $products = (clone $productQuery)
            ->with([
                'brand:id,name',
            ])
            ->lowStockQuantity()
            ->orderBy('qty_total')
            ->orderBy('name')
            ->limit(5)
            ->get([
                'id',
                'name',
                'brand_id',
                'qty_total',
                'low_stock_threshold',
            ]);

        $items = [];

        foreach ($products as $product) {
            $qty = (int) ($product->qty_total ?? 0);
            $threshold = (int) ($product->low_stock_threshold ?? 0);
            $gap = $threshold - $qty;

            $items[] = [
                'priority' => 70,
                'score' => $gap,
                'type' => 'Stock bajo',
                'variant' => 'warning',
                'icon' => 'bi-box-seam',
                'title' => (string) $product->name,
                'subtitle' => $product->brand?->name,
                'detail' => "{$qty} / {$threshold}",
                'detailHint' => $gap > 0 ? "Faltan {$gap}" : null,
                'location' => null,
                'actor' => null,
                'href' => route('inventory.products.show', [
                    'product' => $product->id,
                ]),
            ];
        }

        return $items;
    }

    private function assetRoute(Asset $asset, string $returnTo): string
    {
        return route('inventory.products.assets.show', [
            'product' => $asset->product_id,
            'asset' => $asset->id,
            'returnTo' => $returnTo,
        ]);
    }
}
