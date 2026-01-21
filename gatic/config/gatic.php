<?php

return [
    'errors' => [
        'reporting' => [
            'enabled' => env('GATIC_ERROR_REPORTING_ENABLED', true),
            'retention_days' => env('GATIC_ERROR_REPORTS_RETENTION_DAYS', 30),
        ],
    ],
    'ui' => [
        'pagination' => [
            'per_page' => 15,
        ],
        'toast' => [
            'default_delay_ms' => 5000,
            'undo_delay_ms' => 10000,
        ],
        'polling' => [
            'enabled' => env('GATIC_UI_POLLING_ENABLED', true),
            'badges_interval_s' => 15,
            'metrics_interval_s' => 60,
            'locks_heartbeat_interval_s' => 10,
        ],
        'long_request_threshold_ms' => 3000,
    ],
    'pending_tasks' => [
        'bulk_paste' => [
            'max_lines' => 200,
        ],
        'locks' => [
            // Lease TTL: how long a lock is valid after claim/heartbeat (in seconds)
            'lease_ttl_s' => 180, // 3 minutes
            // Idle guard: no heartbeat renewal if no user activity for this long (in seconds)
            'idle_guard_s' => 120, // 2 minutes
            // Heartbeat interval configured in ui.polling.locks_heartbeat_interval_s
        ],
    ],
];
