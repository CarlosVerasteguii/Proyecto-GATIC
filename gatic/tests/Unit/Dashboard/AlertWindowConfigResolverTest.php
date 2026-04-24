<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Support\Dashboard\AlertWindowConfigResolver;
use App\Support\Settings\SettingsStore;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlertWindowConfigResolverTest extends TestCase
{
    #[Test]
    public function it_uses_the_first_allowed_option_as_the_default_window_when_no_override_is_available(): void
    {
        $store = $this->createMock(SettingsStore::class);
        $store->expects($this->once())
            ->method('getIntList')
            ->with('gatic.alerts.loans.due_soon_window_days_options', [7, 14, 30])
            ->willReturn([7, 14, 30]);
        $store->expects($this->once())
            ->method('getInt')
            ->with('gatic.alerts.loans.due_soon_window_days_default', 7)
            ->willReturn(7);

        $config = (new AlertWindowConfigResolver($store))->resolve(
            optionsKey: 'gatic.alerts.loans.due_soon_window_days_options',
            defaultKey: 'gatic.alerts.loans.due_soon_window_days_default',
            fallbackOptions: [7, 14, 30],
        );

        $this->assertSame([
            'allowedOptions' => [7, 14, 30],
            'fallbackWindowDays' => 7,
            'defaultWindowDays' => 7,
        ], $config);
    }

    #[Test]
    public function it_keeps_a_valid_default_window_value(): void
    {
        $store = $this->createMock(SettingsStore::class);
        $store->expects($this->once())
            ->method('getIntList')
            ->with('gatic.alerts.warranties.due_soon_window_days_options', [7, 14, 30])
            ->willReturn([7, 14, 30]);
        $store->expects($this->once())
            ->method('getInt')
            ->with('gatic.alerts.warranties.due_soon_window_days_default', 7)
            ->willReturn(14);

        $config = (new AlertWindowConfigResolver($store))->resolve(
            optionsKey: 'gatic.alerts.warranties.due_soon_window_days_options',
            defaultKey: 'gatic.alerts.warranties.due_soon_window_days_default',
            fallbackOptions: [7, 14, 30],
        );

        $this->assertSame([
            'allowedOptions' => [7, 14, 30],
            'fallbackWindowDays' => 7,
            'defaultWindowDays' => 14,
        ], $config);
    }

    #[Test]
    public function it_falls_back_to_the_first_allowed_option_when_the_default_window_value_is_invalid(): void
    {
        $store = $this->createMock(SettingsStore::class);
        $store->expects($this->once())
            ->method('getIntList')
            ->with('gatic.alerts.warranties.due_soon_window_days_options', [7, 14, 30])
            ->willReturn([7, 14, 30]);
        $store->expects($this->once())
            ->method('getInt')
            ->with('gatic.alerts.warranties.due_soon_window_days_default', 7)
            ->willReturn(99);

        $config = (new AlertWindowConfigResolver($store))->resolve(
            optionsKey: 'gatic.alerts.warranties.due_soon_window_days_options',
            defaultKey: 'gatic.alerts.warranties.due_soon_window_days_default',
            fallbackOptions: [7, 14, 30],
        );

        $this->assertSame([
            'allowedOptions' => [7, 14, 30],
            'fallbackWindowDays' => 7,
            'defaultWindowDays' => 7,
        ], $config);
    }

    #[Test]
    public function it_uses_the_provided_fallback_options_when_the_allowed_options_are_empty(): void
    {
        $store = $this->createMock(SettingsStore::class);
        $store->expects($this->once())
            ->method('getIntList')
            ->with('gatic.alerts.renewals.due_soon_window_days_options', [30, 60, 90, 180])
            ->willReturn([]);
        $store->expects($this->once())
            ->method('getInt')
            ->with('gatic.alerts.renewals.due_soon_window_days_default', 30)
            ->willReturn(60);

        $config = (new AlertWindowConfigResolver($store))->resolve(
            optionsKey: 'gatic.alerts.renewals.due_soon_window_days_options',
            defaultKey: 'gatic.alerts.renewals.due_soon_window_days_default',
            fallbackOptions: [30, 60, 90, 180],
        );

        $this->assertSame([
            'allowedOptions' => [30, 60, 90, 180],
            'fallbackWindowDays' => 30,
            'defaultWindowDays' => 60,
        ], $config);
    }
}
