<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FrontendMediaController extends Controller
{
    /** Folder inside the React/Vite `public/` directory (single copy; served by the marketing site). */
    public const RELATIVE_DIR = 'cms-uploads';

    private function publicRoot(): string
    {
        $path = (string) env('FRONTEND_PUBLIC_PATH', '');
        $path = $path !== '' ? $path : (string) realpath(base_path('../public'));
        if ($path !== '' && is_dir($path)) {
            return rtrim($path, DIRECTORY_SEPARATOR);
        }

        return rtrim(public_path(), DIRECTORY_SEPARATOR);
    }

    private function uploadDir(): string
    {
        return $this->publicRoot().DIRECTORY_SEPARATOR.self::RELATIVE_DIR;
    }

    public function index(Request $request): Response
    {
        $dir = $this->uploadDir();
        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $files = collect(File::isDirectory($dir) ? File::files($dir) : [])
            ->filter(fn (\SplFileInfo $f) => (bool) preg_match('/\.(jpe?g|png|gif|webp|svg|avif)$/i', $f->getFilename()))
            ->sortByDesc(fn (\SplFileInfo $f) => $f->getMTime())
            ->values()
            ->map(fn (\SplFileInfo $f) => [
                'name' => $f->getFilename(),
                'path' => '/'.self::RELATIVE_DIR.'/'.$f->getFilename(),
                'updated' => $f->getMTime(),
            ])
            ->all();

        $activeDomainId = $request->session()->get('active_domain_id');
        $domain = $activeDomainId ? Domain::find($activeDomainId) : null;
        $baseUrl = $domain ? $domain->publicSiteBaseUrl() : '';
        if ($baseUrl === '') {
            $baseUrl = rtrim((string) env('FRONTEND_PREVIEW_URL', ''), '/');
        }

        return Inertia::render('Media/Index', [
            'mediaItems' => $files,
            'publicSiteBaseUrl' => $baseUrl,
            'uploadSubdir' => self::RELATIVE_DIR,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:5120',
                'mimes:jpg,jpeg,png,gif,webp,svg,avif',
            ],
        ]);

        $dir = $this->uploadDir();
        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $uploaded = $request->file('file');
        $ext = strtolower((string) ($uploaded->getClientOriginalExtension() ?: $uploaded->extension() ?: 'bin'));
        $base = pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = Str::slug((string) $base) ?: 'image';
        $name = $slug.'-'.Str::lower(Str::random(6)).'.'.$ext;
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name) ?: 'image-'.Str::lower(Str::random(8)).'.'.$ext;

        $uploaded->move($dir, $name);

        return back()->with('success', 'Image saved to the frontend public folder.');
    }
}
