<?php

namespace App\Http\Middleware;

use App\Support\ContentLocales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects GET/HEAD CMS requests to /{locale}/... when the first path segment is not a supported locale.
 * POST/PATCH/DELETE must use prefixed URLs from Ziggy (session defaults are not applied the same way).
 */
class RedirectIfCmsMissingLocalePrefix
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->hasVerifiedEmail()) {
            return $next($request);
        }

        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $path = $request->path();
        if ($path === '' || $path === '/') {
            return $next($request);
        }

        $supported = ContentLocales::SUPPORTED;
        $first = strtolower(explode('/', $path, 2)[0] ?? '');
        if (in_array($first, $supported, true)) {
            return $next($request);
        }

        // Inertia pages call JSON routes at /api/* (axios). Those must NOT be prefixed with /{locale}/ —
        // otherwise GET /api/roles becomes /en/api/roles and returns 404.
        if (str_starts_with($path, 'api/')) {
            return $next($request);
        }

        // Google OAuth callback (no /{locale}/ prefix — must match GOOGLE_GSC_REDIRECT_URI)
        if (str_starts_with($path, 'oauth/')) {
            return $next($request);
        }

        $exemptExact = [
            'login',
            'logout',
            'register',
            'forgot-password',
            'verify-email',
            'confirm-password',
            'up',
            'sitemap.xml',
            'robots.txt',
        ];

        foreach ($exemptExact as $ex) {
            if ($path === $ex || str_starts_with($path, $ex.'/')) {
                return $next($request);
            }
        }

        // /compresspdf.id/sitemap.xml — first segment is a hostname, not a CMS locale
        if (preg_match('#^[a-zA-Z0-9][a-zA-Z0-9.\-]*\.[a-zA-Z]{2,}/(sitemap\.xml|robots\.txt)$#', $path)) {
            return $next($request);
        }

        if (str_starts_with($path, 'email/')
            || str_starts_with($path, 'password/')
            || str_starts_with($path, 'verification/')
            || str_starts_with($path, 'sanctum/')
            || str_starts_with($path, 'reset-password')
            || str_starts_with($path, 'build/')
            || str_starts_with($path, 'vendor/')
            || str_starts_with($path, '_')
        ) {
            return $next($request);
        }

        // Onboarding (no active site yet)
        if (! $request->session()->get('active_domain_id')) {
            if (str_starts_with($path, 'domains/select')
                || str_starts_with($path, 'domains/create')
                || $path === 'domains'
                || str_starts_with($path, 'domains/test-connection')
            ) {
                return $next($request);
            }
        }

        // Domain switch stays at /domains/switch (POST); GET list uses locale below
        if (str_starts_with($path, 'domains/switch')) {
            return $next($request);
        }

        $locale = ContentLocales::normalize($request->session()->get('cms_locale'));
        $qs = $request->getQueryString();
        $target = '/'.$locale.'/'.$path.($qs ? '?'.$qs : '');

        return redirect($target);
    }
}
