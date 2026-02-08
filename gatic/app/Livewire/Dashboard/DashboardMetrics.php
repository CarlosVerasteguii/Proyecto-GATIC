<?php

namespace App\Livewire\Dashboard;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\PendingTask;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use App\Support\Dashboard\RecentActivityBuilder;
use App\Support\Errors\ErrorReporter;
use App\Support\Settings\SettingsStore;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

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
     * @var list<array{type: string, icon: string, label: string, title: string, summary: string, actor: string|null, occurred_at: string, occurred_at_human: string, route: string|null}>
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
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $store = app(SettingsStore::class);
        $allowedOptions = $store->getIntList('gatic.alerts.loans.due_soon_window_days_options', [7, 14, 30]);
        if ($allowedOptions === []) {
            $allowedOptions = [7, 14, 30];
        }

        $defaultWindowDays = $store->getInt('gatic.alerts.loans.due_soon_window_days_default', $allowedOptions[0] ?? 7);
        if (! in_array($defaultWindowDays, $allowedOptions, true)) {
            $defaultWindowDays = (int) ($allowedOptions[0] ?? 7);
        }

        $this->loanDueSoonWindowDays = $defaultWindowDays;

        $baseQuery = $this->applyAssetFilters(
            Asset::query()
        )
            ->where('status', Asset::STATUS_LOANED)
            ->whereNotNull('loan_due_date');

        $this->loansOverdueCount = (clone $baseQuery)
            ->where('loan_due_date', '<', $today->toDateString())
            ->count();

        $loansOverdueYesterday = (clone $baseQuery)
            ->where('loan_due_date', '<', $yesterday->toDateString())
            ->count();

        [$this->loansOverdueDeltaLabel, $this->loansOverdueDeltaVariant] = $this->buildDeltaBadge(
            current: $this->loansOverdueCount,
            previous: $loansOverdueYesterday,
            suffix: 'vs ayer',
            increaseVariant: 'danger',
            decreaseVariant: 'success',
        );

        $windowEnd = $today->copy()->addDays($defaultWindowDays);

        $this->loansDueSoonCount = (clone $baseQuery)
            ->whereBetween('loan_due_date', [$today->toDateString(), $windowEnd->toDateString()])
            ->count();

        $windowEndYesterday = $yesterday->copy()->addDays($defaultWindowDays);

        $loansDueSoonYesterday = (clone $baseQuery)
            ->whereBetween('loan_due_date', [$yesterday->toDateString(), $windowEndYesterday->toDateString()])
            ->count();

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
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $store = app(SettingsStore::class);
        $allowedOptions = $store->getIntList('gatic.alerts.warranties.due_soon_window_days_options', [7, 14, 30]);
        if ($allowedOptions === []) {
            $allowedOptions = [7, 14, 30];
        }

        $defaultWindowDays = $store->getInt('gatic.alerts.warranties.due_soon_window_days_default', $allowedOptions[0] ?? 30);
        if (! in_array($defaultWindowDays, $allowedOptions, true)) {
            $defaultWindowDays = (int) ($allowedOptions[0] ?? 30);
        }

        $this->warrantyDueSoonWindowDays = $defaultWindowDays;

        $baseQuery = $this->applyAssetFilters(
            Asset::query()
        )
            ->whereNotNull('warranty_end_date')
            ->where('status', '!=', Asset::STATUS_RETIRED);

        $this->warrantiesExpiredCount = (clone $baseQuery)
            ->where('warranty_end_date', '<', $today->toDateString())
            ->count();

        $warrantiesExpiredYesterday = (clone $baseQuery)
            ->where('warranty_end_date', '<', $yesterday->toDateString())
            ->count();

        [$this->warrantiesExpiredDeltaLabel, $this->warrantiesExpiredDeltaVariant] = $this->buildDeltaBadge(
            current: $this->warrantiesExpiredCount,
            previous: $warrantiesExpiredYesterday,
            suffix: 'vs ayer',
            increaseVariant: 'danger',
            decreaseVariant: 'success',
        );

        $windowEnd = $today->copy()->addDays($defaultWindowDays);

        $this->warrantiesDueSoonCount = (clone $baseQuery)
            ->whereBetween('warranty_end_date', [$today->toDateString(), $windowEnd->toDateString()])
            ->count();

        $windowEndYesterday = $yesterday->copy()->addDays($defaultWindowDays);

        $warrantiesDueSoonYesterday = (clone $baseQuery)
            ->whereBetween('warranty_end_date', [$yesterday->toDateString(), $windowEndYesterday->toDateString()])
            ->count();

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
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $store = app(SettingsStore::class);
        $allowedOptions = $store->getIntList('gatic.alerts.renewals.due_soon_window_days_options', [30, 60, 90, 180]);
        if ($allowedOptions === []) {
            $allowedOptions = [30, 60, 90, 180];
        }

        $defaultWindowDays = $store->getInt('gatic.alerts.renewals.due_soon_window_days_default', $allowedOptions[0] ?? 90);
        if (! in_array($defaultWindowDays, $allowedOptions, true)) {
            $defaultWindowDays = (int) ($allowedOptions[0] ?? 90);
        }

        $this->renewalDueSoonWindowDays = $defaultWindowDays;

        $baseQuery = $this->applyAssetFilters(
            Asset::query()
        )
            ->whereNotNull('expected_replacement_date')
            ->where('status', '!=', Asset::STATUS_RETIRED);

        $this->renewalsOverdueCount = (clone $baseQuery)
            ->where('expected_replacement_date', '<', $today->toDateString())
            ->count();

        $renewalsOverdueYesterday = (clone $baseQuery)
            ->where('expected_replacement_date', '<', $yesterday->toDateString())
            ->count();

        [$this->renewalsOverdueDeltaLabel, $this->renewalsOverdueDeltaVariant] = $this->buildDeltaBadge(
            current: $this->renewalsOverdueCount,
            previous: $renewalsOverdueYesterday,
            suffix: 'vs ayer',
            increaseVariant: 'danger',
            decreaseVariant: 'success',
        );

        $windowEnd = $today->copy()->addDays($defaultWindowDays);

        $this->renewalsDueSoonCount = (clone $baseQuery)
            ->whereBetween('expected_replacement_date', [$today->toDateString(), $windowEnd->toDateString()])
            ->count();

        $windowEndYesterday = $yesterday->copy()->addDays($defaultWindowDays);

        $renewalsDueSoonYesterday = (clone $baseQuery)
            ->whereBetween('expected_replacement_date', [$yesterday->toDateString(), $windowEndYesterday->toDateString()])
            ->count();

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
        if ($current === $previous) {
            return [null, null];
        }

        $delta = $current - $previous;
        if ($previous > 0) {
            $pct = ($delta / $previous) * 100;
            $label = sprintf('%+.1f%% %s', $pct, $suffix);
        } else {
            $label = sprintf('%+d %s', $delta, $suffix);
        }

        $variant = $delta > 0 ? $increaseVariant : $decreaseVariant;

        return [$label, $variant];
    }

    private function loadPendingTasksCounts(): void
    {
        $counts = PendingTask::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $this->pendingTasksReadyCount = (int) ($counts['ready'] ?? 0);
        $this->pendingTasksProcessingCount = (int) ($counts['processing'] ?? 0);
        $this->pendingTasksActiveCount = $this->pendingTasksReadyCount + $this->pendingTasksProcessingCount;
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

    private function normalizeTrendRangeDays(int $value): int
    {
        $allowed = [7, 30, 90];
        $resolved = in_array($value, $allowed, true) ? $value : 30;

        return $resolved > 0 ? $resolved : 30;
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
     * @return array{labels: list<string>, datasets: list<array{key: string, label: string, data: list<int>}>}
     */
    private function buildMovementTrend(int $rangeDays): array
    {
        $rangeDays = $this->normalizeTrendRangeDays($rangeDays);

        $start = Carbon::today()->subDays($rangeDays - 1)->startOfDay();
        $end = Carbon::today()->endOfDay();

        $days = [];
        $dayIndex = [];
        $cursor = $start->copy();
        $i = 0;
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $days[] = $key;
            $dayIndex[$key] = $i;
            $i++;
            $cursor->addDay();
        }

        $assignedOps = array_fill(0, count($days), 0);
        $loanOps = array_fill(0, count($days), 0);
        $qtyInOps = array_fill(0, count($days), 0);
        $qtyOutOps = array_fill(0, count($days), 0);

        $assetRows = $this->buildAssetMovementsQuery()
            ->selectRaw('DATE(asset_movements.created_at) as day, asset_movements.type as type, COUNT(*) as total')
            ->whereBetween('asset_movements.created_at', [$start, $end])
            ->whereIn('asset_movements.type', AssetMovement::TYPES)
            ->groupByRaw('DATE(asset_movements.created_at), asset_movements.type')
            ->get();

        foreach ($assetRows as $row) {
            $day = (string) ($row->day ?? '');
            if ($day === '' || ! array_key_exists($day, $dayIndex)) {
                continue;
            }

            $idx = (int) $dayIndex[$day];
            $type = (string) ($row->type ?? '');
            $total = (int) ($row->total ?? 0);

            if ($type === AssetMovement::TYPE_ASSIGN || $type === AssetMovement::TYPE_UNASSIGN) {
                $assignedOps[$idx] += $total;
            }

            if ($type === AssetMovement::TYPE_LOAN || $type === AssetMovement::TYPE_RETURN) {
                $loanOps[$idx] += $total;
            }
        }

        $qtyRows = $this->buildQuantityMovementsQuery()
            ->selectRaw('DATE(product_quantity_movements.created_at) as day, product_quantity_movements.direction as direction, COUNT(*) as total')
            ->whereBetween('product_quantity_movements.created_at', [$start, $end])
            ->whereIn('product_quantity_movements.direction', ProductQuantityMovement::DIRECTIONS)
            ->groupByRaw('DATE(product_quantity_movements.created_at), product_quantity_movements.direction')
            ->get();

        foreach ($qtyRows as $row) {
            $day = (string) ($row->day ?? '');
            if ($day === '' || ! array_key_exists($day, $dayIndex)) {
                continue;
            }

            $idx = (int) $dayIndex[$day];
            $direction = (string) ($row->direction ?? '');
            $total = (int) ($row->total ?? 0);

            if ($direction === ProductQuantityMovement::DIRECTION_IN) {
                $qtyInOps[$idx] += $total;
            }

            if ($direction === ProductQuantityMovement::DIRECTION_OUT) {
                $qtyOutOps[$idx] += $total;
            }
        }

        return [
            'labels' => $days,
            'datasets' => [
                ['key' => 'assets_assigned_ops', 'label' => 'Asignaciones', 'data' => $assignedOps],
                ['key' => 'assets_loan_ops', 'label' => 'Préstamos', 'data' => $loanOps],
                ['key' => 'qty_out_ops', 'label' => 'Cantidad salida', 'data' => $qtyOutOps],
                ['key' => 'qty_in_ops', 'label' => 'Cantidad entrada', 'data' => $qtyInOps],
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, value: int, href: string|null, variant: string}>
     */
    private function buildAlertsSnapshot(): array
    {
        $canManage = Gate::allows('inventory.manage');
        $filters = $this->buildFilterParams();
        $productFilters = $this->buildProductFilterParams();

        return array_values(array_filter([
            [
                'key' => 'loans_overdue',
                'label' => 'Préstamos vencidos',
                'value' => $this->loansOverdueCount,
                'href' => $canManage ? route('alerts.loans.index', array_merge(['type' => 'overdue'], $filters)) : null,
                'variant' => 'danger',
            ],
            [
                'key' => 'loans_due_soon',
                'label' => 'Préstamos por vencer',
                'value' => $this->loansDueSoonCount,
                'href' => $canManage
                    ? route('alerts.loans.index', array_merge(['type' => 'due-soon', 'windowDays' => $this->loanDueSoonWindowDays], $filters))
                    : null,
                'variant' => 'warning',
            ],
            [
                'key' => 'warranties_expired',
                'label' => 'Garantías vencidas',
                'value' => $this->warrantiesExpiredCount,
                'href' => $canManage ? route('alerts.warranties.index', array_merge(['type' => 'expired'], $filters)) : null,
                'variant' => 'danger',
            ],
            [
                'key' => 'warranties_due_soon',
                'label' => 'Garantías por vencer',
                'value' => $this->warrantiesDueSoonCount,
                'href' => $canManage
                    ? route('alerts.warranties.index', array_merge(['type' => 'due-soon', 'windowDays' => $this->warrantyDueSoonWindowDays], $filters))
                    : null,
                'variant' => 'warning',
            ],
            [
                'key' => 'low_stock',
                'label' => 'Stock bajo',
                'value' => $this->lowStockProductsCount,
                'href' => $canManage ? route('alerts.stock.index', $productFilters) : null,
                'variant' => 'warning',
            ],
            [
                'key' => 'renewals_overdue',
                'label' => 'Renovaciones vencidas',
                'value' => $this->renewalsOverdueCount,
                'href' => $canManage ? route('alerts.renewals.index', array_merge(['type' => 'overdue'], $filters)) : null,
                'variant' => 'danger',
            ],
            [
                'key' => 'renewals_due_soon',
                'label' => 'Renovaciones por vencer',
                'value' => $this->renewalsDueSoonCount,
                'href' => $canManage
                    ? route('alerts.renewals.index', array_merge(['type' => 'due-soon', 'windowDays' => $this->renewalDueSoonWindowDays], $filters))
                    : null,
                'variant' => 'warning',
            ],
            $canManage ? [
                'key' => 'pending_tasks',
                'label' => 'Tareas pendientes',
                'value' => $this->pendingTasksActiveCount,
                'href' => route('pending-tasks.index'),
                'variant' => 'info',
            ] : null,
        ]));
    }

    private function loadInventoryValue(): void
    {
        $topN = (int) config('gatic.dashboard.value.top_n', 5);
        $this->valueBreakdownTopN = $topN > 0 ? $topN : 5;

        $baseValueQuery = DB::table('assets')
            ->join('products', 'assets.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('assets.status', '!=', Asset::STATUS_RETIRED)
            ->whereNotNull('assets.acquisition_cost')
            ->whereNull('assets.deleted_at')
            ->whereNull('products.deleted_at')
            ->whereNull('categories.deleted_at');

        if ($this->locationId !== null) {
            $baseValueQuery->where('assets.location_id', '=', $this->locationId);
        }

        if ($this->categoryId !== null) {
            $baseValueQuery->where('categories.id', '=', $this->categoryId);
        }

        if ($this->brandId !== null) {
            $baseValueQuery->where('products.brand_id', '=', $this->brandId);
        }

        $defaultCurrency = $this->defaultCurrency;
        $baseDefaultCurrencyQuery = (clone $baseValueQuery)
            ->where(static function ($query) use ($defaultCurrency): void {
                $query->where('assets.acquisition_currency', '=', $defaultCurrency)
                    ->orWhereNull('assets.acquisition_currency');
            });

        $totalValue = (float) (clone $baseDefaultCurrencyQuery)->sum('assets.acquisition_cost');
        $this->totalInventoryValue = number_format($totalValue, 2, '.', '');

        // Value breakdown by Category (via Product relationship)
        /** @var list<object{category_id: int, category_name: string, total_value: string}> $categoryBreakdown */
        $categoryBreakdown = (clone $baseDefaultCurrencyQuery)
            ->selectRaw('categories.id as category_id, categories.name as category_name, SUM(assets.acquisition_cost) as total_value')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_value')
            ->limit($this->valueBreakdownTopN)
            ->get()
            ->all();

        $this->valueByCategory = array_map(static fn (object $row): array => [
            'name' => (string) $row->category_name,
            'value' => number_format((float) $row->total_value, 2, '.', ''),
            'id' => (int) $row->category_id,
        ], $categoryBreakdown);

        $categoriesCount = (int) (clone $baseDefaultCurrencyQuery)->distinct()->count('categories.id');
        $sumTopCategories = array_reduce($this->valueByCategory, static fn (float $carry, array $item): float => $carry + (float) $item['value'], 0.0);
        $otherCategoriesValue = (float) $this->totalInventoryValue - $sumTopCategories;
        if ($categoriesCount > $this->valueBreakdownTopN && $otherCategoriesValue > 0.004) {
            $this->valueByCategory[] = [
                'name' => 'Otros',
                'value' => number_format($otherCategoriesValue, 2, '.', ''),
                'id' => null,
            ];
        }

        // Value breakdown by Brand (via Product relationship)
        /** @var list<object{brand_id: int|null, brand_name: string, total_value: string}> $brandBreakdown */
        $brandBreakdown = (clone $baseDefaultCurrencyQuery)
            ->selectRaw('brands.id as brand_id, COALESCE(brands.name, \'Sin marca\') as brand_name, SUM(assets.acquisition_cost) as total_value')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->groupByRaw('brands.id, COALESCE(brands.name, \'Sin marca\')')
            ->orderByDesc('total_value')
            ->limit($this->valueBreakdownTopN)
            ->get()
            ->all();

        $this->valueByBrand = array_map(static fn (object $row): array => [
            'name' => (string) $row->brand_name,
            'value' => number_format((float) $row->total_value, 2, '.', ''),
            'id' => $row->brand_id !== null ? (int) $row->brand_id : null,
        ], $brandBreakdown);

        $brandsCount = (int) (clone $baseDefaultCurrencyQuery)
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->distinct()
            ->count(DB::raw('COALESCE(brands.id, 0)'));
        $sumTopBrands = array_reduce($this->valueByBrand, static fn (float $carry, array $item): float => $carry + (float) $item['value'], 0.0);
        $otherBrandsValue = (float) $this->totalInventoryValue - $sumTopBrands;
        if ($brandsCount > $this->valueBreakdownTopN && $otherBrandsValue > 0.004) {
            $this->valueByBrand[] = [
                'name' => 'Otros',
                'value' => number_format($otherBrandsValue, 2, '.', ''),
                'id' => null,
            ];
        }
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
        $limit = $this->criticalQueuePreviewLimit > 0 ? $this->criticalQueuePreviewLimit : 10;
        $today = Carbon::today();
        $filters = $this->buildFilterParams();
        $returnTo = '/dashboard'.(count($filters) > 0 ? ('?'.http_build_query($filters)) : '');

        $items = [];

        if ($this->loansOverdueCount > 0) {
            $assets = $this->applyAssetFilters(
                Asset::query()
            )
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

            foreach ($assets as $asset) {
                $due = $asset->loan_due_date;
                $days = $due ? $due->diffInDays($today) : null;
                $title = (string) $asset->serial;
                if (is_string($asset->asset_tag) && $asset->asset_tag !== '') {
                    $title = "{$title} ({$asset->asset_tag})";
                }

                $items[] = [
                    'priority' => 100,
                    'score' => is_int($days) ? $days : 0,
                    'type' => 'Préstamo vencido',
                    'variant' => 'danger',
                    'icon' => 'bi-clock-history',
                    'title' => $title,
                    'subtitle' => $asset->product?->name,
                    'detail' => $due?->format('d/m/Y'),
                    'detailHint' => is_int($days) && $days > 0 ? "Hace {$days}d" : null,
                    'location' => $asset->location?->name,
                    'actor' => $asset->currentEmployee?->full_name,
                    'href' => route('inventory.products.assets.show', [
                        'product' => $asset->product_id,
                        'asset' => $asset->id,
                        'returnTo' => $returnTo,
                    ]),
                ];
            }
        }

        if ($this->warrantiesExpiredCount > 0) {
            $assets = $this->applyAssetFilters(
                Asset::query()
            )
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

            foreach ($assets as $asset) {
                $due = $asset->warranty_end_date;
                $days = $due ? $due->diffInDays($today) : null;
                $title = (string) $asset->serial;
                if (is_string($asset->asset_tag) && $asset->asset_tag !== '') {
                    $title = "{$title} ({$asset->asset_tag})";
                }

                $items[] = [
                    'priority' => 90,
                    'score' => is_int($days) ? $days : 0,
                    'type' => 'Garantía vencida',
                    'variant' => 'danger',
                    'icon' => 'bi-shield-x',
                    'title' => $title,
                    'subtitle' => $asset->product?->name,
                    'detail' => $due?->format('d/m/Y'),
                    'detailHint' => is_int($days) && $days > 0 ? "Hace {$days}d" : null,
                    'location' => $asset->location?->name,
                    'actor' => $asset->warrantySupplier?->name,
                    'href' => route('inventory.products.assets.show', [
                        'product' => $asset->product_id,
                        'asset' => $asset->id,
                        'returnTo' => $returnTo,
                    ]),
                ];
            }
        }

        if ($this->renewalsOverdueCount > 0) {
            $assets = $this->applyAssetFilters(
                Asset::query()
            )
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

            foreach ($assets as $asset) {
                $due = $asset->expected_replacement_date;
                $days = $due ? $due->diffInDays($today) : null;
                $title = (string) $asset->serial;
                if (is_string($asset->asset_tag) && $asset->asset_tag !== '') {
                    $title = "{$title} ({$asset->asset_tag})";
                }

                $items[] = [
                    'priority' => 80,
                    'score' => is_int($days) ? $days : 0,
                    'type' => 'Renovación vencida',
                    'variant' => 'danger',
                    'icon' => 'bi-arrow-repeat',
                    'title' => $title,
                    'subtitle' => $asset->product?->name,
                    'detail' => $due?->format('d/m/Y'),
                    'detailHint' => is_int($days) && $days > 0 ? "Hace {$days}d" : null,
                    'location' => $asset->location?->name,
                    'actor' => null,
                    'href' => route('inventory.products.assets.show', [
                        'product' => $asset->product_id,
                        'asset' => $asset->id,
                        'returnTo' => $returnTo,
                    ]),
                ];
            }
        }

        if ($this->lowStockProductsCount > 0) {
            $products = $this->applyProductFilters(
                Product::query()
            )
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

            foreach ($products as $product) {
                $qty = (int) ($product->qty_total ?? 0);
                $threshold = (int) ($product->low_stock_threshold ?? 0);
                $gap = $threshold - $qty;
                $detail = "{$qty} / {$threshold}";

                $items[] = [
                    'priority' => 70,
                    'score' => $gap,
                    'type' => 'Stock bajo',
                    'variant' => 'warning',
                    'icon' => 'bi-box-seam',
                    'title' => (string) $product->name,
                    'subtitle' => $product->brand?->name,
                    'detail' => $detail,
                    'detailHint' => $gap > 0 ? "Faltan {$gap}" : null,
                    'location' => null,
                    'actor' => null,
                    'href' => route('inventory.products.show', [
                        'product' => $product->id,
                    ]),
                ];
            }
        }

        usort($items, static function (array $a, array $b): int {
            $priority = ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0);
            if ($priority !== 0) {
                return $priority;
            }

            return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
        });

        $normalized = [];
        foreach (array_slice($items, 0, $limit) as $item) {
            unset($item['priority'], $item['score']);
            $normalized[] = $item;
        }

        $this->criticalQueue = $normalized;
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
