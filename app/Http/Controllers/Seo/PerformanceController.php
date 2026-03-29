<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\PerformanceSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;

class PerformanceController extends Controller
{
    /**
     * Performance & Speed: caching, minification, lazy loading, CDN.
     */
    public function index(): Response
    {
        $settings = PerformanceSetting::getAll();

        return Inertia::render('Seo/Performance/Index', [
            'settings' => [
                'cache_ttl' => (int) ($settings['cache_ttl'] ?? 3600),
                'minify_html' => (bool) (int) ($settings['minify_html'] ?? 0),
                'lazy_load_images' => (bool) (int) ($settings['lazy_load_images'] ?? 1),
                'cdn_base_url' => (string) ($settings['cdn_base_url'] ?? ''),
            ],
        ]);
    }

    /**
     * Update performance settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'cache_ttl' => ['nullable', 'integer', 'min:0', 'max:31536000'],
            'minify_html' => ['nullable', 'boolean'],
            'lazy_load_images' => ['nullable', 'boolean'],
            'cdn_base_url' => ['nullable', 'string', 'max:500'],
        ]);

        PerformanceSetting::setMany([
            'cache_ttl' => (string) ($request->input('cache_ttl') ?? 0),
            'minify_html' => $request->boolean('minify_html') ? '1' : '0',
            'lazy_load_images' => $request->boolean('lazy_load_images') ? '1' : '0',
            'cdn_base_url' => $request->input('cdn_base_url') ?? '',
        ]);

        return redirect()->route('seo.performance')->with('success', 'Performance settings saved. Page speed and rankings can improve with caching, lazy loading, and CDN.');
    }

    /**
     * Clear application cache (config, route, view, general cache).
     */
    public function clearCache(): JsonResponse
    {
        Artisan::call('cache:clear');
        PerformanceSetting::clearCache();

        return response()->json([
            'message' => 'Application cache cleared.',
        ]);
    }
}
