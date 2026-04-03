<?php

namespace App\Http\Controllers;

use App\Models\FaqItem;
use App\Support\ContentLocales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqSectionController extends Controller
{
    public function index(): Response
    {
        $loc = ContentLocales::normalize(request()->session()->get('cms_locale'));
        $items = FaqItem::where('locale', $loc)->ordered()->get();

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

        $loc = ContentLocales::normalize($request->session()->get('cms_locale'));
        $maxOrder = FaqItem::where('locale', $loc)->max('sort_order') ?? 0;
        FaqItem::create([
            'locale' => $loc,
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('content-manager.faq')->with('success', 'FAQ added.');
    }

    public function update(Request $request, string $faqItem): RedirectResponse
    {
        $model = FaqItem::findOrFail($faqItem);

        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:2000',
        ]);

        $model->update($validated);

        return redirect()->route('content-manager.faq')->with('success', 'FAQ updated.');
    }

    public function destroy(string $faqItem): RedirectResponse
    {
        FaqItem::findOrFail($faqItem)->delete();

        return redirect()->route('content-manager.faq')->with('success', 'FAQ removed.');
    }
}
