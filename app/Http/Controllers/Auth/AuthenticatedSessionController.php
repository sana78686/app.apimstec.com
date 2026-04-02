<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\ContentLocales;
use App\Support\TenantEnvWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Clear any previously selected domain so the picker is always shown after login
        $request->session()->forget('active_domain_id');
        $this->clearTenantEnvFile();

        $loc = ContentLocales::normalize($request->session()->get('cms_locale'));

        return redirect()->intended(route('dashboard', ['cms_locale' => $loc], absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->clearTenantEnvFile();

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function clearTenantEnvFile(): void
    {
        try {
            TenantEnvWriter::forApplication()->removeTenantKeys();
        } catch (\Throwable $e) {
            Log::warning('Tenant .env sync failed: '.$e->getMessage());
        }
    }
}
