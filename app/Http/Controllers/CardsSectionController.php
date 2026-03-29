<?php

namespace App\Http\Controllers;

use App\Models\HomeCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CardsSectionController extends Controller
{
    public function index(): Response
    {
        $cards = HomeCard::ordered()->get();
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

        $maxOrder = HomeCard::max('sort_order') ?? 0;
        HomeCard::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'icon' => $validated['icon'] ?? null,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('content-manager.cards')->with('success', 'Card added.');
    }

    public function update(Request $request, HomeCard $card): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:64',
        ]);

        $card->update($validated);

        return redirect()->route('content-manager.cards')->with('success', 'Card updated.');
    }

    public function destroy(HomeCard $card): RedirectResponse
    {
        $card->delete();

        return redirect()->route('content-manager.cards')->with('success', 'Card removed.');
    }
}
