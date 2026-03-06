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
            ->select(['id', 'rpe', 'name', 'department', 'job_title'])
            ->with([
                'assignedAssets' => fn ($query) => $query
                    ->select(['id', 'product_id', 'current_employee_id', 'serial', 'asset_tag', 'status'])
                    ->with(['product:id,name'])
                    ->orderBy('serial'),
                'loanedAssets' => fn ($query) => $query
                    ->select(['id', 'product_id', 'current_employee_id', 'serial', 'asset_tag', 'status', 'loan_due_date'])
                    ->with(['product:id,name'])
                    ->orderBy('loan_due_date')
                    ->orderBy('serial'),
            ])
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
