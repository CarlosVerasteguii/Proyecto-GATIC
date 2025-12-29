<?php

namespace Tests\Feature\Dev;

use App\Livewire\Dev\LivewireSmokeTest;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireSmokeComponentTest extends TestCase
{
    public function test_component_renders_and_increments_counter(): void
    {
        Livewire::test(LivewireSmokeTest::class)
            ->assertSet('count', 0)
            ->call('increment')
            ->assertSet('count', 1);
    }
}
