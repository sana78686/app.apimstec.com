<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SocialSharingController extends Controller
{
    /**
     * Social Sharing (Open Graph): list pages and blogs with OG title, description, image for Facebook, X, LinkedIn.
     */
    public function index(): Response
    {
        $baseUrl = rtrim(config('app.url'), '/');

        $pages = Page::orderBy('title')->get(['id', 'title', 'slug', 'og_title', 'og_description', 'og_image'])->map(fn ($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'slug' => $p->slug,
            'type' => 'page',
            'url_path' => $p->slug,
            'og_title' => $p->og_title,
            'og_description' => $p->og_description,
            'og_image' => $p->og_image,
            'og_image_absolute' => $p->og_image ? (preg_match('#^https?://#i', $p->og_image) ? $p->og_image : $baseUrl.'/'.ltrim($p->og_image, '/')) : null,
            'has_og' => ! empty(trim((string) $p->og_title)) || ! empty(trim((string) $p->og_description)) || ! empty(trim((string) $p->og_image)),
        ]);

        $blogs = Blog::orderBy('title')->get(['id', 'title', 'slug', 'og_title', 'og_description', 'og_image'])->map(fn ($b) => [
            'id' => $b->id,
            'title' => $b->title,
            'slug' => $b->slug,
            'type' => 'blog',
            'url_path' => 'blog/'.$b->slug,
            'og_title' => $b->og_title,
            'og_description' => $b->og_description,
            'og_image' => $b->og_image,
            'og_image_absolute' => $b->og_image ? (preg_match('#^https?://#i', $b->og_image) ? $b->og_image : $baseUrl.'/'.ltrim($b->og_image, '/')) : null,
            'has_og' => ! empty(trim((string) $b->og_title)) || ! empty(trim((string) $b->og_description)) || ! empty(trim((string) $b->og_image)),
        ]);

        $items = $pages->concat($blogs)->sortBy('title')->values()->all();

        return Inertia::render('Seo/SocialSharing/Index', [
            'items' => $items,
        ]);
    }

    /**
     * Edit Open Graph for a page or blog.
     */
    public function edit(Request $request): Response
    {
        $request->validate([
            'type' => ['required', 'string', Rule::in(['page', 'blog'])],
            'id' => ['required', 'integer', 'min:1'],
        ]);
        $type = $request->input('type');
        $id = (int) $request->input('id');

        if ($type === 'page') {
            $page = Page::findOrFail($id);
            $item = [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'type' => 'page',
                'url_path' => $page->slug,
                'og_title' => $page->og_title ?? '',
                'og_description' => $page->og_description ?? '',
                'og_image' => $page->og_image ?? '',
            ];
        } else {
            $blog = Blog::findOrFail($id);
            $item = [
                'id' => $blog->id,
                'title' => $blog->title,
                'slug' => $blog->slug,
                'type' => 'blog',
                'url_path' => 'blog/'.$blog->slug,
                'og_title' => $blog->og_title ?? '',
                'og_description' => $blog->og_description ?? '',
                'og_image' => $blog->og_image ?? '',
            ];
        }

        return Inertia::render('Seo/SocialSharing/Edit', [
            'item' => $item,
        ]);
    }

    /**
     * Update Open Graph for a page or blog.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'type' => ['required', 'string', Rule::in(['page', 'blog'])],
            'id' => ['required', 'integer', 'min:1'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:500'],
        ]);

        $type = $request->input('type');
        $id = (int) $request->input('id');

        $data = [
            'og_title' => $request->input('og_title') ?: null,
            'og_description' => $request->input('og_description') ?: null,
            'og_image' => $request->input('og_image') ?: null,
        ];

        if ($type === 'page') {
            Page::where('id', $id)->update($data);
        } else {
            Blog::where('id', $id)->update($data);
        }

        return redirect()->route('seo.social-sharing')->with('success', 'Open Graph data updated. Previews will improve on Facebook, X, and LinkedIn.');
    }
}
