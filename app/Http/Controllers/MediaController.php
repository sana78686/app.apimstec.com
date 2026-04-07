<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Models\Media;
use App\Support\FrontendAssetUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Upload an image (e.g. from rich text editor). Stores in public disk and returns URL.
     * Optionally creates a Media record for the library.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|image|max:10240', // 10MB, images only
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

        // Public URL on the live React site (Domain.frontend_url or https://domain), not the CMS app host.
        $absoluteUrl = FrontendAssetUrl::uploadsPublicUrl($domain, $path);
        $publicPath = '/'.FrontendAssetUrl::encodePath($path);

        // Create media record so it appears in media library / image SEO
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
