<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContentManagerController;
use App\Http\Controllers\FaqSectionController;
use App\Http\Controllers\CardsSectionController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Seo\HomePageSeoController;
use App\Http\Controllers\Seo\IndexingController;
use App\Http\Controllers\Seo\MetaManagerController;
use App\Http\Controllers\Seo\SitemapManagerController;
use App\Http\Controllers\Seo\RobotsManagerController;
use App\Http\Controllers\Seo\SchemaMarkupController;
use App\Http\Controllers\Seo\SocialSharingController;
use App\Http\Controllers\Seo\ImageSeoController;
use App\Http\Controllers\Seo\AnalyticsController;
use App\Http\Controllers\Seo\BrokenLinksController;
use App\Http\Controllers\Seo\ContentOptimizationController;
use App\Http\Controllers\Seo\PerformanceController;
use App\Http\Controllers\Seo\UrlRedirectsController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\RobotsTxtController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    if (! session('active_domain_id')) {
        return redirect()->route('domains.select');
    }

    return redirect()->route('dashboard');
});

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsTxtController::class)->name('robots');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', 'active.domain'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Domain onboarding (no active tenant yet)
|--------------------------------------------------------------------------
| Until a website database is selected, the CMS cannot save SEO/content anywhere.
| Only these routes are reachable; everything else requires active.domain.
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/domains/select', [DomainController::class, 'select'])->name('domains.select');
    Route::get('/domains/create', [DomainController::class, 'create'])->name('domains.create');
    Route::post('/domains', [DomainController::class, 'store'])->name('domains.store');
    Route::post('/domains/switch', [DomainController::class, 'switchDomain'])->name('domains.switch');
    Route::post('/domains/test-connection', [DomainController::class, 'testConnection'])->name('domains.test-connection');
});

