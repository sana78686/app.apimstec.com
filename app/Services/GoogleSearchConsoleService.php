<?php

namespace App\Services;

use App\Models\AnalyticsSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * OAuth token storage + Search Analytics API (webmasters v3).
 */
class GoogleSearchConsoleService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const SEARCH_ANALYTICS_URL = 'https://www.googleapis.com/webmasters/v3/sites/%s/searchAnalytics/query';

    public function isConfigured(): bool
    {
        return (bool) (config('google.client_id') && config('google.client_secret') && config('google.gsc_redirect_uri'));
    }

    public function hasRefreshToken(): bool
    {
        $t = AnalyticsSetting::getValue('gsc_refresh_token_encrypted');

        return $t !== null && $t !== '';
    }

    /**
     * @return array{access_token: string, expires_at: ?int}
     */
    public function getAccessToken(): array
    {
        $encrypted = AnalyticsSetting::getValue('gsc_refresh_token_encrypted');
        if ($encrypted === null || $encrypted === '') {
            throw new RuntimeException('Google Search Console is not connected for this site.');
        }

        $expiresAt = AnalyticsSetting::getValue('gsc_token_expires_at');
        $cachedAccess = AnalyticsSetting::getValue('gsc_access_token_encrypted');

        if ($cachedAccess && $expiresAt && is_numeric($expiresAt) && (int) $expiresAt > time() + 60) {
            try {
                return [
                    'access_token' => decrypt($cachedAccess),
                    'expires_at' => (int) $expiresAt,
                ];
            } catch (\Throwable) {
                // fall through to refresh
            }
        }

        try {
            $refresh = decrypt($encrypted);
        } catch (\Throwable $e) {
            throw new RuntimeException('Stored Google token is invalid. Disconnect and reconnect Search Console.', 0, $e);
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => config('google.client_id'),
            'client_secret' => config('google.client_secret'),
            'refresh_token' => $refresh,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            Log::warning('gsc.token_refresh_failed', ['body' => $response->body()]);
            throw new RuntimeException('Could not refresh Google access token. Disconnect and connect Search Console again.');
        }

        $access = (string) $response->json('access_token', '');
        if ($access === '') {
            throw new RuntimeException('Google did not return an access token.');
        }

        $ttl = (int) $response->json('expires_in', 3600);
        $exp = time() + $ttl;

        AnalyticsSetting::setValue('gsc_access_token_encrypted', encrypt($access));
        AnalyticsSetting::setValue('gsc_token_expires_at', (string) $exp);

        return ['access_token' => $access, 'expires_at' => $exp];
    }

    /**
     * Normalize property to match Search Console (trim, ensure URL prefix has trailing slash).
     */
    public static function normalizeSiteUrl(string $raw): string
    {
        $s = trim($raw);
        if ($s === '') {
            return '';
        }
        if (str_starts_with(strtolower($s), 'sc-domain:')) {
            return $s;
        }
        if (str_starts_with($s, 'http://') || str_starts_with($s, 'https://')) {
            return rtrim($s, '/').'/';
        }

        return 'sc-domain:'.$s;
    }

    /**
     * @return array{rows: list<array<string, mixed>>}
     */
    public function searchAnalytics(string $siteUrl, array $body): array
    {
        $token = $this->getAccessToken()['access_token'];
        $encoded = rawurlencode($siteUrl);
        $url = sprintf(self::SEARCH_ANALYTICS_URL, $encoded);

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($url, $body);

        if ($response->status() === 401 || $response->status() === 403) {
            Log::warning('gsc.search_analytics_denied', ['status' => $response->status(), 'body' => $response->body()]);

            throw new RuntimeException('Google denied access to this Search Console property. Check that the connected Google account has access to the property URL you saved.');
        }

        if (! $response->successful()) {
            Log::warning('gsc.search_analytics_error', ['status' => $response->status(), 'body' => $response->body()]);

            throw new RuntimeException('Search Console API error: HTTP '.$response->status());
        }

        return $response->json() ?? ['rows' => []];
    }

    /**
     * @return array{summary: array{clicks: int, impressions: int, ctr: float|null, position: float|null}, topPages: list<array>, topQueries: list<array>, dateRange: array{start: string, end: string}}
     */
    public function fetchDashboardData(string $siteUrl): array
    {
        $siteUrl = self::normalizeSiteUrl($siteUrl);
        if ($siteUrl === '') {
            throw new RuntimeException('Set the Search Console property URL below before loading data.');
        }

        $end = Carbon::now()->subDay();
        $start = Carbon::now()->subDays(28);
        $startStr = $start->toDateString();
        $endStr = $end->toDateString();

        $basePayload = [
            'startDate' => $startStr,
            'endDate' => $endStr,
        ];

        $totals = $this->searchAnalytics($siteUrl, $basePayload);
        $row = $totals['rows'][0] ?? null;
        $summary = [
            'clicks' => (int) ($row['clicks'] ?? 0),
            'impressions' => (int) ($row['impressions'] ?? 0),
            'ctr' => isset($row['ctr']) ? round((float) $row['ctr'] * 100, 2) : null,
            'position' => isset($row['position']) ? round((float) $row['position'], 1) : null,
        ];

        $pagesResp = $this->searchAnalytics($siteUrl, array_merge($basePayload, [
            'dimensions' => ['page'],
            'rowLimit' => 25,
        ]));

        $topPages = [];
        foreach ($pagesResp['rows'] ?? [] as $r) {
            $keys = $r['keys'] ?? [];
            $page = is_array($keys) ? (string) ($keys[0] ?? '') : '';
            $topPages[] = [
                'page' => $page,
                'url' => $page,
                'clicks' => (int) ($r['clicks'] ?? 0),
                'impressions' => (int) ($r['impressions'] ?? 0),
            ];
        }

        $queriesResp = $this->searchAnalytics($siteUrl, array_merge($basePayload, [
            'dimensions' => ['query'],
            'rowLimit' => 25,
        ]));

        $topQueries = [];
        foreach ($queriesResp['rows'] ?? [] as $r) {
            $keys = $r['keys'] ?? [];
            $q = is_array($keys) ? (string) ($keys[0] ?? '') : '';
            $topQueries[] = [
                'query' => $q,
                'keyword' => $q,
                'clicks' => (int) ($r['clicks'] ?? 0),
                'impressions' => (int) ($r['impressions'] ?? 0),
            ];
        }

        return [
            'summary' => $summary,
            'topPages' => $topPages,
            'topQueries' => $topQueries,
            'dateRange' => ['start' => $startStr, 'end' => $endStr],
        ];
    }
}
