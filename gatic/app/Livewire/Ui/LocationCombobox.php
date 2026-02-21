<?php

namespace App\Livewire\Ui;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Location;
use App\Support\Errors\ErrorReporter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Throwable;

class LocationCombobox extends Component
{
    use InteractsWithToasts;

    #[Modelable]
    public ?int $locationId = null;

    public string $locationLabel = '';

    public string $search = '';

    public bool $showDropdown = false;

    public ?string $errorId = null;

    public ?string $inputId = null;

    private const MIN_SEARCH_LENGTH = 2;

    private const MAX_RESULTS = 10;

    public function mount(?int $locationId = null, ?string $inputId = null): void
    {
        Gate::authorize('inventory.manage');

        $this->inputId = $inputId;

        if ($locationId === null) {
            return;
        }

        try {
            $location = Location::query()->find($locationId);
        } catch (Throwable $exception) {
            $this->clearLocationData();
            $this->reportException($exception);

            return;
        }

        if ($location !== null) {
            $this->setLocationData($location);
        }
    }

    public function updatedLocationId(?int $locationId): void
    {
        Gate::authorize('inventory.manage');

        if ($locationId === null) {
            $this->clearLocationData();

            return;
        }

        try {
            $location = Location::query()->find($locationId);
        } catch (Throwable $exception) {
            $this->clearLocationData();
            $this->reportException($exception);

            return;
        }

        if (! $location) {
            $this->clearLocationData();

            return;
        }

        $this->setLocationData($location);
    }

    public function updatedSearch(): void
    {
        Gate::authorize('inventory.manage');

        $this->errorId = null;
        $this->showDropdown = true;
    }

    public function selectLocation(int $locationId): void
    {
        Gate::authorize('inventory.manage');

        try {
            $location = Location::query()->findOrFail($locationId);
        } catch (ModelNotFoundException) {
            $this->toastError('Ubicación no encontrada.', title: 'Ubicación no encontrada');
            $this->showDropdown = true;

            return;
        } catch (Throwable $exception) {
            $this->reportException($exception);
            $this->showDropdown = true;

            return;
        }

        $this->setLocationData($location);
        $this->search = '';
        $this->showDropdown = false;
        $this->errorId = null;
    }

    public function clearSelection(): void
    {
        Gate::authorize('inventory.manage');

        $this->clearLocationData();
        $this->search = '';
        $this->showDropdown = false;
        $this->errorId = null;
    }

    public function closeDropdown(): void
    {
        Gate::authorize('inventory.manage');

        $this->showDropdown = false;
    }

    public function retrySearch(): void
    {
        Gate::authorize('inventory.manage');

        $this->errorId = null;
        $this->showDropdown = true;
    }

