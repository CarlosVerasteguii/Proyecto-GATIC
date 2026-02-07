<?php

namespace App\Livewire\Alerts\Loans;

use App\Models\Asset;
use App\Support\Settings\SettingsStore;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class LoanAlertsIndex extends Component
{
    use WithPagination;

    #[Url(as: 'type')]
    public string $type = 'overdue';

    #[Url(as: 'windowDays')]
    public ?int $windowDays = null;

    public function mount(): void
    {
        $this->normalizeFilters();
    }

    public function updatedType(): void
    {
        $this->normalizeFilters();
        $this->resetPage();
    }

    public function updatedWindowDays(): void
    {
        $this->normalizeFilters();
        $this->resetPage();
    }

    /**
     * @return list<int>
     */
    public function getWindowDaysOptions(): array
    {
        return $this->getWindowDaysOptionsFromConfig();
    }

    public function getResolvedWindowDays(): int
    {
        $options = $this->getWindowDaysOptionsFromConfig();
        $default = $this->getDefaultWindowDaysFromConfig($options);

        if ($this->type !== 'due-soon') {
            return $default;
        }

        $value = (int) ($this->windowDays ?? $default);

        return in_array($value, $options, true) ? $value : $default;
    }

    public function render(): View
    {
        \Illuminate\Support\Facades\Gate::authorize('inventory.manage');

        $today = Carbon::today();
        $resolvedWindowDays = $this->getResolvedWindowDays();
        $windowEnd = $today->copy()->addDays($resolvedWindowDays);

        $alerts = Asset::query()
            ->with([
                'product:id,name',
                'currentEmployee:id,rpe,name',
            ])
            ->where('status', Asset::STATUS_LOANED)
            ->whereNotNull('loan_due_date')
            ->when($this->type === 'overdue', function ($query) use ($today) {
                $query->where('loan_due_date', '<', $today->toDateString());
            })
            ->when($this->type === 'due-soon', function ($query) use ($today, $windowEnd) {
                $query->whereBetween('loan_due_date', [$today->toDateString(), $windowEnd->toDateString()]);
            })
            ->orderBy('loan_due_date')
            ->orderBy('serial')
            ->paginate(config('gatic.ui.pagination.per_page', 15));

        $returnTo = $this->buildReturnToPath($alerts->currentPage(), $resolvedWindowDays);

        return view('livewire.alerts.loans.loan-alerts-index', [
            'alerts' => $alerts,
            'today' => $today,
            'resolvedWindowDays' => $resolvedWindowDays,
            'windowDaysOptions' => $this->getWindowDaysOptionsFromConfig(),
            'returnTo' => $returnTo,
        ]);
    }

    private function normalizeFilters(): void
    {
        if (! in_array($this->type, ['overdue', 'due-soon'], true)) {
            $this->type = 'overdue';
        }

        $options = $this->getWindowDaysOptionsFromConfig();
        $default = $this->getDefaultWindowDaysFromConfig($options);

        if ($this->type !== 'due-soon') {
            $this->windowDays = null;

            return;
        }

        $value = (int) ($this->windowDays ?? $default);
        if (! in_array($value, $options, true)) {
            $value = $default;
        }

        $this->windowDays = $value;
    }

    /**
     * @return list<int>
     */
    private function getWindowDaysOptionsFromConfig(): array
    {
        $store = app(SettingsStore::class);
        $options = $store->getIntList('gatic.alerts.loans.due_soon_window_days_options', [7, 14, 30]);
        if ($options === []) {
            $options = [7, 14, 30];
        }

        $options = array_values(array_unique($options));
        sort($options);

        if ($options === []) {
            $options = [7, 14, 30];
        }

        return $options;
    }

    /**
     * @param  list<int>  $options
     */
    private function getDefaultWindowDaysFromConfig(array $options): int
    {
        $store = app(SettingsStore::class);
        $default = $store->getInt('gatic.alerts.loans.due_soon_window_days_default', $options[0] ?? 7);
        if (! in_array($default, $options, true)) {
            $default = (int) ($options[0] ?? 7);
        }

        return $default;
    }

    private function buildReturnToPath(int $page, int $resolvedWindowDays): string
    {
        $params = ['type' => $this->type];

        if ($this->type === 'due-soon') {
            $params['windowDays'] = $resolvedWindowDays;
        }

        if ($page > 1) {
            $params['page'] = $page;
        }

        $url = route('alerts.loans.index', $params);
        $path = parse_url($url, PHP_URL_PATH) ?: '/alerts/loans';
        $query = parse_url($url, PHP_URL_QUERY);

        return is_string($query) && $query !== ''
            ? "{$path}?{$query}"
            : $path;
    }
}