/*
|--------------------------------------------------------------------------
| Full CMS (requires selected domain → tenant DB for content)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'active.domain'])->group(function () {
    Route::get('/domains', [DomainController::class, 'index'])->name('domains.index');
    Route::get('/domains/{domain}/edit', [DomainController::class, 'edit'])->name('domains.edit');
    Route::put('/domains/{domain}', [DomainController::class, 'update'])->name('domains.update');
    Route::delete('/domains/{domain}', [DomainController::class, 'destroy'])->name('domains.destroy');
    Route::post('/domains/{domain}/sync-schema', [DomainController::class, 'syncSchema'])->name('domains.sync-schema');
    Route::post('/domains/{domain}/migrate-fresh', [DomainController::class, 'migrateFresh'])->name('domains.migrate-fresh');
    Route::post('/domains/{domain}/rollback-schema', [DomainController::class, 'rollbackSchema'])->name('domains.rollback-schema');
    Route::post('/domains/{domain}/test-connection', [DomainController::class, 'testSavedConnection'])->name('domains.test-saved-connection');
});

Route::middleware(['auth', 'verified', 'active.domain'])->prefix('credentials')->name('credentials.')->group(function () {
    Route::get('/', [CredentialController::class, 'index'])->name('index');
    Route::get('/create', [CredentialController::class, 'create'])->name('create');
    Route::post('/', [CredentialController::class, 'store'])->name('store');
    Route::get('/{credential}/edit', [CredentialController::class, 'edit'])->name('edit');
    Route::put('/{credential}', [CredentialController::class, 'update'])->name('update');
    Route::delete('/{credential}', [CredentialController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'verified', 'active.domain'])->prefix('content-manager')->name('content-manager.')->group(function () {
    Route::get('/', [ContentManagerController::class, 'index'])->name('index');
    Route::put('/', [ContentManagerController::class, 'update'])->name('update');
    Route::put('/home-seo', [ContentManagerController::class, 'homeSeoUpdate'])->name('home-seo.update');
    Route::get('/home', [ContentManagerController::class, 'home'])->name('home')->defaults('tab', 'faq');
    Route::get('/home/{tab}', [ContentManagerController::class, 'home'])->where('tab', 'faq|use-cards');
    Route::get('/faq', fn () => redirect()->route('content-manager.home', ['tab' => 'faq']))->name('faq');
    Route::get('/cards', fn () => redirect()->route('content-manager.home', ['tab' => 'use-cards']))->name('cards');
    Route::get('/contact', [ContentManagerController::class, 'contact'])->name('contact');
    Route::put('/contact', [ContentManagerController::class, 'contactUpdate'])->name('contact.update');
    Route::get('/terms', [ContentManagerController::class, 'terms'])->name('terms');
    Route::put('/terms', [ContentManagerController::class, 'termsUpdate'])->name('terms.update');
    Route::get('/privacy-policy', [ContentManagerController::class, 'privacyPolicy'])->name('privacy-policy');
    Route::put('/privacy-policy', [ContentManagerController::class, 'privacyPolicyUpdate'])->name('privacy-policy.update');
    Route::get('/disclaimer', [ContentManagerController::class, 'disclaimer'])->name('disclaimer');
    Route::put('/disclaimer', [ContentManagerController::class, 'disclaimerUpdate'])->name('disclaimer.update');
    Route::get('/about-us', [ContentManagerController::class, 'aboutUs'])->name('about-us');
    Route::put('/about-us', [ContentManagerController::class, 'aboutUsUpdate'])->name('about-us.update');
    Route::get('/cookie-policy', [ContentManagerController::class, 'cookiePolicy'])->name('cookie-policy');
    Route::put('/cookie-policy', [ContentManagerController::class, 'cookiePolicyUpdate'])->name('cookie-policy.update');
    Route::post('/faq', [FaqSectionController::class, 'store'])->name('faq.store');
    Route::put('/faq/{faqItem}', [FaqSectionController::class, 'update'])->name('faq.update');
    Route::delete('/faq/{faqItem}', [FaqSectionController::class, 'destroy'])->name('faq.destroy');
    Route::post('/cards', [CardsSectionController::class, 'store'])->name('cards.store');
    Route::put('/cards/{card}', [CardsSectionController::class, 'update'])->name('cards.update');
    Route::delete('/cards/{card}', [CardsSectionController::class, 'destroy'])->name('cards.destroy');
});

Route::get('/media', function () {
    return Inertia::render('Media/Index');
})->middleware(['auth', 'verified', 'active.domain'])->name('media.index');

Route::middleware(['auth', 'verified', 'active.domain'])->prefix('seo')->name('seo.')->group(function () {
    Route::get('/home-page', [HomePageSeoController::class, 'index'])->name('home-page');
    Route::get('/meta-manager', [MetaManagerController::class, 'index'])->name('meta-manager');
    Route::get('/meta-manager/create', [MetaManagerController::class, 'create'])->name('meta-manager.create');
    Route::get('/url-redirects', [UrlRedirectsController::class, 'index'])->name('url-redirects');
    Route::get('/indexing', [IndexingController::class, 'index'])->name('indexing');
    Route::get('/sitemap', [SitemapManagerController::class, 'index'])->name('sitemap');
    Route::get('/robots', [RobotsManagerController::class, 'index'])->name('robots');
    Route::put('/robots', [RobotsManagerController::class, 'update'])->name('robots.update');
    Route::get('/schema-markup', [SchemaMarkupController::class, 'index'])->name('schema-markup');
    Route::get('/social-sharing', [SocialSharingController::class, 'index'])->name('social-sharing');
    Route::get('/social-sharing/edit', [SocialSharingController::class, 'edit'])->name('social-sharing.edit');
    Route::put('/social-sharing', [SocialSharingController::class, 'update'])->name('social-sharing.update');
    Route::get('/image-seo', [ImageSeoController::class, 'index'])->name('image-seo');
    Route::get('/performance', [PerformanceController::class, 'index'])->name('performance');
    Route::put('/performance', [PerformanceController::class, 'update'])->name('performance.update');
    Route::get('/broken-links', [BrokenLinksController::class, 'index'])->name('broken-links');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::put('/analytics', [AnalyticsController::class, 'update'])->name('analytics.update');
    Route::get('/content-optimization', [ContentOptimizationController::class, 'index'])->name('content-optimization');

    $seoModules = [
        'sitemap' => ['name' => 'Sitemap Manager', 'purpose' => 'Auto-generate XML sitemap and update when content changes.'],
        'robots' => ['name' => 'Robots.txt Manager', 'purpose' => 'Edit robots.txt rules and submit sitemap link.'],
        'social-sharing' => ['name' => 'Social Sharing (Open Graph)', 'purpose' => 'Manage OG title, image & Twitter cards for better preview.'],
        'schema-markup' => ['name' => 'Schema Markup Manager', 'purpose' => 'Structured data: Article, FAQ, Product, Breadcrumb.'],
        'image-seo' => ['name' => 'Image SEO Manager', 'purpose' => 'ALT text, compress images, convert to WebP.'],
        'performance' => ['name' => 'Performance & Speed', 'purpose' => 'Caching, lazy loading, minification & CDN.'],
        'indexing' => ['name' => 'Indexing Controls', 'purpose' => 'noindex, nofollow, exclude from sitemap.'],
        'analytics' => ['name' => 'SEO Analytics & Reports', 'purpose' => 'Clicks, impressions & ranking via Search Console & Analytics.'],
        'content-optimization' => ['name' => 'Content Optimization Tools', 'purpose' => 'Keyword suggestions, readability & heading checks.'],
        'broken-links' => ['name' => 'Broken Link & Error Monitor', 'purpose' => 'Detect 404s and suggest redirects.'],
    ];
    foreach ($seoModules as $slug => $info) {
        if (in_array($slug, ['url-redirects', 'indexing', 'sitemap', 'robots', 'schema-markup', 'social-sharing', 'image-seo', 'performance', 'broken-links', 'analytics', 'content-optimization'], true)) {
            continue;
        }
        Route::get('/'.$slug, fn () => Inertia::render('Seo/Placeholder', [
            'moduleName' => $info['name'],
            'purpose' => $info['purpose'],
        ]))->name($slug);
    }
});

Route::middleware(['auth', 'verified', 'active.domain'])->group(function () {
    Route::post('/maintenance/optimize-clear', [MaintenanceController::class, 'optimizeClear'])->name('maintenance.optimize-clear');
    Route::post('/maintenance/config-clear', [MaintenanceController::class, 'configClear'])->name('maintenance.config-clear');
    Route::post('/maintenance/cache-clear', [MaintenanceController::class, 'cacheClear'])->name('maintenance.cache-clear');
    Route::post('/maintenance/migrate', [MaintenanceController::class, 'migrate'])->name('maintenance.migrate');
    Route::post('/maintenance/rollback', [MaintenanceController::class, 'rollback'])->name('maintenance.rollback');
    Route::post('/maintenance/migrate-fresh', [MaintenanceController::class, 'migrateFresh'])->name('maintenance.migrate-fresh');
    Route::post('/maintenance/seed', [MaintenanceController::class, 'seed'])->name('maintenance.seed');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('permission:users.view')->prefix('users')->name('users.')->group(function () {
        Route::get('/', fn () => Inertia::render('Users/Index'))->name('index');
        Route::get('/create', fn () => Inertia::render('Users/Create'))->name('create')->middleware('permission:users.create');
        Route::get('/{user}/edit', fn ($user) => Inertia::render('Users/Edit', ['userId' => is_object($user) ? $user->id : (int) $user]))->name('edit')->middleware('permission:users.edit');
    });

    Route::middleware('permission:roles.view')->prefix('roles')->name('roles.')->group(function () {
        Route::get('/', fn () => Inertia::render('Roles/Index'))->name('index');
        Route::get('/create', fn () => Inertia::render('Roles/Create'))->name('create')->middleware('permission:roles.create');
        Route::get('/{role}/edit', fn ($role) => Inertia::render('Roles/Edit', ['roleId' => $role]))->name('edit')->middleware('permission:roles.edit');
    });

    Route::prefix('pages')->name('pages.')->group(function () {
        Route::get('/', [PageController::class, 'index'])->name('index');
        Route::get('/create', [PageController::class, 'create'])->name('create');
        Route::get('/{page}/seo', fn ($page) => redirect()->route('seo.meta-manager.create', ['page_id' => is_object($page) ? $page->id : $page]))->name('seo');
        Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
    });

    Route::prefix('blogs')->name('blogs.')->group(function () {
        Route::get('/', [BlogController::class, 'index'])->name('index');
        Route::get('/create', [BlogController::class, 'create'])->name('create');
        Route::get('/{blog}/edit', [BlogController::class, 'edit'])->name('edit');
    });
});

require __DIR__.'/auth.php';
