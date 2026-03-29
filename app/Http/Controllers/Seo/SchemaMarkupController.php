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

class SchemaMarkupController extends Controller
{
    public const SCHEMA_NONE = '';
    public const SCHEMA_ARTICLE = 'article';
    public const SCHEMA_FAQ = 'faq';
    public const SCHEMA_PRODUCT = 'product';
    public const SCHEMA_BREADCRUMB = 'breadcrumb';

    public static function schemaTypeOptions(): array
    {
        return [
            self::SCHEMA_NONE => 'None',
            self::SCHEMA_ARTICLE => 'Article',
            self::SCHEMA_FAQ => 'FAQ',
            self::SCHEMA_PRODUCT => 'Product',
            self::SCHEMA_BREADCRUMB => 'Breadcrumb',
        ];
    }

    /**
     * Schema Markup Manager: list pages and blogs with structured data type (Article, FAQ, Product, Breadcrumb).
     */
    public function index(): Response
    {
        $pages = Page::orderBy('title')->get(['id', 'title', 'slug', 'schema_type', 'schema_data'])->map(fn ($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'slug' => $p->slug,
            'type' => 'page',
            'schema_type' => $p->schema_type ?? self::SCHEMA_NONE,
            'schema_data' => $p->schema_data,
        ]);

        $blogs = Blog::orderBy('title')->get(['id', 'title', 'slug', 'schema_type', 'schema_data'])->map(fn ($b) => [
            'id' => $b->id,
            'title' => $b->title,
            'slug' => $b->slug,
            'type' => 'blog',
            'schema_type' => $b->schema_type ?? self::SCHEMA_NONE,
            'schema_data' => $b->schema_data,
        ]);

        $items = $pages->concat($blogs)->sortBy('title')->values()->all();

        return Inertia::render('Seo/SchemaMarkup/Index', [
            'items' => $items,
            'schemaTypeOptions' => self::schemaTypeOptions(),
        ]);
    }

    /**
     * Update schema type (and optional data) for a page or blog.
     */
    public function updateSchema(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', Rule::in(['page', 'blog'])],
            'id' => ['required', 'integer', 'min:1'],
            'schema_type' => ['nullable', 'string', Rule::in([self::SCHEMA_NONE, self::SCHEMA_ARTICLE, self::SCHEMA_FAQ, self::SCHEMA_PRODUCT, self::SCHEMA_BREADCRUMB])],
            'schema_data' => ['nullable', 'array'],
        ]);

        $type = $request->input('type');
        $id = (int) $request->input('id');
        $schemaType = $request->input('schema_type') ?: self::SCHEMA_NONE;
        $schemaData = $request->input('schema_data');

        if ($type === 'page') {
            $page = Page::findOrFail($id);
            $page->schema_type = $schemaType === self::SCHEMA_NONE ? null : $schemaType;
            $page->schema_data = $schemaData ?: null;
            $page->save();
            return response()->json([
                'message' => 'Schema updated.',
                'schema_type' => $page->schema_type ?? self::SCHEMA_NONE,
                'schema_data' => $page->schema_data,
            ]);
        }

        $blog = Blog::findOrFail($id);
        $blog->schema_type = $schemaType === self::SCHEMA_NONE ? null : $schemaType;
        $blog->schema_data = $schemaData ?: null;
        $blog->save();
        return response()->json([
            'message' => 'Schema updated.',
            'schema_type' => $blog->schema_type ?? self::SCHEMA_NONE,
            'schema_data' => $blog->schema_data,
        ]);
    }
}
