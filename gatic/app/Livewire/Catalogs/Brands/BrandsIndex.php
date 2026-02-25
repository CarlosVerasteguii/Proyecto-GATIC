<?php

namespace App\Livewire\Catalogs\Brands;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Brand;
use App\Support\Catalogs\CatalogUsage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
class BrandsIndex extends Component
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

    public function delete(int $brandId): void
    {
        Gate::authorize('catalogs.manage');

        $brand = Brand::query()->findOrFail($brandId);

        try {
            $usageCounts = CatalogUsage::usageCounts('brands', $brand->id);
        } catch (Throwable $exception) {
            report($exception);
            $this->toastError('No se pudo validar si la marca está en uso.');

            return;
        }

        if ($usageCounts !== []) {
            $details = CatalogUsage::formatUsageCounts($usageCounts);
            $this->toastError("No se puede eliminar: la marca está en uso ({$details}).");

            return;
        }

        $brand->delete();

        $this->toastSuccess('Marca eliminada.');
    }

    public function render(): View
    {
        Gate::authorize('catalogs.manage');

        $search = Brand::normalizeName($this->search);
        $escapedSearch = $search !== null ? $this->escapeLike($search) : null;

        $brands = Brand::query()
            ->when($escapedSearch, function ($query) use ($escapedSearch) {
                $query->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
            })
            ->orderBy('name')
            ->paginate(config('gatic.ui.pagination.per_page', 15));

        return view('livewire.catalogs.brands.brands-index', [
            'brands' => $brands,
            'summary' => [
                'total' => Brand::query()->count(),
                'results' => $brands->total(),
            ],
        ]);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

}
