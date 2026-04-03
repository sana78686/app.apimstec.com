<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CMS admins authenticate against the registry DB (mysql). Tenant DBs have a structural
 * `users` table but it is not kept in sync with admin accounts, so FK blogs.user_id →
 * tenant.users caused integrity errors. blogs.user_id stores the registry user id for
 * attribution; Eloquent author() resolves App\Models\User on the mysql connection.
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('blogs', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        } catch (\Throwable) {
            // FK missing or different name (already migrated manually)
        }
    }

    public function down(): void
    {
        // Do not re-add FK: registry user ids do not exist in tenant.users.
    }
};
