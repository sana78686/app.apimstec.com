<?php

namespace App\Http\Middleware;

use App\Support\ContentLocales;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CMS routes no longer use /{locale}/… . Redirect old bookmarks (GET/HEAD) to canonical paths.
 * Registered as global prepend so routing sees /dashboard, not /en/dashboard.
 */
class StripLegacyCmsLocaleUrlPrefix
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $path = trim($request->path(), '/');
        if ($path === '') {
            return $next($request);
        }

        $segments = explode('/', $path);
        $first = strtolower($segments[0] ?? '');
        if (! in_array($first, ContentLocales::SUPPORTED, true)) {
            return $next($request);
        }

        if (str_contains($first, '.')) {
            return $next($request);
        }

        $rest = implode('/', array_slice($segments, 1));

        $exemptFirst = [
            'sitemap.xml',
            'robots.txt',
        ];
        if ($rest !== '' && in_array(strtolower(explode('/', $rest, 2)[0]), $exemptFirst, true)) {
            return $next($request);
        }

        $targetPath = $rest !== '' ? $rest : 'dashboard';
        $qs = $request->getQueryString();
        $target = '/'.$targetPath.($qs ? '?'.$qs : '');

        return redirect($target, 302);
    }
}
