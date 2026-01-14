<?php

namespace App\Actions\Employees;

use App\Models\Employee;

class UpsertEmployee
{
    /**
     * @param  array{employee_id?: int|null, rpe: string, name: string, department?: string|null, job_title?: string|null}  $data
     */
    public function execute(array $data): Employee
    {
        $employeeId = $data['employee_id'] ?? null;

        $employee = $employeeId
            ? Employee::query()->findOrFail($employeeId)
            : new Employee;

        $employee->fill([
            'rpe' => $data['rpe'],
            'name' => $data['name'],
            'department' => ($data['department'] ?? null) ?: null,
            'job_title' => ($data['job_title'] ?? null) ?: null,
        ]);

        $employee->save();

        return $employee;
    }
}
