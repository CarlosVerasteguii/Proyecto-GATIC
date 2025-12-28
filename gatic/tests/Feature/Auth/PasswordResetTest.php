<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * MVP: Password reset deshabilitado - estas rutas no deben estar disponibles
     */
    public function test_reset_password_link_screen_is_not_available(): void
    {
        $response = $this->get('/forgot-password');

        // MVP: Password reset deshabilitado, debe retornar 404
        $response->assertStatus(404);
    }

    public function test_reset_password_link_cannot_be_requested(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        // MVP: Password reset deshabilitado, debe retornar 404
        $response->assertStatus(404);
    }

    public function test_reset_password_screen_is_not_available(): void
    {
        $response = $this->get('/reset-password/fake-token');

        // MVP: Password reset deshabilitado, debe retornar 404
        $response->assertStatus(404);
    }

    public function test_password_cannot_be_reset(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/reset-password', [
            'token' => 'fake-token',
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        // MVP: Password reset deshabilitado, debe retornar 404
        $response->assertStatus(404);
    }
}
