<?php

namespace App\Support;

use Illuminate\Http\Request;

class ContentLocales
{
    public const SUPPORTED = ['id', 'en', 'ms', 'es', 'fr', 'ar', 'ru'];

    /** Invalid/missing locale on public API & content fallbacks (marketing site). */
    public const DEFAULT_PUBLIC = 'id';

    /** CMS admin workspace when URL/session/cookie have no locale yet. */
    public const DEFAULT_CMS = 'en';

    /** @deprecated Use DEFAULT_PUBLIC */
    public const DEFAULT = self::DEFAULT_PUBLIC;

    /** Display labels for CMS dropdowns (aligned with public language switching). */
    public const LABELS = [
        'id' => 'Bahasa Indonesia',
        'en' => 'English',
        'ms' => 'Bahasa Melayu',
        'es' => 'Español',
        'fr' => 'Français',
        'ar' => 'العربية',
        'ru' => 'Русский',
    ];

    public static function normalize(?string $locale): string
    {
        $l = strtolower(trim((string) $locale));

        return in_array($l, self::SUPPORTED, true) ? $l : self::DEFAULT_PUBLIC;
    }

    /**
     * Infer locale from a public marketing URL path (leading slash, no query).
     * Default locale ({@see DEFAULT_PUBLIC}) has no /{lang}/ prefix; first segment is only a locale if it is in SUPPORTED.
     */
    public static function localeFromPublicPath(string $path, ?string $defaultLocale = null): string
    {
        $default = $defaultLocale ?? self::DEFAULT_PUBLIC;
        $path = trim($path, '/');
        if ($path === '') {
            return $default;
        }
        $first = strtolower(explode('/', $path, 2)[0] ?? '');

        return in_array($first, self::SUPPORTED, true) ? $first : $default;
    }

    /**
     * CMS UI locale: session, then cookie, else English (admin default). URLs no longer embed locale.
     */
    public static function resolveCmsWorkspaceLocale(Request $request): string
    {
        $s = $request->session()->get('cms_locale');
        if (is_string($s) && $s !== '') {
            return self::normalize($s);
        }

        $c = $request->cookie('cms_locale_pref');
        if (is_string($c) && $c !== '') {
            return self::normalize($c);
        }

        return self::DEFAULT_CMS;
    }

    public static function ruleIn(): string
    {
        return 'in:'.implode(',', self::SUPPORTED);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::SUPPORTED as $code) {
            $out[] = [
                'value' => $code,
                'label' => self::LABELS[$code] ?? strtoupper($code),
            ];
        }

        return $out;
    }

    /**
     * Locales for CMS segmented filters (see config/seo.php public_locales).
     *
     * @return list<string>
     */
    public static function publicFilterLocaleCodes(): array
    {
        $configured = config('seo.public_locales', ['id', 'en']);
        if (! is_array($configured)) {
            $configured = ['id', 'en'];
        }
        $out = [];
        foreach ($configured as $c) {
            $n = self::normalize((string) $c);
            if (in_array($n, self::SUPPORTED, true) && ! in_array($n, $out, true)) {
                $out[] = $n;
            }
        }

        return $out !== [] ? $out : ['id', 'en'];
    }

    /**
     * Options for AdminLocaleSegmentGroup (value + short label, e.g. EN, ID).
     *
     * @return list<array{value: string, label: string}>
     */
    public static function publicFilterSegmentOptions(): array
    {
        $opts = [];
        foreach (self::publicFilterLocaleCodes() as $code) {
            $opts[] = [
                'value' => $code,
                'label' => strtoupper($code),
            ];
        }

        return $opts;
    }
}
