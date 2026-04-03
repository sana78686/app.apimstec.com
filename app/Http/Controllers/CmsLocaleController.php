<?php

namespace App\Http\Controllers;

use App\Support\ContentLocales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

class CmsLocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'locale' => ['required', 'string', Rule::in(ContentLocales::SUPPORTED)],
        ]);
        $locale = ContentLocales::normalize($request->input('locale'));
        $request->session()->put('cms_locale', $locale);
        Cookie::queue(
            'cms_locale_pref',
            $locale,
            60 * 24 * 365,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax'
        );

        return back();
    }
}
