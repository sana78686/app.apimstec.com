<?php

namespace App\Support;

class ContentLocales
{
    public const SUPPORTED = ['id', 'en', 'ms', 'es', 'fr', 'ar', 'ru'];

    /** Primary market: compresspdf.id (Bahasa Indonesia). */
    public const DEFAULT = 'id';

    public static function normalize(?string $locale): string
    {
        $l = strtolower(trim((string) $locale));

        return in_array($l, self::SUPPORTED, true) ? $l : self::DEFAULT;
    }

    public static function ruleIn(): string
    {
        return 'in:'.implode(',', self::SUPPORTED);
    }
}
