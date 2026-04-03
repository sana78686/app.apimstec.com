<?php

namespace App\Providers;

use App\Models\Blog;
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
         * Tenant-aware resolution for {blog} / {page} after TenantMiddleware + SubstituteBindings.
         * Prevents TypeError when implicit binding would pass a raw id string into typed controllers.
         */
        Route::bind('blog', function (string $value) {
            return Blog::query()->findOrFail((int) $value);
        });
        Route::bind('page', function (string $value) {
            return Page::query()->findOrFail((int) $value);
        });
    }
}
