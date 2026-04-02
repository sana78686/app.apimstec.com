<?php

namespace App\Providers;

use App\Models\Domain;
use App\Observers\DomainObserver;
use App\Support\CorsAllowedOriginsBuilder;
use Illuminate\Support\ServiceProvider;

class CorsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Domain::observe(DomainObserver::class);

        $local = config('cors.local_dev_origins', []);
        $fromEnv = config('cors.env_origins', []);

        try {
            $fromDb = CorsAllowedOriginsBuilder::cachedOrigins();
        } catch (\Throwable $e) {
            report($e);
            $fromDb = [];
        }

        $merged = array_values(array_unique(array_filter(array_merge($local, $fromDb, $fromEnv))));

        config(['cors.allowed_origins' => $merged]);
    }
}