    public function createFromSearch(): void
    {
        Gate::authorize('catalogs.manage');

        $normalizedSearch = Location::normalizeName($this->search);
        $searchLength = $normalizedSearch !== null ? mb_strlen($normalizedSearch) : 0;

        if ($normalizedSearch === null || $searchLength < self::MIN_SEARCH_LENGTH) {
            return;
        }

        $softDeleted = Location::onlyTrashed()->where('name', $normalizedSearch)->first();
        if ($softDeleted) {
            $this->toastError(
                'La ubicación ya existe en Papelera. Restaúrala para poder usarla.',
                title: 'Ubicación en Papelera',
            );
            $this->showDropdown = true;

            return;
        }

        $existing = Location::query()->where('name', $normalizedSearch)->first();
        if ($existing) {
            $this->setLocationData($existing);
            $this->search = '';
            $this->showDropdown = false;
            $this->toastInfo('La ubicación ya existía. Se seleccionó la existente.', title: 'Ubicación existente');

            return;
        }

        try {
            $location = Location::query()->create(['name' => $normalizedSearch]);
        } catch (QueryException $exception) {
            if ($this->isDuplicateNameException($exception)) {
                $existingAfter = Location::query()->where('name', $normalizedSearch)->first();

                if ($existingAfter) {
                    $this->setLocationData($existingAfter);
                    $this->search = '';
                    $this->showDropdown = false;
                    $this->toastInfo('La ubicación ya existía. Se seleccionó la existente.', title: 'Ubicación existente');

                    return;
                }

                $trashedAfter = Location::onlyTrashed()->where('name', $normalizedSearch)->first();
                if ($trashedAfter) {
                    $this->toastError(
                        'La ubicación ya existe en Papelera. Restaúrala para poder usarla.',
                        title: 'Ubicación en Papelera',
                    );
                    $this->showDropdown = true;

                    return;
                }
            }

            $this->reportException($exception);

            return;
        } catch (Throwable $exception) {
            $this->reportException($exception);

            return;
        }

        $this->setLocationData($location);
        $this->search = '';
        $this->showDropdown = false;
        $this->errorId = null;
        $this->toastSuccess('Ubicación creada correctamente.', title: 'Ubicación creada');
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        $locations = $this->getSuggestions();
        $normalizedSearch = Location::normalizeName($this->search);
        $searchLength = $normalizedSearch !== null ? mb_strlen($normalizedSearch) : 0;
        $componentId = $this->buildDomIdSuffix();

        $showMinCharsMessage = $searchLength > 0 && $searchLength < self::MIN_SEARCH_LENGTH;
        $showNoResults = $searchLength >= self::MIN_SEARCH_LENGTH && $locations->isEmpty();
        $softDeletedMatch = $showNoResults && $normalizedSearch !== null
            ? Location::onlyTrashed()->where('name', $normalizedSearch)->first()
            : null;

        return view('livewire.ui.location-combobox', [
            'locations' => $locations,
            'errorId' => $this->errorId,
            'showMinCharsMessage' => $showMinCharsMessage,
            'showNoResults' => $showNoResults,
            'canCreate' => Gate::allows('catalogs.manage'),
            'hasSoftDeletedExactMatch' => $softDeletedMatch !== null,
            'trashUrl' => route('catalogs.trash.index', ['tab' => 'locations', 'q' => $normalizedSearch]),
            'inputId' => is_string($this->inputId) && $this->inputId !== ''
                ? $this->inputId
                : 'location-input-'.$componentId,
            'listboxId' => 'location-listbox-'.$componentId,
            'optionIdPrefix' => 'location-option-'.$componentId.'-',
            'createOptionId' => 'location-option-create-'.$componentId,
            'trashOptionId' => 'location-option-trash-'.$componentId,
        ]);
    }

    /**
     * @return Collection<int, Location>
     */
    private function getSuggestions(): Collection
    {
        try {
            $normalizedSearch = Location::normalizeName($this->search);

            if ($normalizedSearch === null || mb_strlen($normalizedSearch) < self::MIN_SEARCH_LENGTH) {
                return collect();
            }

            $escapedSearch = $this->escapeLike($normalizedSearch);

            $prefixResults = Location::query()
                ->whereRaw("name like ? escape '\\\\'", ["{$escapedSearch}%"])
                ->orderByRaw('CASE
                    WHEN name = ? THEN 0
                    WHEN name LIKE ? ESCAPE \'\\\\\' THEN 1
                    ELSE 2
                END', [$normalizedSearch, "{$escapedSearch}%"])
                ->orderBy('name')
                ->limit(self::MAX_RESULTS)
                ->get(['id', 'name']);

            if ($prefixResults->count() >= self::MAX_RESULTS) {
                return $prefixResults;
            }

            $remaining = self::MAX_RESULTS - $prefixResults->count();
            $excludeIds = $prefixResults->pluck('id')->all();

            $containsResults = Location::query()
                ->when(
                    $excludeIds !== [],
                    static fn ($query) => $query->whereNotIn('id', $excludeIds),
                )
                ->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"])
                ->orderBy('name')
                ->limit($remaining)
                ->get(['id', 'name']);

            return $prefixResults->concat($containsResults);
        } catch (Throwable $exception) {
            $this->reportException($exception);

            return collect();
        }
    }

    private function setLocationData(Location $location): void
    {
        $this->locationId = $location->id;
        $this->locationLabel = $location->name;
    }

    private function clearLocationData(): void
    {
        $this->locationId = null;
        $this->locationLabel = '';
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

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    private function buildDomIdSuffix(): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '-', $this->getId()) ?? 'component';
    }

    private function reportException(Throwable $exception): void
    {
        $this->errorId = app(ErrorReporter::class)->report($exception, request());

        $this->toastError(
            'Ocurrió un error inesperado.',
            title: 'Error inesperado',
            errorId: $this->errorId,
        );
    }
}
