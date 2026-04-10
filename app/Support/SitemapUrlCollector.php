<?php

namespace App\Support;

use App\Models\Blog;
use App\Models\Domain;
use App\Models\Page;
use Illuminate\Support\Collection;

/**
 * Builds absolute URLs for sitemap.xml.
 * Default locale (id) uses no URL prefix; non-default locales use /{lang}/...
 */
final class SitemapUrlCollector
{
    /**
     * @return list<array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    public static function forDomain(Domain $domain): array
    {
        $base = $domain->publicSiteBaseUrl();
        if ($base === '') {
            return [];
        }

        $urls = [];
        $defaultLocale = ContentLocales::DEFAULT_PUBLIC;

        $pageLocales = Page::query()
            ->where('is_published', true)
            ->where('visibility', Page::VISIBILITY_VISIBLE)
            ->pluck('locale');

        $blogLocales = Blog::query()
            ->where('is_published', true)
            ->where('visibility', Blog::VISIBILITY_VISIBLE)
            ->pluck('locale');

        /** @var Collection<int, string> $locales */
        $locales = $pageLocales->concat($blogLocales)
            ->map(fn ($l) => ContentLocales::normalize((string) $l))
            ->unique()
            ->values();

        if ($locales->isEmpty()) {
            $locales = collect([$defaultLocale]);
        }

        $now = now()->format('Y-m-d');
        foreach ($locales as $locale) {
            $prefix = ($locale === $defaultLocale) ? '' : '/'.rawurlencode($locale);
            $urls[] = [
                'loc' => $base.$prefix.'/',
                'lastmod' => $now,
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ];
            $urls[] = [
                'loc' => $base.$prefix.'/compress',
                'lastmod' => $now,
                'changefreq' => 'monthly',
                'priority' => '0.9',
            ];
            $urls[] = [
                'loc' => $base.$prefix.'/blog',
                'lastmod' => $now,
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
            $urls[] = [
                'loc' => $base.$prefix.'/contact',
                'lastmod' => $now,
                'changefreq' => 'yearly',
                'priority' => '0.4',
            ];
            foreach (['terms', 'privacy-policy', 'disclaimer', 'cookie-policy', 'about-us'] as $legalSlug) {
                $urls[] = [
                    'loc' => $base.$prefix.'/legal/'.$legalSlug,
                    'lastmod' => $now,
                    'changefreq' => 'yearly',
                    'priority' => '0.3',
                ];
            }
        }

        foreach (Page::query()
            ->where('is_published', true)
            ->where('visibility', Page::VISIBILITY_VISIBLE)
            ->orderBy('updated_at', 'desc')
            ->get(['slug', 'locale', 'updated_at']) as $page) {
            $slug = trim((string) $page->slug);
            if ($slug === '') {
                continue;
            }
            $locale = ContentLocales::normalize((string) $page->locale);
            $prefix = ($locale === $defaultLocale) ? '' : '/'.rawurlencode($locale);
            $urls[] = [
                'loc' => $base.$prefix.'/page/'.rawurlencode($slug),
                'lastmod' => $page->updated_at->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        foreach (Blog::query()
            ->where('is_published', true)
            ->where('visibility', Blog::VISIBILITY_VISIBLE)
            ->orderBy('updated_at', 'desc')
            ->get(['slug', 'locale', 'updated_at']) as $blog) {
            $slug = trim((string) $blog->slug);
            if ($slug === '') {
                continue;
            }
            $locale = ContentLocales::normalize((string) $blog->locale);
            $prefix = ($locale === $defaultLocale) ? '' : '/'.rawurlencode($locale);
            $urls[] = [
                'loc' => $base.$prefix.'/blog/'.rawurlencode($slug),
                'lastmod' => $blog->updated_at->format('Y-m-d'),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        return $urls;
    }
}
