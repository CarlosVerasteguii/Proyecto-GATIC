<?php

namespace Tests\Feature\Employees;

use App\Enums\UserRole;
use App\Livewire\Employees\EmployeesIndex;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_editor_can_access_employees_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($admin)
            ->get('/employees')
            ->assertOk();

        $this->actingAs($editor)
            ->get('/employees')
            ->assertOk();
    }

    public function test_lector_cannot_access_employees_page(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector)
            ->get('/employees')
            ->assertForbidden();
    }

    public function test_can_create_employee_and_it_is_normalized(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(EmployeesIndex::class)
            ->set('rpe', '  ABC123  ')
            ->set('name', '  Juan   Pérez  ')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertDatabaseHas('employees', [
            'rpe' => 'ABC123',
            'name' => 'Juan Pérez',
        ]);
    }

    public function test_editor_can_create_employee(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        Livewire::actingAs($editor)
            ->test(EmployeesIndex::class)
            ->set('rpe', 'EDITOR1')
            ->set('name', 'Editor User')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertDatabaseHas('employees', [
            'rpe' => 'EDITOR1',
            'name' => 'Editor User',
        ]);
    }

    public function test_unique_rpe_is_enforced(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Employee::query()->create([
            'rpe' => 'ABC123',
            'name' => 'Juan Pérez',
        ]);

        Livewire::actingAs($admin)
            ->test(EmployeesIndex::class)
            ->set('rpe', 'ABC123')
            ->set('name', 'Otro Empleado')
            ->call('save')
            ->assertHasErrors(['rpe' => 'unique']);
    }

    public function test_can_update_employee(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $employee = Employee::query()->create([
            'rpe' => 'ABC123',
            'name' => 'Juan Pérez',
        ]);

        Livewire::actingAs($admin)
            ->test(EmployeesIndex::class)
            ->call('edit', $employee->id)
            ->set('name', 'Juan Actualizado')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'name' => 'Juan Actualizado',
        ]);
    }

    public function test_editor_can_update_employee(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $employee = Employee::query()->create([
            'rpe' => 'EDITOR2',
            'name' => 'Nombre Inicial',
        ]);

        Livewire::actingAs($editor)
            ->test(EmployeesIndex::class)
            ->call('edit', $employee->id)
            ->set('name', 'Nombre Editado')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'name' => 'Nombre Editado',
        ]);
    }

    public function test_can_delete_employee(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $employee = Employee::query()->create([
            'rpe' => 'ABC123',
            'name' => 'Juan Pérez',
        ]);

        Livewire::actingAs($admin)
            ->test(EmployeesIndex::class)
            ->call('delete', $employee->id)
            ->assertDispatched('ui:toast', type: 'success');

        $this->assertSoftDeleted('employees', [
            'id' => $employee->id,
        ]);
    }

    public function test_search_by_rpe_works(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Employee::query()->create(['rpe' => 'ABC123', 'name' => 'Juan']);
        Employee::query()->create(['rpe' => 'XYZ789', 'name' => 'Pedro']);

        Livewire::actingAs($admin)
            ->test(EmployeesIndex::class)
            ->set('search', 'ABC')
            ->assertSee('ABC123')
            ->assertDontSee('XYZ789');
    }

    public function test_search_by_name_works(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Employee::query()->create(['rpe' => 'ABC123', 'name' => 'Juan Pérez']);
        Employee::query()->create(['rpe' => 'XYZ789', 'name' => 'Pedro López']);

        Livewire::actingAs($admin)
            ->test(EmployeesIndex::class)
            ->set('search', 'Juan')
            ->assertSee('Juan Pérez')
            ->assertDontSee('Pedro López');
    }

    public function test_search_escapes_like_wildcards(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Employee::query()->create(['rpe' => 'TEST%USER', 'name' => 'Test User']);
        Employee::query()->create(['rpe' => 'NORMAL', 'name' => 'Normal User']);

        Livewire::actingAs($admin)
            ->test(EmployeesIndex::class)
            ->set('search', 'TEST%')
            ->assertSee('TEST%USER')
            ->assertDontSee('NORMAL');
    }

    public function test_lector_cannot_execute_employees_livewire_actions(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector);

        $component = new EmployeesIndex;

        try {
            $component->save();
            $this->fail('Expected AuthorizationException for save().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        try {
            $component->edit(1);
            $this->fail('Expected AuthorizationException for edit().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        try {
            $component->delete(1);
            $this->fail('Expected AuthorizationException for delete().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_rpe_is_required(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(EmployeesIndex::class)
            ->set('rpe', '')
            ->set('name', 'Juan Pérez')
            ->call('save')
            ->assertHasErrors(['rpe' => 'required']);
    }

    public function test_name_is_required(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(EmployeesIndex::class)
            ->set('rpe', 'ABC123')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required']);
    }
}
