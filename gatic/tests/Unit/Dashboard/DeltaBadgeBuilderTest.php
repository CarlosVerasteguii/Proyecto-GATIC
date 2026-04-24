<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Support\Dashboard\DeltaBadgeBuilder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeltaBadgeBuilderTest extends TestCase
{
    #[Test]
    public function it_returns_an_empty_badge_when_the_delta_is_zero(): void
    {
        $badge = app(DeltaBadgeBuilder::class)->build(
            current: 5,
            previous: 5,
            suffix: 'vs ayer',
            increaseVariant: 'danger',
            decreaseVariant: 'success',
        );

        $this->assertSame([null, null], $badge);
    }

    #[Test]
    public function it_builds_a_positive_percentage_badge_when_the_previous_value_is_greater_than_zero(): void
    {
        $badge = app(DeltaBadgeBuilder::class)->build(
            current: 6,
            previous: 4,
            suffix: 'vs ayer',
            increaseVariant: 'danger',
            decreaseVariant: 'success',
        );

        $this->assertSame(['+50.0% vs ayer', 'danger'], $badge);
    }

    #[Test]
    public function it_builds_a_signed_absolute_delta_when_the_previous_value_is_zero(): void
    {
        $badge = app(DeltaBadgeBuilder::class)->build(
            current: 3,
            previous: 0,
            suffix: 'vs ayer',
            increaseVariant: 'warning',
            decreaseVariant: 'success',
        );

        $this->assertSame(['+3 vs ayer', 'warning'], $badge);
    }

    #[Test]
    public function it_builds_a_negative_percentage_badge_with_the_decrease_variant(): void
    {
        $badge = app(DeltaBadgeBuilder::class)->build(
            current: 0,
            previous: 2,
            suffix: 'vs ayer',
            increaseVariant: 'danger',
            decreaseVariant: 'success',
        );

        $this->assertSame(['-100.0% vs ayer', 'success'], $badge);
    }
}
