<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class VerifyOtpController extends Controller
{
    /**
     * Verify email using the 6-digit OTP code.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $cacheKey = 'verification_otp:' . $user->getKey();
        $cachedOtp = Cache::get($cacheKey);

        if (!$cachedOtp || $cachedOtp !== $request->code) {
            throw ValidationException::withMessages([
                'code' => ['The code is invalid or has expired.'],
            ]);
        }

        $user->markEmailAsVerified();
        Cache::forget($cacheKey);

        return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
    }
}
