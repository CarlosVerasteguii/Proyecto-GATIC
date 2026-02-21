<?php

namespace App\Livewire\Ui;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Brand;
use App\Support\Errors\ErrorReporter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Throwable;

class BrandCombobox extends Component
{
    use InteractsWithToasts;

    #[Modelable]
    public ?int $brandId = null;

    public string $brandLabel = '';

    public string $search = '';

    public bool $showDropdown = false;

    public ?string $errorId = null;

    public ?string $inputId = null;

    private const MIN_SEARCH_LENGTH = 2;

    private const MAX_RESULTS = 10;

    public function mount(?int $brandId = null, ?string $inputId = null): void
    {
        Gate::authorize('inventory.manage');

        $this->inputId = $inputId;

        if ($brandId === null) {
            return;
        }

        try {
            $brand = Brand::query()->find($brandId);
        } catch (Throwable $exception) {
            $this->clearBrandData();
            $this->reportException($exception);

            return;
        }

        if ($brand !== null) {
            $this->setBrandData($brand);
        }
    }

    public function updatedBrandId(?int $brandId): void
    {
        Gate::authorize('inventory.manage');

        if ($brandId === null) {
            $this->clearBrandData();

            return;
        }

        try {
            $brand = Brand::query()->find($brandId);
        } catch (Throwable $exception) {
            $this->clearBrandData();
            $this->reportException($exception);

            return;
        }

        if (! $brand) {
            $this->clearBrandData();

            return;
        }

        $this->setBrandData($brand);
    }

    public function updatedSearch(): void
    {
        Gate::authorize('inventory.manage');

        $this->errorId = null;
        $this->showDropdown = true;
    }

    public function selectBrand(int $brandId): void
    {
        Gate::authorize('inventory.manage');

        try {
            $brand = Brand::query()->findOrFail($brandId);
        } catch (ModelNotFoundException) {
            $this->toastError('Marca no encontrada.', title: 'Marca no encontrada');
            $this->showDropdown = true;

            return;
        } catch (Throwable $exception) {
            $this->reportException($exception);
            $this->showDropdown = true;

            return;
        }

        $this->setBrandData($brand);
        $this->search = '';
        $this->showDropdown = false;
        $this->errorId = null;
    }

    public function clearSelection(): void
    {
        Gate::authorize('inventory.manage');

        $this->clearBrandData();
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

        $normalizedSearch = Brand::normalizeName($this->search);
        $searchLength = $normalizedSearch !== null ? mb_strlen($normalizedSearch) : 0;

        if ($normalizedSearch === null || $searchLength < self::MIN_SEARCH_LENGTH) {
            return;
        }

        $softDeleted = Brand::onlyTrashed()->where('name', $normalizedSearch)->first();
        if ($softDeleted) {
            $this->toastError(
                'La marca ya existe en Papelera. Restaúrala para poder usarla.',
                title: 'Marca en Papelera',
            );
            $this->showDropdown = true;

            return;
        }

        $existing = Brand::query()->where('name', $normalizedSearch)->first();
        if ($existing) {
            $this->setBrandData($existing);
            $this->search = '';
            $this->showDropdown = false;
            $this->toastInfo('La marca ya existía. Se seleccionó la existente.', title: 'Marca existente');

            return;
        }

        try {
            $brand = Brand::query()->create(['name' => $normalizedSearch]);
        } catch (QueryException $exception) {
            if ($this->isDuplicateNameException($exception)) {
                $existingAfter = Brand::query()->where('name', $normalizedSearch)->first();

                if ($existingAfter) {
                    $this->setBrandData($existingAfter);
                    $this->search = '';
                    $this->showDropdown = false;
                    $this->toastInfo('La marca ya existía. Se seleccionó la existente.', title: 'Marca existente');

                    return;
                }

                $trashedAfter = Brand::onlyTrashed()->where('name', $normalizedSearch)->first();
                if ($trashedAfter) {
                    $this->toastError(
                        'La marca ya existe en Papelera. Restaúrala para poder usarla.',
                        title: 'Marca en Papelera',
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

        $this->setBrandData($brand);
        $this->search = '';
        $this->showDropdown = false;
        $this->errorId = null;
        $this->toastSuccess('Marca creada correctamente.', title: 'Marca creada');
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        $brands = $this->getSuggestions();
        $normalizedSearch = Brand::normalizeName($this->search);
        $searchLength = $normalizedSearch !== null ? mb_strlen($normalizedSearch) : 0;
        $componentId = $this->buildDomIdSuffix();

        $showMinCharsMessage = $searchLength > 0 && $searchLength < self::MIN_SEARCH_LENGTH;
        $showNoResults = $searchLength >= self::MIN_SEARCH_LENGTH && $brands->isEmpty();
        $softDeletedMatch = $showNoResults && $normalizedSearch !== null
            ? Brand::onlyTrashed()->where('name', $normalizedSearch)->first()
            : null;

        return view('livewire.ui.brand-combobox', [
            'brands' => $brands,
            'errorId' => $this->errorId,
            'showMinCharsMessage' => $showMinCharsMessage,
            'showNoResults' => $showNoResults,
            'canCreate' => Gate::allows('catalogs.manage'),
            'hasSoftDeletedExactMatch' => $softDeletedMatch !== null,
            'trashUrl' => route('catalogs.trash.index', ['tab' => 'brands', 'q' => $normalizedSearch]),
            'inputId' => is_string($this->inputId) && $this->inputId !== ''
                ? $this->inputId
                : 'brand-input-'.$componentId,
            'listboxId' => 'brand-listbox-'.$componentId,
            'optionIdPrefix' => 'brand-option-'.$componentId.'-',
            'createOptionId' => 'brand-option-create-'.$componentId,
            'trashOptionId' => 'brand-option-trash-'.$componentId,
        ]);
    }

    /**
     * @return Collection<int, Brand>
     */
    private function getSuggestions(): Collection
    {
        try {
            $normalizedSearch = Brand::normalizeName($this->search);

            if ($normalizedSearch === null || mb_strlen($normalizedSearch) < self::MIN_SEARCH_LENGTH) {
                return collect();
            }

            $escapedSearch = $this->escapeLike($normalizedSearch);

            $prefixResults = Brand::query()
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

            $containsResults = Brand::query()
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

    private function setBrandData(Brand $brand): void
    {
        $this->brandId = $brand->id;
        $this->brandLabel = $brand->name;
    }

    private function clearBrandData(): void
    {
        $this->brandId = null;
        $this->brandLabel = '';
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
