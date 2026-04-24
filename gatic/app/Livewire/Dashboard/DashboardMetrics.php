<?php

namespace App\Livewire\Dashboard;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use App\Support\Dashboard\AlertComparativePeriodResolver;
use App\Support\Dashboard\AlertsSnapshotBuilder;
use App\Support\Dashboard\AlertWindowConfigResolver;
use App\Support\Dashboard\ComparativeAlertCountsReader;
use App\Support\Dashboard\CriticalQueuePreviewItemsBuilder;
use App\Support\Dashboard\DeltaBadgeBuilder;
use App\Support\Dashboard\InventoryValuePayloadBuilder;
use App\Support\Dashboard\InventoryValueReader;
use App\Support\Dashboard\MovementTrendPayloadBuilder;
use App\Support\Dashboard\MovementTrendRowsReader;
use App\Support\Dashboard\PendingTaskCountsReader;
use App\Support\Dashboard\RecentActivityBuilder;
use App\Support\Errors\ErrorReporter;
use App\Support\Settings\SettingsStore;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * @phpstan-import-type ComparativeAlertPeriod from \App\Support\Dashboard\AlertComparativePeriodResolver
 * @phpstan-import-type AlertWindowConfig from \App\Support\Dashboard\AlertWindowConfigResolver
 * @phpstan-import-type ComparativeAlertCounts from \App\Support\Dashboard\ComparativeAlertCountsReader
 * @phpstan-import-type InventoryValuePayload from \App\Support\Dashboard\InventoryValuePayloadBuilder
 * @phpstan-import-type InventoryValueReadResult from \App\Support\Dashboard\InventoryValueReader
 * @phpstan-import-type MovementTrendPayload from \App\Support\Dashboard\MovementTrendPayloadBuilder
 * @phpstan-import-type MovementTrendRows from \App\Support\Dashboard\MovementTrendRowsReader
 * @phpstan-import-type PendingTaskCounts from \App\Support\Dashboard\PendingTaskCountsReader
 * @phpstan-import-type RecentActivityItem from \App\Support\Dashboard\RecentActivityBuilder
 */
#[Layout('layouts.app')]
class DashboardMetrics extends Component
{
    public string $lastUpdatedAtIso = '';

    public ?string $errorId = null;

    public int $trendRangeDays = 30;

    public int $criticalQueuePreviewLimit = 10;

    #[Url(as: 'location')]
    public ?int $locationId = null;

    #[Url(as: 'category')]
    public ?int $categoryId = null;

    #[Url(as: 'brand')]
    public ?int $brandId = null;

    public bool $filtersPanelExpanded = false;

    /**
     * @var list<array{id: int, name: string}>
     */
    public array $locationOptions = [];

    /**
     * @var list<array{id: int, name: string}>
     */
    public array $categoryOptions = [];

    /**
     * @var list<array{id: int, name: string}>
     */
    public array $brandOptions = [];

    public int $assetsAvailable = 0;

    public int $assetsLoaned = 0;

    public int $assetsPendingRetirement = 0;

    public int $assetsAssigned = 0;

    public int $assetsUnavailable = 0;

    public int $movementsToday = 0;

    public int $movementsYesterday = 0;

    public int $loansOverdueCount = 0;

    public int $loansDueSoonCount = 0;

    public int $loanDueSoonWindowDays = 7;

    public ?string $loansOverdueDeltaLabel = null;

    public ?string $loansOverdueDeltaVariant = null;

    public ?string $loansDueSoonDeltaLabel = null;

    public ?string $loansDueSoonDeltaVariant = null;

    public int $lowStockProductsCount = 0;

    public int $renewalsOverdueCount = 0;

    public int $renewalsDueSoonCount = 0;

    public int $renewalDueSoonWindowDays = 90;

    public ?string $renewalsOverdueDeltaLabel = null;

    public ?string $renewalsOverdueDeltaVariant = null;

    public ?string $renewalsDueSoonDeltaLabel = null;

    public ?string $renewalsDueSoonDeltaVariant = null;

