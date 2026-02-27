<?php

namespace Tests\Feature\Dev;

use App\Livewire\Dev\UiBadgesSmokeTest;
use Livewire\Livewire;
use Tests\TestCase;

class UiBadgesSmokeComponentTest extends TestCase
{
    public function test_component_renders(): void
    {
        Livewire::test(UiBadgesSmokeTest::class)
            ->assertSee('Laboratorio de badges', false);
    }
}

