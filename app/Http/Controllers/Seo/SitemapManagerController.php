<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Page;
use Inertia\Inertia;
use Inertia\Response;

class SitemapManagerController extends Controller
{
    /**
     * Sitemap Manager: show sitemap URL and list of URLs included (published pages and blogs).
     * Sitemap updates automatically when content is published, unpublished, or deleted.
     */
    public function index(): Response
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $sitemapUrl = $baseUrl.'/sitemap.xml';

        $pages = Page::where('is_published', true)->orderBy('title')->get(['id', 'title', 'slug', 'updated_at'])->map(fn ($p) => [
            'type' => 'page',
            'title' => $p->title,
            'url' => $baseUrl.'/'.$p->slug,
            'path' => $p->slug,
            'updated_at' => $p->updated_at->toIso8601String(),
        ]);

        $blogs = Blog::where('is_published', true)->orderBy('title')->get(['id', 'title', 'slug', 'updated_at'])->map(fn ($b) => [
            'type' => 'blog',
            'title' => $b->title,
            'url' => $baseUrl.'/blog/'.$b->slug,
            'path' => 'blog/'.$b->slug,
            'updated_at' => $b->updated_at->toIso8601String(),
        ]);

        $urls = $pages->concat($blogs)->values()->all();

        return Inertia::render('Seo/Sitemap/Index', [
            'sitemapUrl' => $sitemapUrl,
            'urls' => $urls,
            'count' => count($urls),
        ]);
    }
}
