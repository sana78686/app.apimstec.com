<?php

namespace App\Http\Controllers;

use App\Models\HomeCard;
use App\Support\ContentLocales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CardsSectionController extends Controller
{
    public function index(): Response
    {
        $loc = ContentLocales::normalize(request()->session()->get('cms_locale'));
        $cards = HomeCard::where('locale', $loc)->ordered()->get();
        $iconOptions = HomeCard::iconOptions();

        return Inertia::render('ContentManager/CardsSection', [
            'cards' => $cards,
            'iconOptions' => $iconOptions,
            'flash' => ['success' => session('success')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:64',
        ]);

        $loc = ContentLocales::normalize($request->session()->get('cms_locale'));
        $maxOrder = HomeCard::where('locale', $loc)->max('sort_order') ?? 0;
        HomeCard::create([
            'locale' => $loc,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'icon' => $validated['icon'] ?? null,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('content-manager.cards')->with('success', 'Card added.');
    }

    public function update(Request $request, string $card): RedirectResponse
    {
        $model = HomeCard::findOrFail($card);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:64',
        ]);

        $model->update($validated);

        return redirect()->route('content-manager.cards')->with('success', 'Card updated.');
    }

    public function destroy(string $card): RedirectResponse
    {
        HomeCard::findOrFail($card)->delete();

        return redirect()->route('content-manager.cards')->with('success', 'Card removed.');
    }
}
