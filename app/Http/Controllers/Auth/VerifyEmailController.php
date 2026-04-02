<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\ContentLocales;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $loc = ContentLocales::normalize($request->session()->get('cms_locale'));
        $dash = route('dashboard', ['cms_locale' => $loc], absolute: false);

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($dash.'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended($dash.'?verified=1');
    }
}
