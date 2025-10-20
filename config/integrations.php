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
];
