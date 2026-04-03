<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RobotsTxt extends Model
{
    protected $connection = 'tenant';

    protected $table = 'robots_txt';

    protected $fillable = ['content'];

    /** Singleton: the one stored robots.txt record (id = 1). */
    public static function getRecord(): ?self
    {
        return self::first();
    }

    /**
     * Get content for robots.txt. If empty, returns default with sitemap link.
     *
     * @param  string|null  $publicSiteBase  Public site origin (e.g. https://compresspdf.id). Falls back to APP_URL.
     */
    public static function getContent(?string $publicSiteBase = null): string
    {
        $base = self::normalizePublicBase($publicSiteBase);
        $record = self::getRecord();
        $content = $record?->content;
        if ($content !== null && trim($content) !== '') {
            return self::ensureSitemapInContent(trim($content), $base);
        }

        return self::defaultContent($base);
    }

    /** Default robots.txt: allow all, include sitemap link on the public site. */
    public static function defaultContent(?string $publicSiteBase = null): string
    {
        $baseUrl = self::normalizePublicBase($publicSiteBase);

        return "User-agent: *\nAllow: /\n\nSitemap: {$baseUrl}/sitemap.xml\n";
    }

    /** If content doesn't contain a Sitemap line, append the sitemap URL. */
    public static function ensureSitemapInContent(string $content, ?string $publicSiteBase = null): string
    {
        if (stripos($content, 'Sitemap:') !== false) {
            return $content;
        }
        $baseUrl = self::normalizePublicBase($publicSiteBase);
        $sitemapLine = "\nSitemap: {$baseUrl}/sitemap.xml\n";

        return rtrim($content).$sitemapLine;
    }

    private static function normalizePublicBase(?string $publicSiteBase): string
    {
        $t = $publicSiteBase !== null ? trim($publicSiteBase) : '';
        if ($t !== '') {
            return rtrim($t, '/');
        }

        return rtrim((string) config('app.url'), '/');
    }

    /** Update the stored content (upsert the single row). */
    public static function setContent(string $content): void
    {
        $record = self::getRecord();
        if ($record) {
            $record->update(['content' => $content]);
        } else {
            self::create(['content' => $content]);
        }
        // Multi-tenant: do not write public/robots.txt — many hosts serve that file as static HTML
        // and ignore Laravel; crawlers must hit the dynamic route only.
    }

    /** @deprecated No-op: kept so old callers do not break. */
    public static function syncToFile(): void
    {
        //
    }
}
