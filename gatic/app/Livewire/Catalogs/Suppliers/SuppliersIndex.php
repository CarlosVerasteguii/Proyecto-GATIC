<?php

namespace App\Livewire\Catalogs\Suppliers;

use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Supplier;
use App\Support\Catalogs\CatalogUsage;
use App\Support\Errors\ErrorReporter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.app')]
class SuppliersIndex extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    public string $search = '';

    public ?int $supplierId = null;

    public string $name = '';

    public string $contact = '';

    public string $notes = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $supplierId): void
    {
        Gate::authorize('catalogs.manage');

        $supplier = Supplier::query()->findOrFail($supplierId);

        $this->supplierId = $supplier->id;
        $this->name = $supplier->name;
        $this->contact = $supplier->contact ?? '';
        $this->notes = $supplier->notes ?? '';
    }

    public function cancelEdit(): void
    {
        $this->reset(['supplierId', 'name', 'contact', 'notes']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $uniqueNameRule = Rule::unique('suppliers', 'name');

        if ($this->supplierId) {
            $uniqueNameRule = $uniqueNameRule->ignore($this->supplierId);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $uniqueNameRule,
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

    public function save(): void
    {
        Gate::authorize('catalogs.manage');

        $this->name = Supplier::normalizeName($this->name) ?? '';

        $this->validate();

        try {
            if (! $this->supplierId) {
                Supplier::query()->create([
                    'name' => $this->name,
                    'contact' => $this->contact ?: null,
                    'notes' => $this->notes ?: null,
                ]);

                $this->reset(['name', 'contact', 'notes']);
                $this->toastSuccess('Proveedor creado.');

                return;
            }

            $supplier = Supplier::query()->findOrFail($this->supplierId);
            $supplier->name = $this->name;
            $supplier->contact = $this->contact ?: null;
            $supplier->notes = $this->notes ?: null;
            $supplier->save();
        } catch (QueryException $exception) {
            if ($this->isDuplicateNameException($exception)) {
                $this->addError('name', 'El proveedor ya existe.');

                return;
            }

            throw $exception;
        }

        $this->reset(['supplierId', 'name', 'contact', 'notes']);
        $this->toastSuccess('Proveedor actualizado.');
    }

    public function delete(int $supplierId): void
    {
        Gate::authorize('catalogs.manage');

        $supplier = Supplier::query()->findOrFail($supplierId);

        try {
            $inUse = CatalogUsage::isInUse('suppliers', $supplier->id);
        } catch (Throwable $exception) {
            if (app()->environment(['local', 'testing'])) {
                throw $exception;
            }

            $errorId = app(ErrorReporter::class)->report($exception, request());
            $this->toastError(
                'No se pudo validar si el proveedor está en uso.',
                title: 'Error inesperado',
                errorId: $errorId
            );

            return;
        }

        if ($inUse) {
            $this->toastError('No se puede eliminar: el proveedor está en uso.');

            return;
        }

        $supplier->delete();

        if ($this->supplierId === $supplierId) {
            $this->reset(['supplierId', 'name', 'contact', 'notes']);
        }

        $this->toastSuccess('Proveedor eliminado.');
    }

    public function render(): View
    {
        Gate::authorize('catalogs.manage');

        $search = Supplier::normalizeName($this->search);
        $escapedSearch = $search !== null ? $this->escapeLike($search) : null;

        return view('livewire.catalogs.suppliers.suppliers-index', [
            'suppliers' => Supplier::query()
                ->when($escapedSearch, function ($query) use ($escapedSearch) {
                    $query->whereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                })
                ->orderBy('name')
                ->paginate(15),
            'isEditing' => (bool) $this->supplierId,
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
