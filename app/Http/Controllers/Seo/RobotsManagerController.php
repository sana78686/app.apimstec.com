<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
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
        $record = RobotsTxt::getRecord();
        $content = $record?->content;
        if ($content === null || trim($content) === '') {
            $content = RobotsTxt::defaultContent();
        } else {
            $content = trim($content);
        }

        $baseUrl = rtrim(config('app.url'), '/');
        $robotsUrl = $baseUrl.'/robots.txt';
        $sitemapUrl = $baseUrl.'/sitemap.xml';

        return Inertia::render('Seo/Robots/Index', [
            'content' => $content,
            'robotsUrl' => $robotsUrl,
            'sitemapUrl' => $sitemapUrl,
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
