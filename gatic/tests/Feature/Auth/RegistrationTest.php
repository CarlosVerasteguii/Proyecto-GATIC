<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * MVP: Registro público deshabilitado - Admin aprovisiona usuarios
     * CRITICAL: Debe retornar 404 (no redirect) para asegurar que la ruta no existe
     */
    public function test_registration_screen_is_not_available(): void
    {
        $response = $this->get('/register');

        // MVP: Registro deshabilitado, debe retornar 404 (no redirect)
        $response->assertStatus(404);
    }

    public function test_users_cannot_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Should not be authenticated
        $this->assertGuest();

        // MVP: Registro deshabilitado, debe retornar 404 (no redirect)
        $response->assertStatus(404);
    }
}