    public int $pendingTasksReadyCount = 0;

    public int $pendingTasksProcessingCount = 0;

    public int $pendingTasksActiveCount = 0;

    public string $totalInventoryValue = '0.00';

    public int $warrantiesExpiredCount = 0;

    public int $warrantiesDueSoonCount = 0;

    public int $warrantyDueSoonWindowDays = 30;

    public ?string $warrantiesExpiredDeltaLabel = null;

    public ?string $warrantiesExpiredDeltaVariant = null;

    public ?string $warrantiesDueSoonDeltaLabel = null;

    public ?string $warrantiesDueSoonDeltaVariant = null;

    public string $defaultCurrency = 'MXN';

    public int $valueBreakdownTopN = 5;

    /**
     * @var array<int, array{name: string, value: string, id: int|null}>
     */
    public array $valueByCategory = [];

    /**
     * @var array<int, array{name: string, value: string, id: int|null}>
     */
    public array $valueByBrand = [];

    /**
     * @var list<RecentActivityItem>
     */
    public array $recentActivity = [];

    /**
     * @var list<array{type: string, variant: string, icon: string, title: string, subtitle: string|null, detail: string|null, detailHint: string|null, location: string|null, actor: string|null, href: string|null}>
     */
    public array $criticalQueue = [];

    public function mount(): void
    {
        $store = app(SettingsStore::class);
        $currency = $store->getString('gatic.inventory.money.default_currency', 'MXN');
        $this->defaultCurrency = strtoupper(trim($currency !== '' ? $currency : 'MXN'));

        $this->trendRangeDays = $this->normalizeTrendRangeDays($this->trendRangeDays);
        $this->loadFilterOptions();
        $this->normalizeFilters();
        $this->refreshMetrics();
    }

    public function updatedTrendRangeDays(): void
    {
        $this->trendRangeDays = $this->normalizeTrendRangeDays($this->trendRangeDays);
        $this->refreshMetrics();
    }

    public function updatedLocationId(): void
    {
        $this->normalizeFilters();
        $this->refreshMetrics();
    }

    public function updatedCategoryId(): void
    {
        $this->normalizeFilters();
        $this->refreshMetrics();
    }

    public function updatedBrandId(): void
    {
        $this->normalizeFilters();
        $this->refreshMetrics();
    }

    public function clearFilters(): void
    {
        $this->reset(['locationId', 'categoryId', 'brandId']);
        $this->refreshMetrics();
    }

    public function toggleFiltersPanel(): void
    {
        $this->filtersPanelExpanded = ! $this->filtersPanelExpanded;
    }

    public function hasActiveFilters(): bool
    {
        return $this->locationId !== null
            || $this->categoryId !== null
            || $this->brandId !== null;
    }

    public function poll(): void
    {
        $this->refreshMetrics();
    }

    public function refreshNow(): void
    {
        $this->refreshMetrics();
    }

    private function refreshMetrics(): void
    {
        $this->errorId = null;

        try {
            $this->normalizeFilters();
            $this->loadAssetStatusCounts();
            $this->loadMovementsToday();
            $this->loadLoanDueDateAlertCounts();
            $this->loadLowStockProductsCount();
            $this->loadWarrantyAlertCounts();
            $this->loadRenewalAlertCounts();
            if (Gate::allows('inventory.manage')) {
                $this->loadCriticalQueuePreview();
            } else {
                $this->resetCriticalQueuePreview();
            }
            if (Gate::allows('inventory.manage')) {
                $this->loadPendingTasksCounts();
            } else {
                $this->resetPendingTasksCounts();
            }
            if (Gate::allows('inventory.manage')) {
                $this->loadInventoryValue();
            } else {
                $this->resetInventoryValue();
            }
            $this->loadRecentActivity();
            $this->lastUpdatedAtIso = now()->toIso8601String();
            $this->dispatchCharts();
        } catch (Throwable $e) {
            if (app()->environment(['local', 'testing'])) {
                throw $e;
            }

            $this->errorId = app(ErrorReporter::class)->report($e, request());

            $this->dispatch(
                'ui:toast',
                type: 'error',
                title: 'Error inesperado',
                message: 'Ocurrió un error al actualizar las métricas.',
                errorId: $this->errorId,
            );
        }
    }

