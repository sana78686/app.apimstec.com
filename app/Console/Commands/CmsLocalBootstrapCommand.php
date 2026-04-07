<?php

namespace App\Console\Commands;

use App\Support\TenantArtisanDatabase;
use Database\Seeders\LocalDemoDomainSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CmsLocalBootstrapCommand extends Command
{
    protected $signature = 'cms:local-bootstrap
        {--seed : Run DatabaseSeeder (admin user + roles) after registry migrations}
        {--skip-tenant : Skip tenant migrations}
        {--skip-domain : Skip LocalDemoDomainSeeder}';

    protected $description = 'Migrate CMS registry DB, tenant DB (docker/local MySQL), and seed a demo Domain row';

    public function handle(): int
    {
        if (config('database.default') !== 'mysql') {
            $this->warn('DB_CONNECTION is not mysql. For the local two-DB setup, set DB_CONNECTION=mysql and DB_DATABASE=cms_registry (see LOCAL_DEV.md).');
        }

        $this->info('Running registry migrations (database/migrations)…');
        $exit = Artisan::call('migrate', ['--force' => true]);
        $this->output->write(Artisan::output());
        if ($exit !== 0) {
            return $exit;
        }

        if ($this->option('seed')) {
            $this->info('Seeding registry (users, roles, permissions)…');
            Artisan::call('db:seed', ['--force' => true]);
            $this->output->write(Artisan::output());
        }

        if (! $this->option('skip-tenant')) {
            $db = (string) config('database.connections.tenant.database');
            $this->info("Running tenant migrations on database \"{$db}\"…");
            $exit = Artisan::call('migrate', TenantArtisanDatabase::tenantMigrateOptions());
            $this->output->write(Artisan::output());
            if ($exit !== 0) {
                return $exit;
            }
        }

        if (! $this->option('skip-domain')) {
            $this->info('Seeding demo Domain (if missing)…');
            Artisan::call('db:seed', ['--class' => LocalDemoDomainSeeder::class, '--force' => true]);
            $this->output->write(Artisan::output());
        }

        $this->newLine();
        $this->info('Done.');
        if ($this->option('seed')) {
            $this->line('  Login: admin@gmail.com / Test@123');
        }
        $this->line('  In the CMS, choose the “Local demo” site, or use Domains → Sync schema if needed.');
        $this->line('  Match React VITE_SITE_DOMAIN to LOCAL_DEMO_DOMAIN_SITE (default compresspdf.local).');

        return 0;
    }
}
