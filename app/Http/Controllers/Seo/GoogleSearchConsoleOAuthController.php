<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsSetting;
use App\Services\GoogleSearchConsoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleSearchConsoleOAuthController extends Controller
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';

    public function redirect(Request $request, GoogleSearchConsoleService $gsc): RedirectResponse
    {
        if (! $gsc->isConfigured()) {
            return $this->backToAnalytics('error', 'Google OAuth is not configured on the server (missing GOOGLE_CLIENT_ID / secret / redirect URI).');
        }

        $state = Crypt::encryptString(json_encode([
            'uid' => $request->user()->id,
            'nonce' => Str::random(40),
        ], JSON_THROW_ON_ERROR));

        $params = http_build_query([
            'client_id' => config('google.client_id'),
            'redirect_uri' => config('google.gsc_redirect_uri'),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);

        return redirect()->away(self::AUTH_URL.'?'.$params);
    }

    public function callback(Request $request, GoogleSearchConsoleService $gsc): RedirectResponse
    {
        if ($request->filled('error')) {
            return $this->redirectWithFlash(
                'error',
                'Google authorization was denied or failed: '.(string) $request->query('error')
            );
        }

        $code = (string) $request->query('code', '');
        $state = (string) $request->query('state', '');
        if ($code === '' || $state === '') {
            return $this->redirectWithFlash(
                'error',
                'Invalid OAuth callback (missing code or state).'
            );
        }

        try {
            $payload = json_decode(Crypt::decryptString($state), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return $this->redirectWithFlash(
                'error',
                'Invalid OAuth state. Try connecting again.'
            );
        }

        if (! is_array($payload) || (int) ($payload['uid'] ?? 0) !== (int) $request->user()->id) {
            return $this->redirectWithFlash(
                'error',
                'OAuth state does not match your session. Try again.'
            );
        }

        if (! $gsc->isConfigured()) {
            return $this->redirectWithFlash('error', 'Google OAuth is not configured on the server.');
        }

        $tokenRes = Http::asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => config('google.client_id'),
            'client_secret' => config('google.client_secret'),
            'redirect_uri' => config('google.gsc_redirect_uri'),
            'grant_type' => 'authorization_code',
        ]);

        if (! $tokenRes->successful()) {
            return $this->redirectWithFlash('error', 'Could not exchange authorization code with Google.');
        }

        $refresh = $tokenRes->json('refresh_token');
        if (! is_string($refresh) || $refresh === '') {
            return $this->redirectWithFlash(
                'error',
                'Google did not return a refresh token. Revoke app access in your Google account settings and try again with prompt=consent (use “Disconnect” here first, then connect again).'
            );
        }

        AnalyticsSetting::setValue('gsc_refresh_token_encrypted', encrypt($refresh));
        AnalyticsSetting::forget('gsc_access_token_encrypted');
        AnalyticsSetting::forget('gsc_token_expires_at');

        $access = (string) $tokenRes->json('access_token', '');
        if ($access !== '') {
            $ttl = (int) $tokenRes->json('expires_in', 3600);
            AnalyticsSetting::setValue('gsc_access_token_encrypted', encrypt($access));
            AnalyticsSetting::setValue('gsc_token_expires_at', (string) (time() + $ttl));
        }

        $email = '';
        if ($access !== '') {
            $ui = Http::withToken($access)->get('https://www.googleapis.com/oauth2/v2/userinfo');
            if ($ui->successful()) {
                $email = (string) ($ui->json('email') ?? '');
            }
        }
        if ($email !== '') {
            AnalyticsSetting::setValue('gsc_connected_email', $email);
        }

        return redirect()->route('seo.analytics')->with('success', 'Google Search Console connected. Save your property URL below if needed, then reload to see data.');
    }

    public function disconnect(Request $request): RedirectResponse
    {
        AnalyticsSetting::forget('gsc_refresh_token_encrypted');
        AnalyticsSetting::forget('gsc_access_token_encrypted');
        AnalyticsSetting::forget('gsc_token_expires_at');
        AnalyticsSetting::forget('gsc_connected_email');

        return redirect()->route('seo.analytics')->with('success', 'Google Search Console disconnected for this website.');
    }

    private function backToAnalytics(string $flashKey, string $message): RedirectResponse
    {
        return redirect()->route('seo.analytics')->with($flashKey, $message);
    }

    private function redirectWithFlash(string $key, string $message): RedirectResponse
    {
        return redirect()->route('seo.analytics')->with($key, $message);
    }
}
