<?php

namespace App\Livewire\Catalogs\Locations;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Location;
use App\Support\Catalogs\CatalogUsage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
class LocationsIndex extends Component
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

    public function delete(int $locationId): void
    {
        Gate::authorize('catalogs.manage');

        $location = Location::query()->findOrFail($locationId);

        try {
            $usageCounts = CatalogUsage::usageCounts('locations', $location->id);
        } catch (Throwable $exception) {
            report($exception);
            $this->toastError('No se pudo validar si la ubicación está en uso.');

            return;
        }

        if ($usageCounts !== []) {
            $details = CatalogUsage::formatUsageCounts($usageCounts);
            $this->toastError("No se puede eliminar: la ubicación está en uso ({$details}).");

            return;
        }

        $location->delete();

        $this->toastSuccess('Ubicación eliminada.');
    }

    public function render(): View
    {
        Gate::authorize('catalogs.manage');

        $search = Location::normalizeName($this->search);
        $escapedSearch = $search !== null ? $this->escapeLike($search) : null;

        $locations = Location::query()
            ->when($escapedSearch, function ($query) use ($escapedSearch) {
                $query->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
            })
            ->orderBy('name')
            ->paginate(config('gatic.ui.pagination.per_page', 15));

        return view('livewire.catalogs.locations.locations-index', [
            'locations' => $locations,
            'summary' => [
                'total' => Location::query()->count(),
                'results' => $locations->total(),
            ],
        ]);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
