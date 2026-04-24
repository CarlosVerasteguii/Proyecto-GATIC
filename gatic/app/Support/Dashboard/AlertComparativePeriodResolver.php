<?php

declare(strict_types=1);

namespace App\Support\Dashboard;

use Illuminate\Support\Carbon;

/**
 * @phpstan-type ComparativeAlertPeriod array{
 *     today: Carbon,
 *     yesterday: Carbon,
 *     windowEnd: Carbon,
 *     windowEndYesterday: Carbon
 * }
 */
final class AlertComparativePeriodResolver
{
    /**
     * @return ComparativeAlertPeriod
     */
    public function resolve(int $windowDays): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        return [
            'today' => $today,
            'yesterday' => $yesterday,
            'windowEnd' => $today->copy()->addDays($windowDays),
            'windowEndYesterday' => $yesterday->copy()->addDays($windowDays),
        ];
    }
}
