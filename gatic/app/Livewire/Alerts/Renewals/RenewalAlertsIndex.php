<?php

namespace App\Livewire\Alerts\Renewals;

use App\Models\Asset;
use App\Support\Settings\SettingsStore;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class RenewalAlertsIndex extends Component
{
    use WithPagination;

    #[Url(as: 'type')]
    public string $type = 'overdue';

    #[Url(as: 'windowDays')]
    public ?int $windowDays = null;

    #[Url(as: 'location')]
    public ?int $locationId = null;

    #[Url(as: 'category')]
    public ?int $categoryId = null;

    #[Url(as: 'brand')]
    public ?int $brandId = null;

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

    public function updatedLocationId(): void
    {
        $this->normalizeFilters();
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->normalizeFilters();
        $this->resetPage();
    }

    public function updatedBrandId(): void
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
            ])
            ->whereNotNull('expected_replacement_date')
            ->where('status', '!=', Asset::STATUS_RETIRED)
            ->when($this->locationId !== null, fn ($q) => $q->where('location_id', $this->locationId))
            ->when($this->categoryId !== null || $this->brandId !== null, function ($q) {
                $categoryId = $this->categoryId;
                $brandId = $this->brandId;

                $q->whereHas('product', function ($q) use ($categoryId, $brandId) {
                    if ($categoryId !== null) {
                        $q->where('category_id', $categoryId);
                    }
                    if ($brandId !== null) {
                        $q->where('brand_id', $brandId);
                    }
                });
            })
            ->when($this->type === 'overdue', function ($query) use ($today) {
                $query->where('expected_replacement_date', '<', $today->toDateString());
            })
            ->when($this->type === 'due-soon', function ($query) use ($today, $windowEnd) {
                $query->whereBetween('expected_replacement_date', [$today->toDateString(), $windowEnd->toDateString()]);
            })
            ->orderBy('expected_replacement_date')
            ->orderBy('serial')
            ->paginate(config('gatic.ui.pagination.per_page', 15));

        $returnTo = $this->buildReturnToPath($alerts->currentPage(), $resolvedWindowDays);

        return view('livewire.alerts.renewals.renewal-alerts-index', [
            'alerts' => $alerts,
            'today' => $today,
            'resolvedWindowDays' => $resolvedWindowDays,
            'windowDaysOptions' => $this->getWindowDaysOptionsFromConfig(),
            'returnTo' => $returnTo,
            'filterParams' => $this->buildFilterParams(),
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

        if ($this->locationId !== null && $this->locationId <= 0) {
            $this->locationId = null;
        }

        if ($this->categoryId !== null && $this->categoryId <= 0) {
            $this->categoryId = null;
        }

        if ($this->brandId !== null && $this->brandId <= 0) {
            $this->brandId = null;
        }
    }

    /**
     * @return list<int>
     */
    private function getWindowDaysOptionsFromConfig(): array
    {
        $store = app(SettingsStore::class);
        $options = $store->getIntList('gatic.alerts.renewals.due_soon_window_days_options', [30, 60, 90, 180]);
        if ($options === []) {
            $options = [30, 60, 90, 180];
        }

        $options = array_values(array_unique($options));
        sort($options);

        if ($options === []) {
            $options = [30, 60, 90, 180];
        }

        return $options;
    }

    /**
     * @param  list<int>  $options
     */
    private function getDefaultWindowDaysFromConfig(array $options): int
    {
        $store = app(SettingsStore::class);
        $default = $store->getInt('gatic.alerts.renewals.due_soon_window_days_default', $options[0] ?? 90);
        if (! in_array($default, $options, true)) {
            $default = (int) ($options[0] ?? 90);
        }

        return $default;
    }

    private function buildReturnToPath(int $page, int $resolvedWindowDays): string
    {
        $params = array_merge(['type' => $this->type], $this->buildFilterParams());

        if ($this->type === 'due-soon') {
            $params['windowDays'] = $resolvedWindowDays;
        }

        if ($page > 1) {
            $params['page'] = $page;
        }

        $url = route('alerts.renewals.index', $params);
        $path = parse_url($url, PHP_URL_PATH) ?: '/alerts/renewals';
        $query = parse_url($url, PHP_URL_QUERY);

        return is_string($query) && $query !== ''
            ? "{$path}?{$query}"
            : $path;
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
}
