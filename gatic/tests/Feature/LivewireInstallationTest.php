<?php

namespace Tests\Feature;

use Tests\TestCase;

class LivewireInstallationTest extends TestCase
{
    public function test_livewire_is_installed(): void
    {
        $this->assertTrue(
            class_exists('Livewire\\Livewire'),
            'Expected Livewire 3 to be installed (class Livewire\\Livewire not found).'
        );
    }
}
