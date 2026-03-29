<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix redirects table: from_path max 191 for MySQL unique key limit.
     */
    public function up(): void
    {
        if (! Schema::hasTable('redirects')) {
            return;
        }
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE redirects MODIFY from_path VARCHAR(191) NOT NULL');

            $indexExists = DB::selectOne(
                "SELECT COUNT(*) as count FROM information_schema.statistics
                 WHERE table_schema = ? AND table_name = 'redirects' AND index_name = 'redirects_from_path_unique'",
                [DB::getDatabaseName()]
            );
            if ((int) $indexExists->count === 0) {
                DB::statement('ALTER TABLE redirects ADD UNIQUE redirects_from_path_unique (from_path)');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('redirects')) {
            return;
        }
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            $indexExists = DB::selectOne(
                "SELECT COUNT(*) as count FROM information_schema.statistics
                 WHERE table_schema = ? AND table_name = 'redirects' AND index_name = 'redirects_from_path_unique'",
                [DB::getDatabaseName()]
            );
            if ((int) $indexExists->count > 0) {
                DB::statement('ALTER TABLE redirects DROP INDEX redirects_from_path_unique');
            }
            DB::statement('ALTER TABLE redirects MODIFY from_path VARCHAR(500) NOT NULL');
        }
    }
};
