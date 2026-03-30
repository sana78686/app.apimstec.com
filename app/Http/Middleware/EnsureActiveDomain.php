<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CMS routes require a selected domain so the tenant connection targets that site's database.
 * Domain management (routes under domains.*) and profile stay on the master DB and skip this check.
 */
class EnsureActiveDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        if ($request->session()->get('active_domain_id')) {
            return $next($request);
        }

        if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
            return response()->json([
                'message' => 'Select a website (domain) before using the CMS.',
            ], 403);
        }

        return redirect()->route('domains.select');
    }
}
