<?php

namespace App\Livewire\Employees;

use App\Actions\Employees\DeleteEmployee;
use App\Actions\Employees\UpsertEmployee;
use App\Livewire\Concerns\InteractsWithToasts;
use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class EmployeesIndex extends Component
{
    use InteractsWithToasts;
    use WithPagination;

    public string $search = '';

    public ?int $employeeId = null;

    public string $rpe = '';

    public string $name = '';

    public string $department = '';

    public string $jobTitle = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $employeeId): void
    {
        Gate::authorize('inventory.manage');

        $employee = Employee::query()->findOrFail($employeeId);

        $this->employeeId = $employee->id;
        $this->rpe = $employee->rpe;
        $this->name = $employee->name;
        $this->department = $employee->department ?? '';
        $this->jobTitle = $employee->job_title ?? '';
    }

    public function cancelEdit(): void
    {
        $this->reset(['employeeId', 'rpe', 'name', 'department', 'jobTitle']);
        $this->resetValidation();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $uniqueRpeRule = Rule::unique('employees', 'rpe');

        if ($this->employeeId) {
            $uniqueRpeRule = $uniqueRpeRule->ignore($this->employeeId);
        }

        return [
            'rpe' => [
                'required',
                'string',
                'max:255',
                $uniqueRpeRule,
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'department' => [
                'nullable',
                'string',
                'max:255',
            ],
            'jobTitle' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'rpe.required' => 'El RPE es obligatorio.',
            'rpe.unique' => 'El RPE ya existe.',
            'name.required' => 'El nombre es obligatorio.',
        ];
    }

    public function save(): void
    {
        Gate::authorize('inventory.manage');

        $this->rpe = Employee::normalizeText($this->rpe) ?? '';
        $this->name = Employee::normalizeText($this->name) ?? '';
        $this->department = Employee::normalizeText($this->department) ?? '';
        $this->jobTitle = Employee::normalizeText($this->jobTitle) ?? '';

        $this->validate();

        try {
            $action = new UpsertEmployee;
            $employee = $action->execute([
                'employee_id' => $this->employeeId,
                'rpe' => $this->rpe,
                'name' => $this->name,
                'department' => $this->department ?: null,
                'job_title' => $this->jobTitle ?: null,
            ]);

            if (! $this->employeeId) {
                $this->reset(['rpe', 'name', 'department', 'jobTitle']);
                $this->resetValidation();
                $this->toastSuccess('Empleado creado.');

                return;
            }
            $this->employeeId = $employee->id;
        } catch (QueryException $exception) {
            if ($this->isDuplicateRpeException($exception)) {
                $this->addError('rpe', 'El RPE ya existe.');

                return;
            }

            throw $exception;
        }

        $this->reset(['employeeId', 'rpe', 'name', 'department', 'jobTitle']);
        $this->resetValidation();
        $this->toastSuccess('Empleado actualizado.');
    }

    public function delete(int $employeeId): void
    {
        Gate::authorize('inventory.manage');

        $action = new DeleteEmployee;
        $action->execute($employeeId);

        if ($this->employeeId === $employeeId) {
            $this->reset(['employeeId', 'rpe', 'name', 'department', 'jobTitle']);
            $this->resetValidation();
        }

        $this->toastSuccess('Empleado eliminado.');
    }

    public function render(): View
    {
        Gate::authorize('inventory.manage');

        $search = Employee::normalizeText($this->search);
        $escapedSearch = $search !== null ? $this->escapeLike($search) : null;

        return view('livewire.employees.employees-index', [
            'employees' => Employee::query()
                ->when($escapedSearch, function ($query) use ($escapedSearch) {
                    $query->where(function ($q) use ($escapedSearch) {
                        $q->whereRaw("rpe like ? escape '\\\\'", ["%{$escapedSearch}%"])
                            ->orWhereRaw("name like ? escape '\\\\'", ["%{$escapedSearch}%"]);
                    });
                })
                ->orderBy('name')
                ->paginate((int) config('gatic.ui.pagination.per_page')),
            'isEditing' => (bool) $this->employeeId,
        ]);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
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
}
