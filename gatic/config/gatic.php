<?php

return [
    'errors' => [
        'reporting' => [
            'enabled' => env('GATIC_ERROR_REPORTING_ENABLED', true),
            'retention_days' => env('GATIC_ERROR_REPORTS_RETENTION_DAYS', 30),
        ],
    ],
    'ui' => [
        'toast' => [
            'default_delay_ms' => 5000,
            'undo_delay_ms' => 10000,
        ],
        'long_request_threshold_ms' => 3000,
    ],
];
