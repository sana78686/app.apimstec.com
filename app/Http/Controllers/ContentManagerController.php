<?php

namespace App\Http\Controllers;

use App\Models\ContentManagerSetting;
use App\Models\FaqItem;
use App\Models\HomeCard;
use App\Support\ContentLocales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function index(Request $request): Response
    {
        $loc = ContentLocales::normalize($request->session()->get('cms_locale'));

        return Inertia::render('ContentManager/Index', [
            'homePageContent' => ContentManagerSetting::get(self::homePageContentKey($loc), ''),
            'homeMetaTitle' => ContentManagerSetting::get(self::KEY_HOME_META_TITLE, ''),
            'homeMetaDescription' => ContentManagerSetting::get(self::KEY_HOME_META_DESCRIPTION, ''),
            'homeMetaKeywords' => ContentManagerSetting::get(self::KEY_HOME_META_KEYWORDS, ''),
            'homeFocusKeyword' => ContentManagerSetting::get(self::KEY_HOME_FOCUS_KEYWORD, ''),
            'homeOgTitle' => ContentManagerSetting::get(self::KEY_HOME_OG_TITLE, ''),
            'homeOgDescription' => ContentManagerSetting::get(self::KEY_HOME_OG_DESCRIPTION, ''),
            'homeOgImage' => ContentManagerSetting::get(self::KEY_HOME_OG_IMAGE, ''),
            'homeMetaRobots' => ContentManagerSetting::get(self::KEY_HOME_META_ROBOTS, 'index,follow'),
            'homeCanonicalUrl' => ContentManagerSetting::get(self::KEY_HOME_CANONICAL_URL, ''),
            'flash' => ['success' => session('success')],
        ]);
    }

    /** Update home page meta tags & SEO only (used by Content Manager and SEO > Home Page). */
    public function homeSeoUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|string|max:255',
            'focus_keyword'    => 'nullable|string|max:255',
            'og_title'         => 'nullable|string|max:255',
            'og_description'   => 'nullable|string|max:500',
            'og_image'         => 'nullable|string|max:2048',
            'meta_robots'      => ['nullable', 'string', \Illuminate\Validation\Rule::in([
                'index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow',
            ])],
            'canonical_url'    => 'nullable|string|max:500',
        ]);

        ContentManagerSetting::set(self::KEY_HOME_META_TITLE,       $validated['meta_title']       ?? '');
        ContentManagerSetting::set(self::KEY_HOME_META_DESCRIPTION, $validated['meta_description'] ?? '');
        ContentManagerSetting::set(self::KEY_HOME_META_KEYWORDS,    $validated['meta_keywords']    ?? '');
        ContentManagerSetting::set(self::KEY_HOME_FOCUS_KEYWORD,    $validated['focus_keyword']    ?? '');
        ContentManagerSetting::set(self::KEY_HOME_OG_TITLE,         $validated['og_title']         ?? '');
        ContentManagerSetting::set(self::KEY_HOME_OG_DESCRIPTION,   $validated['og_description']   ?? '');
        ContentManagerSetting::set(self::KEY_HOME_OG_IMAGE,         $validated['og_image']         ?? '');
        ContentManagerSetting::set(self::KEY_HOME_META_ROBOTS,      $validated['meta_robots']      ?? 'index,follow');
        ContentManagerSetting::set(self::KEY_HOME_CANONICAL_URL,    $validated['canonical_url']    ?? '');

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

    public function contact(): Response
    {
        return Inertia::render('ContentManager/ContactPage', [
            'contactEmail' => ContentManagerSetting::get(self::KEY_CONTACT_EMAIL, ''),
            'contactPhone' => ContentManagerSetting::get(self::KEY_CONTACT_PHONE, ''),
            'contactAddress' => ContentManagerSetting::get(self::KEY_CONTACT_ADDRESS, ''),
            'flash' => ['success' => session('success')],
        ]);
    }

    public function contactUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:64',
            'contact_address' => 'nullable|string|max:500',
        ]);
        ContentManagerSetting::set(self::KEY_CONTACT_EMAIL, $validated['contact_email'] ?? '');
        ContentManagerSetting::set(self::KEY_CONTACT_PHONE, $validated['contact_phone'] ?? '');
        ContentManagerSetting::set(self::KEY_CONTACT_ADDRESS, $validated['contact_address'] ?? '');

        return back()->with('success', 'Contact details saved.');
    }

    public function terms(): Response
    {
        return Inertia::render('ContentManager/TermsPage', [
            'termsContent' => ContentManagerSetting::get(self::KEY_TERMS_CONTENT, ''),
            'flash' => ['success' => session('success')],
        ]);
    }

    public function termsUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'terms_content' => 'nullable|string|max:100000',
        ]);
        ContentManagerSetting::set(self::KEY_TERMS_CONTENT, $validated['terms_content'] ?? '');

        return back()->with('success', 'Terms and conditions saved.');
    }

    /** Generic legal/content page: show editor (same pattern as terms). */
    private function legalPageResponse(string $key, string $view): Response
    {
        return Inertia::render($view, [
            'content' => ContentManagerSetting::get($key, ''),
            'flash' => ['success' => session('success')],
        ]);
    }

    private function legalPageUpdate(Request $request, string $key, string $field, string $successMessage): RedirectResponse
    {
        $validated = $request->validate([$field => 'nullable|string|max:100000']);
        ContentManagerSetting::set($key, $validated[$field] ?? '');

        return back()->with('success', $successMessage);
    }

    public function privacyPolicy(): Response
    {
        return $this->legalPageResponse(self::KEY_PRIVACY_POLICY_CONTENT, 'ContentManager/PrivacyPolicyPage');
    }

    public function privacyPolicyUpdate(Request $request): RedirectResponse
    {
        return $this->legalPageUpdate($request, self::KEY_PRIVACY_POLICY_CONTENT, 'content', 'Privacy policy saved.');
    }

    public function disclaimer(): Response
    {
        return $this->legalPageResponse(self::KEY_DISCLAIMER_CONTENT, 'ContentManager/DisclaimerPage');
    }

    public function disclaimerUpdate(Request $request): RedirectResponse
    {
        return $this->legalPageUpdate($request, self::KEY_DISCLAIMER_CONTENT, 'content', 'Disclaimer saved.');
    }

    public function aboutUs(): Response
    {
        return $this->legalPageResponse(self::KEY_ABOUT_US_CONTENT, 'ContentManager/AboutUsPage');
    }

    public function aboutUsUpdate(Request $request): RedirectResponse
    {
        return $this->legalPageUpdate($request, self::KEY_ABOUT_US_CONTENT, 'content', 'About us saved.');
    }

    public function cookiePolicy(): Response
    {
        return $this->legalPageResponse(self::KEY_COOKIE_POLICY_CONTENT, 'ContentManager/CookiePolicyPage');
    }

    public function cookiePolicyUpdate(Request $request): RedirectResponse
    {
        return $this->legalPageUpdate($request, self::KEY_COOKIE_POLICY_CONTENT, 'content', 'Cookie policy saved.');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'home_page_content' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:64',
            'contact_address' => 'nullable|string|max:500',
        ]);

        if (array_key_exists('home_page_content', $validated)) {
            $loc = ContentLocales::normalize($request->session()->get('cms_locale'));
            ContentManagerSetting::set(self::homePageContentKey($loc), $validated['home_page_content'] ?? '');
        }
        if (array_key_exists('contact_email', $validated)) {
            ContentManagerSetting::set(self::KEY_CONTACT_EMAIL, $validated['contact_email'] ?? '');
        }
        if (array_key_exists('contact_phone', $validated)) {
            ContentManagerSetting::set(self::KEY_CONTACT_PHONE, $validated['contact_phone'] ?? '');
        }
        if (array_key_exists('contact_address', $validated)) {
            ContentManagerSetting::set(self::KEY_CONTACT_ADDRESS, $validated['contact_address'] ?? '');
        }

        return back()->with('success', 'Content manager settings saved.');
    }
}
