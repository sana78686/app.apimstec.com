<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Page;
use App\Models\Redirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UrlRedirectsController extends Controller
{
    /**
     * URL & Redirect Manager: list all content URLs (pages, blogs) and existing redirects.
     */
    public function index(): Response
    {
        $pages = Page::orderBy('title')->get(['id', 'title', 'slug'])->map(fn ($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'slug' => $p->slug,
            'type' => 'page',
            'url_path' => $p->slug,
        ]);

        $blogs = Blog::orderBy('title')->get(['id', 'title', 'slug'])->map(fn ($b) => [
            'id' => $b->id,
            'title' => $b->title,
            'slug' => $b->slug,
            'type' => 'blog',
            'url_path' => 'blog/'.$b->slug,
        ]);

        $urls = $pages->concat($blogs)->sortBy('title')->values()->all();

        $redirects = Redirect::orderBy('from_path')->get(['id', 'from_path', 'to_path', 'status_code'])->map(fn ($r) => [
            'id' => $r->id,
            'from_path' => $r->from_path,
            'to_path' => $r->to_path,
            'status_code' => $r->status_code,
        ]);

        return Inertia::render('Seo/UrlRedirects/Index', [
            'urls' => $urls,
            'redirects' => $redirects,
        ]);
    }

    /**
     * Update slug for a page or blog. Creates 301 redirect if slug changed. SEO-friendly slug (lowercase, hyphens).
     */
    public function updateSlug(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', Rule::in(['page', 'blog'])],
            'id' => ['required', 'integer', 'min:1'],
            'slug' => ['required', 'string', 'max:255'],
        ]);

        $type = $request->input('type');
        $id = (int) $request->input('id');
        $newSlug = Str::slug($request->input('slug'));
        if ($newSlug === '') {
            return response()->json(['message' => 'Slug is required and must be SEO-friendly (letters, numbers, hyphens).'], 422);
        }

        if ($type === 'page') {
            $page = Page::findOrFail($id);
            if (Page::where('slug', $newSlug)->where('id', '!=', $id)->exists()) {
                return response()->json(['message' => 'This slug is already used by another page.'], 422);
            }
            $oldSlug = $page->slug;
            if ($oldSlug === $newSlug) {
                return response()->json(['message' => 'Slug unchanged.', 'slug' => $newSlug]);
            }
            Redirect::create([
                'from_path' => $oldSlug,
                'to_path' => $newSlug,
                'status_code' => 301,
            ]);
            $page->update(['slug' => $newSlug]);
            return response()->json([
                'message' => 'Slug updated. 301 redirect created from '.$oldSlug.' to '.$newSlug.'.',
                'slug' => $newSlug,
            ]);
        }

        $blog = Blog::findOrFail($id);
        if (Blog::where('slug', $newSlug)->where('id', '!=', $id)->exists()) {
            return response()->json(['message' => 'This slug is already used by another blog post.'], 422);
        }
        $oldSlug = $blog->slug;
        if ($oldSlug === $newSlug) {
            return response()->json(['message' => 'Slug unchanged.', 'slug' => $newSlug]);
        }
        Redirect::create([
            'from_path' => 'blog/'.$oldSlug,
            'to_path' => 'blog/'.$newSlug,
            'status_code' => 301,
        ]);
        $blog->update(['slug' => $newSlug]);
        return response()->json([
            'message' => 'Slug updated. 301 redirect created from blog/'.$oldSlug.' to blog/'.$newSlug.'.',
            'slug' => $newSlug,
        ]);
    }

    /**
     * Generate SEO-friendly slug from a title (for use in UI).
     */
    public function generateSlug(Request $request): JsonResponse
    {
        $title = $request->input('title', '');
        $slug = Str::slug($title);
        return response()->json(['slug' => $slug]);
    }
}
