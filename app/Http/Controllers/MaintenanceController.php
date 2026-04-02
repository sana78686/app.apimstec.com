<?php

namespace App\Http\Controllers;

use App\Models\Domain;
use App\Support\TenantArtisanDatabase;
use Database\Seeders\TenantDatabaseSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

class MaintenanceController extends Controller
{
    private function activeDomain(): Domain
    {
        $id = session('active_domain_id');

        return Domain::where('id', $id)->where('is_active', true)->firstOrFail();
    }

    /** Clears compiled caches (bootstrap, config, routes, views). Not database-specific. */
    public function optimizeClear(): RedirectResponse
    {
        try {
            Artisan::call('optimize:clear');
            $out = trim(Artisan::output());

            return back()->with('success', $out ?: 'Application optimize caches cleared.');
        } catch (\Throwable $e) {
            return back()->with('error', 'optimize:clear failed: '.$e->getMessage());
        }
    }

    public function configClear(): RedirectResponse
    {
        try {
            Artisan::call('config:clear');

            return back()->with('success', trim(Artisan::output()) ?: 'Configuration cache cleared.');
        } catch (\Throwable $e) {
            return back()->with('error', 'config:clear failed: '.$e->getMessage());
        }
    }

    public function cacheClear(): RedirectResponse
    {
        try {
            Artisan::call('cache:clear');

            return back()->with('success', trim(Artisan::output()) ?: 'Application cache cleared.');
        } catch (\Throwable $e) {
            return back()->with('error', 'cache:clear failed: '.$e->getMessage());
        }
    }

    /** Pending migrations on the active site database only. */
    public function migrate(): RedirectResponse
    {
        $domain = $this->activeDomain();
        try {
            TenantArtisanDatabase::prepare($domain);

            Artisan::call('migrate', TenantArtisanDatabase::tenantMigrateOptions());

            $target = TenantArtisanDatabase::label($domain);

            return back()->with('success', "Migrations ran on active site ({$target}). ".trim(Artisan::output()));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Migrate failed: '.$e->getMessage());
        } finally {
            TenantArtisanDatabase::restore();
        }
    }

    /** Roll back the last migration batch on the active site database only. */
    public function rollback(): RedirectResponse
    {
        $domain = $this->activeDomain();
        try {
            TenantArtisanDatabase::prepare($domain);

            Artisan::call('migrate:rollback', TenantArtisanDatabase::tenantMigrateOptions());

            $target = TenantArtisanDatabase::label($domain);

            return back()->with('success', "Rollback on active site ({$target}). ".trim(Artisan::output()));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Rollback failed: '.$e->getMessage());
        } finally {
            TenantArtisanDatabase::restore();
        }
    }

    /**
     * Drops all tables on the active site database, then re-runs migrations.
     * Default DatabaseSeeder targets master users/roles — not run here.
     */
    public function migrateFresh(): RedirectResponse
    {
        $domain = $this->activeDomain();
        try {
            TenantArtisanDatabase::prepare($domain);
            $conn = \Illuminate\Support\Facades\DB::connection(TenantArtisanDatabase::CONNECTION);

            $conn->statement('SET FOREIGN_KEY_CHECKS=0');
            foreach ($conn->select('SHOW TABLES') as $row) {
                $table = array_values((array) $row)[0];
                $conn->statement('DROP TABLE IF EXISTS `'.$table.'`');
            }
            $conn->statement('SET FOREIGN_KEY_CHECKS=1');

            Artisan::call('migrate', TenantArtisanDatabase::tenantMigrateOptions());

            $target = TenantArtisanDatabase::label($domain);
            $summary = trim(Artisan::output()) ?: 'Migrations completed.';

            return back()->with(
                'success',
                "Fresh migrate on active site ({$target}). All tables rebuilt. {$summary} (Seeders were not run — they use the CMS master database.)"
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Fresh migrate failed: '.$e->getMessage());
        } finally {
            TenantArtisanDatabase::restore();
        }
    }

    /** Run {@see TenantDatabaseSeeder} on the active site database only. */
    public function seed(): RedirectResponse
    {
        $domain = $this->activeDomain();
        try {
            TenantArtisanDatabase::prepare($domain);

            Artisan::call('db:seed', [
                '--database' => TenantArtisanDatabase::CONNECTION,
                '--class'    => TenantDatabaseSeeder::class,
                '--force'    => true,
            ]);

            $target = TenantArtisanDatabase::label($domain);

            return back()->with('success', "Seed on active site ({$target}). ".trim(Artisan::output()));
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Seed failed: '.$e->getMessage());
        } finally {
            TenantArtisanDatabase::restore();
        }
    }
}
