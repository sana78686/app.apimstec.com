<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public JSON API response cache (GET /api/public/*)
    |--------------------------------------------------------------------------
    |
    | Reduces tenant DB load for React frontends. Keys include X-Domain + path.
    | Uses your default CACHE_STORE. For 20+ sites use CACHE_STORE=redis (see .env.example).
    | Run `php artisan cache:clear` after bulk CMS changes, or lower TTL.
    |
    */

    'cache_enabled' => filter_var(env('PUBLIC_API_CACHE_ENABLED', true), FILTER_VALIDATE_BOOL),

    'cache_ttl_seconds' => (int) env('PUBLIC_API_CACHE_TTL', 300),

];
