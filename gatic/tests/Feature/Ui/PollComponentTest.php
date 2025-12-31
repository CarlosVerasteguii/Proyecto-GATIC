<?php

namespace Tests\Feature\Ui;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PollComponentTest extends TestCase
{
    public function test_enabled_false_string_disables_polling_attribute(): void
    {
        $html = Blade::render('<x-ui.poll method="pollTick" enabled="false">X</x-ui.poll>');

        $this->assertStringNotContainsString('wire:poll', $html);
    }

    public function test_visible_false_string_uses_non_visible_polling_attribute(): void
    {
        $html = Blade::render('<x-ui.poll method="pollTick" visible="false">X</x-ui.poll>');

        $this->assertStringContainsString('wire:poll.', $html);
        $this->assertStringNotContainsString('wire:poll.visible.', $html);
    }
}

