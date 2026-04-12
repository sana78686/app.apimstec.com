<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Support\ContentLocales;
use App\Support\SitemapUrlCollector;
use Inertia\Inertia;
use Inertia\Response;

class SitemapManagerController extends Controller
{
    /**
     * Sitemap Manager: show sitemap URL and list of URLs included (published visible pages and blogs).
     * Sitemap updates automatically when content is published, unpublished, or deleted.
     */
    public function index(): Response
    {
        $domain = app()->bound('active_domain') ? app('active_domain') : null;
        if (! $domain instanceof Domain) {
            return Inertia::render('Seo/Sitemap/Index', [
                'sitemapUrl' => '',
                'sitemapUrlOnCmsHost' => null,
                'urls' => [],
                'count' => 0,
                'domainNote' => 'Select a website (domain) to see sitemap URLs for that property.',
            ]);
        }

        $publicBase = $domain->publicSiteBaseUrl();
        $sitemapUrl = $publicBase !== '' ? $publicBase.'/sitemap.xml' : '';
        $cmsHost = rtrim((string) config('app.url'), '/');
        $sitemapUrlOnCmsHost = $cmsHost.'/'.$domain->domain.'/sitemap.xml';

        $entries = SitemapUrlCollector::forDomain($domain);
        $urls = [];
        foreach ($entries as $u) {
            $path = (string) (parse_url($u['loc'], PHP_URL_PATH) ?? '');
            $locale = ContentLocales::localeFromPublicPath($path);
            $type = 'home';
            if (str_contains($path, '/blog/') || preg_match('#(^|/)blog/?$#', $path) === 1) {
                $type = 'blog';
            } elseif (str_contains($path, '/page/')) {
                $type = 'page';
            } elseif (str_contains($path, '/legal/')) {
                $type = 'legal';
            }
            $urls[] = [
                'type' => $type,
                'title' => $path !== '' ? $path : $u['loc'],
                'url' => $u['loc'],
                'path' => $path,
                'locale' => $locale,
                'updated_at' => $u['lastmod'].'T00:00:00+00:00',
            ];
        }

        return Inertia::render('Seo/Sitemap/Index', [
            'sitemapUrl' => $sitemapUrl,
            'sitemapUrlOnCmsHost' => $sitemapUrlOnCmsHost,
            'urls' => $urls,
            'count' => count($urls),
            'domainNote' => null,
        ]);
    }
}
