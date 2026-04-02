<?php

namespace App\Support;

use App\Models\Domain;
use Illuminate\Support\Facades\Cache;

/**
 * Builds CORS allowed origins from Domains (registry DB) and caches the list.
 * Use CACHE_STORE=redis in production for fast shared invalidation across workers.
 */
final class CorsAllowedOriginsBuilder
{
    public const CACHE_KEY = 'cors:allowed_origins:v1';

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return list<string>
     */
    public static function originsFromDatabase(): array
    {
        $out = [];

        $rows = Domain::query()
            ->where('is_active', true)
            ->get(['domain', 'frontend_url']);

        foreach ($rows as $row) {
            $out = array_merge($out, self::originsForRow($row));
        }

        return array_values(array_unique(array_filter($out)));
    }

    /**
     * @return list<string>
     */
    public static function cachedOrigins(): array
    {
        $ttl = (int) config('cors.origins_cache_ttl_seconds', 3600);
        if ($ttl <= 0) {
            return self::originsFromDatabase();
        }

        return Cache::remember(self::CACHE_KEY, $ttl, fn () => self::originsFromDatabase());
    }

    /**
     * @param  \App\Models\Domain|object{domain?: string, frontend_url?: string|null}  $row
     * @return list<string>
     */
    private static function originsForRow(object $row): array
    {
        $origins = [];

        $frontend = trim((string) ($row->frontend_url ?? ''));
        if ($frontend !== '') {
            $parsed = parse_url($frontend);
            if (! empty($parsed['scheme']) && ! empty($parsed['host'])) {
                $host = strtolower((string) $parsed['host']);
                $scheme = strtolower((string) $parsed['scheme']);
                $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
                $origins[] = $scheme.'://'.$host.$port;
            }
        }

        $domain = trim((string) ($row->domain ?? ''));
        if ($domain !== '') {
            $domain = strtolower($domain);
            $domain = preg_replace('#^https?://#i', '', $domain) ?? $domain;
            $domain = preg_replace('#/.*$#', '', $domain) ?? $domain;
            $domain = preg_replace('#:\d+$#', '', $domain) ?? $domain;
        }

        if ($domain !== '') {
            foreach (['https', 'http'] as $scheme) {
                $origins[] = "{$scheme}://{$domain}";
                if (str_starts_with($domain, 'www.')) {
                    $bare = substr($domain, 4);
                    if ($bare !== '') {
                        $origins[] = "{$scheme}://{$bare}";
                    }
                } else {
                    $origins[] = "{$scheme}://www.{$domain}";
                }
            }
        }

        return array_values(array_unique(array_filter($origins)));
    }
}
