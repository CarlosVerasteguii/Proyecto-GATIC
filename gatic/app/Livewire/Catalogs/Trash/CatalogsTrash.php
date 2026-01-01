<?php

namespace App\Livewire\Catalogs\Trash;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class CatalogsTrash extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    public string $tab = 'categories';

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        Gate::authorize('admin-only');

        if (! in_array($tab, ['categories', 'brands', 'locations'], true)) {
            abort(404);
        }

        $this->tab = $tab;
        $this->resetPage();
    }

    public function restore(string $type, int $id): void
    {
        Gate::authorize('admin-only');

        if (! in_array($type, ['categories', 'brands', 'locations'], true)) {
            abort(404);
        }

        match ($type) {
            'categories' => Category::query()->onlyTrashed()->findOrFail($id)->restore(),
            'brands' => Brand::query()->onlyTrashed()->findOrFail($id)->restore(),
            'locations' => Location::query()->onlyTrashed()->findOrFail($id)->restore(),
        };

        $this->toastSuccess('Registro restaurado.');
        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize('admin-only');

        $search = match ($this->tab) {
            'categories' => Category::normalizeName($this->search),
            'brands' => Brand::normalizeName($this->search),
            'locations' => Location::normalizeName($this->search),
            default => null,
        };

        $escapedSearch = $search !== null ? $this->escapeLike($search) : null;

        return view('livewire.catalogs.trash.catalogs-trash', [
            'tab' => $this->tab,
            'categories' => $this->tab === 'categories'
                ? Category::query()
                    ->onlyTrashed()
                    ->when($escapedSearch, function ($query) use ($escapedSearch) {
                        $query->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                    })
                    ->orderBy('name')
                    ->paginate(15)
                : null,
            'brands' => $this->tab === 'brands'
                ? Brand::query()
                    ->onlyTrashed()
                    ->when($escapedSearch, function ($query) use ($escapedSearch) {
                        $query->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                    })
                    ->orderBy('name')
                    ->paginate(15)
                : null,
            'locations' => $this->tab === 'locations'
                ? Location::query()
                    ->onlyTrashed()
                    ->when($escapedSearch, function ($query) use ($escapedSearch) {
                        $query->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                    })
                    ->orderBy('name')
                    ->paginate(15)
                : null,
        ]);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
