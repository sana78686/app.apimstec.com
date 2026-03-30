<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Runs only on the active site's tenant connection (not the CMS master DB).
 * Add default pages, settings, or sample content here as needed.
 */
class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Intentionally minimal — avoids running UserSeeder / roles on tenant DBs.
    }
}
