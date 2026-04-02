<?php

namespace App\Http\Controllers;

use App\Support\ContentLocales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CmsLocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'locale' => ['required', 'string', Rule::in(ContentLocales::SUPPORTED)],
        ]);
        $request->session()->put('cms_locale', ContentLocales::normalize($request->input('locale')));

        return back();
    }
}
