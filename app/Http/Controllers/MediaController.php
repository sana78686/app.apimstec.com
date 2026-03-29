<?php

namespace App\Http\Controllers;

use App\Models\Media;
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

        $url = Storage::disk('public')->url($path);
        $relativePath = '/storage/' . $path;

        // Create media record so it appears in media library / image SEO
        Media::create([
            'path' => $relativePath,
            'filename' => $originalName,
            'alt_text' => $request->input('alt_text'),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return response()->json([
            'url' => $url,
            'path' => $path,
        ]);
    }
}
