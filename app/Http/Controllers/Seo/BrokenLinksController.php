<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\BrokenLinkLog;
use App\Models\Redirect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BrokenLinksController extends Controller
{
    /**
     * Broken Link & Error Monitor: list 404s and create 301 redirects to prevent SEO loss.
     */
    public function index(): Response
    {
        $logs = BrokenLinkLog::unresolved()
            ->orderByDesc('last_seen_at')
            ->get(['id', 'path', 'hit_count', 'referer', 'first_seen_at', 'last_seen_at'])
            ->map(fn ($log) => [
                'id' => $log->id,
                'path' => $log->path,
                'hit_count' => $log->hit_count,
                'referer' => $log->referer,
                'first_seen_at' => $log->first_seen_at?->toIso8601String(),
                'last_seen_at' => $log->last_seen_at?->toIso8601String(),
            ]);

        $redirects = Redirect::orderBy('from_path')->get(['id', 'from_path', 'to_path', 'status_code'])->map(fn ($r) => [
            'id' => $r->id,
            'from_path' => $r->from_path,
            'to_path' => $r->to_path,
            'status_code' => $r->status_code,
        ]);

        return Inertia::render('Seo/BrokenLinks/Index', [
            'brokenLinks' => $logs,
            'redirects' => $redirects,
        ]);
    }

    /**
     * Create a 301 redirect from a broken path to a target path; mark the log as resolved.
     */
    public function createRedirect(Request $request): JsonResponse
    {
        $request->validate([
            'from_path' => ['required', 'string', 'max:191'],
            'to_path' => ['required', 'string', 'max:500'],
        ]);

        $fromPath = trim($request->input('from_path'), '/');
        $toPath = trim($request->input('to_path'), '/');
        if ($fromPath === '') {
            return response()->json(['message' => 'From path is required.'], 422);
        }
        if ($toPath === '') {
            return response()->json(['message' => 'To path is required.'], 422);
        }
        if ($fromPath === $toPath) {
            return response()->json(['message' => 'From and to path must be different.'], 422);
        }

        if (Redirect::where('from_path', $fromPath)->exists()) {
            return response()->json(['message' => 'A redirect from this path already exists.'], 422);
        }

        Redirect::create([
            'from_path' => $fromPath,
            'to_path' => $toPath,
            'status_code' => 301,
        ]);

        BrokenLinkLog::where('path', $fromPath)->get()->each->markResolved();

        return response()->json([
            'message' => '301 redirect created. Future requests to the broken link will be redirected.',
        ]);
    }

    /**
     * Dismiss a broken link log without creating a redirect (e.g. spam or irrelevant).
     */
    public function dismiss(Request $request): JsonResponse
    {
        $request->validate(['id' => ['required', 'integer', Rule::exists(BrokenLinkLog::class, 'id')]]);

        $log = BrokenLinkLog::findOrFail($request->id);
        $log->markResolved();

        return response()->json(['message' => 'Log dismissed.']);
    }
}
