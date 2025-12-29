<?php

namespace Tests\Feature\Dev;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivewireSmokePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dev/livewire-smoke')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_load_smoke_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dev/livewire-smoke')
            ->assertOk()
            ->assertSee('wire:id', escape: false)
            ->assertSee('Prueba Livewire (smoke)');
    }
}