    private function loadAssetStatusCounts(): void
    {
        $counts = $this->applyAssetFilters(
            Asset::query()
        )
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $this->assetsAvailable = (int) ($counts[Asset::STATUS_AVAILABLE] ?? 0);
        $this->assetsLoaned = (int) ($counts[Asset::STATUS_LOANED] ?? 0);
        $this->assetsPendingRetirement = (int) ($counts[Asset::STATUS_PENDING_RETIREMENT] ?? 0);
        $this->assetsAssigned = (int) ($counts[Asset::STATUS_ASSIGNED] ?? 0);

        $this->assetsUnavailable = $this->assetsLoaned
            + $this->assetsPendingRetirement
            + $this->assetsAssigned;
    }

    private function loadMovementsToday(): void
    {
        $startOfDay = Carbon::today()->startOfDay();
        $endOfDay = Carbon::today()->endOfDay();

        $assetMovementsCount = $this->buildAssetMovementsQuery()
            ->whereBetween('asset_movements.created_at', [$startOfDay, $endOfDay])
            ->count('asset_movements.id');

        $quantityMovementsCount = $this->buildQuantityMovementsQuery()
            ->whereBetween('product_quantity_movements.created_at', [$startOfDay, $endOfDay])
            ->count('product_quantity_movements.id');

        $this->movementsToday = $assetMovementsCount + $quantityMovementsCount;

        $startOfYesterday = Carbon::yesterday()->startOfDay();
        $endOfYesterday = Carbon::yesterday()->endOfDay();

        $assetMovementsYesterdayCount = $this->buildAssetMovementsQuery()
            ->whereBetween('asset_movements.created_at', [$startOfYesterday, $endOfYesterday])
            ->count('asset_movements.id');

        $quantityMovementsYesterdayCount = $this->buildQuantityMovementsQuery()
            ->whereBetween('product_quantity_movements.created_at', [$startOfYesterday, $endOfYesterday])
            ->count('product_quantity_movements.id');

        $this->movementsYesterday = $assetMovementsYesterdayCount + $quantityMovementsYesterdayCount;
    }

    private function loadLoanDueDateAlertCounts(): void
    {
        $windowConfig = $this->resolveAlertWindowConfig(
            optionsKey: 'gatic.alerts.loans.due_soon_window_days_options',
            defaultKey: 'gatic.alerts.loans.due_soon_window_days_default',
            fallbackOptions: [7, 14, 30],
        );
        $defaultWindowDays = $windowConfig['defaultWindowDays'];
        $comparativePeriod = $this->resolveAlertComparativePeriod($defaultWindowDays);

        $this->loanDueSoonWindowDays = $defaultWindowDays;

        $baseQuery = $this->applyAssetFilters(
            Asset::query()
        )
            ->where('status', Asset::STATUS_LOANED)
            ->whereNotNull('loan_due_date');

        /** @var ComparativeAlertCounts $counts */
        $counts = app(ComparativeAlertCountsReader::class)->get(
            baseQuery: $baseQuery,
            dateColumn: 'loan_due_date',
            comparativePeriod: $comparativePeriod,
        );

        $this->loansOverdueCount = $counts['currentOverdue'];
        $loansOverdueYesterday = $counts['previousOverdue'];

        [$this->loansOverdueDeltaLabel, $this->loansOverdueDeltaVariant] = $this->buildDeltaBadge(
            current: $this->loansOverdueCount,
            previous: $loansOverdueYesterday,
            suffix: 'vs ayer',
            increaseVariant: 'danger',
            decreaseVariant: 'success',
        );

        $this->loansDueSoonCount = $counts['currentDueSoon'];
        $loansDueSoonYesterday = $counts['previousDueSoon'];

        [$this->loansDueSoonDeltaLabel, $this->loansDueSoonDeltaVariant] = $this->buildDeltaBadge(
            current: $this->loansDueSoonCount,
            previous: $loansDueSoonYesterday,
            suffix: 'vs ayer',
            increaseVariant: 'warning',
            decreaseVariant: 'success',
        );
    }

