<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_credentials', function (Blueprint $table) {
            $table->json('email_credentials')->nullable()->after('domain');
            $table->json('plesk_credentials')->nullable()->after('email_credentials');
            $table->json('website_credentials')->nullable()->after('plesk_credentials');
            $table->json('portal_credentials')->nullable()->after('website_credentials');
        });

        $this->migrateExistingData();

        Schema::table('domain_credentials', function (Blueprint $table) {
            $table->dropColumn([
                'email_username', 'email_password',
                'plesk_username', 'plesk_password',
                'website_username', 'website_password',
                'portal_url', 'portal_username', 'portal_password',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('domain_credentials', function (Blueprint $table) {
            $table->string('email_username', 255)->nullable();
            $table->text('email_password')->nullable();
            $table->string('plesk_username', 255)->nullable();
            $table->text('plesk_password')->nullable();
            $table->string('website_username', 255)->nullable();
            $table->text('website_password')->nullable();
            $table->string('portal_url', 2048)->nullable();
            $table->string('portal_username', 255)->nullable();
            $table->text('portal_password')->nullable();
        });

        Schema::table('domain_credentials', function (Blueprint $table) {
            $table->dropColumn(['email_credentials', 'plesk_credentials', 'website_credentials', 'portal_credentials']);
        });
    }

    private function migrateExistingData(): void
    {
        $rows = DB::table('domain_credentials')->get();
        foreach ($rows as $row) {
            $updates = [];
            if ($row->email_username || $row->email_password) {
                $updates['email_credentials'] = json_encode([[
                    'username' => $row->email_username,
                    'password' => $row->email_password,
                ]]);
            }
            if ($row->plesk_username || $row->plesk_password) {
                $updates['plesk_credentials'] = json_encode([[
                    'username' => $row->plesk_username,
                    'password' => $row->plesk_password,
                ]]);
            }
            if ($row->website_username || $row->website_password) {
                $updates['website_credentials'] = json_encode([[
                    'username' => $row->website_username,
                    'password' => $row->website_password,
                ]]);
            }
            if ($row->portal_url || $row->portal_username || $row->portal_password) {
                $updates['portal_credentials'] = json_encode([[
                    'url' => $row->portal_url,
                    'username' => $row->portal_username,
                    'password' => $row->portal_password,
                ]]);
            }
            if (! empty($updates)) {
                DB::table('domain_credentials')->where('id', $row->id)->update($updates);
            }
        }
    }
};
