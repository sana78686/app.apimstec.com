<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\ContentManagerController;
use App\Http\Controllers\Controller;
use App\Models\AnalyticsSetting;
use App\Models\ContentManagerSetting;
use App\Services\GoogleSearchConsoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AnalyticsController extends Controller
{
    /**
     * SEO Analytics & Reports: Search Console metrics via API when OAuth + property URL are set.
     */
    public function index(GoogleSearchConsoleService $gsc): Response
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
        $searchConsoleLiveData = false;
        $searchConsoleLiveDataNote = '';
        $gscDateRange = null;
        $gscError = null;
        $gscConnected = $gsc->hasRefreshToken();
        $gscConnectedEmail = (string) ($settings['gsc_connected_email'] ?? '');
        $gscOAuthConfigured = $gsc->isConfigured();

        if ($gscConnected && $gscOAuthConfigured) {
            $prop = trim((string) ($settings['gsc_site_url'] ?? ''));
            if ($prop !== '') {
                try {
                    $data = $gsc->fetchDashboardData($prop);
                    $summary = [
                        'clicks' => $data['summary']['clicks'],
                        'impressions' => $data['summary']['impressions'],
                        'ctr' => $data['summary']['ctr'],
                        'position' => $data['summary']['position'],
                    ];
                    $topPages = $data['topPages'];
                    $topKeywords = $data['topQueries'];
                    $gscDateRange = $data['dateRange'];
                    $searchConsoleLiveData = true;
                    $searchConsoleLiveDataNote = sprintf(
                        'Figures are from Google Search Console (last ~28 days: %s → %s). Data can lag by a few days.',
                        $data['dateRange']['start'],
                        $data['dateRange']['end']
                    );
                } catch (Throwable $e) {
                    $gscError = $e->getMessage();
                    $searchConsoleLiveDataNote = 'Could not load Search Console data. Fix the error below or disconnect and reconnect.';
                }
            } else {
                $searchConsoleLiveDataNote = 'Google account is connected. Enter the exact Search Console property URL below (same as in GSC), save, then reload this page.';
            }
        } elseif ($gscOAuthConfigured) {
            $searchConsoleLiveDataNote = 'Use “Connect Google Search Console” to authorize read-only access. Each website (tenant) stores its own tokens.';
        } else {
            $searchConsoleLiveDataNote = 'Server admin must set GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_GSC_REDIRECT_URI in the CMS .env file.';
        }

        return Inertia::render('Seo/Analytics/Index', [
            'settings' => [
                'gsc_site_url' => (string) ($settings['gsc_site_url'] ?? ''),
                'ga_measurement_id' => (string) ($settings['ga_measurement_id'] ?? ''),
                'frontend_head_snippet' => ContentManagerSetting::get(ContentManagerController::KEY_HOME_FRONTEND_HEAD_SNIPPET, ''),
            ],
            'summary' => $summary,
            'topPages' => $topPages,
            'topKeywords' => $topKeywords,
            'searchConsoleLiveData' => $searchConsoleLiveData,
            'searchConsoleLiveDataNote' => $searchConsoleLiveDataNote,
            'gscDateRange' => $gscDateRange,
            'gscError' => $gscError,
            'gscConnected' => $gscConnected,
            'gscConnectedEmail' => $gscConnectedEmail,
            'gscOAuthConfigured' => $gscOAuthConfigured,
        ]);
    }

    /**
     * Update analytics integration settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'gsc_site_url' => ['nullable', 'string', 'max:500'],
            'ga_measurement_id' => ['nullable', 'string', 'max:50'],
            'frontend_head_snippet' => ['nullable', 'string', 'max:100000'],
        ]);

        AnalyticsSetting::setMany([
            'gsc_site_url' => $request->input('gsc_site_url'),
            'ga_measurement_id' => $request->input('ga_measurement_id'),
        ]);

        ContentManagerSetting::set(
            ContentManagerController::KEY_HOME_FRONTEND_HEAD_SNIPPET,
            $validated['frontend_head_snippet'] ?? ''
        );

        return back()->with('success', 'Analytics settings saved.');
    }
}
