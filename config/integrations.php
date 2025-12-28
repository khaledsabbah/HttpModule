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
    | Circuit Breaker Configuration (Default Settings)
    |--------------------------------------------------------------------------
    |
    | Default circuit breaker settings that services can opt-in to use.
    |
    | Per-service control:
    | 1. Custom config: Override circuitBreakerConfig() method for custom settings
    | 2. Default settings: Set $circuitBreakerEnabled = true to use these defaults
    | 3. Disabled: Default behavior (no override needed)
    |
    | See: docs/CIRCUIT_BREAKER_PER_SERVICE.md
    |
    */
    'circuit_breaker' => [
        'storage' => env('INTEGRATIONS_CIRCUIT_BREAKER_STORAGE', 'cache'),
        'cache_store' => env('INTEGRATIONS_CIRCUIT_BREAKER_CACHE_STORE', null),
        'prefix' => env('INTEGRATIONS_CIRCUIT_BREAKER_PREFIX', 'circuit_breaker'),
        'strategy' => env('INTEGRATIONS_CIRCUIT_BREAKER_STRATEGY', 'rate'),
        'time_window' => env('INTEGRATIONS_CIRCUIT_BREAKER_TIME_WINDOW', 60),
        'failure_rate_threshold' => env('INTEGRATIONS_CIRCUIT_BREAKER_FAILURE_RATE_THRESHOLD', 50),
        'failure_count_threshold' => env('INTEGRATIONS_CIRCUIT_BREAKER_FAILURE_COUNT_THRESHOLD', 5),
        'minimum_requests' => env('INTEGRATIONS_CIRCUIT_BREAKER_MINIMUM_REQUESTS', 10),
        'interval_to_half_open' => env('INTEGRATIONS_CIRCUIT_BREAKER_INTERVAL_TO_HALF_OPEN', 30),
        'success_threshold' => env('INTEGRATIONS_CIRCUIT_BREAKER_SUCCESS_THRESHOLD', 3),
        'failure_status_codes' => [500, 502, 503, 504],
        'ignored_status_codes' => [],
    ],
];
