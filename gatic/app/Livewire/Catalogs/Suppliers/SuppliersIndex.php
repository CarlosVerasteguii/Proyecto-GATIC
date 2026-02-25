<?php

namespace App\Livewire\Catalogs\Suppliers;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Supplier;
use App\Support\Catalogs\CatalogUsage;
use App\Support\Errors\ErrorReporter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
class SuppliersIndex extends Component
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

    public function delete(int $supplierId): void
    {
        Gate::authorize('catalogs.manage');

        $supplier = Supplier::query()->findOrFail($supplierId);

        try {
            $usageCounts = CatalogUsage::usageCounts('suppliers', $supplier->id);
        } catch (Throwable $exception) {
            if (app()->environment(['local', 'testing'])) {
                throw $exception;
            }

            $errorId = app(ErrorReporter::class)->report($exception, request());
            $this->toastError(
                'No se pudo validar si el proveedor está en uso.',
                title: 'Error inesperado',
                errorId: $errorId
            );

            return;
        }

        if ($usageCounts !== []) {
            $details = CatalogUsage::formatUsageCounts($usageCounts);
            $this->toastError("No se puede eliminar: el proveedor está en uso ({$details}).");

            return;
        }

        $supplier->delete();

        $this->toastSuccess('Proveedor eliminado.');
    }

    public function render(): View
    {
        Gate::authorize('catalogs.manage');

        $search = Supplier::normalizeName($this->search);
        $escapedSearch = $search !== null ? $this->escapeLike($search) : null;

        $suppliers = Supplier::query()
            ->when($escapedSearch, function ($query) use ($escapedSearch) {
                $query->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
            })
            ->orderBy('name')
            ->paginate(config('gatic.ui.pagination.per_page', 15));

        return view('livewire.catalogs.suppliers.suppliers-index', [
            'suppliers' => $suppliers,
            'summary' => [
                'total' => Supplier::query()->count(),
                'results' => $suppliers->total(),
            ],
        ]);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

}