    private function loadLowStockProductsCount(): void
    {
        $this->lowStockProductsCount = $this->applyProductFilters(
            Product::query()
        )
            ->lowStockQuantity()
            ->count();
    }

    private function loadWarrantyAlertCounts(): void
    {
        $windowConfig = $this->resolveAlertWindowConfig(
            optionsKey: 'gatic.alerts.warranties.due_soon_window_days_options',
            defaultKey: 'gatic.alerts.warranties.due_soon_window_days_default',
            fallbackOptions: [7, 14, 30],
        );
        $defaultWindowDays = $windowConfig['defaultWindowDays'];
        $comparativePeriod = $this->resolveAlertComparativePeriod($defaultWindowDays);

        $this->warrantyDueSoonWindowDays = $defaultWindowDays;

        $baseQuery = $this->applyAssetFilters(
            Asset::query()
        )
            ->whereNotNull('warranty_end_date')
            ->where('status', '!=', Asset::STATUS_RETIRED);

        /** @var ComparativeAlertCounts $counts */
        $counts = app(ComparativeAlertCountsReader::class)->get(
            baseQuery: $baseQuery,
            dateColumn: 'warranty_end_date',
            comparativePeriod: $comparativePeriod,
        );

        $this->warrantiesExpiredCount = $counts['currentOverdue'];
        $warrantiesExpiredYesterday = $counts['previousOverdue'];

        [$this->warrantiesExpiredDeltaLabel, $this->warrantiesExpiredDeltaVariant] = $this->buildDeltaBadge(
            current: $this->warrantiesExpiredCount,
            previous: $warrantiesExpiredYesterday,
            suffix: 'vs ayer',
            increaseVariant: 'danger',
            decreaseVariant: 'success',
        );

        $this->warrantiesDueSoonCount = $counts['currentDueSoon'];
        $warrantiesDueSoonYesterday = $counts['previousDueSoon'];

        [$this->warrantiesDueSoonDeltaLabel, $this->warrantiesDueSoonDeltaVariant] = $this->buildDeltaBadge(
            current: $this->warrantiesDueSoonCount,
            previous: $warrantiesDueSoonYesterday,
            suffix: 'vs ayer',
            increaseVariant: 'warning',
            decreaseVariant: 'success',
        );
    }

