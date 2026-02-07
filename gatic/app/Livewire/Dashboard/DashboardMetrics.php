<?php

namespace App\Livewire\Dashboard;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
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

    public int $assetsLoaned = 0;

    public int $assetsPendingRetirement = 0;

    public int $assetsAssigned = 0;

    public int $assetsUnavailable = 0;

    public int $movementsToday = 0;

    public int $loansOverdueCount = 0;

    public int $loansDueSoonCount = 0;

    public int $loanDueSoonWindowDays = 7;

    public int $lowStockProductsCount = 0;

    public string $totalInventoryValue = '0.00';

    public string $defaultCurrency = 'MXN';

    public int $valueBreakdownTopN = 5;

    /**
     * @var array<int, array{name: string, value: string}>
     */
    public array $valueByCategory = [];

    /**
     * @var array<int, array{name: string, value: string}>
     */
    public array $valueByBrand = [];

    public function mount(): void
    {
        $store = app(SettingsStore::class);
        $currency = $store->getString('gatic.inventory.money.default_currency', 'MXN');
        $this->defaultCurrency = strtoupper(trim($currency !== '' ? $currency : 'MXN'));

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
            if (Gate::allows('inventory.manage')) {
                $this->loadInventoryValue();
            } else {
                $this->resetInventoryValue();
            }
            $this->lastUpdatedAtIso = now()->toIso8601String();
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
    }

    private function loadLoanDueDateAlertCounts(): void
    {
        $today = Carbon::today();

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

        $windowEnd = $today->copy()->addDays($defaultWindowDays);

        $this->loansDueSoonCount = (clone $baseQuery)
            ->whereBetween('loan_due_date', [$today->toDateString(), $windowEnd->toDateString()])
            ->count();
    }

    private function loadLowStockProductsCount(): void
    {
        $this->lowStockProductsCount = Product::query()->lowStockQuantity()->count();
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
        /** @var list<object{category_name: string, total_value: string}> $categoryBreakdown */
        $categoryBreakdown = (clone $baseDefaultCurrencyQuery)
            ->selectRaw('categories.name as category_name, SUM(assets.acquisition_cost) as total_value')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_value')
            ->limit($this->valueBreakdownTopN)
            ->get()
            ->all();

        $this->valueByCategory = array_map(static fn (object $row): array => [
            'name' => (string) $row->category_name,
            'value' => number_format((float) $row->total_value, 2, '.', ''),
        ], $categoryBreakdown);

        $categoriesCount = (int) (clone $baseDefaultCurrencyQuery)->distinct()->count('categories.id');
        $sumTopCategories = array_reduce($this->valueByCategory, static fn (float $carry, array $item): float => $carry + (float) $item['value'], 0.0);
        $otherCategoriesValue = (float) $this->totalInventoryValue - $sumTopCategories;
        if ($categoriesCount > $this->valueBreakdownTopN && $otherCategoriesValue > 0.004) {
            $this->valueByCategory[] = [
                'name' => 'Otros',
                'value' => number_format($otherCategoriesValue, 2, '.', ''),
            ];
        }

        // Value breakdown by Brand (via Product relationship)
        /** @var list<object{brand_name: string, total_value: string}> $brandBreakdown */
        $brandBreakdown = (clone $baseDefaultCurrencyQuery)
            ->selectRaw('COALESCE(brands.name, \'Sin marca\') as brand_name, SUM(assets.acquisition_cost) as total_value')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->groupByRaw('COALESCE(brands.id, 0), COALESCE(brands.name, \'Sin marca\')')
            ->orderByDesc('total_value')
            ->limit($this->valueBreakdownTopN)
            ->get()
            ->all();

        $this->valueByBrand = array_map(static fn (object $row): array => [
            'name' => (string) $row->brand_name,
            'value' => number_format((float) $row->total_value, 2, '.', ''),
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
            ];
        }
    }

    public function render(): View
    {
        return view('livewire.dashboard.dashboard-metrics');
    }
}
