<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    private function pageToArray(Page $page): array
    {
        return [
            'id' => $page->id,
            'parent_id' => $page->parent_id,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'focus_keyword' => $page->focus_keyword,
            'canonical_url' => $page->canonical_url,
            'meta_robots' => $page->meta_robots,
            'og_title' => $page->og_title,
            'og_description' => $page->og_description,
            'og_image' => $page->og_image,
            'placement' => $page->placement,
            'is_published' => $page->is_published,
            'sort_order' => $page->sort_order,
            'children' => $page->relationLoaded('children') ? $page->children->map(fn ($c) => $this->pageToArray($c))->values()->all() : [],
        ];
    }

    public function index(): Response|JsonResponse
    {
        $pages = Page::with('children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(fn ($p) => $this->pageToArray($p));

        if (request()->is('api/*')) {
            return response()->json(['pages' => $pages]);
        }
        return Inertia::render('Pages/Index');
    }

    public function create(): Response|JsonResponse
    {
        $parents = Page::whereNull('parent_id')->orderBy('sort_order')->orderBy('title')->get(['id', 'title', 'slug']);
        if (request()->is('api/*')) {
            return response()->json(['parents' => $parents]);
        }
        return Inertia::render('Pages/Create', ['parents' => $parents]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'placement' => 'nullable|string|in:header,footer,both',
            'parent_id' => 'nullable|exists:pages,id',
            'is_published' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $page = Page::create([
            'title' => $request->title,
            'slug' => $request->slug ?: Str::slug($request->title),
            'content' => $request->content,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'placement' => $request->placement,
            'parent_id' => $request->parent_id ?: null,
            'is_published' => $request->boolean('is_published', false),
            'sort_order' => (int) ($request->sort_order ?? 0),
        ]);

        if (request()->is('api/*')) {
            return response()->json(['message' => 'Page created.', 'page' => $this->pageToArray($page->load('children'))], 201);
        }
        return redirect()->route('pages.index')->with('success', 'created');
    }

    public function edit(Page $page): Response|JsonResponse
    {
        $page->load('children');
        $parents = Page::whereNull('parent_id')->where('id', '!=', $page->id)->orderBy('sort_order')->orderBy('title')->get(['id', 'title', 'slug']);
        $payload = [
            'pageId' => $page->id,
            'page' => $this->pageToArray($page),
            'parents' => $parents,
        ];
        if (request()->is('api/*')) {
            return response()->json($payload);
        }
        return Inertia::render('Pages/Edit', $payload);
    }

    public function update(Request $request, Page $page): RedirectResponse|JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug,' . $page->id,
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'placement' => 'nullable|string|in:header,footer,both',
            'parent_id' => 'nullable|exists:pages,id',
            'is_published' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $parentId = $request->parent_id;
        if ($parentId && (int) $parentId === (int) $page->id) {
            $parentId = null;
        }

        $page->update([
            'title' => $request->title,
            'slug' => $request->slug,
            'content' => $request->content,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'placement' => $request->placement,
            'parent_id' => $parentId ?: null,
            'is_published' => $request->boolean('is_published', false),
            'sort_order' => (int) ($request->sort_order ?? 0),
        ]);

        if (request()->is('api/*')) {
            return response()->json(['message' => 'Page updated.', 'page' => $this->pageToArray($page->load('children'))]);
        }
        return redirect()->route('pages.index')->with('success', 'updated');
    }

    public function destroy(Page $page): RedirectResponse|JsonResponse
    {
        if ($page->children()->exists()) {
            if (request()->is('api/*')) {
                return response()->json(['message' => 'Cannot delete a page that has children.'], 422);
            }
            return redirect()->route('pages.index')->with('error', 'Cannot delete a page that has children.');
        }
        $page->delete();
        if (request()->is('api/*')) {
            return response()->json(['message' => 'Page deleted.']);
        }
        return redirect()->route('pages.index')->with('success', 'deleted');
    }

    public function togglePublish(Page $page): JsonResponse
    {
        $page->is_published = !$page->is_published;
        $page->visibility = $page->is_published ? Page::VISIBILITY_PUBLISHED : Page::VISIBILITY_DRAFT;
        $page->meta_robots = $page->metaRobotsForVisibility();
        $page->save();
        return response()->json([
            'is_published' => $page->is_published,
            'message' => $page->is_published ? 'Page published.' : 'Page unpublished.',
        ]);
    }

    public function seo(Page $page): Response|JsonResponse
    {
        $payload = [
            'pageId' => $page->id,
            'pageTitle' => $page->title,
            'page' => [
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
                'focus_keyword' => $page->focus_keyword,
                'canonical_url' => $page->canonical_url,
                'meta_robots' => $page->meta_robots ?? 'index,follow',
                'og_title' => $page->og_title,
                'og_description' => $page->og_description,
                'og_image' => $page->og_image,
            ],
        ];
        if (request()->is('api/*')) {
            return response()->json($payload);
        }
        return Inertia::render('Pages/Seo', $payload);
    }

    public function updateSeo(Request $request, Page $page): RedirectResponse|JsonResponse
    {
        $request->validate([
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'focus_keyword' => 'nullable|string|max:255',
            'canonical_url' => 'nullable|string|max:500|url',
            'meta_robots' => ['nullable', 'string', Rule::in(['index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'])],
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|string|max:500|url',
        ]);

        $page->update([
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'focus_keyword' => $request->focus_keyword,
            'canonical_url' => $request->canonical_url,
            'meta_robots' => $request->meta_robots ?? 'index,follow',
            'og_title' => $request->og_title,
            'og_description' => $request->og_description,
            'og_image' => $request->og_image,
        ]);

        if (request()->is('api/*')) {
            return response()->json(['message' => 'SEO settings saved.', 'page' => $this->pageToArray($page->load('children'))]);
        }
        return redirect()->route('pages.index')->with('success', 'SEO updated.');
    }
}
