<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Media;
use App\Models\MediaSource;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ImageSeoController extends Controller
{
    /**
     * Image SEO Manager: list media with ALT, compress, WebP options.
     */
    public function index(): Response
    {
        // Use current request origin so preview images load correctly (avoids APP_URL mismatch)
        $baseUrl = request()->getSchemeAndHttpHost() ?: rtrim(config('app.url'), '/');

        $media = Media::with('sources')->orderBy('updated_at', 'desc')->get()->map(function (Media $m) use ($baseUrl) {
            $path = ltrim($m->path ?? '', '/');
            $url = $m->isLocal() && $baseUrl
                ? $baseUrl.'/'.ltrim($path, '/')
                : $m->path;
            return [
                'id' => $m->id,
                'path' => $m->path,
                'filename' => $m->filename,
                'alt_text' => $m->alt_text,
                'file_size' => $m->file_size,
                'mime_type' => $m->mime_type,
                'webp_path' => $m->webp_path,
                'url' => $url,
                'is_local' => $m->isLocal(),
                'sources' => $m->sources->map(fn ($s) => [
                    'source_type' => $s->source_type,
                    'source_id' => $s->source_id,
                    'usage' => $s->usage,
                ])->toArray(),
            ];
        });

        return Inertia::render('Seo/ImageSeo/Index', [
            'media' => $media,
            'baseUrl' => $baseUrl,
        ]);
    }

    /**
     * Discover images from pages and blogs (og_image) and add to media.
     */
    public function discover(): JsonResponse
    {
        $paths = collect();

        foreach (Page::whereNotNull('og_image')->where('og_image', '!=', '')->get(['id', 'og_image']) as $p) {
            $paths->push(['path' => $p->og_image, 'source_type' => 'page', 'source_id' => $p->id]);
        }
        foreach (Blog::whereNotNull('og_image')->where('og_image', '!=', '')->get(['id', 'og_image']) as $b) {
            $paths->push(['path' => $b->og_image, 'source_type' => 'blog', 'source_id' => $b->id]);
        }

        $added = 0;
        $seen = [];
        foreach ($paths as $item) {
            $path = $item['path'];
            if (isset($seen[$path])) {
                $media = $seen[$path];
            } else {
                $media = Media::firstOrCreate(
                    ['path' => $path],
                    [
                        'filename' => basename($path),
                        'alt_text' => null,
                        'file_size' => null,
                        'mime_type' => null,
                    ]
                );
                $seen[$path] = $media;
                if ($media->wasRecentlyCreated) {
                    $added++;
                }
            }
            if ($item['source_id'] && $media->id) {
                MediaSource::firstOrCreate(
                    [
                        'media_id' => $media->id,
                        'source_type' => $item['source_type'],
                        'source_id' => $item['source_id'],
                        'usage' => 'og_image',
                    ]
                );
            }
        }

        return response()->json([
            'message' => "Discovery complete. {$added} new image(s) added.",
            'added' => $added,
        ]);
    }

    /**
     * Update ALT text for a media.
     */
    public function updateAlt(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required', Rule::exists(Media::class, 'id')],
            'alt_text' => 'nullable|string|max:500',
        ]);

        $media = Media::findOrFail($request->id);
        $media->alt_text = $request->input('alt_text');
        $media->save();

        return response()->json([
            'message' => 'ALT text updated.',
            'alt_text' => $media->alt_text,
        ]);
    }

    /**
     * Compress a local image (reduce quality for JPEG/PNG to reduce file size).
     */
    public function compress(Request $request): JsonResponse
    {
        $request->validate(['id' => ['required', Rule::exists(Media::class, 'id')]]);

        $media = Media::findOrFail($request->id);
        if (! $media->isLocal()) {
            return response()->json(['message' => 'Only local images can be compressed.'], 422);
        }

        $absolutePath = $media->absolutePath();
        if (! $absolutePath) {
            return response()->json(['message' => 'File not found on disk.'], 404);
        }

        $result = $this->compressImage($absolutePath);
        if ($result === false) {
            return response()->json(['message' => 'Compression failed or format not supported.'], 422);
        }

        $media->file_size = filesize($absolutePath);
        $media->save();

        return response()->json([
            'message' => 'Image compressed.',
            'file_size' => $media->file_size,
        ]);
    }

    /**
     * Convert a local image to WebP and save alongside original.
     */
    public function toWebP(Request $request): JsonResponse
    {
        $request->validate(['id' => ['required', Rule::exists(Media::class, 'id')]]);

        $media = Media::findOrFail($request->id);
        if (! $media->isLocal()) {
            return response()->json(['message' => 'Only local images can be converted to WebP.'], 422);
        }

        $absolutePath = $media->absolutePath();
        if (! $absolutePath) {
            return response()->json(['message' => 'File not found on disk.'], 404);
        }

        $webpPath = $this->convertToWebP($absolutePath);
        if (! $webpPath) {
            return response()->json(['message' => 'WebP conversion failed or format not supported.'], 422);
        }

        $relativeWebp = str_replace(public_path(), '', $webpPath);
        $relativeWebp = ltrim(str_replace('\\', '/', $relativeWebp), '/');
        $media->webp_path = $relativeWebp;
        $media->save();

        return response()->json([
            'message' => 'WebP version created.',
            'webp_path' => $media->webp_path,
        ]);
    }

    private function compressImage(string $path): bool
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

    private function convertToWebP(string $path): ?string
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
