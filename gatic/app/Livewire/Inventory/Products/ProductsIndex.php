<?php

namespace App\Livewire\Inventory\Products;

use App\Models\Asset;
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
class ProductsIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'category')]
    public ?int $categoryId = null;

    #[Url(as: 'brand')]
    public ?int $brandId = null;

    #[Url(as: 'availability')]
    public string $availability = 'all';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedBrandId(): void
    {
        $this->resetPage();
    }

    public function updatedAvailability(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'categoryId', 'brandId', 'availability']);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->categoryId !== null
            || $this->brandId !== null
            || $this->availability !== 'all';
    }

    public function render(): View
    {
        Gate::authorize('inventory.view');

        $search = Product::normalizeName($this->search);
        $escapedSearch = $search !== null ? $this->escapeLike($search) : null;

        $productsQuery = Product::query()
            ->select('products.*')
            ->leftJoin('categories as categories_for_counts', function ($join) {
                $join
                    ->on('categories_for_counts.id', '=', 'products.category_id')
                    ->whereNull('categories_for_counts.deleted_at');
            })
            ->addSelect('categories_for_counts.is_serialized as category_is_serialized')
            ->addSelect([
                'assets_total' => Asset::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('assets.product_id', 'products.id')
                    ->where('assets.status', '!=', Asset::STATUS_RETIRED)
                    ->where('categories_for_counts.is_serialized', true),
                'assets_unavailable' => Asset::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('assets.product_id', 'products.id')
                    ->whereIn('assets.status', Asset::UNAVAILABLE_STATUSES)
                    ->where('categories_for_counts.is_serialized', true),
            ])
            ->with(['category', 'brand'])
            ->when($escapedSearch, function ($query) use ($escapedSearch) {
                $query->whereRaw("products.name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
            })
            ->when($this->categoryId !== null, function ($query) {
                $query->where('products.category_id', $this->categoryId);
            })
            ->when($this->brandId !== null, function ($query) {
                $query->where('products.brand_id', $this->brandId);
            })
            ->when($this->availability === 'with_available', function ($query) {
                $query->havingRaw(
                    'CASE
                        WHEN category_is_serialized = 1 THEN (assets_total - assets_unavailable)
                        ELSE COALESCE(products.qty_total, 0)
                    END > 0'
                );
            })
            ->when($this->availability === 'without_available', function ($query) {
                $query->havingRaw(
                    'CASE
                        WHEN category_is_serialized = 1 THEN (assets_total - assets_unavailable)
                        ELSE COALESCE(products.qty_total, 0)
                    END = 0'
                );
            })
            ->orderBy('products.name')
            ->paginate(config('gatic.ui.pagination.per_page', 15));

        $categories = Category::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $brands = Brand::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.inventory.products.products-index', [
            'products' => $productsQuery,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
