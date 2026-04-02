<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropUnique(['slug']);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->string('locale', 8)->default('en')->after('id');
        });
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('locale', 8)->default('en')->after('id');
        });
        Schema::table('faq_items', function (Blueprint $table) {
            $table->string('locale', 8)->default('en')->after('id');
        });
        Schema::table('home_cards', function (Blueprint $table) {
            $table->string('locale', 8)->default('en')->after('id');
        });

        DB::table('pages')->update(['locale' => 'en']);
        DB::table('blogs')->update(['locale' => 'en']);
        DB::table('faq_items')->update(['locale' => 'en']);
        DB::table('home_cards')->update(['locale' => 'en']);

        Schema::table('pages', function (Blueprint $table) {
            $table->unique(['slug', 'locale']);
            $table->index('locale');
        });
        Schema::table('blogs', function (Blueprint $table) {
            $table->unique(['slug', 'locale']);
            $table->index('locale');
        });
        Schema::table('faq_items', function (Blueprint $table) {
            $table->index('locale');
        });
        Schema::table('home_cards', function (Blueprint $table) {
            $table->index('locale');
        });

        $this->migrateHomePageContentKeys();
    }

    private function migrateHomePageContentKeys(): void
    {
        if (! Schema::hasTable('content_manager_settings')) {
            return;
        }
        $legacy = DB::table('content_manager_settings')->where('key', 'home_page_content')->value('value');
        if ($legacy === null || $legacy === '') {
            return;
        }
        $exists = DB::table('content_manager_settings')->where('key', 'home_page_content_en')->exists();
        if ($exists) {
            return;
        }
        DB::table('content_manager_settings')->updateOrInsert(
            ['key' => 'home_page_content_en'],
            ['value' => $legacy, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique(['slug', 'locale']);
            $table->dropIndex(['locale']);
            $table->dropColumn('locale');
        });
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropUnique(['slug', 'locale']);
            $table->dropIndex(['locale']);
            $table->dropColumn('locale');
        });
        Schema::table('faq_items', function (Blueprint $table) {
            $table->dropIndex(['locale']);
            $table->dropColumn('locale');
        });
        Schema::table('home_cards', function (Blueprint $table) {
            $table->dropIndex(['locale']);
            $table->dropColumn('locale');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->unique('slug');
        });
        Schema::table('blogs', function (Blueprint $table) {
            $table->unique('slug');
        });
    }
};
