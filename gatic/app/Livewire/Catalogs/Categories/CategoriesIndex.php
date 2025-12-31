<?php

namespace App\Livewire\Catalogs\Categories;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class CategoriesIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize('catalogs.manage');

        $search = Category::normalizeName($this->search);

        return view('livewire.catalogs.categories.categories-index', [
            'categories' => Category::query()
                ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orderBy('name')
                ->paginate(15),
        ]);
    }
}
