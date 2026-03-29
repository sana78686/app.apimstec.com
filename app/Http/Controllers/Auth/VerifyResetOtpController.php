<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VerifyResetOtpController extends Controller
{
    /**
     * Show the verify reset OTP page (email from session).
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $email = session('password_reset_email');
        if (!$email) {
            return redirect()->route('password.request');
        }
        return Inertia::render('Auth/VerifyResetOtp', ['email' => $email]);
    }

    /**
     * Verify OTP and redirect to reset password with token.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $cacheKey = 'password_reset_otp:' . $request->email;
        $cachedOtp = Cache::get($cacheKey);

        if (!$cachedOtp || $cachedOtp !== $request->code) {
            throw ValidationException::withMessages([
                'code' => ['The code is invalid or has expired.'],
            ]);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return redirect()->route('password.request');
        }

        $token = Password::createToken($user);
        Cache::forget($cacheKey);

        return redirect()->route('password.reset', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }
}
