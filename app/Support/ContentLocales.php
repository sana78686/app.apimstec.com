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
     * CMS UI locale: URL segment wins, then session, then cookie, else English (admin default).
     */
    public static function resolveCmsWorkspaceLocale(Request $request): string
    {
        $fromRoute = $request->route('cms_locale');
        if (is_string($fromRoute) && $fromRoute !== '') {
            return self::normalize($fromRoute);
        }

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
}
