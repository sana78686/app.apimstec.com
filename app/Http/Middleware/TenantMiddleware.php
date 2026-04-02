<?php

namespace App\Http\Middleware;

use App\Models\Domain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Switches the `tenant` database connection on each request.
 *
 * Registered on the `web` stack (after StartSession) so session('active_domain_id') is
 * available for the CMS. Registered on `api` for domain-in-URL public routes and X-Domain.
 *
 * Priority:
 *  1. Admin session → session('active_domain_id')
 *  2. Public API    → route parameter site_domain (e.g. /compresspdf.id/api/public/...)
 *  3. Public API    → X-Domain header (legacy / tools)
 *  4. Fallback      → env CMS_TENANT_* or DB_* (only before a domain is resolved)
 *
 * When a domain is resolved, `database.default` is set to `tenant` so CMS code never
 * accidentally hits the registry DB. User, Role, Permission, Domain models keep
 * `protected $connection = 'mysql'`. Sessions use connection `mysql` (see config/session.php).
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

            // 2. Domain in URL: /{site_domain}/api/public/...
            $route = $request->route();
            if ($route && $route->hasParameter('site_domain')) {
                $siteDomain = $route->parameter('site_domain');
                if (is_string($siteDomain) && $siteDomain !== '') {
                    $fromPath = $this->resolveDomainFromHostHeader($siteDomain);
                    if ($fromPath) {
                        return $fromPath;
                    }
                }
            }

            // 3. X-Domain header (legacy clients, curl, build tools)
            $header = $request->header('X-Domain');
            if ($header) {
                return $this->resolveDomainFromHostHeader($header);
            }
        } catch (\Throwable $e) {
            // Domains table may not exist yet (migration pending) — fail gracefully
        }

        return null;
    }

    /**
     * Match `domains.domain` even if React sends www. prefix or different casing.
     */
    private function resolveDomainFromHostHeader(string $header): ?Domain
    {
        $raw = strtolower(trim($header));
        if ($raw === '') {
            return null;
        }

        $host = preg_replace('#:\d+$#', '', $raw) ?? $raw;
        $candidates = array_unique(array_filter([
            $host,
            str_starts_with($host, 'www.') ? substr($host, 4) : 'www.'.$host,
        ]));

        return Domain::query()
            ->where('is_active', true)
            ->whereIn('domain', $candidates)
            ->first();
    }

    private function switchTenantConnection(Domain $domain): void
    {
        // Override the tenant connection config at runtime
        config(['database.connections.tenant' => $domain->connectionConfig()]);

        // Purge any cached PDO instance and reconnect with new credentials
        DB::purge('tenant');
        DB::reconnect('tenant');

        // CMS content must use the site DB; only explicit `mysql` models touch the registry.
        config(['database.default' => 'tenant']);
    }
}
