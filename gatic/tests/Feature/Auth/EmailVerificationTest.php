<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * MVP: Email verification deshabilitado - estas rutas no deben estar disponibles
     */
    public function test_email_verification_screen_is_not_available(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        // MVP: Email verification deshabilitado, debe retornar 404
        $response->assertStatus(404);
    }

    public function test_email_verification_notification_route_is_not_available(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/email/verification-notification');

        // MVP: Email verification deshabilitado, debe retornar 404
        $response->assertStatus(404);
    }
}
