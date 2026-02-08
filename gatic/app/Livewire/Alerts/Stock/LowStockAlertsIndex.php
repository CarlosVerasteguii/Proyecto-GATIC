<?php

namespace App\Livewire\Alerts\Stock;

use App\Models\Brand;
use App\Models\Category;
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
    use WithPagination;

    #[Url(as: 'category')]
    public ?int $categoryId = null;

    #[Url(as: 'brand')]
    public ?int $brandId = null;

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedBrandId(): void
    {
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

        $categories = Category::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $brands = Brand::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

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
            'categories' => $categories,
            'brands' => $brands,
            'filterParams' => $this->buildFilterParams(),
        ]);
    }

    /**
     * @return array{category?: int, brand?: int}
     */
    private function buildFilterParams(): array
    {
        return array_filter([
            'category' => $this->categoryId,
            'brand' => $this->brandId,
        ], static fn ($value): bool => $value !== null);
    }
}
