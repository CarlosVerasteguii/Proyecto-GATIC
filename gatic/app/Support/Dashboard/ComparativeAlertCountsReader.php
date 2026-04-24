<?php

declare(strict_types=1);

namespace App\Support\Dashboard;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;

/**
 * @phpstan-type ComparativeAlertCounts array{
 *     currentOverdue: int,
 *     previousOverdue: int,
 *     currentDueSoon: int,
 *     previousDueSoon: int
 * }
 */
final class ComparativeAlertCountsReader
{
    /**
     * @param  Builder<Asset>  $baseQuery
     * @param  array{
     *     today: \Illuminate\Support\Carbon,
     *     yesterday: \Illuminate\Support\Carbon,
     *     windowEnd: \Illuminate\Support\Carbon,
     *     windowEndYesterday: \Illuminate\Support\Carbon
     * }  $comparativePeriod
     * @return ComparativeAlertCounts
     */
    public function get(Builder $baseQuery, string $dateColumn, array $comparativePeriod): array
    {
        $today = $comparativePeriod['today']->toDateString();
        $yesterday = $comparativePeriod['yesterday']->toDateString();
        $windowEnd = $comparativePeriod['windowEnd']->toDateString();
        $windowEndYesterday = $comparativePeriod['windowEndYesterday']->toDateString();

        return [
            'currentOverdue' => (clone $baseQuery)
                ->where($dateColumn, '<', $today)
                ->count(),
            'previousOverdue' => (clone $baseQuery)
                ->where($dateColumn, '<', $yesterday)
                ->count(),
            'currentDueSoon' => (clone $baseQuery)
                ->whereBetween($dateColumn, [$today, $windowEnd])
                ->count(),
            'previousDueSoon' => (clone $baseQuery)
                ->whereBetween($dateColumn, [$yesterday, $windowEndYesterday])
                ->count(),
        ];
    }
}
