<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Caches successful GET responses for /api/public/* per X-Domain + path.
 */
class CachePublicApiGet
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('public_api.cache_enabled', true)) {
            return $next($request);
        }

        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        if (! $this->isPublicApiPath($request)) {
            return $next($request);
        }

        $ttl = (int) config('public_api.cache_ttl_seconds', 300);
        if ($ttl <= 0) {
            return $next($request);
        }

        $key = $this->cacheKey($request);

        $payload = Cache::get($key);
        if (is_array($payload) && isset($payload['status'], $payload['content'])) {
            return response($payload['content'], (int) $payload['status'], $payload['headers'] ?? [])
                ->header('X-Public-Api-Cache', 'HIT');
        }

        /** @var Response $response */
        $response = $next($request);

        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $content = $response->getContent();
        $headers = $this->cacheableHeaders($response);

        Cache::put($key, [
            'status' => 200,
            'content' => $content,
            'headers' => $headers,
        ], $ttl);

        return $response->header('X-Public-Api-Cache', 'MISS');
    }

    private function isPublicApiPath(Request $request): bool
    {
        $path = $request->path();

        return $path === 'api/public' || str_starts_with($path, 'api/public/');
    }

    private function cacheKey(Request $request): string
    {
        $domain = strtolower(trim((string) $request->header('X-Domain', 'default')));
        $domain = preg_replace('#:\d+$#', '', $domain) ?: 'default';

        $pathPart = $request->path().'?'.$request->getQueryString();

        return 'public_api:v1:'.hash('sha256', $domain.'|'.$pathPart);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function cacheableHeaders(Response $response): array
    {
        $keep = [];
        $contentType = $response->headers->get('Content-Type');
        if ($contentType !== null && $contentType !== '') {
            $keep['Content-Type'] = [$contentType];
        }

        return $keep;
    }
}
