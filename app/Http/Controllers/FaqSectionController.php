<?php

namespace App\Http\Controllers;

use App\Models\FaqItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqSectionController extends Controller
{
    public function index(): Response
    {
        $items = FaqItem::ordered()->get();

        return Inertia::render('ContentManager/FaqSection', [
            'faqItems' => $items,
            'flash' => ['success' => session('success')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:2000',
        ]);

        $maxOrder = FaqItem::max('sort_order') ?? 0;
        FaqItem::create([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('content-manager.faq')->with('success', 'FAQ added.');
    }

    public function update(Request $request, FaqItem $faqItem): RedirectResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:2000',
        ]);

        $faqItem->update($validated);

        return redirect()->route('content-manager.faq')->with('success', 'FAQ updated.');
    }

    public function destroy(FaqItem $faqItem): RedirectResponse
    {
        $faqItem->delete();

        return redirect()->route('content-manager.faq')->with('success', 'FAQ removed.');
    }
}
