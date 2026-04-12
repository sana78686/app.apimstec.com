<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Support\SitemapXmlBuilder;
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

        $xml = SitemapXmlBuilder::forDomain($domain);

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
