<?php

namespace App\Support;

class ContentLocales
{
    public const SUPPORTED = ['id', 'en', 'ms', 'es', 'fr', 'ar', 'ru'];

    /** Primary market: compresspdf.id (Bahasa Indonesia). */
    public const DEFAULT = 'id';

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

        return in_array($l, self::SUPPORTED, true) ? $l : self::DEFAULT;
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
