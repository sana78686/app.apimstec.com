<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\RobotsTxt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RobotsManagerController extends Controller
{
    /**
     * Robots.txt Manager: edit rules that control crawlers (allow/block sections, sitemap link).
     */
    public function index(): Response
    {
        $domain = app()->bound('active_domain') ? app('active_domain') : null;
        $publicBase = $domain instanceof Domain ? $domain->publicSiteBaseUrl() : '';
        if ($publicBase === '') {
            $publicBase = rtrim((string) config('app.url'), '/');
        }

        $record = RobotsTxt::getRecord();
        $content = $record?->content;
        if ($content === null || trim($content) === '') {
            $pub = $domain instanceof Domain ? $domain->publicSiteBaseUrl() : '';
            $content = RobotsTxt::defaultContent($pub !== '' ? $pub : null);
        } else {
            $content = trim($content);
        }

        $cmsHost = rtrim((string) config('app.url'), '/');
        $robotsUrl = $publicBase.'/robots.txt';
        $sitemapUrl = $publicBase.'/sitemap.xml';
        $robotsUrlOnCmsHost = $domain instanceof Domain ? $cmsHost.'/'.$domain->domain.'/robots.txt' : null;
        $sitemapUrlOnCmsHost = $domain instanceof Domain ? $cmsHost.'/'.$domain->domain.'/sitemap.xml' : null;

        return Inertia::render('Seo/Robots/Index', [
            'content' => $content,
            'robotsUrl' => $robotsUrl,
            'sitemapUrl' => $sitemapUrl,
            'robotsUrlOnCmsHost' => $robotsUrlOnCmsHost,
            'sitemapUrlOnCmsHost' => $sitemapUrlOnCmsHost,
        ]);
    }

    /**
     * Update robots.txt content.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'content' => ['nullable', 'string', 'max:65535'],
        ]);

        RobotsTxt::setContent($request->input('content') ?? '');

        return redirect()->route('seo.robots')->with('success', 'Robots.txt updated. Crawlers will see the new rules.');
    }
}
