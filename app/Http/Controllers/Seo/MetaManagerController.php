<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MetaManagerController extends Controller
{
    /**
     * List only pages whose meta tags are set. Include seo_status: ok (green), warning (yellow), error (red).
     * Rules: title 30-60 chars, description 120-160 chars = ok; title < 20 or desc < 100 = error; else warning.
     */
    public function index(): Response
    {
        $pages = Page::orderBy('title')
            ->where(function ($q) {
                $q->whereNotNull('meta_title')->where('meta_title', '!=', '')
                    ->orWhereNotNull('meta_description')->where('meta_description', '!=', '');
            })
            ->get(['id', 'title', 'slug', 'meta_title', 'meta_description'])
            ->map(fn (Page $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'meta_done' => true,
                'seo_status' => $this->seoStatusForPage($p),
            ]);

        return Inertia::render('Seo/MetaManager/Index', [
            'pages' => $pages,
        ]);
    }

    private function seoStatusForPage(Page $p): string
    {
        $titleLen = mb_strlen(trim((string) $p->meta_title));
        $descLen = mb_strlen(trim((string) $p->meta_description));

        if ($titleLen < 20 || $descLen < 100) {
            return 'error';
        }
        if ($titleLen >= 30 && $titleLen <= 60 && $descLen >= 120 && $descLen <= 160) {
            return 'ok';
        }
        return 'warning';
    }

    /**
     * Create or edit meta: page selector + form. Query ?page_id=123 to pre-load that page.
     */
    public function create(Request $request): Response
    {
        $pages = Page::orderBy('title')->get(['id', 'title', 'slug'])->map(fn ($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'slug' => $p->slug,
        ]);

        $selectedPage = null;
        $pageId = $request->integer('page_id');
        if ($pageId > 0) {
            $page = Page::find($pageId);
            if ($page) {
                $contentStripped = $page->content
                    ? trim(preg_replace('/\s+/', ' ', strip_tags($page->content)))
                    : '';
                $selectedPage = [
                    'id' => $page->id,
                    'title' => $page->title,
                    'slug' => $page->slug,
                    'content_stripped' => mb_substr($contentStripped, 0, 500),
                    'meta_title' => $page->meta_title,
                    'meta_description' => $page->meta_description,
                    'focus_keyword' => $page->focus_keyword,
                    'canonical_url' => $page->canonical_url,
                    'meta_robots' => $page->meta_robots ?? 'index,follow',
                    'og_title' => $page->og_title,
                    'og_description' => $page->og_description,
                    'og_image' => $page->og_image,
                ];
            }
        }

        return Inertia::render('Seo/MetaManager/Create', [
            'pages' => $pages,
            'selectedPage' => $selectedPage,
        ]);
    }
}
