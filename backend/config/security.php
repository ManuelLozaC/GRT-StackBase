<?php

return [
    'auth_cookie' => [
        'name' => env('AUTH_COOKIE_NAME', 'stackbase_access_token'),
        'minutes' => (int) env('AUTH_COOKIE_TTL_MINUTES', 480),
        'path' => env('AUTH_COOKIE_PATH', '/'),
        'domain' => env('AUTH_COOKIE_DOMAIN'),
        'secure' => filter_var(env('AUTH_COOKIE_SECURE', false), FILTER_VALIDATE_BOOL),
        'same_site' => env('AUTH_COOKIE_SAME_SITE', 'lax'),
    ],

    'webhooks' => [
        'replay_window_seconds' => (int) env('WEBHOOK_REPLAY_WINDOW_SECONDS', 300),
        'require_timestamp' => filter_var(env('WEBHOOK_REQUIRE_TIMESTAMP', true), FILTER_VALIDATE_BOOL),
    ],
];
