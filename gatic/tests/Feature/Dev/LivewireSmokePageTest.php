<?php

namespace Tests\Feature\Dev;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
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
        $this->assertTrue((bool) config('gatic.ui.polling.enabled', true));
        $pollIntervalS = (int) config('gatic.ui.polling.badges_interval_s', 15);

        $this->actingAs($user)
            ->get('/dev/livewire-smoke')
            ->assertOk()
            ->assertSee('wire:id', escape: false)
            ->assertSee('wire:poll.visible.'.$pollIntervalS.'s', escape: false)
            ->assertSee('Prueba Livewire (smoke)');
    }

    public function test_polling_can_be_disabled_via_config(): void
    {
        Config::set('gatic.ui.polling.enabled', false);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dev/livewire-smoke')
            ->assertOk()
            ->assertDontSee('wire:poll.visible.', escape: false)
            ->assertDontSee('wire:poll.', escape: false);
    }
}
