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
    'alerts' => [
        'loans' => [
            // Default "por vencer" window shown in dashboard and alert list (days).
            'due_soon_window_days_default' => 7,
            // Allowed window options (days) exposed via query string + UI.
            'due_soon_window_days_options' => [7, 14, 30],
        ],
        'warranties' => [
            // Default "por vencer" window shown in warranty alert list (days).
            'due_soon_window_days_default' => 30,
            // Allowed window options (days) exposed via query string + UI.
            'due_soon_window_days_options' => [7, 14, 30],
        ],
        'renewals' => [
            // Default "por vencer" window shown in renewal alert list (days).
            'due_soon_window_days_default' => 90,
            // Allowed window options (days) exposed via query string + UI.
            'due_soon_window_days_options' => [30, 60, 90, 180],
        ],
    ],
    'perf' => [
        'log_enabled' => (bool) env('PERF_LOG', false),
    ],
    'inventory' => [
        'money' => [
            // Single-currency MVP: Pesos Mexicanos (MXN).
            'allowed_currencies' => ['MXN'],
            'default_currency' => 'MXN',
        ],
        'bulk_actions' => [
            'max_assets' => (int) env('GATIC_INVENTORY_BULK_MAX_ASSETS', 50),
        ],
    ],
    'dashboard' => [
        'value' => [
            // Number of top categories/brands to show in breakdown
            'top_n' => 5,
        ],
    ],
];
