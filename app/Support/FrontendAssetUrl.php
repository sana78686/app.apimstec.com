<?php

namespace App\Support;

use App\Models\Domain;

/**
 * Build public marketing-site URLs for uploads and SEO (no CMS app hostname in links).
 */
final class FrontendAssetUrl
{
    /** URL-encode each path segment; keep slashes. */
    public static function encodePath(string $relative): string
    {
        $relative = str_replace('\\', '/', trim($relative, '/'));

        return implode('/', array_map('rawurlencode', explode('/', $relative)));
    }

    public static function publicBase(?Domain $domain): string
    {
        if (! $domain instanceof Domain) {
            return '';
        }

        return rtrim($domain->publicSiteBaseUrl(), '/');
    }

    /**
     * Full URL on the live frontend for a file stored on the public disk under uploads/...
     * (physical file stays on CMS server; nginx on the frontend should proxy /uploads → /storage).
     */
    public static function uploadsPublicUrl(?Domain $domain, string $storageRelativePath): string
    {
        $base = self::publicBase($domain);
        if ($base === '') {
            $base = rtrim((string) config('app.url'), '/');
        }
        $rel = str_replace('\\', '/', ltrim($storageRelativePath, '/'));

        return $base.'/'.self::encodePath($rel);
    }

    /**
     * Rewrite stored og_image / absolute CMS /storage/... URLs to the site's public origin.
     */
    public static function rewriteAssetUrl(?string $value, ?Domain $domain): ?string
    {
        if ($value === null) {
            return null;
        }
        $v = trim((string) $value);
        if ($v === '') {
            return $value;
        }

        $base = self::publicBase($domain);
        if ($base === '') {
            return $value;
        }

        if (str_starts_with($v, $base.'/') || str_starts_with($v, $base.'?')) {
            return $v;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && str_starts_with($v, $appUrl.'/storage/')) {
            $tail = substr($v, strlen($appUrl.'/storage/'));

            return $base.'/'.self::encodePath($tail);
        }

        if (preg_match('#^https?://[^/]+/storage/(uploads/.+)$#i', $v, $m)) {
            return $base.'/'.self::encodePath($m[1]);
        }

        if (preg_match('#^/storage/(uploads/.+)$#i', $v, $m)) {
            return $base.'/'.self::encodePath($m[1]);
        }

        if (preg_match('#^uploads/.+#', $v)) {
            return $base.'/'.self::encodePath($v);
        }

        if (str_starts_with($v, '/uploads/')) {
            return $base.self::encodePath(substr($v, 1));
        }

        return $value;
    }

    /**
     * Rewrite canonical or other page URLs that still point at the CMS host.
     */
    public static function rewritePublicPageUrl(?string $value, ?Domain $domain): ?string
    {
        if ($value === null) {
            return null;
        }
        $v = trim((string) $value);
        if ($v === '') {
            return $value;
        }

        $base = self::publicBase($domain);
        if ($base === '') {
            return $value;
        }

        if (str_starts_with($v, $base.'/') || $v === $base) {
            return $v;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (! is_string($appHost) || $appHost === '') {
            return $value;
        }

        $parts = @parse_url($v);
        if (! is_array($parts) || empty($parts['host'])) {
            return $value;
        }

        $host = strtolower((string) $parts['host']);
        if ($host !== strtolower($appHost)) {
            return $value;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $base.$path.$query;
    }
}
