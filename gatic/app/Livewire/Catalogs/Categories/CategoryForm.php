<?php

namespace App\Livewire\Catalogs\Categories;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CategoryForm extends Component
{
    public ?int $categoryId = null;

    public string $name = '';

    public bool $is_serialized = false;

    public bool $requires_asset_tag = false;

    public ?string $default_useful_life_months = null;

    public function updatedIsSerialized(bool $value): void
    {
        if ($value) {
            return;
        }

        $this->requires_asset_tag = false;
        $this->default_useful_life_months = null;
        $this->resetValidation(['requires_asset_tag', 'default_useful_life_months']);
    }

    public function mount(?string $category = null): void
    {
        Gate::authorize('catalogs.manage');

        if (! $category) {
            return;
        }

        if (! ctype_digit($category)) {
            abort(404);
        }

        $this->categoryId = (int) $category;

        $model = Category::query()->findOrFail($this->categoryId);
        $this->name = $model->name;
        $this->is_serialized = (bool) $model->is_serialized;
        $this->requires_asset_tag = $this->is_serialized ? (bool) $model->requires_asset_tag : false;
        $this->default_useful_life_months = $this->is_serialized && $model->default_useful_life_months !== null
            ? (string) $model->default_useful_life_months
            : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($this->categoryId),
            ],
            'is_serialized' => ['boolean'],
            'requires_asset_tag' => ['boolean'],
            'default_useful_life_months' => ['nullable', 'integer', 'min:1', 'max:600'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.unique' => 'El nombre ya existe.',
            'default_useful_life_months.integer' => 'La vida útil debe ser un número entero.',
            'default_useful_life_months.min' => 'La vida útil debe ser mayor o igual a 1.',
            'default_useful_life_months.max' => 'La vida útil no debe exceder 600 meses.',
        ];
    }

    public function save(): mixed
    {
        Gate::authorize('catalogs.manage');

        $this->name = Category::normalizeName($this->name) ?? '';

        if (is_string($this->default_useful_life_months)) {
            $this->default_useful_life_months = trim($this->default_useful_life_months);
        }
        if ($this->default_useful_life_months === '') {
            $this->default_useful_life_months = null;
        }
        if (! $this->is_serialized) {
            $this->default_useful_life_months = null;
            $this->resetValidation('default_useful_life_months');
        }

        if (! $this->is_serialized && $this->requires_asset_tag) {
            $this->addError('requires_asset_tag', 'Solo aplica si la categoría es serializada.');

            return null;
        }

        $validated = $this->validate();

        $defaultUsefulLifeMonths = null;
        if (
            $this->is_serialized
            && isset($validated['default_useful_life_months'])
            && $validated['default_useful_life_months'] !== null
        ) {
            $defaultUsefulLifeMonths = (int) $validated['default_useful_life_months'];
        }

        if (! $this->categoryId) {
            Category::query()->create([
                'name' => $this->name,
                'is_serialized' => $this->is_serialized,
                'requires_asset_tag' => $this->requires_asset_tag,
                'default_useful_life_months' => $defaultUsefulLifeMonths,
            ]);

            return redirect()
                ->route('catalogs.categories.index')
                ->with('status', 'Categoría creada.');
        }

        $model = Category::query()->findOrFail($this->categoryId);
        $model->name = $this->name;
        $model->is_serialized = $this->is_serialized;
        $model->requires_asset_tag = $this->requires_asset_tag;
        $model->default_useful_life_months = $defaultUsefulLifeMonths;
        $model->save();

        return redirect()
            ->route('catalogs.categories.index')
            ->with('status', 'Categoría actualizada.');
    }

    public function render(): View
    {
        Gate::authorize('catalogs.manage');

        return view('livewire.catalogs.categories.category-form', [
            'isEdit' => (bool) $this->categoryId,
        ]);
    }
}
