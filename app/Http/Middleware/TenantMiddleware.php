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
            // 1. Domain in URL path (must beat CMS session so /{domain}/sitemap.xml & API stay correct)
            $route = $request->route();
            if ($route && $route->hasParameter('site_domain')) {
                $siteDomain = $route->parameter('site_domain');
                if (is_string($siteDomain) && $siteDomain !== '') {
                    $seoByPath = $request->routeIs('sitemap.by-domain', 'robots.by-domain');
                    $fromPath = $this->resolveDomainFromHostHeader($siteDomain, $seoByPath);
                    if ($fromPath) {
                        return $fromPath;
                    }
                }
            }

            // 2. Admin session (CMS workspace)
            $domainId = session('active_domain_id');
            if ($domainId) {
                return Domain::where('id', $domainId)->where('is_active', true)->first();
            }

            // 3. Public hostname for /sitemap.xml and /robots.txt (crawler hits live site host)
            if ($this->isRootSeoFileRequest($request)) {
                $fromHost = $this->resolveDomainFromHostHeader($request->getHost(), true);
                if ($fromHost) {
                    return $fromHost;
                }
            }

            // 4. X-Domain header (legacy /api/public/* clients)
            $header = $request->header('X-Domain');
            if ($header) {
                return $this->resolveDomainFromHostHeader($header, false);
            }
        } catch (\Throwable $e) {
            // Domains table may not exist yet (migration pending) — fail gracefully
        }

        return null;
    }

    private function isRootSeoFileRequest(Request $request): bool
    {
        $p = $request->path();

        return $p === 'sitemap.xml' || $p === 'robots.txt';
    }

    /**
     * Match `domains.domain` even if React sends www. prefix or different casing.
     *
     * @param  bool  $inactiveFallbackForPublicSeo  If true, allow inactive domains when config allows (sitemap/robots only).
     */
    private function resolveDomainFromHostHeader(string $header, bool $inactiveFallbackForPublicSeo = false): ?Domain
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

        $active = Domain::query()
            ->where('is_active', true)
            ->whereIn('domain', $candidates)
            ->first();

        if ($active) {
            return $active;
        }

        if ($inactiveFallbackForPublicSeo && config('seo.allow_inactive_domains_for_sitemap_robots', true)) {
            return Domain::query()
                ->whereIn('domain', $candidates)
                ->first();
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

        // CMS content must use the site DB; only explicit `mysql` models touch the registry.
        config(['database.default' => 'tenant']);
    }
}
