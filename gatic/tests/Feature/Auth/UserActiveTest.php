<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors([
            'email' => 'Tu usuario está deshabilitado. Contacta a un administrador.',
        ]);
    }

    public function test_user_is_logged_out_on_next_request_when_disabled(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $response = $this->get('/dashboard');

        $this->assertGuest();
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'Tu usuario está deshabilitado. Contacta a un administrador.',
        ]);
    }
}
