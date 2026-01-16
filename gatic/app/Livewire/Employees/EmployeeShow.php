<?php

namespace App\Livewire\Employees;

use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class EmployeeShow extends Component
{
    public int $employeeId;

    public ?Employee $employeeModel = null;

    public function mount(string $employee): void
    {
        Gate::authorize('inventory.manage');

        if (! ctype_digit($employee)) {
            abort(404);
        }

        $this->employeeId = (int) $employee;

        $this->employeeModel = Employee::query()
            ->with(['assignedAssets.product', 'loanedAssets.product'])
            ->findOrFail($this->employeeId);
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        return view('livewire.employees.employee-show', [
            'employee' => $this->employeeModel,
        ]);
    }
}
