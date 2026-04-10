<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Media;
use App\Support\FrontendAssetUrl;
use App\Support\FrontendPublicPath;
use App\Http\Controllers\FrontendMediaController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Upload an image (e.g. from rich text editor or blog cover).
     * Saves to CMS storage AND copies to the frontend's public directory
     * so the image is directly accessible on the live site.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|image|max:10240',
        ]);

        $file = $request->file('image');
        $originalName = $file->getClientOriginalName();
        $ext = $file->getClientOriginalExtension() ?: $file->guessExtension();
        $filename = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '-' . Str::random(6) . '.' . strtolower($ext);
        $dir = 'uploads/editor';
        $path = $file->storeAs($dir, $filename, 'public');

        $domain = null;
        $domainId = session('active_domain_id');
        if ($domainId) {
            $domain = Domain::query()->where('id', $domainId)->where('is_active', true)->first();
        }

        // Also copy the file to the frontend's public directory for direct access
        if ($domain instanceof Domain) {
            try {
                $segment = FrontendMediaController::domainSegment($domain);
                $frontendDir = FrontendPublicPath::root()
                    . DIRECTORY_SEPARATOR . FrontendMediaController::RELATIVE_DIR
                    . DIRECTORY_SEPARATOR . $segment;
                if (! is_dir($frontendDir)) {
                    File::makeDirectory($frontendDir, 0755, true);
                }
                $src = Storage::disk('public')->path($path);
                @copy($src, $frontendDir . DIRECTORY_SEPARATOR . $filename);
            } catch (\Throwable $e) {
                // Non-fatal: CMS storage copy is the primary
            }
        }

        $absoluteUrl = FrontendAssetUrl::uploadsPublicUrl($domain, $path);
        $publicPath = '/'.FrontendAssetUrl::encodePath($path);

        Media::create([
            'path' => '/storage/'.$path,
            'filename' => $originalName,
            'alt_text' => $request->input('alt_text'),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return response()->json([
            'url' => $absoluteUrl,
            'absolute_url' => $absoluteUrl,
            'path' => $path,
            'public_path' => $publicPath,
            'relative_url' => $publicPath,
        ]);
    }
}
