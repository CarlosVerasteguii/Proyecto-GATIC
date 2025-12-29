<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_includes_sidebar_and_topbar_markers(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-testid="app-sidebar"', false)
            ->assertSee('data-testid="app-topbar"', false);
    }

    public function test_sidebar_offcanvas_markup_is_present_for_mobile_toggle(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('data-bs-toggle="offcanvas"', false)
            ->assertSee('data-bs-target="#appSidebarOffcanvas"', false)
            ->assertSee('id="appSidebarOffcanvas"', false);
    }

    public function test_admin_sees_users_link_in_sidebar(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this
            ->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Usuarios')
            ->assertSee('href="'.route('admin.users.index').'"', false);
    }

    public function test_editor_and_lector_do_not_see_users_link_in_sidebar(): void
    {
        $roles = [UserRole::Editor, UserRole::Lector];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this
                ->actingAs($user)
                ->get('/dashboard')
                ->assertOk()
                ->assertDontSee('href="'.route('admin.users.index').'"', false);
        }
    }

    public function test_admin_can_access_admin_users(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this
            ->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertSee('Usuarios');
    }

    public function test_editor_and_lector_are_forbidden_from_admin_users(): void
    {
        $roles = [UserRole::Editor, UserRole::Lector];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this
                ->actingAs($user)
                ->get('/admin/users')
                ->assertStatus(403);
        }
    }
}
