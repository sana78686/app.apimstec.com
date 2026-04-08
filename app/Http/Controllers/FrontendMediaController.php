<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FrontendMediaController extends Controller
{
    /** Folder inside each React/Vite `public/` directory. */
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

    /** Stable folder/file prefix from tenant domain (e.g. compresspdf.id → compresspdf-id). */
    public static function domainSegment(Domain $domain): string
    {
        $host = strtolower(trim((string) $domain->domain));
        $host = preg_replace('#^www\.#i', '', $host) ?? $host;
        $host = preg_replace('/[^a-z0-9._-]+/i', '-', $host) ?? $host;
        $host = trim($host, '-.');

        return $host !== '' ? $host : 'site';
    }

    private function uploadDirForDomain(Domain $domain): string
    {
        $segment = self::domainSegment($domain);

        return $this->publicRoot().DIRECTORY_SEPARATOR.self::RELATIVE_DIR.DIRECTORY_SEPARATOR.$segment;
    }

    private function resolveDomain(Request $request): ?Domain
    {
        $id = $request->session()->get('active_domain_id');

        return $id ? Domain::find($id) : null;
    }

    private function ensureTenantDir(string $dir): void
    {
        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
    }

    /** Public URL path segment for marketing site (leading slash, no origin). */
    private function publicPathForFile(Domain $domain, string $filename): string
    {
        $segment = self::domainSegment($domain);

        return '/'.self::RELATIVE_DIR.'/'.$segment.'/'.basename($filename);
    }

    private function fullPath(Domain $domain, string $filename): string
    {
        $dir = $this->uploadDirForDomain($domain);
        $base = basename($filename);

        return $dir.DIRECTORY_SEPARATOR.$base;
    }

    private function fileExistsInTenant(Domain $domain, string $filename): bool
    {
        $path = $this->fullPath($domain, $filename);

        return $path === realpath($path) && is_file($path);
    }

    private function uniqueFilename(string $dir, string $segment, string $slug, string $ext): string
    {
        $slug = $slug !== '' ? $slug : 'image';
        for ($i = 0; $i < 30; $i += 1) {
            $name = $segment.'-'.$slug.'-'.Str::lower(Str::random(6)).'.'.$ext;
            $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $name) ?: $segment.'-'.Str::lower(Str::random(8)).'.'.$ext;
            if (! File::exists($dir.DIRECTORY_SEPARATOR.$name)) {
                return $name;
            }
        }

        return $segment.'-'.$slug.'-'.Str::lower(Str::random(10)).'.'.$ext;
    }

    public function index(Request $request): InertiaResponse
    {
        $domain = $this->resolveDomain($request);
        if (! $domain instanceof Domain) {
            return Inertia::render('Media/Index', [
                'mediaItems' => [],
                'publicSiteBaseUrl' => '',
                'uploadSubdir' => self::RELATIVE_DIR,
                'domainSegment' => '',
                'tenantDomain' => '',
                'requiresDomain' => true,
            ]);
        }

        $dir = $this->uploadDirForDomain($domain);
        $this->ensureTenantDir($dir);

        $files = collect(File::isDirectory($dir) ? File::files($dir) : [])
            ->filter(fn (\SplFileInfo $f) => (bool) preg_match('/\.(jpe?g|png|gif|webp|svg|avif)$/i', $f->getFilename()))
            ->sortByDesc(fn (\SplFileInfo $f) => $f->getMTime())
            ->values()
            ->map(fn (\SplFileInfo $f) => [
                'name' => $f->getFilename(),
                'path' => $this->publicPathForFile($domain, $f->getFilename()),
                'updated' => $f->getMTime(),
            ])
            ->all();

        $baseUrl = $domain->publicSiteBaseUrl();
        if ($baseUrl === '') {
            $baseUrl = rtrim((string) env('FRONTEND_PREVIEW_URL', ''), '/');
        }

        return Inertia::render('Media/Index', [
            'mediaItems' => $files,
            'publicSiteBaseUrl' => $baseUrl,
            'uploadSubdir' => self::RELATIVE_DIR,
            'domainSegment' => self::domainSegment($domain),
            'tenantDomain' => $domain->domain,
            'requiresDomain' => false,
        ]);
    }

    /**
     * Authenticated thumbnail/original preview from disk (fixes broken &lt;img&gt; when the marketing host
     * does not yet serve the same files as the CMS upload path).
     */
    public function preview(Request $request, string $filename): BinaryFileResponse
    {
        $domain = $this->resolveDomain($request);
        if (! $domain instanceof Domain) {
            abort(403);
        }

        $filename = basename($filename);
        if (! preg_match('/\.(jpe?g|png|gif|webp|svg|avif)$/i', $filename)) {
            abort(404);
        }

        $path = $this->fullPath($domain, $filename);
        if (! is_file($path)) {
            abort(404);
        }

        $real = realpath($path);
        $dirReal = realpath($this->uploadDirForDomain($domain));
        if ($real === false || $dirReal === false || ! str_starts_with($real, $dirReal)) {
            abort(404);
        }

        return response()->file($real, [
            'Content-Type' => File::mimeType($real) ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $domain = $this->resolveDomain($request);
        if (! $domain instanceof Domain) {
            return back()->with('error', 'Choose a website before uploading.');
        }

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:5120',
                'mimes:jpg,jpeg,png,gif,webp,svg,avif',
            ],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        $dir = $this->uploadDirForDomain($domain);
        $this->ensureTenantDir($dir);

        $segment = self::domainSegment($domain);
        $uploaded = $request->file('file');
        $ext = strtolower((string) ($uploaded->getClientOriginalExtension() ?: $uploaded->extension() ?: 'bin'));
        $base = pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = $request->filled('label')
            ? Str::slug((string) $request->input('label'))
            : (Str::slug((string) $base) ?: 'image');
        $name = $this->uniqueFilename($dir, $segment, $slug, $ext);

        $uploaded->move($dir, $name);

        return back()->with('success', 'Image saved for '.$domain->domain.' ('.$name.').');
    }

    public function update(Request $request, string $filename): RedirectResponse
    {
        $domain = $this->resolveDomain($request);
        if (! $domain instanceof Domain) {
            return back()->with('error', 'Choose a website first.');
        }

        $filename = basename($filename);
        if (! $this->fileExistsInTenant($domain, $filename)) {
            return back()->with('error', 'File not found.');
        }

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:5120',
                'mimes:jpg,jpeg,png,gif,webp,svg,avif',
            ],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        $dir = $this->uploadDirForDomain($domain);
        $segment = self::domainSegment($domain);
        $oldPath = $this->fullPath($domain, $filename);

        $uploaded = $request->file('file');
        $ext = strtolower((string) ($uploaded->getClientOriginalExtension() ?: $uploaded->extension() ?: 'bin'));
        $base = pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = $request->filled('label')
            ? Str::slug((string) $request->input('label'))
            : (Str::slug((string) $base) ?: 'image');
        $newName = $this->uniqueFilename($dir, $segment, $slug, $ext);

        $uploaded->move($dir, $newName);
        File::delete($oldPath);

        return back()->with('success', 'Image replaced. Old file removed. New name: '.$newName);
    }

    public function rename(Request $request, string $filename): RedirectResponse
    {
        $domain = $this->resolveDomain($request);
        if (! $domain instanceof Domain) {
            return back()->with('error', 'Choose a website first.');
        }

        $filename = basename($filename);
        if (! $this->fileExistsInTenant($domain, $filename)) {
            return back()->with('error', 'File not found.');
        }

        $request->validate([
            'label' => ['required', 'string', 'max:120'],
        ]);

        $dir = $this->uploadDirForDomain($domain);
        $segment = self::domainSegment($domain);
        $oldPath = $this->fullPath($domain, $filename);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION) ?: 'bin');
        $slug = Str::slug((string) $request->input('label')) ?: 'image';
        $newName = $this->uniqueFilename($dir, $segment, $slug, $ext);

        if (! @File::move($oldPath, $dir.DIRECTORY_SEPARATOR.$newName)) {
            return back()->with('error', 'Could not rename file.');
        }

        return back()->with('success', 'Renamed to '.$newName);
    }

    public function destroy(Request $request, string $filename): RedirectResponse
    {
        $domain = $this->resolveDomain($request);
        if (! $domain instanceof Domain) {
            return back()->with('error', 'Choose a website first.');
        }

        $filename = basename($filename);
        if (! $this->fileExistsInTenant($domain, $filename)) {
            return back()->with('error', 'File not found.');
        }

        File::delete($this->fullPath($domain, $filename));

        return back()->with('success', 'Image deleted.');
    }
}
