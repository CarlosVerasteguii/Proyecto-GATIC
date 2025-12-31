<?php

use App\Models\ErrorReport;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('gatic:purge-error-reports', function () {
    $retentionDays = (int) config('gatic.errors.reporting.retention_days', 30);

    if ($retentionDays <= 0) {
        $this->warn('Retention disabled (retention_days <= 0). No records deleted.');

        return 0;
    }

    $cutoff = now()->subDays($retentionDays);

    $deleted = ErrorReport::query()
        ->where('created_at', '<', $cutoff)
        ->delete();

    $this->info("Deleted {$deleted} error_reports older than {$retentionDays} days (before {$cutoff->toDateTimeString()}).");

    return 0;
})->purpose('Purge old error_reports based on configured retention');
