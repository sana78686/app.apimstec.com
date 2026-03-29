<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Page;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Serve sitemap.xml. Generated on each request so it stays fresh:
     * includes only published pages and blogs; deleted or unpublished content is omitted.
     */
    public function __invoke(): Response
    {
        $baseUrl = rtrim(config('app.url'), '/');

        $urls = [];

        foreach (Page::where('is_published', true)->orderBy('updated_at', 'desc')->get(['slug', 'updated_at']) as $page) {
            $urls[] = [
                'loc' => $baseUrl.'/'.$page->slug,
                'lastmod' => $page->updated_at->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        foreach (Blog::where('is_published', true)->orderBy('updated_at', 'desc')->get(['slug', 'updated_at']) as $blog) {
            $urls[] = [
                'loc' => $baseUrl.'/blog/'.$blog->slug,
                'lastmod' => $blog->updated_at->format('Y-m-d'),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8').'</loc>'."\n";
            $xml .= '    <lastmod>'.htmlspecialchars($u['lastmod'], ENT_XML1, 'UTF-8').'</lastmod>'."\n";
            $xml .= '    <changefreq>'.htmlspecialchars($u['changefreq'], ENT_XML1, 'UTF-8').'</changefreq>'."\n";
            $xml .= '    <priority>'.htmlspecialchars($u['priority'], ENT_XML1, 'UTF-8').'</priority>'."\n";
            $xml .= '  </url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
