<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ContentManagerController;
use App\Models\Blog;
use App\Models\ContentManagerSetting;
use App\Models\FaqItem;
use App\Models\HomeCard;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PublicApiController extends Controller
{
    /**
     * Contact details for the frontend contact page (no auth).
     * The contact_email is where form submissions are sent (set in CMS Content Manager).
     */
    public function contact(Request $request): JsonResponse
    {
        return response()->json([
            'contact_email' => config('contact.form_mail_to'),
            'contact_phone' => ContentManagerSetting::get(ContentManagerController::KEY_CONTACT_PHONE, ''),
            'contact_address' => ContentManagerSetting::get(ContentManagerController::KEY_CONTACT_ADDRESS, ''),
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

        $toEmail = (string) config('contact.form_mail_to');
        $siteName = (string) config('contact.public_site_name');
        if ($toEmail === '') {
            throw ValidationException::withMessages([
                'form' => ['Contact form is not configured. Set CONTACT_FORM_MAIL_TO in .env.'],
            ]);
        }

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
        $pages = Page::where('visibility', Page::VISIBILITY_VISIBLE)
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
    public function pageBySlug(string $slug): JsonResponse
    {
        $page = Page::where('slug', $slug)
            ->where('visibility', Page::VISIBILITY_VISIBLE)
            ->first();

        if (! $page) {
            return response()->json(['message' => 'Page not found.'], 404);
        }

        return response()->json([
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
            'meta_title' => $page->meta_title ?? $page->title,
            'meta_description' => $page->meta_description,
            'canonical_url' => $page->canonical_url,
            'meta_robots' => $page->meta_robots ?? $page->metaRobotsForVisibility(),
            'og_title' => $page->og_title ?? $page->meta_title ?? $page->title,
            'og_description' => $page->og_description ?? $page->meta_description,
            'og_image' => $page->og_image,
            'schema_type' => $page->schema_type,
            'schema_data' => $page->schema_data,
        ]);
    }

    /**
     * List published blogs for nav/listing (no auth).
     */
    public function blogs(Request $request): JsonResponse
    {
        $blogs = Blog::where('is_published', true)
            ->where('visibility', Blog::VISIBILITY_VISIBLE)
            ->orderBy('published_at', 'desc')
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'excerpt', 'published_at', 'og_title', 'og_description', 'og_image'])
            ->map(fn ($b) => [
                'id' => $b->id,
                'title' => $b->title,
                'slug' => $b->slug,
                'excerpt' => $b->excerpt,
                'published_at' => $b->published_at?->toIso8601String(),
                'og_title' => $b->og_title,
                'og_description' => $b->og_description,
                'og_image' => $b->og_image,
            ]);
        return response()->json(['blogs' => $blogs]);
    }

    /**
     * Get a single published blog by slug with full content and SEO (no auth).
     */
    public function blogBySlug(string $slug): JsonResponse
    {
        $blog = Blog::where('slug', $slug)
            ->where('is_published', true)
            ->where('visibility', Blog::VISIBILITY_VISIBLE)
            ->first();

        if (! $blog) {
            return response()->json(['message' => 'Blog not found.'], 404);
        }

        $blog->loadMissing('author:id,name');

        return response()->json([
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'excerpt' => $blog->excerpt,
            'content' => $blog->content,
            'published_at' => $blog->published_at?->toIso8601String(),
            'updated_at' => $blog->updated_at?->toIso8601String(),
            'author' => $blog->author ? ['id' => $blog->author->id, 'name' => $blog->author->name] : null,
            'meta_title' => $blog->meta_title ?? $blog->og_title ?? $blog->title,
            'meta_description' => $blog->meta_description ?? $blog->og_description ?? $blog->excerpt,
            'canonical_url' => $blog->canonical_url,
            'meta_robots' => $blog->meta_robots ?? $blog->metaRobotsForVisibility(),
            'og_title' => $blog->og_title ?? $blog->meta_title ?? $blog->title,
            'og_description' => $blog->og_description ?? $blog->meta_description ?? $blog->excerpt,
            'og_image' => $blog->og_image,
            'schema_type' => $blog->schema_type,
            'schema_data' => $blog->schema_data,
        ]);
    }

    /**
     * List FAQ items for the home page FAQ section (no auth).
     */
    public function faq(Request $request): JsonResponse
    {
        $items = FaqItem::ordered()->get(['id', 'question', 'answer', 'sort_order']);

        return response()->json(['faq' => $items]);
    }

    /**
     * List home cards for "Why use our PDF compressor" (no auth).
     */
    public function homeCards(Request $request): JsonResponse
    {
        $cards = HomeCard::ordered()->get(['id', 'title', 'description', 'icon', 'sort_order']);

        return response()->json(['cards' => $cards]);
    }

    /**
     * Home page rich text content and meta/SEO (shown above FAQ on frontend). No auth.
     */
    public function homeContent(Request $request): JsonResponse
    {
        return response()->json([
            'content'          => ContentManagerSetting::get(ContentManagerController::KEY_HOME_PAGE_CONTENT, ''),
            'meta_title'       => ContentManagerSetting::get(ContentManagerController::KEY_HOME_META_TITLE, ''),
            'meta_description' => ContentManagerSetting::get(ContentManagerController::KEY_HOME_META_DESCRIPTION, ''),
            'meta_keywords'    => ContentManagerSetting::get(ContentManagerController::KEY_HOME_META_KEYWORDS, ''),
            'focus_keyword'    => ContentManagerSetting::get(ContentManagerController::KEY_HOME_FOCUS_KEYWORD, ''),
            'og_title'         => ContentManagerSetting::get(ContentManagerController::KEY_HOME_OG_TITLE, ''),
            'og_description'   => ContentManagerSetting::get(ContentManagerController::KEY_HOME_OG_DESCRIPTION, ''),
            'og_image'         => ContentManagerSetting::get(ContentManagerController::KEY_HOME_OG_IMAGE, ''),
            'meta_robots'      => ContentManagerSetting::get(ContentManagerController::KEY_HOME_META_ROBOTS, 'index,follow'),
            'canonical_url'    => ContentManagerSetting::get(ContentManagerController::KEY_HOME_CANONICAL_URL, ''),
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
        $content = ContentManagerSetting::get($key, '');

        return response()->json([
            'slug' => $slug,
            'title' => $title,
            'content' => $content,
        ]);
    }
}