    private function loadRenewalAlertCounts(): void
    {
        $windowConfig = $this->resolveAlertWindowConfig(
            optionsKey: 'gatic.alerts.renewals.due_soon_window_days_options',
            defaultKey: 'gatic.alerts.renewals.due_soon_window_days_default',
            fallbackOptions: [30, 60, 90, 180],
        );
        $defaultWindowDays = $windowConfig['defaultWindowDays'];
        $comparativePeriod = $this->resolveAlertComparativePeriod($defaultWindowDays);

        $this->renewalDueSoonWindowDays = $defaultWindowDays;

        $baseQuery = $this->applyAssetFilters(
            Asset::query()
        )
            ->whereNotNull('expected_replacement_date')
            ->where('status', '!=', Asset::STATUS_RETIRED);

        /** @var ComparativeAlertCounts $counts */
        $counts = app(ComparativeAlertCountsReader::class)->get(
            baseQuery: $baseQuery,
            dateColumn: 'expected_replacement_date',
            comparativePeriod: $comparativePeriod,
        );

        $this->renewalsOverdueCount = $counts['currentOverdue'];
        $renewalsOverdueYesterday = $counts['previousOverdue'];

        [$this->renewalsOverdueDeltaLabel, $this->renewalsOverdueDeltaVariant] = $this->buildDeltaBadge(
            current: $this->renewalsOverdueCount,
            previous: $renewalsOverdueYesterday,
            suffix: 'vs ayer',
            increaseVariant: 'danger',
            decreaseVariant: 'success',
        );

        $this->renewalsDueSoonCount = $counts['currentDueSoon'];
        $renewalsDueSoonYesterday = $counts['previousDueSoon'];

        [$this->renewalsDueSoonDeltaLabel, $this->renewalsDueSoonDeltaVariant] = $this->buildDeltaBadge(
            current: $this->renewalsDueSoonCount,
            previous: $renewalsDueSoonYesterday,
            suffix: 'vs ayer',
            increaseVariant: 'warning',
            decreaseVariant: 'success',
        );
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function buildDeltaBadge(
        int $current,
        int $previous,
        string $suffix,
        string $increaseVariant,
        string $decreaseVariant,
    ): array {
        return app(DeltaBadgeBuilder::class)->build(
            current: $current,
            previous: $previous,
            suffix: $suffix,
            increaseVariant: $increaseVariant,
            decreaseVariant: $decreaseVariant,
        );
    }

    private function loadPendingTasksCounts(): void
    {
        /** @var PendingTaskCounts $counts */
        $counts = app(PendingTaskCountsReader::class)->get();

        $this->pendingTasksReadyCount = $counts['ready'];
        $this->pendingTasksProcessingCount = $counts['processing'];
        $this->pendingTasksActiveCount = $counts['active'];
    }

    private function resetPendingTasksCounts(): void
    {
        $this->pendingTasksReadyCount = 0;
        $this->pendingTasksProcessingCount = 0;
        $this->pendingTasksActiveCount = 0;
    }

    private function resetInventoryValue(): void
    {
        $this->totalInventoryValue = '0.00';
        $this->valueBreakdownTopN = (int) config('gatic.dashboard.value.top_n', 5);
        if ($this->valueBreakdownTopN <= 0) {
            $this->valueBreakdownTopN = 5;
        }
        $this->valueByCategory = [];
        $this->valueByBrand = [];
    }

    /**
     * @param  non-empty-list<int>  $fallbackOptions
     * @return AlertWindowConfig
     */
    private function resolveAlertWindowConfig(
        string $optionsKey,
        string $defaultKey,
        array $fallbackOptions,
    ): array {
        /** @var AlertWindowConfig $windowConfig */
        $windowConfig = app(AlertWindowConfigResolver::class)->resolve(
            optionsKey: $optionsKey,
            defaultKey: $defaultKey,
            fallbackOptions: $fallbackOptions,
        );

        return $windowConfig;
    }

    /**
     * @return ComparativeAlertPeriod
     */
    private function resolveAlertComparativePeriod(int $windowDays): array
    {
        /** @var ComparativeAlertPeriod $comparativePeriod */
        $comparativePeriod = app(AlertComparativePeriodResolver::class)->resolve(windowDays: $windowDays);

        return $comparativePeriod;
    }

    private function normalizeTrendRangeDays(int $value): int
    {
        $allowed = [7, 30, 90];
        $resolved = in_array($value, $allowed, true) ? $value : 30;

        return $resolved;
    }

    private function dispatchCharts(): void
    {
        $movementTrend = $this->buildMovementTrend($this->trendRangeDays);
        $alertsSnapshot = $this->buildAlertsSnapshot();

        $this->dispatch('dashboard:charts', charts: [
            'rangeDays' => $this->trendRangeDays,
            'movementTrend' => $movementTrend,
            'alerts' => $alertsSnapshot,
        ]);
    }

    /**
     * @return MovementTrendPayload
     */
    private function buildMovementTrend(int $rangeDays): array
    {
        $rangeDays = $this->normalizeTrendRangeDays($rangeDays);
        $today = Carbon::today();
        $start = $today->copy()->subDays($rangeDays - 1)->startOfDay();
        $end = $today->copy()->endOfDay();

        /** @var MovementTrendRows $trendRows */
        $trendRows = app(MovementTrendRowsReader::class)->get(
            assetMovementsQuery: $this->buildAssetMovementsQuery(),
            quantityMovementsQuery: $this->buildQuantityMovementsQuery(),
            start: $start,
            end: $end,
        );

        return app(MovementTrendPayloadBuilder::class)->build(
            rangeDays: $rangeDays,
            assetRows: $trendRows['assetRows'],
            quantityRows: $trendRows['quantityRows'],
            today: $today,
        );
    }

    /**
     * @return list<array{key: string, label: string, value: int, href: string|null, variant: string}>
     */
    private function buildAlertsSnapshot(): array
    {
        return app(AlertsSnapshotBuilder::class)->build([
            'canManage' => Gate::allows('inventory.manage'),
            'filters' => $this->buildFilterParams(),
            'productFilters' => $this->buildProductFilterParams(),
            'loansOverdueCount' => $this->loansOverdueCount,
            'loansDueSoonCount' => $this->loansDueSoonCount,
            'loanDueSoonWindowDays' => $this->loanDueSoonWindowDays,
            'warrantiesExpiredCount' => $this->warrantiesExpiredCount,
            'warrantiesDueSoonCount' => $this->warrantiesDueSoonCount,
            'warrantyDueSoonWindowDays' => $this->warrantyDueSoonWindowDays,
            'lowStockProductsCount' => $this->lowStockProductsCount,
            'renewalsOverdueCount' => $this->renewalsOverdueCount,
            'renewalsDueSoonCount' => $this->renewalsDueSoonCount,
            'renewalDueSoonWindowDays' => $this->renewalDueSoonWindowDays,
            'pendingTasksActiveCount' => $this->pendingTasksActiveCount,
        ]);
    }

    private function loadInventoryValue(): void
    {
        $topN = (int) config('gatic.dashboard.value.top_n', 5);
        $this->valueBreakdownTopN = $topN > 0 ? $topN : 5;

        /** @var InventoryValueReadResult $inventoryValueReadResult */
        $inventoryValueReadResult = app(InventoryValueReader::class)->get(
            locationId: $this->locationId,
            categoryId: $this->categoryId,
            brandId: $this->brandId,
            defaultCurrency: $this->defaultCurrency,
            topN: $this->valueBreakdownTopN,
        );

        /** @var InventoryValuePayload $inventoryValue */
        $inventoryValue = app(InventoryValuePayloadBuilder::class)->build(
            totalValue: $inventoryValueReadResult['totalValue'],
            categoryRows: $inventoryValueReadResult['categoryRows'],
            categoriesCount: $inventoryValueReadResult['categoriesCount'],
            brandRows: $inventoryValueReadResult['brandRows'],
            brandsCount: $inventoryValueReadResult['brandsCount'],
            topN: $this->valueBreakdownTopN,
        );

        $this->totalInventoryValue = $inventoryValue['totalInventoryValue'];
        $this->valueByCategory = $inventoryValue['valueByCategory'];
        $this->valueByBrand = $inventoryValue['valueByBrand'];
    }

    private function loadRecentActivity(): void
    {
        $builder = new RecentActivityBuilder(
            canViewAttachments: Gate::allows('attachments.view'),
            canManageInventory: Gate::allows('inventory.manage'),
        );

        $events = $builder->build();

        $this->recentActivity = $events;
    }

    private function resetCriticalQueuePreview(): void
    {
        $this->criticalQueue = [];
    }

    private function loadCriticalQueuePreview(): void
    {
        $today = Carbon::today();
        $filters = $this->buildFilterParams();
        $returnTo = '/dashboard'.(count($filters) > 0 ? ('?'.http_build_query($filters)) : '');

        $this->criticalQueue = app(CriticalQueuePreviewItemsBuilder::class)->build(
            assetQuery: $this->applyAssetFilters(Asset::query()),
            productQuery: $this->applyProductFilters(Product::query()),
            today: $today,
            returnTo: $returnTo,
            limit: $this->criticalQueuePreviewLimit,
            loansOverdueCount: $this->loansOverdueCount,
            warrantiesExpiredCount: $this->warrantiesExpiredCount,
            renewalsOverdueCount: $this->renewalsOverdueCount,
            lowStockProductsCount: $this->lowStockProductsCount,
        );
    }

    private function loadFilterOptions(): void
    {
        $this->locationOptions = Location::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (Location $loc): array => ['id' => $loc->id, 'name' => $loc->name])
            ->all();

        $this->categoryOptions = Category::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (Category $cat): array => ['id' => $cat->id, 'name' => $cat->name])
            ->all();

