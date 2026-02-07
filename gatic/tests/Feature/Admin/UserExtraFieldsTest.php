<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Livewire\Admin\Users\UserForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserExtraFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_department_and_position_for_existing_user(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $target = User::factory()->create([
            'role' => UserRole::Editor,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(UserForm::class, ['user' => (string) $target->id])
            ->set('department', 'TI')
            ->set('position', 'Analista')
            ->call('save')
            ->assertHasNoErrors();

        $target->refresh();

        $this->assertSame('TI', $target->department);
        $this->assertSame('Analista', $target->position);
    }

    public function test_department_and_position_max_length_is_255(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $target = User::factory()->create([
            'role' => UserRole::Editor,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(UserForm::class, ['user' => (string) $target->id])
            ->set('department', str_repeat('a', 256))
            ->set('position', str_repeat('b', 256))
            ->call('save')
            ->assertHasErrors([
                'department' => ['max'],
                'position' => ['max'],
            ]);
    }
}
