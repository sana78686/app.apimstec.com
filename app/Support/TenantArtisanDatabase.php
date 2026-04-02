<?php

namespace App\Support;

use App\Models\Domain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Points Laravel's `tenant` connection at a domain's database for Artisan migrate/rollback,
 * syncs CMS_TENANT_* in .env, then restores after the operation.
 */
class TenantArtisanDatabase
{
    public const CONNECTION = 'tenant';

    /**
     * Per-site DB: content (pages, blogs, redirects, …), domain_credentials, and a mirror of
     * core Laravel / CMS tables (users, roles, cache, jobs, domains shape, …). The app still
     * uses the `mysql` connection for real logins and the domain registry; run `php artisan migrate`
     * on the master for that. See database/migrations/tenant/2025_01_01_000000_*.
     */
    public const TENANT_MIGRATIONS_PATH = 'database/migrations/tenant';

    /**
     * Options for migrate / migrate:rollback on the tenant connection only.
     *
     * @return array<string, mixed>
     */
    public static function tenantMigrateOptions(): array
    {
        return [
            '--database' => self::CONNECTION,
            '--path'     => self::TENANT_MIGRATIONS_PATH,
            '--force'    => true,
        ];
    }

    public static function syncEnvToFile(?Domain $domain): void
    {
        try {
            $writer = TenantEnvWriter::forApplication();
            if ($domain) {
                $writer->writeFromDomain($domain);
            } else {
                $writer->removeTenantKeys();
            }
        } catch (\Throwable $e) {
            Log::warning('Tenant .env sync failed: '.$e->getMessage());
        }
    }

    /**
     * @throws \InvalidArgumentException|\RuntimeException
     */
    public static function prepare(Domain $domain): void
    {
        if ($domain->targetsMasterDatabase()) {
            throw new \InvalidArgumentException(
                'This domain uses the same database as the CMS master. Schema actions run only on each website’s own database — create a separate DB in Plesk for this site.'
            );
        }

        self::syncEnvToFile($domain);

        config(['database.connections.'.self::CONNECTION => $domain->connectionConfig()]);
        DB::purge(self::CONNECTION);
        DB::reconnect(self::CONNECTION);

        $resolved = DB::connection(self::CONNECTION)->getDatabaseName();
        if ($resolved !== $domain->db_name) {
            DB::purge(self::CONNECTION);
            throw new \RuntimeException(
                'Could not verify target database (expected "'.$domain->db_name.'", got "'.$resolved.'").'
            );
        }
    }

    public static function restore(): void
    {
        DB::purge(self::CONNECTION);

        $domainId = session('active_domain_id');
        if ($domainId && ($d = Domain::where('id', $domainId)->where('is_active', true)->first())) {
            config(['database.connections.'.self::CONNECTION => $d->connectionConfig()]);
        } else {
            config(['database.connections.'.self::CONNECTION => self::defaultFromEnv()]);
        }

        DB::reconnect(self::CONNECTION);
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultFromEnv(): array
    {
        return [
            'driver'    => 'mysql',
            'host'      => env('CMS_TENANT_HOST', env('DB_HOST', '127.0.0.1')),
            'port'      => env('CMS_TENANT_PORT', env('DB_PORT', '3306')),
            'database'  => env('CMS_TENANT_DATABASE', env('DB_DATABASE', 'laravel')),
            'username'  => env('CMS_TENANT_USERNAME', env('DB_USERNAME', 'root')),
            'password'  => env('CMS_TENANT_PASSWORD', env('DB_PASSWORD', '')),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ];
    }

    public static function label(Domain $domain): string
    {
        return $domain->db_host.' / '.$domain->db_name;
    }
}
