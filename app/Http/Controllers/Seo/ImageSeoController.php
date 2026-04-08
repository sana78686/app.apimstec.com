<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\ContentManagerController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontendMediaController;
use App\Models\Blog;
use App\Models\ContentManagerSetting;
use App\Models\Domain;
use App\Models\FaqItem;
use App\Models\Media;
use App\Models\MediaSource;
use App\Models\Page;
use App\Support\ContentLocales;
use App\Support\FrontendPublicPath;
use App\Support\ImageToolkit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
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
     * Discover images from CMS content (pages, blogs, home, legal, FAQ), OG fields, editor uploads, and tenant cms-uploads folder.
     */
    public function discover(): JsonResponse
    {
        $domainId = session('active_domain_id');
        $domain = $domainId ? Domain::find($domainId) : null;

        $items = [];

        foreach (Page::query()->get(['id', 'og_image', 'content']) as $p) {
            $this->pushPath($items, $p->og_image, 'page', (int) $p->id, 'og_image', $domain);
            foreach ($this->extractImageUrlsFromHtml($p->content) as $u) {
                $this->pushPath($items, $u, 'page', (int) $p->id, 'html', $domain);
            }
        }

        foreach (Blog::query()->get(['id', 'og_image', 'content']) as $b) {
            $this->pushPath($items, $b->og_image, 'blog', (int) $b->id, 'og_image', $domain);
            foreach ($this->extractImageUrlsFromHtml($b->content) as $u) {
                $this->pushPath($items, $u, 'blog', (int) $b->id, 'html', $domain);
            }
        }

        foreach (ContentLocales::SUPPORTED as $loc) {
            $homeHtml = ContentManagerSetting::get(ContentManagerController::homePageContentKey($loc), '');
            foreach ($this->extractImageUrlsFromHtml($homeHtml) as $u) {
                $this->pushPath($items, $u, 'home_page', 0, 'html:'.$loc, $domain);
            }
            $this->pushPath(
                $items,
                ContentManagerController::getLocalized(ContentManagerController::KEY_HOME_OG_IMAGE, $loc),
                'home_page',
                0,
                'og_image:'.$loc,
                $domain
            );
        }

        foreach (ContentManagerController::legalPageMap() as $slug => [$key]) {
            foreach (ContentLocales::SUPPORTED as $loc) {
                $html = ContentManagerController::getLocalized($key, $loc);
                foreach ($this->extractImageUrlsFromHtml($html) as $u) {
                    $this->pushPath($items, $u, 'legal_page', 0, 'html:'.$slug.':'.$loc, $domain);
                }
            }
        }

        foreach (FaqItem::query()->get(['id', 'question', 'answer']) as $faq) {
            foreach ($this->extractImageUrlsFromHtml($faq->question) as $u) {
                $this->pushPath($items, $u, 'faq_item', (int) $faq->id, 'question', $domain);
            }
            foreach ($this->extractImageUrlsFromHtml($faq->answer) as $u) {
                $this->pushPath($items, $u, 'faq_item', (int) $faq->id, 'answer', $domain);
            }
        }

        if ($domain instanceof Domain) {
            $segment = FrontendMediaController::domainSegment($domain);
            $dir = FrontendPublicPath::root().DIRECTORY_SEPARATOR.FrontendMediaController::RELATIVE_DIR.DIRECTORY_SEPARATOR.$segment;
            if (is_dir($dir)) {
                foreach (File::files($dir) as $f) {
                    if (! preg_match('/\.(jpe?g|png|gif|webp|svg|avif)$/i', $f->getFilename())) {
                        continue;
                    }
                    $webPath = '/'.FrontendMediaController::RELATIVE_DIR.'/'.$segment.'/'.basename($f->getFilename());
                    $items[] = [
                        'path' => $webPath,
                        'source_type' => 'frontend_media',
                        'source_id' => 0,
                        'usage' => 'cms_uploads',
                    ];
                }
            }
        }

        $added = 0;
        $seen = [];
        foreach ($items as $item) {
            $path = $item['path'];
            if ($path === '' || $path === null) {
                continue;
            }
            if (isset($seen[$path])) {
                $media = $seen[$path];
            } else {
                $media = Media::firstOrCreate(
                    ['path' => $path],
                    [
                        'filename' => basename(parse_url($path, PHP_URL_PATH) ?: $path),
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
            if ($media->id) {
                MediaSource::firstOrCreate(
                    [
                        'media_id' => $media->id,
                        'source_type' => $item['source_type'],
                        'source_id' => $item['source_id'],
                        'usage' => $item['usage'],
                    ],
                    []
                );
            }
        }

        return response()->json([
            'message' => "Discovery complete. {$added} new image record(s) created; existing images got extra source links where found.",
            'added' => $added,
        ]);
    }

    /**
     * @param  list<array{path:string,source_type:string,source_id:int,usage:string}>  $items
     */
    private function pushPath(array &$items, ?string $raw, string $sourceType, int $sourceId, string $usage, ?Domain $domain): void
    {
        $path = $this->normalizeToMediaPath($raw, $domain);
        if ($path === null || $path === '') {
            return;
        }
        $items[] = [
            'path' => $path,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'usage' => $usage,
        ];
    }

    /**
     * @return list<string>
     */
    private function extractImageUrlsFromHtml(?string $html): array
    {
        if ($html === null || $html === '') {
            return [];
        }
        $urls = [];
        if (preg_match_all('/<img[^>]+src\s*=\s*["\']([^"\']+)["\']/i', $html, $m)) {
            $urls = array_merge($urls, $m[1]);
        }
        if (preg_match_all('/<img[^>]+data-src\s*=\s*["\']([^"\']+)["\']/i', $html, $m)) {
            $urls = array_merge($urls, $m[1]);
        }
        if (preg_match_all('/url\(\s*["\']?([^"\'()]+\.(?:jpe?g|png|gif|webp|svg|avif))["\']?\s*\)/i', $html, $m)) {
            $urls = array_merge($urls, $m[1]);
        }

        return array_values(array_unique(array_filter(array_map('trim', $urls))));
    }

    private function normalizeToMediaPath(?string $ref, ?Domain $domain): ?string
    {
        if ($ref === null) {
            return null;
        }
        $ref = trim($ref);
        if ($ref === '') {
            return null;
        }
        $ref = preg_replace('/\?.*$/s', '', $ref) ?? $ref;
        if (preg_match('#^(data:|blob:|javascript:)#i', $ref)) {
            return null;
        }

        $base = $domain instanceof Domain ? rtrim($domain->publicSiteBaseUrl(), '/') : '';
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($base !== '' && str_starts_with($ref, $base.'/')) {
            $tail = substr($ref, strlen($base));

            return $this->normalizeToMediaPath($tail, $domain);
        }

        if (preg_match('#^//[^/]+(/.*)$#', $ref, $m)) {
            return $this->normalizeToMediaPath($m[1], $domain);
        }

        if (preg_match('#^https?://#i', $ref)) {
            if ($base !== '' && str_starts_with($ref, $base.'/')) {
                return $this->normalizeToMediaPath(substr($ref, strlen($base)), $domain);
            }
            if ($appUrl !== '' && str_starts_with($ref, $appUrl.'/storage/')) {
                return substr($ref, strlen($appUrl));
            }
            if ($appUrl !== '' && str_starts_with($ref, $appUrl.'/')) {
                $tail = substr($ref, strlen($appUrl));

                return $this->normalizeToMediaPath($tail, $domain);
            }

            return $ref;
        }

        if (preg_match('#^/cms-uploads/.+#i', $ref)) {
            return $ref;
        }

        if (preg_match('#^/storage/.+#i', $ref)) {
            return $ref;
        }

        if (preg_match('#^/uploads/.+#i', $ref)) {
            return $ref;
        }

        if (preg_match('#^storage/(uploads/.+)#i', $ref, $m)) {
            return '/'.$m[1];
        }

        if (preg_match('#^uploads/.+#i', $ref)) {
            return '/storage/'.$ref;
        }

        return null;
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

        if (! ImageToolkit::compress($absolutePath)) {
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

        $webpPath = ImageToolkit::convertToWebP($absolutePath);
        if ($webpPath === null) {
            return response()->json(['message' => 'WebP conversion failed or format not supported.'], 422);
        }

        $webpNorm = str_replace('\\', '/', $webpPath);
        $publicNorm = rtrim(str_replace('\\', '/', public_path()), '/');
        $frontNorm = rtrim(str_replace('\\', '/', FrontendPublicPath::root()), '/');
        if (str_starts_with($webpNorm, $publicNorm.'/')) {
            $relativeWebp = ltrim(substr($webpNorm, strlen($publicNorm)), '/');
        } elseif (str_starts_with($webpNorm, $frontNorm.'/')) {
            $relativeWebp = ltrim(substr($webpNorm, strlen($frontNorm)), '/');
        } else {
            $relativeWebp = ltrim(str_replace('\\', '/', str_replace(public_path(), '', $webpPath)), '/');
        }
        $media->webp_path = '/'.$relativeWebp;
        $media->save();

        return response()->json([
            'message' => 'WebP version created.',
            'webp_path' => $media->webp_path,
        ]);
    }
}
