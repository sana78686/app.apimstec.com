<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ResetPasswordOtp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetOtpController extends Controller
{
    /**
     * Show the form to request a password reset OTP (email is on ForgotPassword; we redirect here after submit).
     * Actually we send OTP from ForgotPassword with a second action. So this controller only handles sending.
     */

    /**
     * Send password reset OTP to the given email.
     */
    public function store(Request $request): RedirectResponse|Response
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => [__('We can\'t find a user with that email address.')],
            ]);
        }

        $otp = (string) random_int(100000, 999999);
        Cache::put('password_reset_otp:' . $request->email, $otp, now()->addMinutes(15));

        $user->notify(new ResetPasswordOtp($otp));

        return redirect()->route('password.verify-otp')->with('password_reset_email', $request->email);
    }
}
