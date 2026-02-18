<?php

namespace App\Livewire\Catalogs\Categories;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Category;
use App\Support\Catalogs\CatalogUsage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
class CategoriesIndex extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->reset('search');
        $this->resetPage();
    }

    public function delete(int $categoryId): void
    {
        Gate::authorize('catalogs.manage');

        $category = Category::query()->findOrFail($categoryId);

        try {
            $inUse = CatalogUsage::isInUse('categories', $category->id);
        } catch (Throwable $exception) {
            report($exception);
            $this->toastError('No se pudo validar si la categoría está en uso.');

            return;
        }

        if ($inUse) {
            $this->toastError('No se puede eliminar: la categoría está en uso.');

            return;
        }

        $category->delete();
        $this->toastSuccess('Categoría eliminada.');
    }

    public function render(): View
    {
        Gate::authorize('catalogs.manage');

        $search = Category::normalizeName($this->search);
        $escapedSearch = $search !== null ? $this->escapeLike($search) : null;

        $categories = Category::query()
            ->when($escapedSearch, function ($query) use ($escapedSearch) {
                $query->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
            })
            ->orderBy('name')
            ->paginate(config('gatic.ui.pagination.per_page', 15));

        return view('livewire.catalogs.categories.categories-index', [
            'categories' => $categories,
            'summary' => [
                'total' => Category::query()->count(),
                'results' => $categories->total(),
            ],
        ]);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
