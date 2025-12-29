<?php
return [
    'base_uri' => env('INTEGRATIONS_BASE_URI', null),
    'timeout'  => env('INTEGRATIONS_TIMEOUT', null),
    'retry'    => [
        'times'    => env('INTEGRATIONS_RETRY_TIMES', 0),
        'sleep_ms' => env('INTEGRATIONS_RETRY_SLEEP_MS', 0),
    ],
    'default_headers' => [],
    'logging' => [
        'channel' => env('INTEGRATIONS_LOG_CHANNEL', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Storage Prefix
    |--------------------------------------------------------------------------
    |
    | Storage key prefix for circuit breaker state (app-namespaced).
    | This ensures multiple applications can share the same Redis/Cache
    | without key collisions.
    |
    | Note: APP_NAME is sanitized to replace ':' with '_' to prevent key parsing issues.
    |
    */
    'circuit_breaker_prefix' => env(
        'INTEGRATIONS_CIRCUIT_BREAKER_PREFIX',
        'cb:' . str_replace(':', '_', env('APP_NAME', 'app'))
    ),
];
