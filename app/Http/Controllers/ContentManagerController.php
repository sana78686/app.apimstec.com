<?php

namespace App\Http\Controllers;

use App\Models\ContentManagerSetting;
use App\Models\FaqItem;
use App\Models\HomeCard;
use App\Support\ContentLocales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ContentManagerController extends Controller
{
    public const KEY_HOME_PAGE_CONTENT = 'home_page_content';
    public const KEY_HOME_META_TITLE = 'home_meta_title';
    public const KEY_HOME_META_DESCRIPTION = 'home_meta_description';
    public const KEY_HOME_META_KEYWORDS = 'home_meta_keywords';
    public const KEY_HOME_FOCUS_KEYWORD = 'home_focus_keyword';
    public const KEY_HOME_OG_TITLE = 'home_og_title';
    public const KEY_HOME_OG_DESCRIPTION = 'home_og_description';
    public const KEY_HOME_OG_IMAGE = 'home_og_image';
    public const KEY_HOME_META_ROBOTS = 'home_meta_robots';
    public const KEY_HOME_CANONICAL_URL = 'home_canonical_url';

    /** Google Search Console verification meta, gtag/GTM snippets, etc. — injected into React app <head>. */
    public const KEY_HOME_FRONTEND_HEAD_SNIPPET = 'home_frontend_head_snippet';
    public const KEY_CONTACT_EMAIL = 'contact_email';
    public const KEY_CONTACT_PHONE = 'contact_phone';
    public const KEY_CONTACT_ADDRESS = 'contact_address';
    public const KEY_TERMS_CONTENT = 'terms_content';
    public const KEY_PRIVACY_POLICY_CONTENT = 'privacy_policy_content';
    public const KEY_DISCLAIMER_CONTENT = 'disclaimer_content';
    public const KEY_ABOUT_US_CONTENT = 'about_us_content';
    public const KEY_COOKIE_POLICY_CONTENT = 'cookie_policy_content';

    /** Per-locale home body HTML: home_page_content_en, home_page_content_ms, … */
    public static function homePageContentKey(string $locale): string
    {
        return 'home_page_content_'.ContentLocales::normalize($locale);
    }

    /** Slug => [key, title] for legal/content pages exposed to frontend */
    public static function legalPageMap(): array
    {
        return [
            'terms' => [self::KEY_TERMS_CONTENT, 'Terms and conditions'],
            'privacy-policy' => [self::KEY_PRIVACY_POLICY_CONTENT, 'Privacy policy'],
            'disclaimer' => [self::KEY_DISCLAIMER_CONTENT, 'Disclaimer'],
            'about-us' => [self::KEY_ABOUT_US_CONTENT, 'About us'],
            'cookie-policy' => [self::KEY_COOKIE_POLICY_CONTENT, 'Cookie policy'],
        ];
    }

    /**
     * Read `{baseKey}_{locale}` from settings; fall back to legacy unsuffixed `baseKey` (pre-localization data).
     */
    public static function getLocalized(string $baseKey, string $locale): string
    {
        $loc = ContentLocales::normalize($locale);
        $v = ContentManagerSetting::get($baseKey.'_'.$loc, '');
        if ($v !== '') {
            return $v;
        }

        return ContentManagerSetting::get($baseKey, '');
    }

    public static function setLocalized(string $baseKey, string $locale, string $value): void
    {
        $loc = ContentLocales::normalize($locale);
        ContentManagerSetting::set($baseKey.'_'.$loc, $value);
    }

    private function contentEditorLocale(Request $request): string
    {
        return ContentLocales::normalize(
            $request->query('content_locale') ?? $request->session()->get('cms_locale')
        );
    }

    public function index(Request $request): Response
    {
        $loc = $this->contentEditorLocale($request);

        return Inertia::render('ContentManager/Index', [
            'contentLocale' => $loc,
            'homePageContent' => ContentManagerSetting::get(self::homePageContentKey($loc), ''),
            'homeMetaTitle' => self::getLocalized(self::KEY_HOME_META_TITLE, $loc),
            'homeMetaDescription' => self::getLocalized(self::KEY_HOME_META_DESCRIPTION, $loc),
            'homeMetaKeywords' => self::getLocalized(self::KEY_HOME_META_KEYWORDS, $loc),
            'homeFocusKeyword' => self::getLocalized(self::KEY_HOME_FOCUS_KEYWORD, $loc),
            'homeOgTitle' => self::getLocalized(self::KEY_HOME_OG_TITLE, $loc),
            'homeOgDescription' => self::getLocalized(self::KEY_HOME_OG_DESCRIPTION, $loc),
            'homeOgImage' => self::getLocalized(self::KEY_HOME_OG_IMAGE, $loc),
            'homeMetaRobots' => self::getLocalized(self::KEY_HOME_META_ROBOTS, $loc) ?: 'index,follow',
            'homeCanonicalUrl' => self::getLocalized(self::KEY_HOME_CANONICAL_URL, $loc),
            'homeFrontendHeadSnippet' => self::getLocalized(self::KEY_HOME_FRONTEND_HEAD_SNIPPET, $loc),
            'flash' => ['success' => session('success')],
        ]);
    }

    /** Update home page meta tags & SEO only (used by Content Manager and SEO > Home Page). */
    public function homeSeoUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(ContentLocales::SUPPORTED)],
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|string|max:2000',
            'focus_keyword'    => 'nullable|string|max:255',
            'og_title'         => 'nullable|string|max:255',
            'og_description'   => 'nullable|string|max:500',
            'og_image'         => 'nullable|string|max:2048',
            'meta_robots'      => ['nullable', 'string', Rule::in([
                'index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow',
            ])],
            'canonical_url'    => 'nullable|string|max:500',
            'frontend_head_snippet' => 'nullable|string|max:65535',
        ]);

        $loc = ContentLocales::normalize($validated['locale']);
        self::setLocalized(self::KEY_HOME_META_TITLE, $loc, $validated['meta_title'] ?? '');
        self::setLocalized(self::KEY_HOME_META_DESCRIPTION, $loc, $validated['meta_description'] ?? '');
        self::setLocalized(self::KEY_HOME_META_KEYWORDS, $loc, $validated['meta_keywords'] ?? '');
        self::setLocalized(self::KEY_HOME_FOCUS_KEYWORD, $loc, $validated['focus_keyword'] ?? '');
        self::setLocalized(self::KEY_HOME_OG_TITLE, $loc, $validated['og_title'] ?? '');
        self::setLocalized(self::KEY_HOME_OG_DESCRIPTION, $loc, $validated['og_description'] ?? '');
        self::setLocalized(self::KEY_HOME_OG_IMAGE, $loc, $validated['og_image'] ?? '');
        self::setLocalized(self::KEY_HOME_META_ROBOTS, $loc, $validated['meta_robots'] ?? 'index,follow');
        self::setLocalized(self::KEY_HOME_CANONICAL_URL, $loc, $validated['canonical_url'] ?? '');
        self::setLocalized(self::KEY_HOME_FRONTEND_HEAD_SNIPPET, $loc, $validated['frontend_head_snippet'] ?? '');

        self::bumpPublicApiCacheGeneration();

        return back()->with('success', 'Home page meta tags & SEO saved.');
    }

    /** Home page with URL-driven tabs: content-manager/home/faq and content-manager/home/use-cards */
    public function home(Request $request, ?string $tab = null): Response
    {
        $tab = in_array($tab, ['faq', 'use-cards'], true) ? $tab : 'faq';
        $loc = ContentLocales::normalize($request->session()->get('cms_locale'));

        return Inertia::render('ContentManager/Home', [
            'faqItems' => FaqItem::where('locale', $loc)->ordered()->get(),
            'cards' => HomeCard::where('locale', $loc)->ordered()->get(),
            'iconOptions' => HomeCard::iconOptions(),
            'activeTab' => $tab,
            'cmsLocale' => $loc,
            'flash' => ['success' => session('success')],
        ]);
    }

    public function contact(Request $request): Response
    {
        $loc = $this->contentEditorLocale($request);

        return Inertia::render('ContentManager/ContactPage', [
            'contentLocale' => $loc,
            'contactEmail' => self::getLocalized(self::KEY_CONTACT_EMAIL, $loc),
            'contactPhone' => self::getLocalized(self::KEY_CONTACT_PHONE, $loc),
            'contactAddress' => self::getLocalized(self::KEY_CONTACT_ADDRESS, $loc),
            'flash' => ['success' => session('success')],
        ]);
    }

    public function contactUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(ContentLocales::SUPPORTED)],
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:64',
            'contact_address' => 'nullable|string|max:500',
        ]);
        $loc = ContentLocales::normalize($validated['locale']);
        self::setLocalized(self::KEY_CONTACT_EMAIL, $loc, $validated['contact_email'] ?? '');
        self::setLocalized(self::KEY_CONTACT_PHONE, $loc, $validated['contact_phone'] ?? '');
        self::setLocalized(self::KEY_CONTACT_ADDRESS, $loc, $validated['contact_address'] ?? '');

        return back()->with('success', 'Contact details saved.');
    }

    public function terms(Request $request): Response
    {
        $loc = $this->contentEditorLocale($request);

        return Inertia::render('ContentManager/TermsPage', [
            'contentLocale' => $loc,
            'termsContent' => self::getLocalized(self::KEY_TERMS_CONTENT, $loc),
            'flash' => ['success' => session('success')],
        ]);
    }

    public function termsUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(ContentLocales::SUPPORTED)],
            'terms_content' => 'nullable|string|max:100000',
        ]);
        self::setLocalized(
            self::KEY_TERMS_CONTENT,
            ContentLocales::normalize($validated['locale']),
            $validated['terms_content'] ?? ''
        );

        return back()->with('success', 'Terms and conditions saved.');
    }

    /** Generic legal/content page: show editor (same pattern as terms). */
    private function legalPageResponse(Request $request, string $key, string $view): Response
    {
        $loc = $this->contentEditorLocale($request);

        return Inertia::render($view, [
            'contentLocale' => $loc,
            'content' => self::getLocalized($key, $loc),
            'flash' => ['success' => session('success')],
        ]);
    }

    private function legalPageUpdate(Request $request, string $key, string $field, string $successMessage): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(ContentLocales::SUPPORTED)],
            $field => 'nullable|string|max:100000',
        ]);
        self::setLocalized($key, ContentLocales::normalize($validated['locale']), $validated[$field] ?? '');

        return back()->with('success', $successMessage);
    }

    public function privacyPolicy(Request $request): Response
    {
        return $this->legalPageResponse($request, self::KEY_PRIVACY_POLICY_CONTENT, 'ContentManager/PrivacyPolicyPage');
    }

    public function privacyPolicyUpdate(Request $request): RedirectResponse
    {
        return $this->legalPageUpdate($request, self::KEY_PRIVACY_POLICY_CONTENT, 'content', 'Privacy policy saved.');
    }

    public function disclaimer(Request $request): Response
    {
        return $this->legalPageResponse($request, self::KEY_DISCLAIMER_CONTENT, 'ContentManager/DisclaimerPage');
    }

    public function disclaimerUpdate(Request $request): RedirectResponse
    {
        return $this->legalPageUpdate($request, self::KEY_DISCLAIMER_CONTENT, 'content', 'Disclaimer saved.');
    }

    public function aboutUs(Request $request): Response
    {
        return $this->legalPageResponse($request, self::KEY_ABOUT_US_CONTENT, 'ContentManager/AboutUsPage');
    }

    public function aboutUsUpdate(Request $request): RedirectResponse
    {
        return $this->legalPageUpdate($request, self::KEY_ABOUT_US_CONTENT, 'content', 'About us saved.');
    }

    public function cookiePolicy(Request $request): Response
    {
        return $this->legalPageResponse($request, self::KEY_COOKIE_POLICY_CONTENT, 'ContentManager/CookiePolicyPage');
    }

    public function cookiePolicyUpdate(Request $request): RedirectResponse
    {
        return $this->legalPageUpdate($request, self::KEY_COOKIE_POLICY_CONTENT, 'content', 'Cookie policy saved.');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(ContentLocales::SUPPORTED)],
            'home_page_content' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:64',
            'contact_address' => 'nullable|string|max:500',
        ]);

        $loc = ContentLocales::normalize($validated['locale']);
        if (array_key_exists('home_page_content', $validated)) {
            ContentManagerSetting::set(self::homePageContentKey($loc), $validated['home_page_content'] ?? '');
            self::bumpPublicApiCacheGeneration();
        }
        if (array_key_exists('contact_email', $validated)) {
            self::setLocalized(self::KEY_CONTACT_EMAIL, $loc, $validated['contact_email'] ?? '');
        }
        if (array_key_exists('contact_phone', $validated)) {
            self::setLocalized(self::KEY_CONTACT_PHONE, $loc, $validated['contact_phone'] ?? '');
        }
        if (array_key_exists('contact_address', $validated)) {
            self::setLocalized(self::KEY_CONTACT_ADDRESS, $loc, $validated['contact_address'] ?? '');
        }

        return back()->with('success', 'Content manager settings saved.');
    }

    /**
     * Invalidate cached GET /api/public/* responses so home-content and other JSON update immediately.
     */
    public static function bumpPublicApiCacheGeneration(): void
    {
        $k = 'public_api:invalidate_generation';
        Cache::put($k, (int) Cache::get($k, 0) + 1);
    }
}
