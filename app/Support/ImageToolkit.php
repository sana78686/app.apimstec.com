<?php

namespace App\Support;

/**
 * Shared image compression / WebP conversion (GD). Used by Image SEO and Media library.
 */
final class ImageToolkit
{
    public static function compress(string $path): bool
    {
        if (! function_exists('imagejpeg') || ! function_exists('imagecreatefromjpeg')) {
            return false;
        }

        $info = @getimagesize($path);
        if (! $info) {
            return false;
        }

        $mime = $info['mime'] ?? '';
        $image = null;
        if ($mime === 'image/jpeg') {
            $image = @imagecreatefromjpeg($path);
        } elseif ($mime === 'image/png') {
            $image = @imagecreatefrompng($path);
            if ($image) {
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
        } elseif ($mime === 'image/gif') {
            $image = @imagecreatefromgif($path);
        }

        if (! $image) {
            return false;
        }

        $quality = 82;
        $result = false;
        if ($mime === 'image/jpeg') {
            $result = imagejpeg($image, $path, $quality);
        } elseif ($mime === 'image/png') {
            $result = imagepng($image, $path, (int) round(9 - (9 * $quality / 100)));
        } elseif ($mime === 'image/gif') {
            $result = imagegif($image, $path);
        }

        imagedestroy($image);

        return $result;
    }

    public static function convertToWebP(string $path): ?string
    {
        if (! function_exists('imagewebp')) {
            return null;
        }

        $info = @getimagesize($path);
        if (! $info) {
            return null;
        }

        $mime = $info['mime'] ?? '';
        $image = null;
        if ($mime === 'image/jpeg') {
            $image = @imagecreatefromjpeg($path);
        } elseif ($mime === 'image/png') {
            $image = @imagecreatefrompng($path);
            if ($image) {
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
        } elseif ($mime === 'image/gif') {
            $image = @imagecreatefromgif($path);
        } elseif ($mime === 'image/webp') {
            $image = @imagecreatefromwebp($path);
        }

        if (! $image) {
            return null;
        }

        $dir = dirname($path);
        $base = pathinfo($path, PATHINFO_FILENAME);
        $webpPath = $dir.DIRECTORY_SEPARATOR.$base.'.webp';

        $ok = imagewebp($image, $webpPath, 85);
        imagedestroy($image);

        return $ok ? $webpPath : null;
    }
}
