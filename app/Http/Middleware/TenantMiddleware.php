<?php

namespace App\Http\Middleware;

use App\Models\Domain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Switches the `tenant` database connection on every request.
 *
 * Priority order:
 *  1. Admin session  → session('active_domain_id')
 *  2. Public API     → X-Domain request header (e.g. "compresspdf.id")
 *  3. Fallback       → tenant connection stays pointing at master DB
 */
class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $domain = $this->resolveDomain($request);

        if ($domain) {
            $this->switchTenantConnection($domain);
            // Make the active domain available to controllers / Inertia
            app()->instance('active_domain', $domain);
        }

        return $next($request);
    }

    private function resolveDomain(Request $request): ?Domain
    {
        try {
            // 1. Admin session (web routes)
            $domainId = session('active_domain_id');
            if ($domainId) {
                return Domain::where('id', $domainId)->where('is_active', true)->first();
            }

            // 2. X-Domain header (public API routes from React frontends)
            $header = $request->header('X-Domain');
            if ($header) {
                return Domain::where('domain', $header)->where('is_active', true)->first();
            }
        } catch (\Throwable $e) {
            // Domains table may not exist yet (migration pending) — fail gracefully
        }

        return null;
    }

    private function switchTenantConnection(Domain $domain): void
    {
        // Override the tenant connection config at runtime
        config(['database.connections.tenant' => $domain->connectionConfig()]);

        // Purge any cached PDO instance and reconnect with new credentials
        DB::purge('tenant');
        DB::reconnect('tenant');
    }
}
