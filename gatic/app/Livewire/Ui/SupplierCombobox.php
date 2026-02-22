<?php

namespace App\Livewire\Ui;

use App\Actions\Suppliers\SearchSuppliers;
use App\Actions\Suppliers\UpsertSupplier;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Supplier;
use App\Support\Errors\ErrorReporter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Throwable;

class SupplierCombobox extends Component
{
    use InteractsWithToasts;

    #[Modelable]
    public ?int $supplierId = null;

    public string $supplierLabel = '';

    public string $search = '';

    public bool $showDropdown = false;

    public bool $showCreateModal = false;

    public string $createName = '';

    public string $createContact = '';

    public string $createNotes = '';

    public ?string $createErrorId = null;

    public ?string $errorId = null;

    public ?string $createTrashUrl = null;

    public ?string $inputId = null;

    private const MIN_SEARCH_LENGTH = 2;

    private const MAX_RESULTS = 10;

    public function mount(?int $supplierId = null, ?string $inputId = null): void
    {
        Gate::authorize('inventory.manage');

        $this->inputId = $inputId;

        if ($supplierId === null) {
            return;
        }

        try {
            $supplier = Supplier::query()->find($supplierId);
        } catch (Throwable $exception) {
            $this->clearSupplierData();
            $this->reportSearchException($exception);

            return;
        }

        if ($supplier !== null) {
            $this->setSupplierData($supplier);
        }
    }

    public function updatedSupplierId(?int $supplierId): void
    {
        Gate::authorize('inventory.manage');

        if ($supplierId === null) {
            $this->clearSupplierData();

            return;
        }

        try {
            $supplier = Supplier::query()->find($supplierId);
        } catch (Throwable $exception) {
            $this->clearSupplierData();
            $this->reportSearchException($exception);

            return;
        }

        if (! $supplier) {
            $this->clearSupplierData();

            return;
        }

        $this->setSupplierData($supplier);
    }

    public function updatedSearch(): void
    {
        Gate::authorize('inventory.manage');

        $this->errorId = null;
        $this->showDropdown = true;
    }

    public function selectSupplier(int $supplierId): void
    {
        Gate::authorize('inventory.manage');

        try {
            $supplier = Supplier::query()->findOrFail($supplierId);
        } catch (ModelNotFoundException) {
            $this->toastError('Proveedor no encontrado.', title: 'Proveedor no encontrado');
            $this->showDropdown = true;

            return;
        } catch (Throwable $exception) {
            $this->reportSearchException($exception);
            $this->showDropdown = true;

            return;
        }

        $this->setSupplierData($supplier);
        $this->search = '';
        $this->showDropdown = false;
        $this->errorId = null;
    }

