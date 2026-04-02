<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Detects tenant-scoped public JSON routes: /{domain}/api/public/... (and legacy /api/public/...).
 */
final class PublicApiPath
{
    public static function matches(Request $request): bool
    {
        $path = $request->path();

        if ($path === 'api/public' || str_starts_with($path, 'api/public/')) {
            return true;
        }

        return (bool) preg_match('#^[^/]+/api/public(?:/|$)#', $path);
    }
}
