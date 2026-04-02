<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures CORS works for the React app's public JSON API under /api/public/*.
 *
 * Browsers send a preflight (OPTIONS) when custom headers like X-Domain are used.
 * If the default HandleCors path/origin check fails on the server (config cache,
 * reverse proxy path, etc.), this middleware still answers OPTIONS and adds ACAO on
 * the real response using the same rules as config/cors.php.
 */
class HandlePublicApiCors
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isPublicApiPath($request)) {
            return $next($request);
        }

        $origin = (string) $request->headers->get('Origin');
        if ($origin === '') {
            return $next($request);
        }

        if (! $this->isOriginAllowed($origin)) {
            return $next($request);
        }

        if ($request->isMethod('OPTIONS')) {
            return $this->preflightResponse($request, $origin);
        }

        $response = $next($request);

        if (! $response->headers->has('Access-Control-Allow-Origin')) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $this->appendVary($response, 'Origin');
        }

        return $response;
    }

    /**
     * Match Laravel route path (e.g. api/public/pages). str_starts_with avoids edge cases
     * where Request::is() patterns differ under some reverse-proxy setups.
     */
    private function isPublicApiPath(Request $request): bool
    {
        $path = $request->path();

        return $path === 'api/public' || str_starts_with($path, 'api/public/');
    }

    private function preflightResponse(Request $request, string $origin): Response
    {
        $reqHeaders = (string) $request->headers->get('Access-Control-Request-Headers');

        $response = response('', 204);
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set(
            'Access-Control-Allow-Headers',
            $reqHeaders !== '' ? $reqHeaders : 'Accept, Content-Type, X-Domain, X-Requested-With'
        );
        $maxAge = (int) (config('cors.max_age') ?? 0);
        if ($maxAge > 0) {
            $response->headers->set('Access-Control-Max-Age', (string) $maxAge);
        }
        $response->headers->set('Vary', 'Origin, Access-Control-Request-Method, Access-Control-Request-Headers');

        return $response;
    }

    private function isOriginAllowed(string $origin): bool
    {
        $origins = config('cors.allowed_origins', []);
        if (in_array('*', $origins, true)) {
            return true;
        }
        if (in_array($origin, $origins, true)) {
            return true;
        }
        foreach (config('cors.allowed_origins_patterns', []) as $pattern) {
            if ($pattern !== '' && @preg_match($pattern, $origin) === 1) {
                return true;
            }
        }

        return false;
    }

    private function appendVary(Response $response, string $header): void
    {
        $existing = $response->headers->get('Vary');
        if ($existing === null || $existing === '') {
            $response->headers->set('Vary', $header);
        } elseif (stripos($existing, $header) === false) {
            $response->headers->set('Vary', $existing.', '.$header);
        }
    }
}
