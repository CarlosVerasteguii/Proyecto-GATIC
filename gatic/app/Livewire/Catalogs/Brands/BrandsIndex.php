<?php

namespace App\Livewire\Catalogs\Brands;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Brand;
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
class BrandsIndex extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    public string $search = '';

    public ?int $brandId = null;

    public string $name = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $brandId): void
    {
        Gate::authorize('catalogs.manage');

        $brand = Brand::query()->findOrFail($brandId);

        $this->brandId = $brand->id;
        $this->name = $brand->name;
    }

    public function cancelEdit(): void
    {
        $this->reset(['brandId', 'name']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $uniqueNameRule = Rule::unique('brands', 'name');

        if ($this->brandId) {
            $uniqueNameRule = $uniqueNameRule->ignore($this->brandId);
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
            'name.unique' => 'La marca ya existe.',
        ];
    }

    public function save(): void
    {
        Gate::authorize('catalogs.manage');

        $this->name = Brand::normalizeName($this->name) ?? '';

        $this->validate();

        try {
            if (! $this->brandId) {
                Brand::query()->create(['name' => $this->name]);

                $this->reset('name');
                $this->toastSuccess('Marca creada.');

                return;
            }

            $brand = Brand::query()->findOrFail($this->brandId);
            $brand->name = $this->name;
            $brand->save();
        } catch (QueryException $exception) {
            if ($this->isDuplicateNameException($exception)) {
                $this->addError('name', 'La marca ya existe.');

                return;
            }

            throw $exception;
        }

        $this->reset(['brandId', 'name']);
        $this->toastSuccess('Marca actualizada.');
    }

    public function delete(int $brandId): void
    {
        Gate::authorize('catalogs.manage');

        $brand = Brand::query()->findOrFail($brandId);

        try {
            $inUse = CatalogUsage::isInUse('brands', $brand->id);
        } catch (Throwable $exception) {
            report($exception);
            $this->toastError('No se pudo validar si la marca está en uso.');

            return;
        }

        if ($inUse) {
            $this->toastError('No se puede eliminar: la marca está en uso.');

            return;
        }

        $brand->delete();

        if ($this->brandId === $brandId) {
            $this->reset(['brandId', 'name']);
        }

        $this->toastSuccess('Marca eliminada.');
    }

    public function render(): View
    {
        Gate::authorize('catalogs.manage');

        $search = Brand::normalizeName($this->search);
        $escapedSearch = $search !== null ? $this->escapeLike($search) : null;

        return view('livewire.catalogs.brands.brands-index', [
            'brands' => Brand::query()
                ->when($escapedSearch, function ($query) use ($escapedSearch) {
                    $query->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                })
                ->orderBy('name')
                ->paginate(15),
            'isEditing' => (bool) $this->brandId,
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
