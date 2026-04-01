<?php

return [
    'auth_cookie' => [
        'name' => env('AUTH_COOKIE_NAME', 'stackbase_access_token'),
        'csrf_cookie_name' => env('AUTH_COOKIE_CSRF_NAME', 'stackbase_xsrf_token'),
        'csrf_header_name' => env('AUTH_COOKIE_CSRF_HEADER', 'X-StackBase-CSRF'),
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

    'channels' => [
        'api_bearer' => [
            'transport' => 'authorization_header',
            'requires_csrf' => false,
            'rate_limit_key' => 'auth-api',
        ],
        'web_cookie' => [
            'transport' => 'http_only_cookie',
            'requires_csrf' => true,
            'csrf_methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
            'same_site' => env('AUTH_COOKIE_SAME_SITE', 'lax'),
            'secure' => filter_var(env('AUTH_COOKIE_SECURE', false), FILTER_VALIDATE_BOOL),
        ],
        'webhooks' => [
            'transport' => 'signed_http_request',
            'require_signature' => true,
            'require_timestamp' => filter_var(env('WEBHOOK_REQUIRE_TIMESTAMP', true), FILTER_VALIDATE_BOOL),
            'replay_window_seconds' => (int) env('WEBHOOK_REPLAY_WINDOW_SECONDS', 300),
        ],
        'signed_urls' => [
            'transport' => 'signed_route',
            'default_ttl_minutes' => (int) env('SIGNED_URL_DEFAULT_TTL_MINUTES', 30),
            'max_ttl_minutes' => (int) env('SIGNED_URL_MAX_TTL_MINUTES', 1440),
            'record_downloads' => true,
        ],
        'push' => [
            'transport' => 'fcm_device_token',
            'requires_authenticated_subscription' => true,
            'allow_relative_action_urls' => true,
            'allow_absolute_action_urls' => false,
        ],
    ],
];
