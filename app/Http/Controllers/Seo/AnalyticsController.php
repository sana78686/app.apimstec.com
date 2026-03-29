<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    /**
     * SEO Analytics & Reports: track clicks, impressions, top pages & keywords.
     * Integrates with Google Search Console & Google Analytics (settings stored; data can be fetched via API later).
     */
    public function index(): Response
    {
        $settings = AnalyticsSetting::getAll();

        $summary = [
            'clicks' => null,
            'impressions' => null,
            'ctr' => null,
            'position' => null,
        ];

        $topPages = [];
        $topKeywords = [];

        return Inertia::render('Seo/Analytics/Index', [
            'settings' => [
                'gsc_site_url' => (string) ($settings['gsc_site_url'] ?? ''),
                'ga_measurement_id' => (string) ($settings['ga_measurement_id'] ?? ''),
            ],
            'summary' => $summary,
            'topPages' => $topPages,
            'topKeywords' => $topKeywords,
        ]);
    }

    /**
     * Update analytics integration settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'gsc_site_url' => ['nullable', 'string', 'max:500'],
            'ga_measurement_id' => ['nullable', 'string', 'max:50'],
        ]);

        AnalyticsSetting::setMany([
            'gsc_site_url' => $request->input('gsc_site_url'),
            'ga_measurement_id' => $request->input('ga_measurement_id'),
        ]);

        return redirect()->route('seo.analytics')->with('success', 'Analytics settings saved.');
    }
}
