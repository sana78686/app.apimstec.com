<?php

namespace App\Providers;

use App\Models\Page;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Schema::defaultStringLength(191);

        /*
         * {page} explicit binding after tenant is active (avoids wrong-DB / 404 during SubstituteBindings).
         * {blog} is left as a raw route value; BlogController uses mixed + resolveBlog() after the full stack.
         */
        Route::bind('page', function (string $value) {
            return Page::query()->findOrFail((int) $value);
        });
    }
}
