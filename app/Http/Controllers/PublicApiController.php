<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ContentManagerController;
use App\Models\AnalyticsSetting;
use App\Models\Blog;
use App\Models\ContentManagerSetting;
use App\Models\ContentSection;
use App\Models\ContentSectionItem;
use App\Models\Domain;
use App\Models\FaqItem;
use App\Models\HomeCard;
use App\Models\Media;
use App\Models\Page;
use Carbon\Carbon;
use App\Support\ContentLocales;
use App\Support\FrontendAssetUrl;
use App\Support\FrontendPublicPath;
use App\Support\PublicJsonLdBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PublicApiController extends Controller
{
    private function activeDomainModel(): ?Domain
    {
        return app()->bound('active_domain') ? app('active_domain') : null;
    }

    private function rewriteAssetForPublic(?string $value): ?string
    {
        return FrontendAssetUrl::rewriteAssetUrl($value, $this->activeDomainModel());
    }

    private function rewriteCanonicalForPublic(?string $value): ?string
    {
        return FrontendAssetUrl::rewritePublicPageUrl($value, $this->activeDomainModel());
    }

    private function publicLocale(Request $request): string
    {
        return ContentLocales::normalize($request->query('locale'));
    }

    /**
     * Locales that have a visible page with this slug (for hreflang + parity checks).
     *
     * @return list<string>
     */
    private function alternateLocalesForPageSlug(string $slug): array
    {
        return Page::query()
            ->where('slug', $slug)
            ->where('visibility', Page::VISIBILITY_VISIBLE)
            ->whereIn('locale', ContentLocales::SUPPORTED)
            ->pluck('locale')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * If the requested locale has no page, try other locales (same slug) for a soft redirect on the SPA.
     */
    private function fallbackLocaleForPageSlug(string $slug, string $requestedLocale): ?string
    {
        $priority = array_values(array_unique(array_merge(
            [ContentLocales::DEFAULT_PUBLIC, ContentLocales::DEFAULT_CMS],
            ContentLocales::SUPPORTED
        )));
        $priority = array_values(array_filter($priority, fn (string $l) => $l !== $requestedLocale));

        foreach ($priority as $loc) {
            $exists = Page::query()
                ->where('slug', $slug)
                ->where('locale', $loc)
                ->where('visibility', Page::VISIBILITY_VISIBLE)
                ->exists();
            if ($exists) {
                return $loc;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function alternateLocalesForBlogSlug(string $slug): array
    {
        return Blog::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->where('visibility', Blog::VISIBILITY_VISIBLE)
            ->whereIn('locale', ContentLocales::SUPPORTED)
            ->pluck('locale')
            ->unique()
            ->values()
            ->all();
    }

    private function fallbackLocaleForBlogSlug(string $slug, string $requestedLocale): ?string
    {
        $priority = array_values(array_unique(array_merge(
            [ContentLocales::DEFAULT_PUBLIC, ContentLocales::DEFAULT_CMS],
            ContentLocales::SUPPORTED
        )));
        $priority = array_values(array_filter($priority, fn (string $l) => $l !== $requestedLocale));

        foreach ($priority as $loc) {
            $exists = Blog::query()
                ->where('slug', $slug)
                ->where('locale', $loc)
                ->where('is_published', true)
                ->where('visibility', Blog::VISIBILITY_VISIBLE)
                ->exists();
            if ($exists) {
                return $loc;
            }
        }

        return null;
    }

    /**
     * Inbox for the public contact form: CMS “Contact us” email, else CONTACT_FORM_MAIL_TO env.
     */
    private function contactFormMailTo(string $locale): string
    {
        $fromCms = trim((string) ContentManagerController::getLocalized(ContentManagerController::KEY_CONTACT_EMAIL, $locale));

        if ($fromCms !== '') {
            return $fromCms;
        }

        $fromEnv = trim((string) config('contact.form_mail_to'));

        return $fromEnv !== '' ? $fromEnv : 'apimstecofficial@gmail.com';
    }

    /**
     * Contact details for the frontend contact page (no auth).
     * Email/phone/address come from CMS Content Manager (tenant); email falls back to env.
     */
    public function contact(Request $request): JsonResponse
    {
        $loc = $this->publicLocale($request);

        return response()->json([
            'contact_email' => $this->contactFormMailTo($loc),
            'contact_phone' => ContentManagerController::getLocalized(ContentManagerController::KEY_CONTACT_PHONE, $loc),
            'contact_address' => ContentManagerController::getLocalized(ContentManagerController::KEY_CONTACT_ADDRESS, $loc),
        ]);
    }

    /**
     * Submit contact form. Sends email to the address configured in CMS Content Manager.
     */
    public function sendContact(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'accepts_terms' => 'required|accepted',
        ]);

        $toEmail = $this->contactFormMailTo($this->publicLocale($request));
        $siteName = (string) config('contact.public_site_name');

        $name = $validated['name'];
        $email = $validated['email'];
        $subject = $validated['subject'];
        $messageBody = $validated['message'];

        $body = "Contact form submission\n"
            ."Website: {$siteName}\n\n"
            ."From: {$name} <{$email}>\n"
            ."Subject: {$subject}\n\n"
            ."Message:\n{$messageBody}\n";

        try {
            Mail::raw($body, function ($mail) use ($toEmail, $email, $name, $subject, $siteName) {
                $mail->to($toEmail)
                    ->replyTo($email, $name)
                    ->subject("[{$siteName}] Contact: {$subject}");
            });
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages([
                'form' => ['Unable to send message. Please try again later.'],
            ]);
        }

        return response()->json(['message' => 'Message sent successfully.']);
    }
    /**
     * List visible pages for nav (no auth). Only placement header, footer, or both (null = not listed).
     * Direct URLs /page/{slug} still work for any visible page via pageBySlug.
     */
    public function pages(Request $request): JsonResponse
    {
        $locale = $this->publicLocale($request);
        $pages = Page::where('locale', $locale)
            ->where('visibility', Page::VISIBILITY_VISIBLE)
            ->whereIn('placement', ['header', 'footer', 'both'])
            ->orderByRaw('parent_id IS NULL DESC')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'parent_id', 'title', 'slug', 'meta_title', 'meta_description', 'placement', 'sort_order'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'parent_id' => $p->parent_id,
                'title' => $p->title,
                'slug' => $p->slug,
                'meta_title' => $p->meta_title,
                'meta_description' => $p->meta_description,
                'placement' => $p->placement,
                'sort_order' => $p->sort_order,

            ]);

        return response()->json(['pages' => $pages]);
    }

    /**
     * Get a single page by slug with full content and SEO (no auth).
     * Public access when visibility is visible (not gated on is_published).
     */
    public function pageBySlug(Request $request, string $slug): JsonResponse
    {
        $locale = $this->publicLocale($request);
        $page = Page::where('slug', $slug)
            ->where('locale', $locale)
            ->where('visibility', Page::VISIBILITY_VISIBLE)
            ->first();

        if (! $page) {
            $fallbackLocale = $this->fallbackLocaleForPageSlug($slug, $locale);
            if ($fallbackLocale !== null) {
                return response()->json([
                    '_seo_redirect' => [
                        'locale' => $fallbackLocale,
                        'slug' => $slug,
                    ],
                ]);
            }

            return response()->json(['message' => 'Page not found.'], 404);
        }

        $domain = $this->activeDomainModel();
        $og = $this->rewriteAssetForPublic($page->og_image);
        $canon = $this->rewriteCanonicalForPublic($page->canonical_url);
        $jsonLd = $domain instanceof Domain
            ? PublicJsonLdBuilder::pageGraph($domain, $locale, $page, [
                'canonical_url' => $canon,
                'og_image' => $og,
            ])
            : null;

        return response()->json([
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'canonical_url' => $canon,
            'meta_robots' => (trim($page->meta_robots ?? '') !== '') ? trim($page->meta_robots) : $page->metaRobotsForVisibility(),
            'og_title' => $page->og_title,
            'og_description' => $page->og_description,
            'og_image' => $og,
            'schema_type' => $page->schema_type,
            'schema_data' => $page->schema_data,
            'alternate_locales' => $this->alternateLocalesForPageSlug($slug),
            'json_ld' => $jsonLd,
        ]);
    }

    /**
     * List published blogs for nav/listing (no auth).
     */
    public function blogs(Request $request): JsonResponse
    {
        $locale = $this->publicLocale($request);
        $blogs = Blog::where('locale', $locale)
            ->where('is_published', true)
            ->where('visibility', Blog::VISIBILITY_VISIBLE)
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'excerpt', 'published_at', 'og_title', 'og_description', 'og_image'])
            ->map(function ($b) {
                $img = $this->rewriteAssetForPublic($b->og_image);

                return [
                    'id' => $b->id,
                    'title' => $b->title,
                    'slug' => $b->slug,
                    'excerpt' => $b->excerpt,
                    'published_at' => $b->published_at?->toIso8601String(),
                    'og_title' => $b->og_title,
                    'og_description' => $b->og_description,
                    'og_image' => $img,
                    'image' => $img,
                ];
            });
        return response()->json(['blogs' => $blogs]);
    }

    /**
     * Get a single published blog by slug with full content and SEO (no auth).
     */
    public function blogBySlug(Request $request, string $slug): JsonResponse
    {
        $locale = $this->publicLocale($request);
        $blog = Blog::where('slug', $slug)
            ->where('locale', $locale)
            ->where('is_published', true)
            ->where('visibility', Blog::VISIBILITY_VISIBLE)
            ->first();

        if (! $blog) {
            $fallbackLocale = $this->fallbackLocaleForBlogSlug($slug, $locale);
            if ($fallbackLocale !== null) {
                return response()->json([
                    '_seo_redirect' => [
                        'locale' => $fallbackLocale,
                        'slug' => $slug,
                        'kind' => 'blog',
                    ],
                ]);
            }

            return response()->json(['message' => 'Blog not found.'], 404);
        }

        $blog->loadMissing('author:id,name');

        $domain = $this->activeDomainModel();
        $og = $this->rewriteAssetForPublic($blog->og_image);
        $canon = $this->rewriteCanonicalForPublic($blog->canonical_url);
        $jsonLd = $domain instanceof Domain
            ? PublicJsonLdBuilder::blogGraph($domain, $locale, $blog, [
                'canonical_url' => $canon,
                'og_image' => $og,
            ])
            : null;

        return response()->json([
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'excerpt' => $blog->excerpt,
            'content' => $blog->content,
            'published_at' => $blog->published_at?->toIso8601String(),
            'updated_at' => $blog->updated_at?->toIso8601String(),
            'author' => $blog->author ? ['id' => $blog->author->id, 'name' => $blog->author->name] : null,
            'meta_title' => $blog->meta_title,
            'meta_description' => $blog->meta_description,
            'canonical_url' => $canon,
            'meta_robots' => (trim($blog->meta_robots ?? '') !== '') ? trim($blog->meta_robots) : $blog->metaRobotsForVisibility(),
            'og_title' => $blog->og_title,
            'og_description' => $blog->og_description,
            'og_image' => $og,
            'image' => $og,
            'schema_type' => $blog->schema_type,
            'schema_data' => $blog->schema_data,
            'alternate_locales' => $this->alternateLocalesForBlogSlug($slug),
            'json_ld' => $jsonLd,
        ]);
    }

    /**
     * Locales that have at least one visible page or published blog (for language switcher scope).
     */
    public function contentLocales(): JsonResponse
    {
        $supported = ContentLocales::SUPPORTED;

        $pageLocales = Page::query()
            ->where('visibility', Page::VISIBILITY_VISIBLE)
            ->whereIn('locale', $supported)
            ->distinct()
            ->pluck('locale');

        $blogLocales = Blog::query()
            ->where('is_published', true)
            ->where('visibility', Blog::VISIBILITY_VISIBLE)
            ->whereIn('locale', $supported)
            ->distinct()
            ->pluck('locale');

        $merged = $pageLocales->merge($blogLocales)->unique()->values()->all();

        if ($merged === []) {
            $merged = [ContentLocales::DEFAULT_PUBLIC];
        }

        return response()->json(['locales' => $merged]);
    }

    /**
     * List FAQ items for the home page FAQ section (no auth).
     */
    public function faq(Request $request): JsonResponse
    {
        $locale = $this->publicLocale($request);
        $items = FaqItem::where('locale', $locale)->ordered()->get(['id', 'question', 'answer', 'sort_order']);

        return response()->json(['faq' => $items]);
    }

    /**
     * List home cards for "Why use our PDF compressor" (no auth).
     */
    public function homeCards(Request $request): JsonResponse
    {
        $locale = $this->publicLocale($request);
        $cards = HomeCard::where('locale', $locale)->ordered()->get(['id', 'title', 'description', 'icon', 'sort_order']);
        $section = [
            'title' => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_HOW_TITLE, $locale),
            'description' => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_HOW_DESCRIPTION, $locale),
            'card_style' => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_HOW_CARD_STYLE, $locale) ?: 'numbered',
        ];

        return response()->json([
            'section' => $section,
            'cards' => $cards,
        ]);
    }

    /**
     * Dynamic content sections with nested module items (cards/paragraphs) for frontend rendering.
     */
    public function sections(Request $request): JsonResponse
    {
        $locale = $this->publicLocale($request);
        $sections = ContentSection::query()
            ->where('locale', $locale)
            ->where('is_active', true)
            ->with(['items' => fn ($q) => $q->where('is_active', true)->ordered()])
            ->ordered()
            ->get(['id', 'locale', 'title', 'description', 'layout', 'sort_order', 'is_active']);

        return response()->json(['sections' => $sections]);
    }

    /**
     * Home page rich text content and meta/SEO (shown above FAQ on frontend). No auth.
     */
    public function homeContent(Request $request): JsonResponse
    {
        $loc = $this->publicLocale($request);
        $contentKey = ContentManagerController::homePageContentKey($loc);

        $ogHome = ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_OG_IMAGE, $loc);
        $canonHome = ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_CANONICAL_URL, $loc);

        $homeRow = [
            'content'          => ContentManagerSetting::get($contentKey, ''),
            'meta_title'       => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_META_TITLE, $loc),
            'meta_description' => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_META_DESCRIPTION, $loc),
            'meta_keywords'    => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_META_KEYWORDS, $loc),
            'focus_keyword'    => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_FOCUS_KEYWORD, $loc),
            'og_title'         => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_OG_TITLE, $loc),
            'og_description'   => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_OG_DESCRIPTION, $loc),
            'og_image'         => $this->rewriteAssetForPublic($ogHome),
            'meta_robots'      => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_META_ROBOTS, $loc) ?: 'index,follow',
            'canonical_url'    => $this->rewriteCanonicalForPublic($canonHome),
            'head_snippet'     => ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_FRONTEND_HEAD_SNIPPET, $loc),
            'ga_measurement_id' => (string) AnalyticsSetting::getValue('ga_measurement_id', ''),
        ];

        $domain = $this->activeDomainModel();
        $homeRow['json_ld'] = $domain instanceof Domain
            ? PublicJsonLdBuilder::homeGraph($domain, $loc, $homeRow)
            : null;

        return response()->json($homeRow);
    }

    /**
     * JSON-LD for the PDF compressor tool route (WebApplication + FAQ + breadcrumb). Tenant + locale aware.
     */
    public function schemaTool(Request $request): JsonResponse
    {
        $locale = $this->publicLocale($request);
        $domain = $this->activeDomainModel();
        if (! $domain instanceof Domain) {
            return response()->json(['json_ld' => null]);
        }
        $items = FaqItem::where('locale', $locale)->ordered()->get(['question', 'answer']);

        return response()->json([
            'json_ld' => PublicJsonLdBuilder::toolCompressGraph($domain, $locale, $items),
        ]);
    }

    /**
     * Legal/content page by slug: terms, privacy-policy, disclaimer, about-us, cookie-policy. No auth.
     */
    public function legalPage(Request $request, string $slug): JsonResponse
    {
        $map = ContentManagerController::legalPageMap();
        if (! isset($map[$slug])) {
            return response()->json(['message' => 'Page not found.'], 404);
        }
        [$key, $title] = $map[$slug];
        $locale = $this->publicLocale($request);
        $content = ContentManagerController::getLocalized($key, $locale);

        return response()->json([
            'slug' => $slug,
            'title' => $title,
            'content' => $content,
        ]);
    }

    /**
     * Which legal pages have non-empty body text (for footer links). No auth.
     *
     * @return JsonResponse{legal: array<string, bool>}
     */
    public function legalNav(Request $request): JsonResponse
    {
        $map = ContentManagerController::legalPageMap();
        $locale = $this->publicLocale($request);
        $legal = [];
        foreach ($map as $slug => [$key]) {
            $content = ContentManagerController::getLocalized($key, $locale);
            $legal[$slug] = self::legalSettingHasBody($content);
        }

        return response()->json(['legal' => $legal]);
    }

    private static function legalSettingHasBody(?string $html): bool
    {
        if ($html === null || $html === '') {
            return false;
        }
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');

        return $text !== '';
    }

    /**
     * Single timestamp for “when any public-facing tenant content last changed”.
     * React uses it to invalidate local JSON/session cache after CMS saves (no need to guess TTL).
     */
    public function contentRevision(): JsonResponse
    {
        $times = [];
        foreach ([Blog::class, Page::class, FaqItem::class, HomeCard::class, ContentSection::class, ContentSectionItem::class, Media::class, ContentManagerSetting::class] as $model) {
            try {
                $m = $model::query()->max('updated_at');
                if ($m) {
                    $times[] = Carbon::parse($m);
                }
            } catch (\Throwable) {
                // Table or column missing on some tenants — ignore
            }
        }

        $latest = $times === [] ? null : collect($times)->max();

        return response()->json([
            'revision' => $latest !== null ? $latest->getTimestamp() : 0,
            'revision_iso' => $latest?->toIso8601String(),
        ]);
    }

    /**
     * Serve whitelisted public-disk files (e.g. blog cover uploads) with open cross-origin headers
     * so the React site can display them in &lt;img&gt; (direct /storage/ URLs are often blocked).
     */
    public function publicMedia(Request $request)
    {
        $path = (string) $request->query('path', '');
        $path = str_replace('\\', '/', $path);
        $path = trim($path, '/');
        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        // Editor uploads on Laravel public disk.
        if (preg_match('#^uploads/(?:editor|blog|images)/[A-Za-z0-9._/ %-]+$#', $path)) {
            if (! Storage::disk('public')->exists($path)) {
                abort(404);
            }

            return response()->file(Storage::disk('public')->path($path), [
                'Cross-Origin-Resource-Policy' => 'cross-origin',
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'public, max-age=604800',
            ]);
        }

        // Frontend media library files under React public/cms-uploads/... (tenant-specific).
        if (preg_match('#^cms-uploads/[A-Za-z0-9._/-]+$#', $path)) {
            $abs = FrontendPublicPath::absoluteFromWebPath($path);
            if (! is_string($abs) || $abs === '' || ! is_file($abs)) {
                abort(404);
            }

            return response()->file($abs, [
                'Cross-Origin-Resource-Policy' => 'cross-origin',
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'public, max-age=604800',
            ]);
        }

        abort(404);
    }
}
