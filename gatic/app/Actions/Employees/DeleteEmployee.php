<?php

namespace App\Actions\Employees;

use App\Models\Employee;

class DeleteEmployee
{
    public function execute(int $employeeId): void
    {
        $employee = Employee::query()->findOrFail($employeeId);
        $employee->delete();
    }
}
