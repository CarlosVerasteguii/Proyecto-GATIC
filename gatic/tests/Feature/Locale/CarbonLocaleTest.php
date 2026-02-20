<?php

namespace Tests\Feature\Locale;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarbonLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_relative_time_is_not_english(): void
    {
        $text = CarbonImmutable::now()->subMinute()->diffForHumans();

        $this->assertStringNotContainsString('minute', $text);
        $this->assertStringNotContainsString('ago', $text);
    }
}
