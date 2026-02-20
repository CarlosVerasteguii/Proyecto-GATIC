<?php

namespace App\Livewire\Ui;

use App\Actions\Employees\SearchEmployees;
use App\Actions\Employees\UpsertEmployee;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Employee;
use App\Support\Errors\ErrorReporter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Throwable;

class EmployeeCombobox extends Component
{
    use InteractsWithToasts;

    #[Modelable]
    public ?int $employeeId = null;

    public string $employeeLabel = '';

    public ?string $employeeRpe = null;

    public ?string $employeeName = null;

    public ?string $employeeDepartment = null;

    public string $search = '';

    public bool $showDropdown = false;

    public bool $showCreateModal = false;

    public string $createRpe = '';

    public string $createName = '';

    public ?string $createErrorId = null;

    public ?string $errorId = null;

    private const MIN_SEARCH_LENGTH = 2;

    private const MAX_RESULTS = 10;

    public function mount(?int $employeeId = null): void
    {
        Gate::authorize('inventory.manage');

        if ($employeeId !== null) {
            try {
                $employee = Employee::query()->find($employeeId);
            } catch (Throwable $exception) {
                $this->clearEmployeeData();
                $this->reportSearchException($exception);

                return;
            }

            if ($employee !== null) {
                $this->setEmployeeData($employee);
            }
        }
    }

    public function updatedEmployeeId(?int $employeeId): void
    {
        Gate::authorize('inventory.manage');

        if ($employeeId === null) {
            $this->clearEmployeeData();

            return;
        }

        try {
            $employee = Employee::query()->find($employeeId);
        } catch (Throwable $exception) {
            $this->clearEmployeeData();
            $this->reportSearchException($exception);

            return;
        }

        if (! $employee) {
            $this->clearEmployeeData();

            return;
        }

        $this->setEmployeeData($employee);
    }

    public function updatedSearch(): void
    {
        Gate::authorize('inventory.manage');

        $this->errorId = null;
        $this->showDropdown = true;
    }

    public function selectEmployee(int $employeeId): void
    {
        Gate::authorize('inventory.manage');

        try {
            $employee = Employee::query()->findOrFail($employeeId);
        } catch (ModelNotFoundException) {
            $this->toastError('Empleado no encontrado.', title: 'Empleado no encontrado');
            $this->showDropdown = true;

            return;
        } catch (Throwable $exception) {
            $this->reportSearchException($exception);
            $this->showDropdown = true;

            return;
        }

        $this->setEmployeeData($employee);
        $this->search = '';
        $this->showDropdown = false;
        $this->errorId = null;
    }

    public function clearSelection(): void
    {
        Gate::authorize('inventory.manage');

        $this->clearEmployeeData();
        $this->search = '';
        $this->showDropdown = false;
        $this->errorId = null;
    }

    public function closeDropdown(): void
    {
        Gate::authorize('inventory.manage');

        $this->showDropdown = false;
    }

    public function openCreateEmployeeModal(): void
    {
        Gate::authorize('inventory.manage');

        $normalizedSearch = Employee::normalizeText($this->search) ?? '';
        $looksLikeRpe = $normalizedSearch !== ''
            && ! str_contains($normalizedSearch, ' ')
            && preg_match('/\d/', $normalizedSearch) === 1;

        $this->createRpe = $looksLikeRpe ? $normalizedSearch : '';
        $this->createName = $looksLikeRpe ? '' : $normalizedSearch;
        $this->createErrorId = null;
        $this->resetValidation(['createRpe', 'createName']);
        $this->showCreateModal = true;
        $this->showDropdown = false;
    }

    public function closeCreateEmployeeModal(): void
    {
        Gate::authorize('inventory.manage');

        $this->showCreateModal = false;
        $this->createRpe = '';
        $this->createName = '';
        $this->createErrorId = null;
        $this->resetValidation(['createRpe', 'createName']);
        $this->dispatchFocusToInput();
    }

