<?php

namespace App\Http\Controllers;

use App\Models\ContentSection;
use App\Models\ContentSectionItem;
use App\Support\ContentLocales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ContentSectionController extends Controller
{
    public function index(Request $request): Response
    {
        $loc = ContentLocales::normalize($request->session()->get('cms_locale'));
        $codes = ContentLocales::publicFilterLocaleCodes();
        $sections = ContentSection::query()
            ->whereIn('locale', $codes)
            ->with('items')
            ->ordered()
            ->get();

        return Inertia::render('ContentManager/Sections', [
            'sections' => $sections,
            'cmsLocale' => $loc,
            'localeFilterOptions' => ContentLocales::publicFilterSegmentOptions(),
            'flash' => ['success' => session('success')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(ContentLocales::SUPPORTED)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'layout' => ['nullable', 'string', Rule::in(['cards', 'paragraphs', 'mixed'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $loc = ContentLocales::normalize($validated['locale']);
        $maxOrder = ContentSection::query()->where('locale', $loc)->max('sort_order') ?? 0;
        ContentSection::create([
            'locale' => $loc,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'layout' => $validated['layout'] ?? 'cards',
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) $maxOrder + 1,
        ]);

        ContentManagerController::bumpPublicApiCacheGeneration();

        return redirect()->route('content-manager.sections')->with('success', 'Section added.');
    }

    public function update(Request $request, string $section): RedirectResponse
    {
        $model = ContentSection::query()->findOrFail($section);
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(ContentLocales::SUPPORTED)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'layout' => ['nullable', 'string', Rule::in(['cards', 'paragraphs', 'mixed'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $model->update([
            'locale' => ContentLocales::normalize($validated['locale']),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'layout' => $validated['layout'] ?? 'cards',
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        ContentManagerController::bumpPublicApiCacheGeneration();

        return redirect()->route('content-manager.sections')->with('success', 'Section updated.');
    }

    public function destroy(string $section): RedirectResponse
    {
        ContentSection::query()->findOrFail($section)->delete();
        ContentManagerController::bumpPublicApiCacheGeneration();

        return redirect()->route('content-manager.sections')->with('success', 'Section removed.');
    }

    public function storeItem(Request $request, string $section): RedirectResponse
    {
        $model = ContentSection::query()->findOrFail($section);
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:65535'],
            'item_type' => ['nullable', 'string', Rule::in(['card', 'paragraph'])],
            'media_type' => ['nullable', 'string', Rule::in(['none', 'number', 'icon', 'image'])],
            'media_value' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $maxOrder = ContentSectionItem::query()->where('content_section_id', $model->id)->max('sort_order') ?? 0;
        ContentSectionItem::create([
            'content_section_id' => $model->id,
            'title' => $validated['title'] ?? '',
            'body' => $validated['body'] ?? '',
            'item_type' => $validated['item_type'] ?? 'card',
            'media_type' => $validated['media_type'] ?? 'none',
            'media_value' => $validated['media_value'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) $maxOrder + 1,
        ]);

        ContentManagerController::bumpPublicApiCacheGeneration();

        return redirect()->route('content-manager.sections')->with('success', 'Section item added.');
    }

    public function updateItem(Request $request, string $item): RedirectResponse
    {
        $model = ContentSectionItem::query()->findOrFail($item);
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:65535'],
            'item_type' => ['nullable', 'string', Rule::in(['card', 'paragraph'])],
            'media_type' => ['nullable', 'string', Rule::in(['none', 'number', 'icon', 'image'])],
            'media_value' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $model->update([
            'title' => $validated['title'] ?? '',
            'body' => $validated['body'] ?? '',
            'item_type' => $validated['item_type'] ?? 'card',
            'media_type' => $validated['media_type'] ?? 'none',
            'media_value' => $validated['media_value'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        ContentManagerController::bumpPublicApiCacheGeneration();

        return redirect()->route('content-manager.sections')->with('success', 'Section item updated.');
    }

    public function destroyItem(string $item): RedirectResponse
    {
        ContentSectionItem::query()->findOrFail($item)->delete();
        ContentManagerController::bumpPublicApiCacheGeneration();

        return redirect()->route('content-manager.sections')->with('success', 'Section item removed.');
    }
}

