<?php

namespace Tests\Feature\Employees;

use App\Enums\UserRole;
use App\Livewire\Ui\EmployeeCombobox;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeComboboxTest extends TestCase
{
    use RefreshDatabase;

    // AC1 - RBAC: Admin and Editor can get suggestions
    public function test_admin_can_search_employees(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Employee::query()->create(['rpe' => 'ABC123', 'name' => 'Juan Perez']);

        Livewire::actingAs($admin)
            ->test(EmployeeCombobox::class)
            ->set('search', 'ABC')
            ->assertSee('ABC123')
            ->assertSee('Juan Perez');
    }

    public function test_editor_can_search_employees(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        Employee::query()->create(['rpe' => 'XYZ789', 'name' => 'Pedro Lopez']);

        Livewire::actingAs($editor)
            ->test(EmployeeCombobox::class)
            ->set('search', 'Pedro')
            ->assertSee('XYZ789')
            ->assertSee('Pedro Lopez');
    }

    // AC1 - RBAC: Lector cannot execute combobox actions
    public function test_lector_cannot_execute_employee_combobox_actions(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector);

        $component = new EmployeeCombobox;

        foreach (['mount', 'updatedSearch', 'clearSelection', 'retrySearch'] as $method) {
            try {
                $component->{$method}();
                $this->fail("Expected AuthorizationException for {$method}().");
            } catch (AuthorizationException) {
                $this->addToAssertionCount(1);
            }
        }

        try {
            $component->selectEmployee(1);
            $this->fail('Expected AuthorizationException for selectEmployee().');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }
    }

    // AC2 - Search by RPE (prefix and substring)
    public function test_search_by_rpe_prefix(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Employee::query()->create(['rpe' => 'ABC123', 'name' => 'Juan']);
        Employee::query()->create(['rpe' => 'XYZ789', 'name' => 'Pedro']);

        Livewire::actingAs($admin)
            ->test(EmployeeCombobox::class)
            ->set('search', 'ABC')
            ->assertSee('ABC123')
            ->assertDontSee('XYZ789');
    }

    public function test_search_by_rpe_substring(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Employee::query()->create(['rpe' => 'ABC123', 'name' => 'Juan']);

        Livewire::actingAs($admin)
            ->test(EmployeeCombobox::class)
            ->set('search', '123')
            ->assertSee('ABC123');
    }

    // AC2 - Search by name
    public function test_search_by_name(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Employee::query()->create(['rpe' => 'ABC123', 'name' => 'Juan Perez']);
        Employee::query()->create(['rpe' => 'XYZ789', 'name' => 'Pedro Lopez']);

        Livewire::actingAs($admin)
            ->test(EmployeeCombobox::class)
            ->set('search', 'Juan')
            ->assertSee('Juan Perez')
            ->assertDontSee('Pedro Lopez');
    }

    // AC5 - Escape LIKE wildcards
    public function test_search_escapes_percent_wildcard(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Employee::query()->create(['rpe' => 'TEST%USER', 'name' => 'Test User']);
        Employee::query()->create(['rpe' => 'NORMAL', 'name' => 'Normal User']);

        Livewire::actingAs($admin)
            ->test(EmployeeCombobox::class)
            ->set('search', 'TEST%')
            ->assertSee('TEST%USER')
            ->assertDontSee('NORMAL');
    }

    public function test_search_escapes_underscore_wildcard(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Employee::query()->create(['rpe' => 'TEST_USER', 'name' => 'Test User']);
        Employee::query()->create(['rpe' => 'TESTXUSER', 'name' => 'Another User']);

        Livewire::actingAs($admin)
            ->test(EmployeeCombobox::class)
            ->set('search', 'TEST_')
            ->assertSee('TEST_USER')
            ->assertDontSee('TESTXUSER');
    }

    // AC2 - Minimum 2 characters to search
    public function test_no_search_with_less_than_2_characters(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Employee::query()->create(['rpe' => 'ABC123', 'name' => 'Juan']);

        Livewire::actingAs($admin)
            ->test(EmployeeCombobox::class)
            ->set('search', 'A')
            ->assertDontSee('ABC123')
            ->assertSee('Escribe al menos 2 caracteres');
    }

    // AC2 - Limit results
    public function test_results_are_limited(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        for ($i = 1; $i <= 15; $i++) {
            Employee::query()->create([
                'rpe' => 'TEST'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => 'Employee '.$i,
            ]);
        }

        $component = Livewire::actingAs($admin)
            ->test(EmployeeCombobox::class)
            ->set('search', 'TEST');

        $this->assertSame(10, substr_count($component->html(), 'role="option"'));
    }

    // AC3 - Selection sets employee_id
    public function test_select_employee_sets_employee_id(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = Employee::query()->create(['rpe' => 'ABC123', 'name' => 'Juan Perez']);

        Livewire::actingAs($admin)
            ->test(EmployeeCombobox::class)
            ->set('search', 'ABC')
            ->call('selectEmployee', $employee->id)
            ->assertSet('employeeId', $employee->id)
            ->assertSet('employeeLabel', 'ABC123 - Juan Perez');
    }

    // AC2 - Binding: updating employeeId from parent recomputes label
    public function test_setting_employee_id_updates_employee_label(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = Employee::query()->create(['rpe' => 'ABC123', 'name' => 'Juan Perez']);

        Livewire::actingAs($admin)
            ->test(EmployeeCombobox::class)
            ->set('employeeId', $employee->id)
            ->assertSet('employeeLabel', 'ABC123 - Juan Perez');
    }

    // AC4 - Clear selection returns to null
    public function test_clear_selection_resets_to_null(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $employee = Employee::query()->create(['rpe' => 'ABC123', 'name' => 'Juan Perez']);

        Livewire::actingAs($admin)
            ->test(EmployeeCombobox::class)
            ->set('search', 'ABC')
            ->call('selectEmployee', $employee->id)
            ->call('clearSelection')
            ->assertSet('employeeId', null)
            ->assertSet('employeeLabel', '');
    }

    // AC4 - No results message
    public function test_shows_no_results_message(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(EmployeeCombobox::class)
            ->set('search', 'NONEXISTENT')
            ->assertSee('Sin resultados');
    }

    // AC2 - Case insensitive search
    public function test_search_is_case_insensitive(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Employee::query()->create(['rpe' => 'ABC123', 'name' => 'Juan Perez']);

        Livewire::actingAs($admin)
            ->test(EmployeeCombobox::class)
            ->set('search', 'abc')
            ->assertSee('ABC123');
    }
}

