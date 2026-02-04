<?php

namespace App\Livewire\Inventory\Products;

use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ProductsIndex extends Component
{
    use WithPagination;

    private const MIN_CHARS = 2;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'category')]
    public ?int $categoryId = null;

    #[Url(as: 'brand')]
    public ?int $brandId = null;

    #[Url(as: 'availability')]
    public string $availability = 'all';

    public bool $showMinCharsMessage = false;

    public function mount(): void
    {
        $normalized = trim($this->search);
        $this->showMinCharsMessage = $normalized !== '' && mb_strlen($normalized) < self::MIN_CHARS;
    }

    public function updatedSearch(): void
    {
        $normalized = trim($this->search);
        $this->showMinCharsMessage = $normalized !== '' && mb_strlen($normalized) < self::MIN_CHARS;
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

    public function applySearch(): void
    {
        $normalized = trim($this->search);
        $this->showMinCharsMessage = $normalized !== '' && mb_strlen($normalized) < self::MIN_CHARS;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'categoryId', 'brandId', 'availability']);
        $this->showMinCharsMessage = false;
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
        $likePattern = null;
        if (! $this->showMinCharsMessage && $search !== null) {
            $tokens = preg_split('/\\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if ($tokens !== []) {
                $escapedTokens = array_map(fn (string $token): string => $this->escapeLike($token), $tokens);
                $likePattern = implode('%', $escapedTokens).'%';
            }
        }

        $unavailableStatuses = Asset::UNAVAILABLE_STATUSES;
        $unavailablePlaceholders = implode(',', array_fill(0, count($unavailableStatuses), '?'));

        $assetCountsSubquery = Asset::query()
            ->select('assets.product_id')
            ->selectRaw(
                'sum(case when assets.status <> ? then 1 else 0 end) as assets_total',
                [Asset::STATUS_RETIRED]
            )
            ->selectRaw(
                "sum(case when assets.status in ($unavailablePlaceholders) then 1 else 0 end) as assets_unavailable",
                $unavailableStatuses
            )
            ->groupBy('assets.product_id');

        $productsQuery = Product::query()
            ->select('products.*')
            ->leftJoin('categories as categories_for_counts', function ($join) {
                $join
                    ->on('categories_for_counts.id', '=', 'products.category_id')
                    ->whereNull('categories_for_counts.deleted_at');
            })
            ->leftJoinSub($assetCountsSubquery, 'asset_counts', function ($join) {
                $join->on('asset_counts.product_id', '=', 'products.id');
            })
            ->addSelect('categories_for_counts.is_serialized as category_is_serialized')
            ->addSelect(DB::raw('coalesce(asset_counts.assets_total, 0) as assets_total'))
            ->addSelect(DB::raw('coalesce(asset_counts.assets_unavailable, 0) as assets_unavailable'))
            ->with(['category', 'brand', 'supplier'])
            ->when($likePattern, function ($query) use ($likePattern) {
                $query->whereRaw("products.name like ? escape '\\\\'", [$likePattern]);
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
            ->simplePaginate(config('gatic.ui.pagination.per_page', 15));

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
            'minChars' => self::MIN_CHARS,
        ]);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
