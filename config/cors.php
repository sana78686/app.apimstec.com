<?php

/**
 * Runtime: allowed_origins is merged in App\Providers\CorsServiceProvider from:
 *   - local_dev_origins (Vite / local tools)
 *   - active Domains table (domain + frontend_url) on mysql connection, cached
 *   - env_origins (CORS_ALLOWED_ORIGINS)
 *
 * Set CACHE_STORE=redis and CORS_ORIGINS_CACHE_TTL for many sites.
 */

return [

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        '*/api/public',
        '*/api/public/*',
    ],

    'allowed_methods' => ['*'],

    /** Filled at boot — see CorsServiceProvider */
    'allowed_origins' => [],

    'local_dev_origins' => [
        'http://localhost:5173',
        'http://localhost:5000',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5000',
        'http://127.0.0.1:3000',
    ],

    'env_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
    ))),

    /** How long the domain-derived origin list is cached (Redis recommended). */
    'origins_cache_ttl_seconds' => (int) env('CORS_ORIGINS_CACHE_TTL', 3600),

    'allowed_origins_patterns' => [
        '#^https?://localhost(?::\d+)?$#i',
        '#^https?://127\.0\.0\.1(?::\d+)?$#i',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,

];
