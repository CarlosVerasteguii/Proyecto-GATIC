<?php

namespace App\Support\Dashboard;

use App\Enums\PendingTaskStatus;
use App\Models\PendingTask;

/**
 * @phpstan-type PendingTaskCounts array{ready: int, processing: int, active: int}
 */
final class PendingTaskCountsReader
{
    /**
     * @return PendingTaskCounts
     */
    public function get(): array
    {
        $counts = PendingTask::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $ready = (int) ($counts[PendingTaskStatus::Ready->value] ?? 0);
        $processing = (int) ($counts[PendingTaskStatus::Processing->value] ?? 0);

        return [
            'ready' => $ready,
            'processing' => $processing,
            'active' => $ready + $processing,
        ];
    }
}
