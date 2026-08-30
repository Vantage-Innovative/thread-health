<?php

return [
    'endpoint' => env('THREAD_HEALTH_ENDPOINT'),
    'token' => env('THREAD_HEALTH_TOKEN'),
    'environment' => env('THREAD_HEALTH_ENVIRONMENT', env('APP_ENV', 'production')),
    'cadence_minutes' => (int) env('THREAD_HEALTH_CADENCE_MINUTES', 15),
    'connect_timeout_seconds' => (int) env('THREAD_HEALTH_CONNECT_TIMEOUT_SECONDS', 4),
    'request_timeout_seconds' => (int) env('THREAD_HEALTH_REQUEST_TIMEOUT_SECONDS', 20),
    'slow_request_ms' => (int) env('THREAD_HEALTH_SLOW_REQUEST_MS', 1000),
    'slow_job_ms' => (int) env('THREAD_HEALTH_SLOW_JOB_MS', 1000),
    'slow_query_ms' => (int) env('THREAD_HEALTH_SLOW_QUERY_MS', 1000),
];