    public function createEmployee(): void
    {
        Gate::authorize('inventory.manage');

        $this->createErrorId = null;
        $this->createRpe = Employee::normalizeText($this->createRpe) ?? '';
        $this->createName = Employee::normalizeText($this->createName) ?? '';

        $this->validate([
            'createRpe' => ['required', 'string', 'max:255', 'unique:employees,rpe'],
            'createName' => ['required', 'string', 'max:255'],
        ], [
            'createRpe.required' => 'El RPE es obligatorio.',
            'createRpe.unique' => 'El RPE ya existe.',
            'createName.required' => 'El nombre es obligatorio.',
        ]);

        try {
            $action = app(UpsertEmployee::class);
            $employee = $action->execute([
                'rpe' => $this->createRpe,
                'name' => $this->createName,
            ]);
        } catch (QueryException $exception) {
            if ($this->isDuplicateRpeException($exception)) {
                $this->addError('createRpe', 'El RPE ya existe.');

                return;
            }

            $this->reportCreateException($exception);

            return;
        } catch (Throwable $exception) {
            $this->reportCreateException($exception);

            return;
        }

        $this->setEmployeeData($employee);
        $this->search = '';
        $this->showDropdown = false;
        $this->closeCreateEmployeeModal();
        $this->toastSuccess('Empleado creado correctamente.', title: 'Empleado creado');
    }

    public function retrySearch(): void
    {
        Gate::authorize('inventory.manage');

        $this->errorId = null;
        $this->showDropdown = true;
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        $employees = $this->getEmployeeSuggestions();
        $normalizedSearch = Employee::normalizeText($this->search);
        $searchLength = $normalizedSearch !== null ? mb_strlen($normalizedSearch) : 0;
        $componentId = $this->buildDomIdSuffix();

        return view('livewire.ui.employee-combobox', [
            'employees' => $employees,
            'errorId' => $this->errorId,
            'showMinCharsMessage' => $searchLength > 0 && $searchLength < self::MIN_SEARCH_LENGTH,
            'showNoResults' => $searchLength >= self::MIN_SEARCH_LENGTH && $employees->isEmpty(),
            'inputId' => 'employee-input-'.$componentId,
            'listboxId' => 'employee-listbox-'.$componentId,
            'optionIdPrefix' => 'employee-option-'.$componentId.'-',
            'createOptionId' => 'employee-option-create-'.$componentId,
            'createModalId' => 'employee-create-modal-'.$componentId,
            'createModalTitleId' => 'employee-create-title-'.$componentId,
            'createRpeInputId' => 'employee-create-rpe-'.$componentId,
            'createNameInputId' => 'employee-create-name-'.$componentId,
        ]);
    }

    /**
     * @return Collection<int, Employee>
     */
    private function getEmployeeSuggestions(): Collection
    {
        try {
            $action = new SearchEmployees;

            return $action->execute($this->search, self::MAX_RESULTS);
        } catch (Throwable $e) {
            $this->reportSearchException($e);

            return collect();
        }
    }

    private function formatEmployeeLabel(Employee $employee): string
    {
        return $employee->rpe.' - '.$employee->name;
    }

    private function setEmployeeData(Employee $employee): void
    {
        $this->employeeId = $employee->id;
        $this->employeeLabel = $this->formatEmployeeLabel($employee);
        $this->employeeRpe = $employee->rpe;
        $this->employeeName = $employee->name;
        $this->employeeDepartment = $employee->department;
    }

    private function clearEmployeeData(): void
    {
        $this->employeeId = null;
        $this->employeeLabel = '';
        $this->employeeRpe = null;
        $this->employeeName = null;
        $this->employeeDepartment = null;
    }

    private function isDuplicateRpeException(QueryException $exception): bool
    {
        $errorInfo = $exception->errorInfo;

        if (! is_array($errorInfo) || count($errorInfo) < 2) {
            return false;
        }

        $driverCode = (int) ($errorInfo[1] ?? 0);

        return $driverCode === 1062;
    }

    private function buildDomIdSuffix(): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '-', $this->getId()) ?? 'component';
    }

    private function dispatchFocusToInput(): void
    {
        $this->dispatch(
            'employee-combobox:focus-input',
            inputId: 'employee-input-'.$this->buildDomIdSuffix(),
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
