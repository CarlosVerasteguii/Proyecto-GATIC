<?php

namespace App\Livewire\Catalogs\Suppliers;

use App\Models\Supplier;
use App\Support\Ui\ReturnToPath;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SupplierForm extends Component
{
    public ?int $supplierId = null;

    public string $name = '';

    public string $contact = '';

    public string $notes = '';

    public ?string $returnTo = null;

    public function mount(?string $supplier = null): void
    {
        Gate::authorize('catalogs.manage');

        $returnToQuery = request()->query('returnTo');
        $this->returnTo = is_string($returnToQuery)
            ? ReturnToPath::sanitize($returnToQuery)
            : null;

        if (! $supplier) {
            return;
        }

        if (! ctype_digit($supplier)) {
            abort(404);
        }

        $this->supplierId = (int) $supplier;

        $model = Supplier::query()->findOrFail($this->supplierId);
        $this->name = $model->name;
        $this->contact = $model->contact ?? '';
        $this->notes = $model->notes ?? '';
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
                Rule::unique('suppliers', 'name')->ignore($this->supplierId),
            ],
            'contact' => [
                'nullable',
                'string',
                'max:255',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
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
            'name.unique' => 'El proveedor ya existe.',
        ];
    }

    public function save(): mixed
    {
        Gate::authorize('catalogs.manage');

        $this->name = Supplier::normalizeName($this->name) ?? '';

        $this->contact = trim($this->contact);
        $this->notes = trim($this->notes);

        try {
            $validated = $this->validate();
        } catch (ValidationException $exception) {
            $failed = $exception->validator->failed();
            $failedNameRules = array_change_key_case($failed['name'] ?? [], CASE_LOWER);

            if (
                $this->supplierId === null
                && array_key_exists('unique', $failedNameRules)
                && Supplier::query()->onlyTrashed()->where('name', $this->name)->exists()
            ) {
                $exception->validator->errors()->forget('name');
                $exception->validator->errors()->add(
                    'name',
                    'El nombre ya existe en la Papelera. Restaura el proveedor desde Catálogos → Papelera.'
                );
            }

            throw $exception;
        }

        try {
            if (! $this->supplierId) {
                $created = Supplier::query()->create([
                    'name' => $validated['name'],
                    'contact' => isset($validated['contact']) && is_string($validated['contact']) && trim($validated['contact']) !== ''
                        ? trim($validated['contact'])
                        : null,
                    'notes' => isset($validated['notes']) && is_string($validated['notes']) && trim($validated['notes']) !== ''
                        ? trim($validated['notes'])
                        : null,
                ]);

                $returnTo = ReturnToPath::sanitize($this->returnTo);
                if ($returnTo !== null) {
                    return redirect()
                        ->to(ReturnToPath::withQuery($returnTo, ['created_id' => (int) $created->id]));
                }

                return redirect()
                    ->route('catalogs.suppliers.index')
                    ->with('status', 'Proveedor creado.');
            }

            $model = Supplier::query()->findOrFail($this->supplierId);
            $model->name = $validated['name'];
            $model->contact = isset($validated['contact']) && is_string($validated['contact']) && trim($validated['contact']) !== ''
                ? trim($validated['contact'])
                : null;
            $model->notes = isset($validated['notes']) && is_string($validated['notes']) && trim($validated['notes']) !== ''
                ? trim($validated['notes'])
                : null;
            $model->save();
        } catch (QueryException $exception) {
            if ($this->isDuplicateNameException($exception)) {
                $this->addError('name', 'El proveedor ya existe.');

                return null;
            }

            throw $exception;
        }

        return redirect()
            ->route('catalogs.suppliers.index')
            ->with('status', 'Proveedor actualizado.');
    }

    public function render(): View
    {
        Gate::authorize('catalogs.manage');

        return view('livewire.catalogs.suppliers.supplier-form', [
            'isEdit' => (bool) $this->supplierId,
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
