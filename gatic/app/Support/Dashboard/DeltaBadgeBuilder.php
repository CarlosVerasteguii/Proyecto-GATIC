<?php

declare(strict_types=1);

namespace App\Support\Dashboard;

/**
 * @phpstan-type DeltaBadge array{0: string|null, 1: string|null}
 */
final class DeltaBadgeBuilder
{
    /**
     * @return DeltaBadge
     */
    public function build(
        int $current,
        int $previous,
        string $suffix,
        string $increaseVariant,
        string $decreaseVariant,
    ): array {
        if ($current === $previous) {
            return [null, null];
        }

        $delta = $current - $previous;
        if ($previous > 0) {
            $pct = ($delta / $previous) * 100;
            $label = sprintf('%+.1f%% %s', $pct, $suffix);
        } else {
            $label = sprintf('%+d %s', $delta, $suffix);
        }

        $variant = $delta > 0 ? $increaseVariant : $decreaseVariant;

        return [$label, $variant];
    }
}
