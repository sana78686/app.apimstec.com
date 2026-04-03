<?php

namespace App\Http\Middleware;

use App\Support\ContentLocales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs before HandleInertiaRequests so Ziggy::toArray() sees cms_locale via URL::getDefaultParameters().
 * Route-group SyncCmsLocaleFromUrl still syncs session; this only sets generator defaults early.
 */
class ApplyCmsLocaleToUrlGenerator
{
    public function handle(Request $request, Closure $next): Response
    {
        $param = $request->route('cms_locale');
        if (is_string($param) && $param !== '') {
            URL::defaults(['cms_locale' => ContentLocales::normalize($param)]);
        }

        return $next($request);
    }
}
