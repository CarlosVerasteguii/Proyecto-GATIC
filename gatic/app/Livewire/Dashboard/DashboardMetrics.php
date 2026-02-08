<?php

namespace App\Livewire\Dashboard;

use App\Models\Asset;
use App\Models\AssetMovement;
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
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
class DashboardMetrics extends Component
{
    public string $lastUpdatedAtIso = '';

    public ?string $errorId = null;

    public int $trendRangeDays = 30;

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

    public function mount(): void
    {
        $store = app(SettingsStore::class);
        $currency = $store->getString('gatic.inventory.money.default_currency', 'MXN');
        $this->defaultCurrency = strtoupper(trim($currency !== '' ? $currency : 'MXN'));

        $this->trendRangeDays = $this->normalizeTrendRangeDays($this->trendRangeDays);
        $this->refreshMetrics();
    }

    public function updatedTrendRangeDays(): void
    {
        $this->trendRangeDays = $this->normalizeTrendRangeDays($this->trendRangeDays);
        $this->refreshMetrics();
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
            $this->loadAssetStatusCounts();
            $this->loadMovementsToday();
            $this->loadLoanDueDateAlertCounts();
            $this->loadLowStockProductsCount();
            $this->loadWarrantyAlertCounts();
            $this->loadRenewalAlertCounts();
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
        $counts = Asset::query()
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

        $assetMovementsCount = AssetMovement::query()
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();

        $quantityMovementsCount = ProductQuantityMovement::query()
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();

        $this->movementsToday = $assetMovementsCount + $quantityMovementsCount;

        $startOfYesterday = Carbon::yesterday()->startOfDay();
        $endOfYesterday = Carbon::yesterday()->endOfDay();

        $assetMovementsYesterdayCount = AssetMovement::query()
            ->whereBetween('created_at', [$startOfYesterday, $endOfYesterday])
            ->count();

        $quantityMovementsYesterdayCount = ProductQuantityMovement::query()
            ->whereBetween('created_at', [$startOfYesterday, $endOfYesterday])
            ->count();

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

        $baseQuery = Asset::query()
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
        $this->lowStockProductsCount = Product::query()->lowStockQuantity()->count();
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

        $baseQuery = Asset::query()
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

        $baseQuery = Asset::query()
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

        $assetRows = AssetMovement::query()
            ->selectRaw('DATE(created_at) as day, type, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('type', AssetMovement::TYPES)
            ->groupByRaw('DATE(created_at), type')
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

        $qtyRows = ProductQuantityMovement::query()
            ->selectRaw('DATE(created_at) as day, direction, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('direction', ProductQuantityMovement::DIRECTIONS)
            ->groupByRaw('DATE(created_at), direction')
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

        return array_values(array_filter([
            [
                'key' => 'loans_overdue',
                'label' => 'Préstamos vencidos',
                'value' => $this->loansOverdueCount,
                'href' => $canManage ? route('alerts.loans.index', ['type' => 'overdue']) : null,
                'variant' => 'danger',
            ],
            [
                'key' => 'loans_due_soon',
                'label' => 'Préstamos por vencer',
                'value' => $this->loansDueSoonCount,
                'href' => $canManage ? route('alerts.loans.index', ['type' => 'due-soon', 'windowDays' => $this->loanDueSoonWindowDays]) : null,
                'variant' => 'warning',
            ],
            [
                'key' => 'warranties_expired',
                'label' => 'Garantías vencidas',
                'value' => $this->warrantiesExpiredCount,
                'href' => $canManage ? route('alerts.warranties.index', ['type' => 'expired']) : null,
                'variant' => 'danger',
            ],
            [
                'key' => 'warranties_due_soon',
                'label' => 'Garantías por vencer',
                'value' => $this->warrantiesDueSoonCount,
                'href' => $canManage ? route('alerts.warranties.index', ['type' => 'due-soon', 'windowDays' => $this->warrantyDueSoonWindowDays]) : null,
                'variant' => 'warning',
            ],
            [
                'key' => 'low_stock',
                'label' => 'Stock bajo',
                'value' => $this->lowStockProductsCount,
                'href' => $canManage ? route('alerts.stock.index') : null,
                'variant' => 'warning',
            ],
            [
                'key' => 'renewals_overdue',
                'label' => 'Renovaciones vencidas',
                'value' => $this->renewalsOverdueCount,
                'href' => $canManage ? route('alerts.renewals.index', ['type' => 'overdue']) : null,
                'variant' => 'danger',
            ],
            [
                'key' => 'renewals_due_soon',
                'label' => 'Renovaciones por vencer',
                'value' => $this->renewalsDueSoonCount,
                'href' => $canManage ? route('alerts.renewals.index', ['type' => 'due-soon', 'windowDays' => $this->renewalDueSoonWindowDays]) : null,
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

    public function render(): View
    {
        return view('livewire.dashboard.dashboard-metrics');
    }
}
