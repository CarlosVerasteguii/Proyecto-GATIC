<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_users_management_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_editor_cannot_access_users_management_page(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->actingAs($editor)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_lector_cannot_access_users_management_page(): void
    {
        $lector = User::factory()->create(['role' => UserRole::Lector]);

        $this->actingAs($lector)
            ->get('/admin/users')
            ->assertForbidden();
    }
}
