<?php

namespace App\Livewire\Dashboard;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Product;
use App\Models\ProductQuantityMovement;
use App\Support\Errors\ErrorReporter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
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

    public function mount(): void
    {
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

        $allowedOptions = config('gatic.alerts.loans.due_soon_window_days_options', [7, 14, 30]);
        if (! is_array($allowedOptions) || $allowedOptions === []) {
            $allowedOptions = [7, 14, 30];
        }

        $defaultWindowDays = (int) config('gatic.alerts.loans.due_soon_window_days_default', $allowedOptions[0] ?? 7);
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

    public function render(): View
    {
        return view('livewire.dashboard.dashboard-metrics');
    }
}
