<?php

namespace App\Http\Middleware;

use App\Support\ContentLocales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets session cms_locale and URL generator defaults from the {cms_locale} route segment.
 */
class SyncCmsLocaleFromUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $param = $request->route('cms_locale');
        $locale = ContentLocales::normalize(is_string($param) ? $param : null);
        $request->session()->put('cms_locale', $locale);
        URL::defaults(['cms_locale' => $locale]);

        return $next($request);
    }
}
