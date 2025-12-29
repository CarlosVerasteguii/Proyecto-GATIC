<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Livewire\Admin\Users\UserForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminLockoutPreventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_last_active_admin_cannot_demote_self(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(UserForm::class, ['user' => (string) $admin->id])
            ->set('role', UserRole::Editor->value)
            ->call('save')
            ->assertHasErrors(['role']);

        $admin->refresh();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->is_active);
    }

    public function test_admin_can_change_another_users_role(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => UserRole::Lector,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(UserForm::class, ['user' => (string) $user->id])
            ->set('role', UserRole::Editor->value)
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame(UserRole::Editor, $user->role);
    }
}
