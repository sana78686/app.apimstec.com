<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    private function blogToArray(Blog $blog): array
    {
        $blog->loadMissing('author:id,name');
        return [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'excerpt' => $blog->excerpt,
            'content' => $blog->content,
            'published_at' => $blog->published_at?->toIso8601String(),
            'user_id' => $blog->user_id,
            'author' => $blog->author ? ['id' => $blog->author->id, 'name' => $blog->author->name] : null,
            'is_published' => $blog->is_published,
            'visibility' => $blog->visibility,
            'meta_title' => $blog->meta_title,
            'meta_description' => $blog->meta_description,
            'canonical_url' => $blog->canonical_url,
            'meta_robots' => $blog->meta_robots,
            'og_title' => $blog->og_title,
            'og_description' => $blog->og_description,
            'og_image' => $blog->og_image,
            'schema_type' => $blog->schema_type,
            'schema_data' => $blog->schema_data,
            'created_at' => $blog->created_at->toIso8601String(),
        ];
    }

    public function index(): Response|JsonResponse
    {
        $blogs = Blog::with('author:id,name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($b) => $this->blogToArray($b));

        if (request()->is('api/*')) {
            return response()->json(['blogs' => $blogs]);
        }
        return Inertia::render('Blogs/Index');
    }

    public function create(): Response|JsonResponse
    {
        if (request()->is('api/*')) {
            return response()->json([]);
        }
        return Inertia::render('Blogs/Create');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(Blog::class, 'slug')],
            'excerpt' => 'nullable|string|max:1000',
            'content' => 'nullable|string',
            'published_at' => 'nullable|date',
            'is_published' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|string|max:500',
            'meta_robots' => 'nullable|string|max:50',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|string|max:500',
        ]);

        $visibility = $request->input('visibility', Blog::VISIBILITY_DRAFT);
        $blog = Blog::create([
            'title' => $request->title,
            'slug' => $request->slug ?: Str::slug($request->title),
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'published_at' => $request->published_at,
            'user_id' => $request->user()?->id,
            'is_published' => ($visibility === Blog::VISIBILITY_VISIBLE),
            'visibility' => $visibility,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'canonical_url' => $request->canonical_url,
            'meta_robots' => $request->meta_robots,
            'og_title' => $request->og_title,
            'og_description' => $request->og_description,
            'og_image' => $request->og_image,
        ]);

        if (request()->is('api/*')) {
            return response()->json(['message' => 'Blog created.', 'blog' => $this->blogToArray($blog)], 201);
        }
        return redirect()->route('blogs.index')->with('success', 'created');
    }

    public function edit(Blog $blog): Response|JsonResponse
    {
        $payload = ['blogId' => $blog->id, 'blog' => $this->blogToArray($blog)];
        if (request()->is('api/*')) {
            return response()->json($payload);
        }
        return Inertia::render('Blogs/Edit', $payload);
    }

    public function update(Request $request, Blog $blog): RedirectResponse|JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique(Blog::class, 'slug')->ignore($blog->id)],
            'excerpt' => 'nullable|string|max:1000',
            'content' => 'nullable|string',
            'published_at' => 'nullable|date',
            'visibility' => 'nullable|string|in:draft,visible,disabled',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|string|max:500',
            'meta_robots' => 'nullable|string|max:50',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|string|max:500',
        ]);

        $visibility = $request->input('visibility', $blog->visibility ?? Blog::VISIBILITY_DRAFT);
        $blog->update([
            'title' => $request->title,
            'slug' => $request->slug,
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'published_at' => $request->published_at,
            'is_published' => ($visibility === Blog::VISIBILITY_VISIBLE),
            'visibility' => $visibility,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'canonical_url' => $request->canonical_url,
            'meta_robots' => $request->meta_robots,
            'og_title' => $request->og_title,
            'og_description' => $request->og_description,
            'og_image' => $request->og_image,
        ]);

        if (request()->is('api/*')) {
            return response()->json(['message' => 'Blog updated.', 'blog' => $this->blogToArray($blog)]);
        }
        return redirect()->route('blogs.index')->with('success', 'updated');
    }

    public function destroy(Blog $blog): RedirectResponse|JsonResponse
    {
        $blog->delete();
        if (request()->is('api/*')) {
            return response()->json(['message' => 'Blog deleted.']);
        }
        return redirect()->route('blogs.index')->with('success', 'deleted');
    }

    /** Quick status update from the blogs list (PATCH /api/blogs/{id}/status). */
    public function updateStatus(Request $request, Blog $blog): JsonResponse
    {
        $request->validate([
            'visibility' => ['required', 'string', \Illuminate\Validation\Rule::in(['draft', 'visible', 'disabled'])],
        ]);
        $blog->visibility   = $request->visibility;
        $blog->is_published = ($blog->visibility === Blog::VISIBILITY_VISIBLE);
        $blog->save();
        return response()->json([
            'visibility'   => $blog->visibility,
            'is_published' => $blog->is_published,
            'message'      => 'Status updated.',
        ]);
    }

    /** @deprecated Keep for backward compat */
    public function togglePublish(Blog $blog): JsonResponse
    {
        $blog->visibility   = $blog->visibility === Blog::VISIBILITY_VISIBLE ? Blog::VISIBILITY_DRAFT : Blog::VISIBILITY_VISIBLE;
        $blog->is_published = ($blog->visibility === Blog::VISIBILITY_VISIBLE);
        $blog->save();
        return response()->json([
            'visibility'   => $blog->visibility,
            'is_published' => $blog->is_published,
            'message'      => 'Status updated.',
        ]);
    }

    /** @deprecated Alias for updateStatus */
    public function updateVisibility(Request $request, Blog $blog): JsonResponse
    {
        return $this->updateStatus($request, $blog);
    }
}
