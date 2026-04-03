<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\RobotsTxt;
use Illuminate\Http\Response;

class RobotsTxtController extends Controller
{
    /**
     * Serve robots.txt for crawlers. Content is from Robots.txt Manager; includes sitemap link if missing.
     * Sitemap URL uses the site's public origin when a domain context is resolved.
     */
    public function __invoke(): Response
    {
        $domain = app()->bound('active_domain') ? app('active_domain') : null;
        $pub = $domain instanceof Domain ? $domain->publicSiteBaseUrl() : '';
        $content = RobotsTxt::getContent($pub !== '' ? $pub : null);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
