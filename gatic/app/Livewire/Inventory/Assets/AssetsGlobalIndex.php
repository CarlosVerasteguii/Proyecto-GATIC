<?php

namespace App\Livewire\Inventory\Assets;

use App\Models\Asset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AssetsGlobalIndex extends Component
{
    use WithPagination;

    private const STATUS_ALL = 'all';

    private const STATUS_UNAVAILABLE = 'unavailable';

    /**
     * @var array<string, string>
     */
    private const SORT_COLUMNS = [
        'product' => 'products.name',
        'serial' => 'assets.serial',
        'asset_tag' => 'assets.asset_tag',
        'status' => 'assets.status',
        'location' => 'locations.name',
    ];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'location')]
    public ?int $locationId = null;

    #[Url(as: 'category')]
    public ?int $categoryId = null;

    #[Url(as: 'brand')]
    public ?int $brandId = null;

    #[Url(as: 'status')]
    public string $status = self::STATUS_ALL;

    #[Url(as: 'sort')]
    public string $sort = 'serial';

    #[Url(as: 'dir')]
    public string $direction = 'asc';

    public function mount(): void
    {
        Gate::authorize('inventory.view');

        $this->status = $this->normalizeStatus($this->status);
        $this->sort = $this->normalizeSort($this->sort);
        $this->direction = $this->normalizeDirection($this->direction);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLocationId(): void
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

    public function updatedStatus(): void
    {
        $this->status = $this->normalizeStatus($this->status);
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'locationId', 'categoryId', 'brandId', 'status']);
        $this->status = self::STATUS_ALL;
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->locationId !== null
            || $this->categoryId !== null
            || $this->brandId !== null
            || $this->status !== self::STATUS_ALL;
    }

    public function sortBy(string $key): void
    {
        $key = $this->normalizeSort($key);

        if ($this->sort === $key) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $key;
            $this->direction = 'asc';
        }

        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize('inventory.view');

        $this->status = $this->normalizeStatus($this->status);
        $this->sort = $this->normalizeSort($this->sort);
        $this->direction = $this->normalizeDirection($this->direction);

        $escapedSearch = trim($this->search) !== '' ? $this->escapeLike(trim($this->search)) : null;

        $locations = Location::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $categories = Category::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $brands = Brand::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        $query = Asset::query()
            ->select('assets.*')
            ->join('products', function ($join) {
                $join->on('products.id', '=', 'assets.product_id')
                    ->whereNull('products.deleted_at');
            })
            ->leftJoin('locations', function ($join) {
                $join->on('locations.id', '=', 'assets.location_id')
                    ->whereNull('locations.deleted_at');
            })
            ->with(['product.category', 'location', 'currentEmployee'])
            ->when($escapedSearch, function ($query) use ($escapedSearch) {
                $query->where(function ($query) use ($escapedSearch) {
                    $query->whereRaw("assets.serial like ? escape '\\\\'", ["%{$escapedSearch}%"])
                        ->orWhereRaw("assets.asset_tag like ? escape '\\\\'", ["%{$escapedSearch}%"])
                        ->orWhereRaw("products.name like ? escape '\\\\'", ["%{$escapedSearch}%"])
                        ->orWhereRaw("locations.name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                });
            })
            ->when($this->locationId !== null, function ($query) {
                $query->where('assets.location_id', $this->locationId);
            })
            ->when($this->categoryId !== null, function ($query) {
                $query->where('products.category_id', $this->categoryId);
            })
            ->when($this->brandId !== null, function ($query) {
                $query->where('products.brand_id', $this->brandId);
            });

        if ($this->status === self::STATUS_ALL) {
            $query->where('assets.status', '!=', Asset::STATUS_RETIRED);
        } elseif ($this->status === self::STATUS_UNAVAILABLE) {
            $query->whereIn('assets.status', Asset::UNAVAILABLE_STATUSES);
        } else {
            $query->where('assets.status', $this->status);
        }

        $sortColumn = self::SORT_COLUMNS[$this->sort] ?? self::SORT_COLUMNS['serial'];

        return view('livewire.inventory.assets.assets-global-index', [
            'locations' => $locations,
            'categories' => $categories,
            'brands' => $brands,
            'assetStatuses' => Asset::STATUSES,
            'assets' => $query
                ->orderBy($sortColumn, $this->direction)
                ->orderBy('assets.id')
                ->paginate(config('gatic.ui.pagination.per_page', 15)),
        ]);
    }

    private function normalizeStatus(string $status): string
    {
        $normalized = trim($status);
        if ($normalized === '') {
            return self::STATUS_ALL;
        }

        $allowed = array_merge([self::STATUS_ALL, self::STATUS_UNAVAILABLE], Asset::STATUSES);

        return in_array($normalized, $allowed, true) ? $normalized : self::STATUS_ALL;
    }

    private function normalizeSort(string $sort): string
    {
        $normalized = trim($sort);
        if ($normalized === '' || ! array_key_exists($normalized, self::SORT_COLUMNS)) {
            return 'serial';
        }

        return $normalized;
    }

    private function normalizeDirection(string $direction): string
    {
        $normalized = strtolower(trim($direction));

        return in_array($normalized, ['asc', 'desc'], true) ? $normalized : 'asc';
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