        $this->brandOptions = Brand::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (Brand $brand): array => ['id' => $brand->id, 'name' => $brand->name])
            ->all();
    }

    private function normalizeFilters(): void
    {
        if ($this->locationId !== null) {
            $locationIds = array_column($this->locationOptions, 'id');
            if (! in_array($this->locationId, $locationIds, true)) {
                $this->locationId = null;
            }
        }

        if ($this->categoryId !== null) {
            $categoryIds = array_column($this->categoryOptions, 'id');
            if (! in_array($this->categoryId, $categoryIds, true)) {
                $this->categoryId = null;
            }
        }

        if ($this->brandId !== null) {
            $brandIds = array_column($this->brandOptions, 'id');
            if (! in_array($this->brandId, $brandIds, true)) {
                $this->brandId = null;
            }
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Asset>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Asset>
     */
    private function applyAssetFilters(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        $locationId = $this->locationId;
        $categoryId = $this->categoryId;
        $brandId = $this->brandId;

        if ($locationId !== null) {
            $query->where('location_id', $locationId);
        }

        if ($categoryId !== null || $brandId !== null) {
            $query->whereHas('product', function ($q) use ($categoryId, $brandId) {
                if ($categoryId !== null) {
                    $q->where('category_id', $categoryId);
                }
                if ($brandId !== null) {
                    $q->where('brand_id', $brandId);
                }
            });
        }

        return $query;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Product>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Product>
     */
    private function applyProductFilters(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        if ($this->categoryId !== null) {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->brandId !== null) {
            $query->where('brand_id', $this->brandId);
        }

        return $query;
    }

    /**
     * @return array{location?: int, category?: int, brand?: int}
     */
    private function buildFilterParams(): array
    {
        return array_filter([
            'location' => $this->locationId,
            'category' => $this->categoryId,
            'brand' => $this->brandId,
        ], static fn ($value): bool => $value !== null);
    }

    /**
     * @return array{category?: int, brand?: int}
     */
    private function buildProductFilterParams(): array
    {
        return array_filter([
            'category' => $this->categoryId,
            'brand' => $this->brandId,
        ], static fn ($value): bool => $value !== null);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<AssetMovement>
     */
    private function buildAssetMovementsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = AssetMovement::query();

        if ($this->locationId === null && $this->categoryId === null && $this->brandId === null) {
            return $query;
        }

        $query->join('assets', function ($join) {
            $join->on('assets.id', '=', 'asset_movements.asset_id')
                ->whereNull('assets.deleted_at');
        });

        if ($this->categoryId !== null || $this->brandId !== null) {
            $query->join('products', function ($join) {
                $join->on('products.id', '=', 'assets.product_id')
                    ->whereNull('products.deleted_at');
            });
        }

        if ($this->locationId !== null) {
            $query->where('assets.location_id', $this->locationId);
        }

        if ($this->categoryId !== null) {
            $query->where('products.category_id', $this->categoryId);
        }

        if ($this->brandId !== null) {
            $query->where('products.brand_id', $this->brandId);
        }

        return $query;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<ProductQuantityMovement>
     */
    private function buildQuantityMovementsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = ProductQuantityMovement::query();

        if ($this->categoryId === null && $this->brandId === null) {
            return $query;
        }

        $query->join('products', function ($join) {
            $join->on('products.id', '=', 'product_quantity_movements.product_id')
                ->whereNull('products.deleted_at');
        });

        if ($this->categoryId !== null) {
            $query->where('products.category_id', $this->categoryId);
        }

        if ($this->brandId !== null) {
            $query->where('products.brand_id', $this->brandId);
        }

        return $query;
    }

    public function render(): View
    {
        return view('livewire.dashboard.dashboard-metrics');
    }
}
