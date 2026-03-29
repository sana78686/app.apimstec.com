<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class IndexingController extends Controller
{
    /**
     * Indexing Controls: list pages and blogs with visibility (draft → noindex, private → hidden, published → index).
     */
    public function index(): Response
    {
        $pages = Page::orderBy('title')->get(['id', 'title', 'slug', 'visibility', 'is_published', 'meta_robots'])->map(fn ($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'slug' => $p->slug,
            'type' => 'page',
            'visibility' => $p->visibility ?? Page::VISIBILITY_DRAFT,
            'is_published' => $p->is_published,
            'meta_robots' => $p->meta_robots,
            'indexing_summary' => $this->summaryFor($p->visibility ?? Page::VISIBILITY_DRAFT),
        ]);

        $blogs = Blog::orderBy('title')->get(['id', 'title', 'slug', 'visibility', 'is_published'])->map(fn ($b) => [
            'id' => $b->id,
            'title' => $b->title,
            'slug' => $b->slug,
            'type' => 'blog',
            'visibility' => $b->visibility ?? Blog::VISIBILITY_DRAFT,
            'is_published' => $b->is_published,
            'meta_robots' => $b->metaRobotsForVisibility(),
            'indexing_summary' => $this->summaryFor($b->visibility ?? Blog::VISIBILITY_DRAFT),
        ]);

        $items = $pages->concat($blogs)->sortBy('title')->values()->all();

        return Inertia::render('Seo/Indexing/Index', [
            'items' => $items,
            'visibilityOptions' => [
                'draft'    => 'Draft',
                'visible'  => 'Visible',
                'disabled' => 'Disabled',
            ],
        ]);
    }

    /**
     * Update visibility for a page or blog. Syncs meta_robots and is_published.
     */
    public function updateVisibility(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', Rule::in(['page', 'blog'])],
            'id' => ['required', 'integer', 'min:1'],
            'visibility' => ['required', 'string', Rule::in(['draft', 'visible', 'disabled'])],
        ]);

        $type = $request->input('type');
        $id = (int) $request->input('id');
        $visibility = $request->input('visibility');

        if ($type === 'page') {
            $page = Page::findOrFail($id);
            $page->visibility   = $visibility;
            $page->meta_robots  = $page->metaRobotsForVisibility();
            $page->is_published = ($visibility === Page::VISIBILITY_VISIBLE);
            $page->save();
            return response()->json([
                'message'          => 'Status updated.',
                'visibility'       => $page->visibility,
                'meta_robots'      => $page->meta_robots,
                'is_published'     => $page->is_published,
                'indexing_summary' => $this->summaryFor($page->visibility),
            ]);
        }

        $blog = Blog::findOrFail($id);
        $blog->visibility   = $visibility;
        $blog->is_published = ($visibility === Blog::VISIBILITY_VISIBLE);
        $blog->save();
        return response()->json([
            'message' => 'Visibility updated.',
            'visibility' => $blog->visibility,
            'meta_robots' => $blog->metaRobotsForVisibility(),
            'is_published' => $blog->is_published,
            'indexing_summary' => $this->summaryFor($blog->visibility),
        ]);
    }

    private function summaryFor(string $visibility): string
    {
        return match ($visibility) {
            'visible'  => 'visible & indexed',
            'disabled' => 'disabled',
            default    => 'draft (noindex)',
        };
    }
}
