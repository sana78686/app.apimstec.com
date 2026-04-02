<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CORS for the unauthenticated JSON API under /api/public/* (React sites, many origins).
 *
 * Uses Access-Control-Allow-Origin: * because these routes do not use cookies or
 * credentialed fetch — so any frontend domain works without maintaining an allowlist.
 * Browsers still preflight when custom headers (e.g. X-Domain) are sent; we answer
 * OPTIONS here before the rest of the stack so proxies/config cache cannot break it.
 *
 * Do not add credentials: 'include' on the frontend for these calls, or * will fail.
 */
class HandlePublicApiCors
{
    private const PUBLIC_ALLOW_ORIGIN = '*';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isPublicApiPath($request)) {
            return $next($request);
        }

        if ($request->isMethod('OPTIONS')) {
            return $this->preflightResponse($request);
        }

        $response = $next($request);

        if (! $response->headers->has('Access-Control-Allow-Origin')) {
            $response->headers->set('Access-Control-Allow-Origin', self::PUBLIC_ALLOW_ORIGIN);
        }

        return $response;
    }

    /**
     * Match Laravel path (e.g. api/public/pages), including behind reverse proxies
     * where the path is still normalized to the app prefix.
     */
    private function isPublicApiPath(Request $request): bool
    {
        $path = $request->path();

        return $path === 'api/public' || str_starts_with($path, 'api/public/');
    }

    private function preflightResponse(Request $request): Response
    {
        $reqHeaders = (string) $request->headers->get('Access-Control-Request-Headers');

        $response = response('', 204);
        $response->headers->set('Access-Control-Allow-Origin', self::PUBLIC_ALLOW_ORIGIN);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set(
            'Access-Control-Allow-Headers',
            $reqHeaders !== '' ? $reqHeaders : 'Accept, Content-Type, X-Domain, X-Requested-With, Authorization'
        );
        $maxAge = (int) (config('cors.max_age') ?? 0);
        if ($maxAge > 0) {
            $response->headers->set('Access-Control-Max-Age', (string) $maxAge);
        }

        return $response;
    }
}
