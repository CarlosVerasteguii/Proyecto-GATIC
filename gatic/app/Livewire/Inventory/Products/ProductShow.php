<?php

namespace App\Livewire\Inventory\Products;

use App\Models\Asset;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class ProductShow extends Component
{
    public int $productId;

    public ?Product $productModel = null;

    public bool $productIsSerialized = false;

    public int $total = 0;

    public int $available = 0;

    public int $unavailable = 0;

    /**
     * @var array<int, array{status:string, count:int}>
     */
    public array $statusBreakdown = [];

    public function mount(string $product): void
    {
        Gate::authorize('inventory.view');

        if (! ctype_digit($product)) {
            abort(404);
        }

        $this->productId = (int) $product;

        $this->loadProduct();
    }

    #[On('inventory:product-changed')]
    public function onProductChanged(int $productId): void
    {
        if ($productId !== $this->productId) {
            return;
        }

        $this->loadProduct();
    }

    public function render(): View
    {
        Gate::authorize('inventory.view');

        return view('livewire.inventory.products.product-show', [
            'product' => $this->productModel,
            'productIsSerialized' => $this->productIsSerialized,
        ]);
    }

    private function loadProduct(): void
    {
        $this->productModel = Product::query()
            ->with(['category', 'brand', 'supplier'])
            ->findOrFail($this->productId);

        $this->productIsSerialized = (bool) $this->productModel->category?->is_serialized;

        if (! $this->productIsSerialized) {
            $this->total = (int) ($this->productModel->qty_total ?? 0);
            $this->unavailable = 0;
            $this->available = $this->total;
            $this->statusBreakdown = [];

            return;
        }

        $breakdown = Asset::query()
            ->select('status')
            ->selectRaw('count(*) as total')
            ->where('product_id', $this->productId)
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $breakdownCounts = [];
        foreach ($breakdown as $status => $count) {
            $breakdownCounts[(string) $status] = (int) $count;
        }

        $retiredCount = $breakdownCounts[Asset::STATUS_RETIRED] ?? 0;
        $totalIncludingRetired = array_sum($breakdownCounts);

        $this->total = max($totalIncludingRetired - $retiredCount, 0);

        $this->unavailable = 0;
        foreach (Asset::UNAVAILABLE_STATUSES as $status) {
            $this->unavailable += (int) ($breakdownCounts[$status] ?? 0);
        }

        $this->available = max($this->total - $this->unavailable, 0);

        $this->statusBreakdown = [];
        foreach (Asset::STATUSES as $status) {
            $this->statusBreakdown[] = [
                'status' => $status,
                'count' => (int) ($breakdownCounts[$status] ?? 0),
            ];
        }
    }
}
