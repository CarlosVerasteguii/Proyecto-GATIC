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

    public function updatedIsSerialized(bool $value): void
    {
        if ($value) {
            return;
        }

        $this->requires_asset_tag = false;
        $this->resetValidation('requires_asset_tag');
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
        ];
    }

    public function save(): mixed
    {
        Gate::authorize('catalogs.manage');

        $this->name = Category::normalizeName($this->name) ?? '';

        if (! $this->is_serialized && $this->requires_asset_tag) {
            $this->addError('requires_asset_tag', 'Solo aplica si la categoría es serializada.');

            return null;
        }

        $this->validate();

        if (! $this->categoryId) {
            Category::query()->create([
                'name' => $this->name,
                'is_serialized' => $this->is_serialized,
                'requires_asset_tag' => $this->requires_asset_tag,
            ]);

            return redirect()
                ->route('catalogs.categories.index')
                ->with('status', 'Categoría creada.');
        }

        $model = Category::query()->findOrFail($this->categoryId);
        $model->name = $this->name;
        $model->is_serialized = $this->is_serialized;
        $model->requires_asset_tag = $this->requires_asset_tag;
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
