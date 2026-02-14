<?php

namespace App\Livewire\Inventory\Assets;

use App\Models\Asset;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class AssetShow extends Component
{
    public int $productId;

    public int $assetId;

    public ?Product $productModel = null;

    public ?Asset $assetModel = null;

    public ?string $returnTo = null;

    public function mount(string $product, string $asset): void
    {
        Gate::authorize('inventory.view');

        if (! ctype_digit($product) || ! ctype_digit($asset)) {
            abort(404);
        }

        $this->productId = (int) $product;
        $this->assetId = (int) $asset;
        $this->returnTo = $this->sanitizeReturnTo(request()->query('returnTo'));

        $this->productModel = Product::query()
            ->with('category')
            ->findOrFail($this->productId);

        if (! $this->productModel->category?->is_serialized) {
            abort(404);
        }

        $this->assetModel = Asset::query()
            ->with(['location', 'currentEmployee', 'contract.supplier', 'warrantySupplier'])
            ->where('product_id', $this->productId)
            ->findOrFail($this->assetId);
    }

    #[On('inventory:asset-changed')]
    public function onAssetChanged(int $assetId): void
    {
        if ($assetId !== $this->assetId) {
            return;
        }

        $this->assetModel = Asset::query()
            ->with(['location', 'currentEmployee', 'contract.supplier', 'warrantySupplier'])
            ->where('product_id', $this->productId)
            ->findOrFail($this->assetId);
    }

    public function render(): View
    {
        Gate::authorize('inventory.view');

        return view('livewire.inventory.assets.asset-show', [
            'product' => $this->productModel,
            'asset' => $this->assetModel,
            'returnTo' => $this->returnTo,
        ]);
    }

    private function sanitizeReturnTo(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || ! str_starts_with($value, '/') || str_starts_with($value, '//')) {
            return null;
        }

        if (str_contains($value, "\n") || str_contains($value, "\r") || strlen($value) > 2000) {
            return null;
        }

        return $value;
    }
}
