<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class RobotsTxt extends Model
{
    protected $table = 'robots_txt';

    protected $fillable = ['content'];

    /** Singleton: the one stored robots.txt record (id = 1). */
    public static function getRecord(): ?self
    {
        return self::first();
    }

    /** Get content for robots.txt. If empty, returns default with sitemap link. */
    public static function getContent(): string
    {
        $record = self::getRecord();
        $content = $record?->content;
        if ($content !== null && trim($content) !== '') {
            return self::ensureSitemapInContent(trim($content));
        }
        return self::defaultContent();
    }

    /** Default robots.txt: allow all, include sitemap link. */
    public static function defaultContent(): string
    {
        $baseUrl = rtrim(config('app.url'), '/');
        return "User-agent: *\nAllow: /\n\nSitemap: {$baseUrl}/sitemap.xml\n";
    }

    /** If content doesn't contain a Sitemap line, append the sitemap URL. */
    public static function ensureSitemapInContent(string $content): string
    {
        if (stripos($content, 'Sitemap:') !== false) {
            return $content;
        }
        $baseUrl = rtrim(config('app.url'), '/');
        $sitemapLine = "\nSitemap: {$baseUrl}/sitemap.xml\n";
        return rtrim($content).$sitemapLine;
    }

    /** Update the stored content (upsert the single row) and sync to public/robots.txt. */
    public static function setContent(string $content): void
    {
        $record = self::getRecord();
        if ($record) {
            $record->update(['content' => $content]);
        } else {
            self::create(['content' => $content]);
        }
        self::syncToFile();
    }

    /** Write current robots.txt content to public/robots.txt so the physical file is in sync. */
    public static function syncToFile(): void
    {
        $path = public_path('robots.txt');
        File::put($path, self::getContent());
    }
}
