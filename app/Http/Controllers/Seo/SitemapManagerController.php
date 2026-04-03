<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Domain;
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
            $urls[] = [
                'type' => str_contains($path, '/blog/') ? 'blog' : (str_contains($path, '/page/') ? 'page' : 'home'),
                'title' => $path !== '' ? $path : $u['loc'],
                'url' => $u['loc'],
                'path' => $path,
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
