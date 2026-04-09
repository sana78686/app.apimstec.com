<?php

namespace App\Support;

use App\Models\Blog;
use App\Models\Domain;
use App\Models\Page;
use Illuminate\Support\Collection;

/**
 * Builds absolute URLs for sitemap.xml (matches React routes: /{lang}/page/{slug}, /{lang}/blog/...).
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
            $locales = collect([ContentLocales::DEFAULT]);
        }

        $now = now()->format('Y-m-d');
        foreach ($locales as $locale) {
            $enc = rawurlencode($locale);
            $urls[] = [
                'loc' => $base.'/'.$enc.'/',
                'lastmod' => $now,
                'changefreq' => 'weekly',
                'priority' => '1.0',
            ];
            $urls[] = [
                'loc' => $base.'/'.$enc.'/compress',
                'lastmod' => $now,
                'changefreq' => 'monthly',
                'priority' => '0.9',
            ];
            $urls[] = [
                'loc' => $base.'/'.$enc.'/blog',
                'lastmod' => $now,
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
            $urls[] = [
                'loc' => $base.'/'.$enc.'/contact',
                'lastmod' => $now,
                'changefreq' => 'yearly',
                'priority' => '0.4',
            ];
            foreach (['terms', 'privacy-policy', 'disclaimer', 'cookie-policy', 'about-us'] as $legalSlug) {
                $urls[] = [
                    'loc' => $base.'/'.$enc.'/legal/'.$legalSlug,
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
            $urls[] = [
                'loc' => $base.'/'.rawurlencode($locale).'/page/'.rawurlencode($slug),
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
            $urls[] = [
                'loc' => $base.'/'.rawurlencode($locale).'/blog/'.rawurlencode($slug),
                'lastmod' => $blog->updated_at->format('Y-m-d'),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        return $urls;
    }
}
