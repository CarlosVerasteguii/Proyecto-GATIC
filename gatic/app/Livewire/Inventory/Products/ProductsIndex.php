<?php

namespace App\Livewire\Inventory\Products;

use App\Models\Asset;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ProductsIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
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
            ->orderBy('products.name')
            ->paginate(15);

        return view('livewire.inventory.products.products-index', [
            'products' => $productsQuery,
        ]);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
