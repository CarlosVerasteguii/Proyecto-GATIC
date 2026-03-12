<?php

namespace App\Livewire\Inventory\Products;

use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
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

    private const AVAILABILITY_ALL = 'all';

    private const AVAILABILITY_WITH_AVAILABLE = 'with_available';

    private const AVAILABILITY_WITHOUT_AVAILABLE = 'without_available';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'category')]
    public ?int $categoryId = null;

    #[Url(as: 'brand')]
    public ?int $brandId = null;

    #[Url(as: 'availability')]
    public string $availability = self::AVAILABILITY_ALL;

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
        $this->availability = self::AVAILABILITY_ALL;
        $this->showMinCharsMessage = false;
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->categoryId !== null
            || $this->brandId !== null
            || $this->availability !== self::AVAILABILITY_ALL;
    }

    public function render(): View
    {
        Gate::authorize('inventory.view');

        $likePattern = $this->buildLikePattern();
        $productsQuery = $this->buildProductsQuery($likePattern, withRelations: true);

        $categories = Category::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $brands = Brand::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = (clone $productsQuery)
            ->orderBy('products.name')
            ->simplePaginate(config('gatic.ui.pagination.per_page', 15));

        return view('livewire.inventory.products.products-index', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'minChars' => self::MIN_CHARS,
            'summary' => $this->buildSummary($likePattern),
        ]);
    }

    private function buildLikePattern(): ?string
    {
        $search = Product::normalizeName($this->search);

        if ($this->showMinCharsMessage || $search === null) {
            return null;
        }

        $tokens = preg_split('/\\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($tokens === []) {
            return null;
        }

        $escapedTokens = array_map(fn (string $token): string => $this->escapeLike($token), $tokens);

        return implode('%', $escapedTokens).'%';
    }

    /**
     * @return Builder<Product>
     */
    private function buildProductsQuery(?string $likePattern, bool $withRelations): Builder
    {
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

        $query = Product::query()
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
            ->addSelect(DB::raw(
                'case
                    when categories_for_counts.is_serialized = 1 then greatest(coalesce(asset_counts.assets_total, 0) - coalesce(asset_counts.assets_unavailable, 0), 0)
                    else coalesce(products.qty_total, 0)
                end as available_units'
            ))
            ->addSelect(DB::raw(
                'case
                    when categories_for_counts.is_serialized = 0
                        and products.low_stock_threshold is not null
                        and products.qty_total is not null
                        and products.qty_total <= products.low_stock_threshold
                    then 1 else 0
                end as is_low_stock'
            ))
            ->when($withRelations, function (Builder $query) {
                $query->with(['category', 'brand', 'supplier']);
            })
            ->when($likePattern, function (Builder $query) use ($likePattern) {
                $query->whereRaw("products.name like ? escape '\\\\'", [$likePattern]);
            })
            ->when($this->categoryId !== null, function (Builder $query) {
                $query->where('products.category_id', $this->categoryId);
            })
            ->when($this->brandId !== null, function (Builder $query) {
                $query->where('products.brand_id', $this->brandId);
            });

        if ($this->availability === self::AVAILABILITY_WITH_AVAILABLE) {
            $query->havingRaw('available_units > 0');
        }

        if ($this->availability === self::AVAILABILITY_WITHOUT_AVAILABLE) {
            $query->havingRaw('available_units = 0');
        }

        return $query;
    }

    /**
     * @return array{total_products:int, with_available:int, without_available:int, low_stock:int}
     */
    private function buildSummary(?string $likePattern): array
    {
        $summary = DB::query()
            ->fromSub($this->buildProductsQuery($likePattern, withRelations: false), 'inventory_products')
            ->selectRaw('count(*) as total_products')
            ->selectRaw('sum(case when available_units > 0 then 1 else 0 end) as with_available_count')
            ->selectRaw('sum(case when available_units = 0 then 1 else 0 end) as without_available_count')
            ->selectRaw('sum(case when is_low_stock = 1 then 1 else 0 end) as low_stock_count')
            ->first();

        if ($summary === null) {
            return [
                'total_products' => 0,
                'with_available' => 0,
                'without_available' => 0,
                'low_stock' => 0,
            ];
        }

        return [
            'total_products' => (int) $summary->total_products,
            'with_available' => (int) $summary->with_available_count,
            'without_available' => (int) $summary->without_available_count,
            'low_stock' => (int) $summary->low_stock_count,
        ];
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
