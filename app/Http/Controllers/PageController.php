<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\ContentLocales;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    private function cmsLocale(Request $request): string
    {
        return ContentLocales::normalize($request->session()->get('cms_locale'));
    }

    /**
     * Accept either a resolved Page (implicit binding) or raw id from the route.
     * Avoids TypeError when binding passes a string before tenant DB is used consistently.
     */
    private function resolvePage(mixed $page): Page
    {
        $this->reconnectTenantFromSession();

        return $page instanceof Page ? $page : Page::query()->findOrFail((int) $page);
    }

    private function pageToArray(Page $page): array
    {
        return [
            'id' => $page->id,
            'locale' => $page->locale ?? ContentLocales::DEFAULT,
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
            'visibility' => $page->visibility ?? Page::VISIBILITY_DRAFT,
            'is_published' => $page->is_published,
            'sort_order' => $page->sort_order,
            'children' => $page->relationLoaded('children') ? $page->children->map(fn ($c) => $this->pageToArray($c))->values()->all() : [],
        ];
    }

    public function index(Request $request): Response|JsonResponse
    {
        $this->reconnectTenantFromSession();
        $requestedLocale = strtolower(trim((string) $request->query('locale', 'all')));
        $pages = Page::with(['children' => function ($q) use ($requestedLocale) {
            if (in_array($requestedLocale, ContentLocales::SUPPORTED, true)) {
                $q->where('locale', $requestedLocale);
            }
        }])
            ->whereNull('parent_id')
            ->when(
                in_array($requestedLocale, ContentLocales::SUPPORTED, true),
                fn ($q) => $q->where('locale', $requestedLocale)
            )
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(fn ($p) => $this->pageToArray($p));

        if ($request->is('api/*')) {
            return response()->json(['pages' => $pages]);
        }
        return Inertia::render('Pages/Index', [
            'localeFilterOptions' => ContentLocales::publicFilterSegmentOptions(),
        ]);
    }

    public function create(): Response|JsonResponse
    {
        $loc = ContentLocales::normalize(
            request()->query('locale') ?? $this->cmsLocale(request())
        );
        $parents = Page::whereNull('parent_id')->where('locale', $loc)->orderBy('sort_order')->orderBy('title')->get(['id', 'title', 'slug']);
        if (request()->is('api/*')) {
            return response()->json(['parents' => $parents]);
        }
        return Inertia::render('Pages/Create', ['parents' => $parents]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'locale' => ['required', 'string', Rule::in(ContentLocales::SUPPORTED)],
            'title' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique(Page::class, 'slug')->where(fn ($q) => $q->where('locale', ContentLocales::normalize($request->input('locale'))))],
            'content' => 'nullable|string',
            'schema_type' => ['nullable', 'string', Rule::in(['page', 'article', 'product', 'breadcrumb', 'faq'])],
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'placement' => 'nullable|string|in:header,footer,both',
            'parent_id' => ['nullable', Rule::exists(Page::class, 'id')->where(fn ($q) => $q->where('locale', ContentLocales::normalize($request->input('locale'))))],
            'visibility' => 'nullable|string|in:draft,visible,disabled',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $loc = ContentLocales::normalize($request->input('locale'));
        $visibility = $request->input('visibility', Page::VISIBILITY_DRAFT);
        $page = Page::create([
            'locale' => $loc,
            'title' => $request->title,
            'slug' => $request->slug ?: Str::slug($request->title),
            'content' => $request->content,
            'schema_type' => $request->schema_type ?: null,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'placement' => $request->placement,
            'parent_id' => $request->parent_id ?: null,
            'visibility' => $visibility,
            'is_published' => ($visibility === Page::VISIBILITY_VISIBLE),
            'sort_order' => (int) ($request->sort_order ?? 0),
        ]);

        ContentManagerController::bumpPublicApiCacheGeneration();

        if (request()->is('api/*')) {
            return response()->json(['message' => 'Page created.', 'page' => $this->pageToArray($page->load('children'))], 201);
        }
        return redirect()->route('pages.index')->with('success', 'created');
    }

    public function edit(mixed $cmsPage): Response|JsonResponse
    {
        $page = $this->resolvePage($cmsPage);
        $page->load(['children' => fn ($q) => $q->where('locale', $page->locale)]);
        $parents = Page::whereNull('parent_id')
            ->where('locale', $page->locale)
            ->where('id', '!=', $page->id)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'title', 'slug']);
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

    public function update(Request $request, mixed $page): RedirectResponse|JsonResponse
    {
        $page = $this->resolvePage($page);
        $request->validate([
            'locale' => ['required', 'string', Rule::in(ContentLocales::SUPPORTED)],
            'title' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique(Page::class, 'slug')->where(fn ($q) => $q->where('locale', ContentLocales::normalize($request->input('locale'))))->ignore($page->id)],
            'content' => 'nullable|string',
            'schema_type' => ['nullable', 'string', Rule::in(['page', 'article', 'product', 'breadcrumb', 'faq'])],
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'placement' => 'nullable|string|in:header,footer,both',
            'parent_id' => ['nullable', Rule::exists(Page::class, 'id')->where(fn ($q) => $q->where('locale', ContentLocales::normalize($request->input('locale'))))],
            'visibility' => 'nullable|string|in:draft,visible,disabled',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $loc = ContentLocales::normalize($request->input('locale'));
        $parentId = $request->parent_id;
        if ($parentId && (int) $parentId === (int) $page->id) {
            $parentId = null;
        }

        $visibility = $request->input('visibility', $page->visibility ?? Page::VISIBILITY_DRAFT);
        $page->update([
            'locale' => $loc,
            'title' => $request->title,
            'slug' => $request->slug,
            'content' => $request->content,
            'schema_type' => $request->schema_type ?: null,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'placement' => $request->placement,
            'parent_id' => $parentId ?: null,
            'visibility' => $visibility,
            'is_published' => ($visibility === Page::VISIBILITY_VISIBLE),
            'sort_order' => (int) ($request->sort_order ?? 0),
        ]);

        ContentManagerController::bumpPublicApiCacheGeneration();

        if (request()->is('api/*')) {
            return response()->json(['message' => 'Page updated.', 'page' => $this->pageToArray($page->load('children'))]);
        }
        return redirect()->route('pages.index')->with('success', 'updated');
    }

    public function destroy(mixed $page): RedirectResponse|JsonResponse
    {
        $page = $this->resolvePage($page);
        if ($page->children()->where('locale', $page->locale)->exists()) {
            if (request()->is('api/*')) {
                return response()->json(['message' => 'Cannot delete a page that has children.'], 422);
            }
            return redirect()->route('pages.index')->with('error', 'Cannot delete a page that has children.');
        }
        $page->delete();
        ContentManagerController::bumpPublicApiCacheGeneration();
        if (request()->is('api/*')) {
            return response()->json(['message' => 'Page deleted.']);
        }
        return redirect()->route('pages.index')->with('success', 'deleted');
    }

    /** Quick status update from the pages list (PATCH /api/pages/{id}/status). */
    public function updateStatus(Request $request, mixed $page): JsonResponse
    {
        $page = $this->resolvePage($page);
        $request->validate([
            'visibility' => ['required', 'string', Rule::in(['draft', 'visible', 'disabled'])],
        ]);
        $page->visibility   = $request->visibility;
        $page->is_published = ($page->visibility === Page::VISIBILITY_VISIBLE);
        $page->meta_robots  = $page->metaRobotsForVisibility();
        $page->save();
        ContentManagerController::bumpPublicApiCacheGeneration();
        return response()->json([
            'visibility'   => $page->visibility,
            'is_published' => $page->is_published,
            'meta_robots'  => $page->meta_robots,
            'message'      => 'Status updated.',
        ]);
    }

    /** @deprecated Keep for backward compat; redirects to updateStatus. */
    public function togglePublish(mixed $page): JsonResponse
    {
        $page = $this->resolvePage($page);
        $page->visibility   = $page->visibility === Page::VISIBILITY_VISIBLE ? Page::VISIBILITY_DRAFT : Page::VISIBILITY_VISIBLE;
        $page->is_published = ($page->visibility === Page::VISIBILITY_VISIBLE);
        $page->meta_robots  = $page->metaRobotsForVisibility();
        $page->save();
        return response()->json([
            'visibility'   => $page->visibility,
            'is_published' => $page->is_published,
            'message'      => 'Status updated.',
        ]);
    }

    public function seo(mixed $page): Response|JsonResponse
    {
        $page = $this->resolvePage($page);
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

    public function updateSeo(Request $request, mixed $page): RedirectResponse|JsonResponse
    {
        $page = $this->resolvePage($page);
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

        ContentManagerController::bumpPublicApiCacheGeneration();

        if (request()->is('api/*')) {
            return response()->json(['message' => 'SEO settings saved.', 'page' => $this->pageToArray($page->load('children'))]);
        }
        return redirect()->route('pages.index')->with('success', 'SEO updated.');
    }
}
