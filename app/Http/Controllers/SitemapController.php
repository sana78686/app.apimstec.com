<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Support\SitemapUrlCollector;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Serve sitemap.xml for the resolved tenant (Host header or /{domain}/sitemap.xml path).
     * URLs use the site's public origin (Domain.frontend_url or https://domain) and React paths.
     */
    public function __invoke(): Response
    {
        $domain = app()->bound('active_domain') ? app('active_domain') : null;
        if (! $domain instanceof Domain) {
            abort(404, 'Sitemap is not available for this host. Use your live site URL or /{your-domain}/sitemap.xml on the CMS host.');
        }

        $urls = SitemapUrlCollector::forDomain($domain);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>'."\n";
            $xml .= '    <lastmod>'.htmlspecialchars($u['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</lastmod>'."\n";
            $xml .= '    <changefreq>'.htmlspecialchars($u['changefreq'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</changefreq>'."\n";
            $xml .= '    <priority>'.htmlspecialchars($u['priority'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</priority>'."\n";
            $xml .= '  </url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
