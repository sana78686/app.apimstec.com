<?php

$defaultOrigins = [
    'http://localhost:5173',
    'http://localhost:5000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:5000',
    'http://127.0.0.1:3000',
    'http://compresspdf.id',
    'https://compresspdf.id',
    'http://www.compresspdf.id',
    'https://www.compresspdf.id',
];

$fromEnv = array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))));

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique(array_filter(array_merge($defaultOrigins, $fromEnv)))),

    /*
     * Case / subdomain tolerant (browsers send Origin exactly; list above must match).
     * Also covers staging like https://staging.compresspdf.id when you add DNS.
     */
    'allowed_origins_patterns' => [
        '#^https?://([a-z0-9-]+\.)*compresspdf\.id(?::\d+)?$#i',
        '#^https?://localhost(?::\d+)?$#i',
        '#^https?://127\.0\.0\.1(?::\d+)?$#i',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,

];
