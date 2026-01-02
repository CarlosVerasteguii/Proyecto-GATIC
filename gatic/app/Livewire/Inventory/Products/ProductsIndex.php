<?php

namespace App\Livewire\Inventory\Products;

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

        return view('livewire.inventory.products.products-index', [
            'products' => Product::query()
                ->with(['category', 'brand'])
                ->when($escapedSearch, function ($query) use ($escapedSearch) {
                    $query->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                })
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
