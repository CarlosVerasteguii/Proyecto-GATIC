<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Support\Dashboard\AlertComparativePeriodResolver;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlertComparativePeriodResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function it_resolves_the_current_and_comparison_period_for_a_typical_window(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 17, 12, 0, 0));

        $period = (new AlertComparativePeriodResolver)->resolve(windowDays: 7);

        $this->assertSame('2026-01-17', $period['today']->toDateString());
        $this->assertSame('2026-01-16', $period['yesterday']->toDateString());
        $this->assertSame('2026-01-24', $period['windowEnd']->toDateString());
        $this->assertSame('2026-01-23', $period['windowEndYesterday']->toDateString());
    }

    #[Test]
    public function it_keeps_the_window_boundaries_stable_across_calendar_edges(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 3, 1, 9, 15, 0));

        $period = (new AlertComparativePeriodResolver)->resolve(windowDays: 1);

        $this->assertSame('2024-03-01', $period['today']->toDateString());
        $this->assertSame('2024-02-29', $period['yesterday']->toDateString());
        $this->assertSame('2024-03-02', $period['windowEnd']->toDateString());
        $this->assertSame('2024-03-01', $period['windowEndYesterday']->toDateString());
    }

    #[Test]
    public function it_supports_zero_day_windows_without_shifting_the_start_dates(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 17, 12, 0, 0));

        $period = (new AlertComparativePeriodResolver)->resolve(windowDays: 0);

        $this->assertSame('2026-01-17', $period['today']->toDateString());
        $this->assertSame('2026-01-16', $period['yesterday']->toDateString());
        $this->assertSame('2026-01-17', $period['windowEnd']->toDateString());
        $this->assertSame('2026-01-16', $period['windowEndYesterday']->toDateString());
    }
}
