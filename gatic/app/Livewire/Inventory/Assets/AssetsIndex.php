<?php

namespace App\Livewire\Inventory\Assets;

use App\Models\Asset;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AssetsIndex extends Component
{
    use WithPagination;

    public int $productId;

    public ?Product $productModel = null;

    public bool $productIsSerialized = false;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'location')]
    public ?int $locationId = null;

    #[Url(as: 'status')]
    public string $status = 'all';

    #[On('inventory:asset-changed')]
    public function onAssetChanged(int $assetId): void
    {
        Gate::authorize('inventory.view');
    }

    public function mount(string $product): void
    {
        Gate::authorize('inventory.view');

        if (! ctype_digit($product)) {
            abort(404);
        }

        $this->productId = (int) $product;

        $this->productModel = Product::query()
            ->with('category')
            ->findOrFail($this->productId);

        $this->productIsSerialized = (bool) $this->productModel->category?->is_serialized;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLocationId(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'locationId', 'status']);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->locationId !== null
            || $this->status !== 'all';
    }

    public function render(): View
    {
        Gate::authorize('inventory.view');

        if (! $this->productIsSerialized) {
            return view('livewire.inventory.assets.assets-index', [
                'product' => $this->productModel,
                'productIsSerialized' => $this->productIsSerialized,
                'assets' => null,
                'locations' => collect(),
                'assetStatuses' => [],
            ]);
        }

        $escapedSearch = trim($this->search) !== '' ? $this->escapeLike(trim($this->search)) : null;

        $locations = Location::query()
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.inventory.assets.assets-index', [
            'product' => $this->productModel,
            'productIsSerialized' => $this->productIsSerialized,
            'locations' => $locations,
            'assetStatuses' => Asset::STATUSES,
            'assets' => Asset::query()
                ->with('location')
                ->where('product_id', $this->productId)
                ->when($escapedSearch, function ($query) use ($escapedSearch) {
                    $query->where(function ($query) use ($escapedSearch) {
                        $query->whereRaw("serial like ? escape '\\\\'", ["%{$escapedSearch}%"])
                            ->orWhereRaw("asset_tag like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                    });
                })
                ->when($this->locationId !== null, function ($query) {
                    $query->where('location_id', $this->locationId);
                })
                ->when($this->status !== 'all', function ($query) {
                    $query->where('status', $this->status);
                })
                ->when($this->status === 'all', function ($query) {
                    $query->where('status', '!=', Asset::STATUS_RETIRED);
                })
                ->orderBy('serial')
                ->paginate(config('gatic.ui.pagination.per_page', 15)),
        ]);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
