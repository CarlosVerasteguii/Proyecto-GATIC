<?php

namespace Tests\Feature\Employees;

use App\Enums\UserRole;
use App\Livewire\Employees\EmployeeShow;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeShowTest extends TestCase
{
    use RefreshDatabase;

    // AC1: Admin can view employee detail
    public function test_admin_can_access_employee_show_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = Employee::query()->create([
            'rpe' => 'ABC123',
            'name' => 'Juan Pérez',
            'department' => 'Sistemas',
            'job_title' => 'Analista',
        ]);

        $this->actingAs($admin)
            ->get("/employees/{$employee->id}")
            ->assertOk();
    }

    // AC1: Editor can view employee detail
    public function test_editor_can_access_employee_show_page(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $employee = Employee::query()->create([
            'rpe' => 'XYZ789',
            'name' => 'Pedro López',
        ]);

        $this->actingAs($editor)
            ->get("/employees/{$employee->id}")
            ->assertOk();
    }

    // AC1: Lector gets 403
    public function test_lector_cannot_access_employee_show_page(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
        $employee = Employee::query()->create([
            'rpe' => 'DEF456',
            'name' => 'María García',
        ]);

        $this->actingAs($lector)
            ->get("/employees/{$employee->id}")
            ->assertForbidden();
    }

    // AC1: Lector cannot trigger Livewire render (defense in depth)
    public function test_lector_cannot_render_employee_show_component(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);
        $employee = Employee::query()->create([
            'rpe' => 'GHI789',
            'name' => 'Carlos Ruiz',
        ]);

        // Livewire wraps authorization in its own exception handling
        // The route middleware already blocks Lector (tested in test_lector_cannot_access_employee_show_page)
        // Here we verify Gate::authorize inside mount() also rejects Lector
        Livewire::actingAs($lector)
            ->test(EmployeeShow::class, ['employee' => (string) $employee->id])
            ->assertForbidden();
    }

    // AC4: Non-existent employee returns 404
    public function test_nonexistent_employee_returns_404(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/employees/99999')
            ->assertNotFound();
    }

    // AC4: Invalid (non-numeric) employee ID returns 404
    public function test_invalid_employee_id_returns_404(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/employees/abc')
            ->assertNotFound();
    }

    // AC2: Employee detail shows required fields
    public function test_employee_show_displays_required_fields(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = Employee::query()->create([
            'rpe' => 'TEST001',
            'name' => 'Ana Torres',
            'department' => 'Recursos Humanos',
            'job_title' => 'Coordinadora',
        ]);

        Livewire::actingAs($admin)
            ->test(EmployeeShow::class, ['employee' => (string) $employee->id])
            ->assertSee('TEST001')
            ->assertSee('Ana Torres')
            ->assertSee('Recursos Humanos')
            ->assertSee('Coordinadora');
    }

    // AC3: Empty asset sections are displayed
    public function test_employee_show_displays_empty_asset_sections(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = Employee::query()->create([
            'rpe' => 'EMPTY01',
            'name' => 'Sin Activos',
        ]);

        Livewire::actingAs($admin)
            ->test(EmployeeShow::class, ['employee' => (string) $employee->id])
            ->assertSee('Activos asignados')
            ->assertSee('Activos prestados')
            ->assertSee('Sin activos asignados')
            ->assertSee('Sin activos prestados');
    }

    public function test_employee_show_displays_asset_counts_and_rows(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = Employee::query()->create([
            'rpe' => 'OPS777',
            'name' => 'Laura Campos',
            'department' => 'Operaciones',
            'job_title' => 'Coordinadora',
        ]);

        $assignedProduct = Product::factory()->create([
            'name' => 'Laptop corporativa',
        ]);
        $loanedProduct = Product::factory()->create([
            'name' => 'Monitor externo',
        ]);

        Asset::factory()->create([
            'product_id' => $assignedProduct->id,
            'current_employee_id' => $employee->id,
            'serial' => 'ASSET-ASSIGNED-01',
            'asset_tag' => 'TAG-ASSIGNED-01',
            'status' => Asset::STATUS_ASSIGNED,
        ]);

        Asset::factory()->create([
            'product_id' => $loanedProduct->id,
            'current_employee_id' => $employee->id,
            'serial' => 'ASSET-LOANED-01',
            'asset_tag' => 'TAG-LOANED-01',
            'status' => Asset::STATUS_LOANED,
            'loan_due_date' => now()->addDays(3),
        ]);

        Livewire::actingAs($admin)
            ->test(EmployeeShow::class, ['employee' => (string) $employee->id])
            ->assertSee('Laura Campos')
            ->assertSee('Ficha completa')
            ->assertSee('Total Activos')
            ->assertSee('Activos actuales')
            ->assertSee('Laptop corporativa')
            ->assertSee('Monitor externo')
            ->assertSee('ASSET-ASSIGNED-01')
            ->assertSee('ASSET-LOANED-01')
            ->assertSee('Por vencer');
    }
}
