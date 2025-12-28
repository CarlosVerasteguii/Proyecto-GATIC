<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * MVP: Profile management deshabilitado - Story 1.3 scope = "solo login/logout"
     */
    public function test_profile_page_is_not_available(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        // MVP: Profile deshabilitado, debe retornar 404
        $response->assertStatus(404);
    }

    public function test_profile_information_cannot_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        // MVP: Profile deshabilitado, debe retornar 404
        $response->assertStatus(404);
    }

    public function test_user_cannot_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        // MVP: Profile deshabilitado, debe retornar 404
        $response->assertStatus(404);
    }
}
