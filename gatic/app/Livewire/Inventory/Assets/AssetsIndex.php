<?php

namespace App\Livewire\Inventory\Assets;

use App\Models\Asset;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AssetsIndex extends Component
{
    use WithPagination;

    public int $productId;

    public ?Product $productModel = null;

    public bool $productIsSerialized = false;

    public string $search = '';

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

    public function render(): View
    {
        Gate::authorize('inventory.view');

        if (! $this->productIsSerialized) {
            return view('livewire.inventory.assets.assets-index', [
                'product' => $this->productModel,
                'productIsSerialized' => $this->productIsSerialized,
                'assets' => null,
            ]);
        }

        $escapedSearch = trim($this->search) !== '' ? $this->escapeLike(trim($this->search)) : null;

        return view('livewire.inventory.assets.assets-index', [
            'product' => $this->productModel,
            'productIsSerialized' => $this->productIsSerialized,
            'assets' => Asset::query()
                ->with('location')
                ->where('product_id', $this->productId)
                ->when($escapedSearch, function ($query) use ($escapedSearch) {
                    $query->where(function ($query) use ($escapedSearch) {
                        $query->whereRaw("serial like ? escape '\\\\'", ["%{$escapedSearch}%"])
                            ->orWhereRaw("asset_tag like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                    });
                })
                ->orderBy('serial')
                ->paginate(15),
        ]);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