    public function clearSelection(): void
    {
        Gate::authorize('inventory.manage');

        $this->clearSupplierData();
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

    public function openCreateSupplierModal(): void
    {
        Gate::authorize('inventory.manage');

        $this->createName = Supplier::normalizeName($this->search) ?? '';
        $this->createContact = '';
        $this->createNotes = '';
        $this->createErrorId = null;
        $this->createTrashUrl = null;
        $this->resetValidation(['createName', 'createContact', 'createNotes']);
        $this->showCreateModal = true;
        $this->showDropdown = false;
    }

    public function closeCreateSupplierModal(): void
    {
        Gate::authorize('inventory.manage');

        $this->showCreateModal = false;
        $this->createName = '';
        $this->createContact = '';
        $this->createNotes = '';
        $this->createErrorId = null;
        $this->createTrashUrl = null;
        $this->resetValidation(['createName', 'createContact', 'createNotes']);
        $this->dispatchFocusToInput();
    }

    public function createSupplier(): void
    {
        Gate::authorize('catalogs.manage');

        $this->createErrorId = null;
        $this->createTrashUrl = null;
        $this->createName = Supplier::normalizeName($this->createName) ?? '';
        $this->createContact = $this->normalizeOptionalText($this->createContact) ?? '';
        $this->createNotes = $this->normalizeOptionalText($this->createNotes) ?? '';

        $this->validate([
            'createName' => ['required', 'string', 'max:255'],
            'createContact' => ['nullable', 'string', 'max:255'],
            'createNotes' => ['nullable', 'string', 'max:1000'],
        ], [
            'createName.required' => 'El nombre es obligatorio.',
            'createName.max' => 'El nombre no debe exceder 255 caracteres.',
            'createContact.max' => 'El contacto no debe exceder 255 caracteres.',
            'createNotes.max' => 'Las notas no deben exceder 1000 caracteres.',
        ]);

        try {
            $action = app(UpsertSupplier::class);
            $result = $action->execute([
                'name' => $this->createName,
                'contact' => $this->normalizeOptionalText($this->createContact),
                'notes' => $this->normalizeOptionalText($this->createNotes),
            ]);
        } catch (Throwable $exception) {
            $this->reportCreateException($exception);

            return;
        }

        /** @var array{status:string,supplier:Supplier} $result */
        $supplier = $result['supplier'];

        if ($result['status'] === UpsertSupplier::STATUS_TRASHED) {
            $this->createTrashUrl = route('catalogs.trash.index', [
                'tab' => 'suppliers',
                'q' => $supplier->name,
            ]);

            $this->addError('createName', 'El proveedor ya existe en Papelera.');
            $this->toastError(
                'El proveedor ya existe en Papelera. Restaúralo para poder usarlo.',
                title: 'Proveedor en Papelera',
            );

            return;
        }

        if ($result['status'] === UpsertSupplier::STATUS_EXISTING) {
            $this->selectExistingSupplier($supplier);
            $this->toastInfo('El proveedor ya existía. Se seleccionó el existente.', title: 'Proveedor existente');

            return;
        }

        $this->setSupplierData($supplier);
        $this->search = '';
        $this->showDropdown = false;
        $this->errorId = null;
        $this->closeCreateSupplierModal();
        $this->toastSuccess('Proveedor creado correctamente.', title: 'Proveedor creado');
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        $suppliers = $this->getSuggestions();
        $normalizedSearch = Supplier::normalizeName($this->search);
        $searchLength = $normalizedSearch !== null ? mb_strlen($normalizedSearch) : 0;
        $componentId = $this->buildDomIdSuffix();

        $showMinCharsMessage = $searchLength < self::MIN_SEARCH_LENGTH;
        $showNoResults = $searchLength >= self::MIN_SEARCH_LENGTH && $suppliers->isEmpty();
        $softDeletedMatch = $showNoResults && $normalizedSearch !== null
            ? Supplier::onlyTrashed()->where('name', $normalizedSearch)->first()
            : null;

        return view('livewire.ui.supplier-combobox', [
            'suppliers' => $suppliers,
            'errorId' => $this->errorId,
            'createErrorId' => $this->createErrorId,
            'createTrashUrl' => $this->createTrashUrl,
            'showMinCharsMessage' => $showMinCharsMessage,
            'showNoResults' => $showNoResults,
            'canCreate' => Gate::allows('catalogs.manage'),
            'hasSoftDeletedExactMatch' => $softDeletedMatch !== null,
            'trashUrl' => route('catalogs.trash.index', ['tab' => 'suppliers', 'q' => $normalizedSearch]),
            'inputId' => is_string($this->inputId) && $this->inputId !== ''
                ? $this->inputId
                : 'supplier-input-'.$componentId,
            'listboxId' => 'supplier-listbox-'.$componentId,
            'optionIdPrefix' => 'supplier-option-'.$componentId.'-',
            'createOptionId' => 'supplier-option-create-'.$componentId,
            'trashOptionId' => 'supplier-option-trash-'.$componentId,
            'createModalId' => 'supplier-create-modal-'.$componentId,
            'createModalTitleId' => 'supplier-create-title-'.$componentId,
            'createNameInputId' => 'supplier-create-name-'.$componentId,
            'createContactInputId' => 'supplier-create-contact-'.$componentId,
            'createNotesInputId' => 'supplier-create-notes-'.$componentId,
        ]);
    }

    /**
     * @return Collection<int, Supplier>
     */
    private function getSuggestions(): Collection
    {
        try {
            $action = app(SearchSuppliers::class);

            return $action->execute($this->search, self::MAX_RESULTS);
        } catch (Throwable $exception) {
            $this->reportSearchException($exception);

            return collect();
        }
    }

    private function setSupplierData(Supplier $supplier): void
    {
        $this->supplierId = $supplier->id;
        $this->supplierLabel = $supplier->name;
    }

    private function clearSupplierData(): void
    {
        $this->supplierId = null;
        $this->supplierLabel = '';
    }

    private function selectExistingSupplier(Supplier $supplier): void
    {
        $this->setSupplierData($supplier);
        $this->search = '';
        $this->showDropdown = false;
        $this->errorId = null;
        $this->closeCreateSupplierModal();
    }

    private function normalizeOptionalText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/\\s+/u', ' ', trim($value));

        if (! is_string($normalized)) {
            return null;
        }

        return $normalized === '' ? null : $normalized;
    }

    private function buildDomIdSuffix(): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '-', $this->getId()) ?? 'component';
    }

    private function dispatchFocusToInput(): void
    {
        $this->dispatch(
            'supplier-combobox:focus-input',
            inputId: is_string($this->inputId) && $this->inputId !== ''
                ? $this->inputId
                : 'supplier-input-'.$this->buildDomIdSuffix(),
        );
    }

    private function reportSearchException(Throwable $exception): void
    {
        $this->errorId = app(ErrorReporter::class)->report($exception, request());

        $this->toastError(
            'Ocurrió un error inesperado.',
            title: 'Error inesperado',
            errorId: $this->errorId,
        );
    }

    private function reportCreateException(Throwable $exception): void
    {
        $this->createErrorId = app(ErrorReporter::class)->report($exception, request());

        $this->toastError(
            'Ocurrió un error inesperado.',
            title: 'Error inesperado',
            errorId: $this->createErrorId,
        );
    }
}
