<?php

/**
 * Public JSON API for React sites: /{site_domain}/api/public/...
 * site_domain matches CMS Domains.domain (e.g. compresspdf.id). Tenant is resolved from this segment.
 *
 * If the host returns 404 for these URLs, nginx may only pass /api/* to PHP — use the legacy
 * routes in routes/api.php (GET /api/public/... + X-Domain) or set VITE_API_DOMAIN_PATH=false
 * on the React build; the SPA also retries legacy automatically after 404/403 on this path.
 */

use App\Http\Controllers\PublicApiController;
use Illuminate\Support\Facades\Route;

Route::get('content-locales', [PublicApiController::class, 'contentLocales'])->name('api.public.content-locales');
Route::get('pages', [PublicApiController::class, 'pages'])->name('api.public.pages');
Route::get('pages/{slug}', [PublicApiController::class, 'pageBySlug'])->name('api.public.pages.show');
Route::get('blogs', [PublicApiController::class, 'blogs'])->name('api.public.blogs');
Route::get('blogs/{slug}', [PublicApiController::class, 'blogBySlug'])->name('api.public.blogs.show');
Route::get('contact', [PublicApiController::class, 'contact'])->name('api.public.contact');
Route::post('contact/send', [PublicApiController::class, 'sendContact'])->name('api.public.contact.send');
Route::get('faq', [PublicApiController::class, 'faq'])->name('api.public.faq');
Route::get('home-cards', [PublicApiController::class, 'homeCards'])->name('api.public.home-cards');
Route::get('home-content', [PublicApiController::class, 'homeContent'])->name('api.public.home-content');
Route::get('legal-nav', [PublicApiController::class, 'legalNav'])->name('api.public.legal-nav');
Route::get('legal/{slug}', [PublicApiController::class, 'legalPage'])->name('api.public.legal')->where('slug', 'terms|privacy-policy|disclaimer|about-us|cookie-policy');
