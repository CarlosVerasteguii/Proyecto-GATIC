<?php

namespace App\Livewire\Inventory\Assets;

use App\Models\Asset;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AssetShow extends Component
{
    public int $productId;

    public int $assetId;

    public ?Product $productModel = null;

    public ?Asset $assetModel = null;

    public function mount(string $product, string $asset): void
    {
        Gate::authorize('inventory.view');

        if (! ctype_digit($product) || ! ctype_digit($asset)) {
            abort(404);
        }

        $this->productId = (int) $product;
        $this->assetId = (int) $asset;

        $this->productModel = Product::query()
            ->with('category')
            ->findOrFail($this->productId);

        if (! $this->productModel->category?->is_serialized) {
            abort(404);
        }

        $this->assetModel = Asset::query()
            ->with(['location', 'currentEmployee'])
            ->where('product_id', $this->productId)
            ->findOrFail($this->assetId);
    }

    public function render(): View
    {
        Gate::authorize('inventory.view');

        return view('livewire.inventory.assets.asset-show', [
            'product' => $this->productModel,
            'asset' => $this->assetModel,
        ]);
    }
}
