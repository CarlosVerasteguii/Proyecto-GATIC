<?php

namespace App\Livewire\Alerts\Warranties;

use App\Models\Asset;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class WarrantyAlertsIndex extends Component
{
    use WithPagination;

    #[Url(as: 'type')]
    public string $type = 'expired';

    #[Url(as: 'windowDays')]
    public ?int $windowDays = null;

    public function mount(): void
    {
        \Illuminate\Support\Facades\Gate::authorize('inventory.manage');

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
                'location:id,name',
                'warrantySupplier:id,name',
            ])
            ->whereNotNull('warranty_end_date')
            ->where('status', '!=', Asset::STATUS_RETIRED)
            ->when($this->type === 'expired', function ($query) use ($today) {
                $query->where('warranty_end_date', '<', $today->toDateString());
            })
            ->when($this->type === 'due-soon', function ($query) use ($today, $windowEnd) {
                $query->whereBetween('warranty_end_date', [$today->toDateString(), $windowEnd->toDateString()]);
            })
            ->orderBy('warranty_end_date')
            ->orderBy('serial')
            ->paginate(config('gatic.ui.pagination.per_page', 15));

        $returnTo = $this->buildReturnToPath($alerts->currentPage(), $resolvedWindowDays);

        return view('livewire.alerts.warranties.warranty-alerts-index', [
            'alerts' => $alerts,
            'today' => $today,
            'resolvedWindowDays' => $resolvedWindowDays,
            'windowDaysOptions' => $this->getWindowDaysOptionsFromConfig(),
            'returnTo' => $returnTo,
        ]);
    }

    private function normalizeFilters(): void
    {
        if (! in_array($this->type, ['expired', 'due-soon'], true)) {
            $this->type = 'expired';
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
        $options = config('gatic.alerts.warranties.due_soon_window_days_options', [7, 14, 30]);
        if (! is_array($options) || $options === []) {
            $options = [7, 14, 30];
        }

        $options = array_values(array_unique(array_map('intval', $options)));
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
        $default = (int) config('gatic.alerts.warranties.due_soon_window_days_default', $options[0] ?? 30);
        if (! in_array($default, $options, true)) {
            $default = (int) ($options[0] ?? 30);
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

        $url = route('alerts.warranties.index', $params);
        $path = parse_url($url, PHP_URL_PATH) ?: '/alerts/warranties';
        $query = parse_url($url, PHP_URL_QUERY);

        return is_string($query) && $query !== ''
            ? "{$path}?{$query}"
            : $path;
    }
}
