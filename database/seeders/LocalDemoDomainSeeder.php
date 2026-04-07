<?php

namespace Database\Seeders;

use App\Models\Domain;
use Illuminate\Database\Seeder;

/**
 * Inserts a Domain row pointing at the local tenant DB (cms_tenant_demo by default).
 * Safe to run multiple times — skips if LOCAL_DEMO_DOMAIN_SITE already exists.
 */
class LocalDemoDomainSeeder extends Seeder
{
    public function run(): void
    {
        $site = env('LOCAL_DEMO_DOMAIN_SITE', 'compresspdf.local');
        if (Domain::where('domain', $site)->exists()) {
            return;
        }

        $pass = (string) (env('CMS_TENANT_PASSWORD', env('DB_PASSWORD', '')));

        Domain::query()->where('is_default', true)->update(['is_default' => false]);

        Domain::create([
            'name'          => env('LOCAL_DEMO_DOMAIN_NAME', 'Local demo (UI)'),
            'domain'        => $site,
            'frontend_url'  => rtrim((string) env('LOCAL_DEMO_FRONTEND_URL', 'http://localhost:5173'), '/'),
            'db_host'       => env('CMS_TENANT_HOST', env('DB_HOST', '127.0.0.1')),
            'db_port'       => (int) env('CMS_TENANT_PORT', env('DB_PORT', 3306)),
            'db_name'       => env('CMS_TENANT_DATABASE', 'cms_tenant_demo'),
            'db_username'   => env('CMS_TENANT_USERNAME', env('DB_USERNAME', 'root')),
            'db_password'   => encrypt($pass),
            'is_active'     => true,
            'is_default'    => true,
        ]);
    }
}
