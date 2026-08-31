<?php

return [
    'name' => env('APP_NAME', 'LokalPay Pro'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'Europe/Warsaw'),
    'locale' => env('APP_LOCALE', 'pl'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'pl'),
    'faker_locale' => 'pl_PL',
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => array_filter(explode(',', env('APP_PREVIOUS_KEYS', ''))),
    'maintenance' => ['driver' => env('APP_MAINTENANCE_DRIVER', 'file')],
];
