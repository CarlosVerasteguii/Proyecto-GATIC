<?php

namespace App\Livewire\Catalogs\Brands;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Brand;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

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

        if (! $this->brandId) {
            Brand::query()->create(['name' => $this->name]);

            $this->reset('name');
            $this->toastSuccess('Marca creada.');

            return;
        }

        $brand = Brand::query()->findOrFail($this->brandId);
        $brand->name = $this->name;
        $brand->save();

        $this->reset(['brandId', 'name']);
        $this->toastSuccess('Marca actualizada.');
    }

    public function delete(int $brandId): void
    {
        Gate::authorize('catalogs.manage');

        $brand = Brand::query()->findOrFail($brandId);
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
}
