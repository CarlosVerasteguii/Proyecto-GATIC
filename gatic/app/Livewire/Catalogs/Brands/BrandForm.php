<?php

namespace App\Livewire\Catalogs\Brands;

use App\Models\Brand;
use App\Support\Ui\ReturnToPath;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BrandForm extends Component
{
    public ?int $brandId = null;

    public string $name = '';

    public ?string $returnTo = null;

    public function mount(?string $brand = null): void
    {
        Gate::authorize('catalogs.manage');

        $returnToQuery = request()->query('returnTo');
        $this->returnTo = is_string($returnToQuery)
            ? ReturnToPath::sanitize($returnToQuery)
            : null;

        if (! $brand) {
            return;
        }

        if (! ctype_digit($brand)) {
            abort(404);
        }

        $this->brandId = (int) $brand;

        $model = Brand::query()->findOrFail($this->brandId);
        $this->name = $model->name;
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
                Rule::unique('brands', 'name')->ignore($this->brandId),
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

    public function save(): mixed
    {
        Gate::authorize('catalogs.manage');

        $this->name = Brand::normalizeName($this->name) ?? '';

        try {
            $validated = $this->validate();
        } catch (ValidationException $exception) {
            $failed = $exception->validator->failed();
            $failedNameRules = array_change_key_case($failed['name'] ?? [], CASE_LOWER);

            if (
                $this->brandId === null
                && array_key_exists('unique', $failedNameRules)
                && Brand::query()->onlyTrashed()->where('name', $this->name)->exists()
            ) {
                $exception->validator->errors()->forget('name');
                $exception->validator->errors()->add(
                    'name',
                    'El nombre ya existe en la Papelera. Restaura la marca desde Catálogos → Papelera.'
                );
            }

            throw $exception;
        }

        try {
            if (! $this->brandId) {
                $created = Brand::query()->create([
                    'name' => $validated['name'],
                ]);

                $returnTo = ReturnToPath::sanitize($this->returnTo);
                if ($returnTo !== null) {
                    return redirect()
                        ->to(ReturnToPath::withQuery($returnTo, ['created_id' => (int) $created->id]));
                }

                return redirect()
                    ->route('catalogs.brands.index')
                    ->with('status', 'Marca creada.');
            }

            $model = Brand::query()->findOrFail($this->brandId);
            $model->name = $validated['name'];
            $model->save();
        } catch (QueryException $exception) {
            if ($this->isDuplicateNameException($exception)) {
                $this->addError('name', 'La marca ya existe.');

                return null;
            }

            throw $exception;
        }

        return redirect()
            ->route('catalogs.brands.index')
            ->with('status', 'Marca actualizada.');
    }

    public function render(): View
    {
        Gate::authorize('catalogs.manage');

        return view('livewire.catalogs.brands.brand-form', [
            'isEdit' => (bool) $this->brandId,
        ]);
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
