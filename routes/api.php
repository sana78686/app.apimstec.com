<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PublicApiController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Seo\BrokenLinksController;
use App\Http\Controllers\Seo\IndexingController;
use App\Http\Controllers\Seo\ContentOptimizationController;
use App\Http\Controllers\Seo\ImageSeoController;
use App\Http\Controllers\Seo\PerformanceController;
use App\Http\Controllers\Seo\SchemaMarkupController;
use App\Http\Controllers\Seo\UrlRedirectsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public JSON API (primary): routes/api-public-by-domain.php → /{site_domain}/api/public/...
//
// Legacy fallback: /api/public/* + X-Domain — use if a WAF/proxy blocks dotted first path segments
// (403 + “no CORS header” in the browser). Named api.legacy.public.* to avoid clashing with api.public.*.

Route::prefix('public')->name('api.legacy.public.')->group(function () {
    Route::get('pages', [PublicApiController::class, 'pages'])->name('pages');
    Route::get('pages/{slug}', [PublicApiController::class, 'pageBySlug'])->name('pages.show');
    Route::get('blogs', [PublicApiController::class, 'blogs'])->name('blogs');
    Route::get('blogs/{slug}', [PublicApiController::class, 'blogBySlug'])->name('blogs.show');
    Route::get('contact', [PublicApiController::class, 'contact'])->name('contact');
    Route::post('contact/send', [PublicApiController::class, 'sendContact'])->name('contact.send');
    Route::get('faq', [PublicApiController::class, 'faq'])->name('faq');
    Route::get('home-cards', [PublicApiController::class, 'homeCards'])->name('home-cards');
    Route::get('home-content', [PublicApiController::class, 'homeContent'])->name('home-content');
    Route::get('legal/{slug}', [PublicApiController::class, 'legalPage'])->name('legal')->where('slug', 'terms|privacy-policy|disclaimer|about-us|cookie-policy');
});

Route::middleware(['web', 'auth', 'verified', 'active.domain'])->group(function () {
    Route::middleware('permission:users.view')->prefix('users')->name('api.users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create')->middleware('permission:users.create');
        Route::post('/', [UserController::class, 'store'])->name('store')->middleware('permission:users.create');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('permission:users.edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update')->middleware('permission:users.edit');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('permission:users.delete');
    });

    Route::middleware('permission:roles.view')->prefix('roles')->name('api.roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/create', [RoleController::class, 'create'])->name('create')->middleware('permission:roles.create');
        Route::post('/', [RoleController::class, 'store'])->name('store')->middleware('permission:roles.create');
        Route::get('/{role}', [RoleController::class, 'show'])->name('show');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit')->middleware('permission:roles.edit');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update')->middleware('permission:roles.edit');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy')->middleware('permission:roles.delete');
    });

    Route::prefix('pages')->name('api.pages.')->group(function () {
        Route::get('/', [PageController::class, 'index'])->name('index');
        Route::get('/create', [PageController::class, 'create'])->name('create');
        Route::post('/', [PageController::class, 'store'])->name('store');
        Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
        Route::get('/{page}/seo', [PageController::class, 'seo'])->name('seo');
        Route::put('/{page}', [PageController::class, 'update'])->name('update');
        Route::put('/{page}/seo', [PageController::class, 'updateSeo'])->name('update-seo');
        Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');
        Route::patch('/{page}/status', [PageController::class, 'updateStatus'])->name('update-status');
        Route::post('/{page}/toggle-publish', [PageController::class, 'togglePublish'])->name('toggle-publish');
    });

    Route::prefix('blogs')->name('api.blogs.')->group(function () {
        Route::get('/', [BlogController::class, 'index'])->name('index');
        Route::get('/create', [BlogController::class, 'create'])->name('create');
        Route::post('/', [BlogController::class, 'store'])->name('store');
        Route::get('/{blog}/edit', [BlogController::class, 'edit'])->name('edit');
        Route::put('/{blog}', [BlogController::class, 'update'])->name('update');
        Route::delete('/{blog}', [BlogController::class, 'destroy'])->name('destroy');
        Route::patch('/{blog}/status', [BlogController::class, 'updateStatus'])->name('update-status');
        Route::post('/{blog}/toggle-publish', [BlogController::class, 'togglePublish'])->name('toggle-publish');
        Route::patch('/{blog}/visibility', [BlogController::class, 'updateVisibility'])->name('update-visibility');
    });

    Route::prefix('seo/url-redirects')->name('api.seo.url-redirects.')->group(function () {
        Route::put('update-slug', [UrlRedirectsController::class, 'updateSlug'])->name('update-slug');
        Route::get('generate-slug', [UrlRedirectsController::class, 'generateSlug'])->name('generate-slug');
    });

    Route::prefix('seo/indexing')->name('api.seo.indexing.')->group(function () {
        Route::put('update-visibility', [IndexingController::class, 'updateVisibility'])->name('update-visibility');
    });

    Route::prefix('seo/schema-markup')->name('api.seo.schema-markup.')->group(function () {
        Route::put('update-schema', [SchemaMarkupController::class, 'updateSchema'])->name('update-schema');
    });

    Route::prefix('seo/image-seo')->name('api.seo.image-seo.')->group(function () {
        Route::post('discover', [ImageSeoController::class, 'discover'])->name('discover');
        Route::put('update-alt', [ImageSeoController::class, 'updateAlt'])->name('update-alt');
        Route::post('compress', [ImageSeoController::class, 'compress'])->name('compress');
        Route::post('to-webp', [ImageSeoController::class, 'toWebP'])->name('to-webp');
    });

    Route::prefix('seo/performance')->name('api.seo.performance.')->group(function () {
        Route::post('clear-cache', [PerformanceController::class, 'clearCache'])->name('clear-cache');
    });

    Route::prefix('seo/broken-links')->name('api.seo.broken-links.')->group(function () {
        Route::post('create-redirect', [BrokenLinksController::class, 'createRedirect'])->name('create-redirect');
        Route::post('dismiss', [BrokenLinksController::class, 'dismiss'])->name('dismiss');
    });

    Route::prefix('seo/content-optimization')->name('api.seo.content-optimization.')->group(function () {
        Route::post('analyze', [ContentOptimizationController::class, 'analyze'])->name('analyze');
    });

    Route::prefix('media')->name('api.media.')->group(function () {
        Route::post('upload', [MediaController::class, 'upload'])->name('upload');
    });
});
