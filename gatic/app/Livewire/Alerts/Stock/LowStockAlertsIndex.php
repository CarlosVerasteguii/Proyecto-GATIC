<?php

namespace App\Livewire\Alerts\Stock;

use App\Livewire\Alerts\Concerns\LoadsInventoryAlertOptions;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class LowStockAlertsIndex extends Component
{
    use LoadsInventoryAlertOptions;
    use WithPagination;

    #[Url(as: 'category')]
    public ?int $categoryId = null;

    #[Url(as: 'brand')]
    public ?int $brandId = null;

    public function mount(): void
    {
        $this->normalizeFilters();
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

    public function clearFilters(): void
    {
        $this->reset(['categoryId', 'brandId']);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->categoryId !== null || $this->brandId !== null;
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        $alerts = Product::query()
            ->with([
                'category:id,name,is_serialized',
                'brand:id,name',
            ])
            ->when($this->categoryId !== null, fn ($q) => $q->where('category_id', $this->categoryId))
            ->when($this->brandId !== null, fn ($q) => $q->where('brand_id', $this->brandId))
            ->lowStockQuantity()
            ->orderBy('name')
            ->paginate(config('gatic.ui.pagination.per_page', 15));

        return view('livewire.alerts.stock.low-stock-alerts-index', [
            'alerts' => $alerts,
            'categories' => $this->getAlertCategories(),
            'brands' => $this->getAlertBrands(),
        ]);
    }

    private function normalizeFilters(): void
    {
        if ($this->categoryId !== null && $this->categoryId <= 0) {
            $this->categoryId = null;
        }

        if ($this->brandId !== null && $this->brandId <= 0) {
            $this->brandId = null;
        }
    }
}
