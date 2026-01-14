<?php

namespace App\Livewire\Ui;

use App\Actions\Employees\SearchEmployees;
use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Throwable;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class EmployeeCombobox extends Component
{
    #[Modelable]
    public ?int $employeeId = null;

    public string $employeeLabel = '';

    public string $search = '';

    public bool $showDropdown = false;

    public ?string $errorId = null;

    private const MIN_SEARCH_LENGTH = 2;

    private const MAX_RESULTS = 10;

    public function mount(?int $employeeId = null): void
    {
        Gate::authorize('inventory.manage');

        if ($employeeId !== null) {
            $employee = Employee::query()->find($employeeId);
            if ($employee) {
                $this->employeeId = $employee->id;
                $this->employeeLabel = $this->formatEmployeeLabel($employee);
            }
        }
    }

    public function updatedEmployeeId(?int $employeeId): void
    {
        Gate::authorize('inventory.manage');

        if ($employeeId === null) {
            $this->employeeLabel = '';

            return;
        }

        $employee = Employee::query()->find($employeeId);

        if (! $employee) {
            $this->employeeLabel = '';
            $this->employeeId = null;

            return;
        }

        $this->employeeLabel = $this->formatEmployeeLabel($employee);
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

        $employee = Employee::query()->findOrFail($employeeId);

        $this->employeeId = $employee->id;
        $this->employeeLabel = $this->formatEmployeeLabel($employee);
        $this->search = '';
        $this->showDropdown = false;
        $this->errorId = null;
    }

    public function clearSelection(): void
    {
        Gate::authorize('inventory.manage');

        $this->employeeId = null;
        $this->employeeLabel = '';
        $this->search = '';
        $this->showDropdown = false;
        $this->errorId = null;
    }

    public function closeDropdown(): void
    {
        $this->showDropdown = false;
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

        return view('livewire.ui.employee-combobox', [
            'employees' => $employees,
            'errorId' => $this->errorId,
            'showMinCharsMessage' => $searchLength > 0 && $searchLength < self::MIN_SEARCH_LENGTH,
            'showNoResults' => $searchLength >= self::MIN_SEARCH_LENGTH && $employees->isEmpty(),
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
            $this->errorId = app(\App\Support\Errors\ErrorReporter::class)->report($e, request());

            $this->dispatch(
                'ui:toast',
                type: 'error',
                title: 'Error inesperado',
                message: 'Ocurrio un error inesperado.',
                errorId: $this->errorId,
            );

            return collect();
        }
    }

    private function formatEmployeeLabel(Employee $employee): string
    {
        return $employee->rpe.' - '.$employee->name;
    }
}
