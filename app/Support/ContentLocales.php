<?php

namespace App\Support;

class ContentLocales
{
    public const SUPPORTED = ['en', 'ms', 'es', 'fr', 'ar', 'ru'];

    public const DEFAULT = 'en';

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
