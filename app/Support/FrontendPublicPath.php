<?php

namespace App\Support;

/**
 * Path to the marketing site's `public/` folder (React `public/`), where `cms-uploads/` lives.
 */
final class FrontendPublicPath
{
    public static function root(): string
    {
        $path = (string) env('FRONTEND_PUBLIC_PATH', '');
        $path = $path !== '' ? $path : (string) realpath(base_path('../public'));
        if ($path !== '' && is_dir($path)) {
            return rtrim($path, DIRECTORY_SEPARATOR);
        }

        return rtrim(public_path(), DIRECTORY_SEPARATOR);
    }

    /**
     * Web path like cms-uploads/foo/bar.png → absolute filesystem path if file exists.
     */
    public static function absoluteFromWebPath(string $webPath): ?string
    {
        $webPath = ltrim(str_replace('\\', '/', $webPath), '/');
        if ($webPath === '' || str_contains($webPath, '..')) {
            return null;
        }
        if (! str_starts_with($webPath, 'cms-uploads/')) {
            return null;
        }
        $full = self::root().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $webPath);
        $real = realpath($full);

        return $real && is_file($real) ? $real : null;
    }
}
