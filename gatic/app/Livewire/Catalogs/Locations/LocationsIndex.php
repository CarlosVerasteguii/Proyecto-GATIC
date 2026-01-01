<?php

namespace App\Livewire\Catalogs\Locations;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Location;
use App\Support\Catalogs\CatalogUsage;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
class LocationsIndex extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    public string $search = '';

    public ?int $locationId = null;

    public string $name = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $locationId): void
    {
        Gate::authorize('catalogs.manage');

        $location = Location::query()->findOrFail($locationId);

        $this->locationId = $location->id;
        $this->name = $location->name;
    }

    public function cancelEdit(): void
    {
        $this->reset(['locationId', 'name']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $uniqueNameRule = Rule::unique('locations', 'name');

        if ($this->locationId) {
            $uniqueNameRule = $uniqueNameRule->ignore($this->locationId);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $uniqueNameRule,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.unique' => 'La ubicacion ya existe.',
        ];
    }

    public function save(): void
    {
        Gate::authorize('catalogs.manage');

        $this->name = Location::normalizeName($this->name) ?? '';

        $this->validate();

        try {
            if (! $this->locationId) {
                Location::query()->create(['name' => $this->name]);

                $this->reset('name');
                $this->toastSuccess('Ubicacion creada.');

                return;
            }

            $location = Location::query()->findOrFail($this->locationId);
            $location->name = $this->name;
            $location->save();
        } catch (QueryException $exception) {
            if ($this->isDuplicateNameException($exception)) {
                $this->addError('name', 'La ubicacion ya existe.');

                return;
            }

            throw $exception;
        }

        $this->reset(['locationId', 'name']);
        $this->toastSuccess('Ubicacion actualizada.');
    }

    public function delete(int $locationId): void
    {
        Gate::authorize('catalogs.manage');

        $location = Location::query()->findOrFail($locationId);

        try {
            $inUse = CatalogUsage::isInUse('locations', $location->id);
        } catch (Throwable $exception) {
            report($exception);
            $this->toastError('No se pudo validar si la ubicación está en uso.');

            return;
        }

        if ($inUse) {
            $this->toastError('No se puede eliminar: la ubicación está en uso.');

            return;
        }

        $location->delete();

        if ($this->locationId === $locationId) {
            $this->reset(['locationId', 'name']);
        }

        $this->toastSuccess('Ubicacion eliminada.');
    }

    public function render(): View
    {
        Gate::authorize('catalogs.manage');

        $search = Location::normalizeName($this->search);
        $escapedSearch = $search !== null ? $this->escapeLike($search) : null;

        return view('livewire.catalogs.locations.locations-index', [
            'locations' => Location::query()
                ->when($escapedSearch, function ($query) use ($escapedSearch) {
                    $query->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                })
                ->orderBy('name')
                ->paginate(15),
            'isEditing' => (bool) $this->locationId,
        ]);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    private function isDuplicateNameException(QueryException $exception): bool
    {
        $errorInfo = $exception->errorInfo;

        if (! is_array($errorInfo) || count($errorInfo) < 2) {
            return false;
        }

        $driverCode = (int) ($errorInfo[1] ?? 0);

        return $driverCode === 1062;
    }
}
