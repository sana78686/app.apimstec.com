<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SEO-focused fields for content management.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('focus_keyword', 255)->nullable()->after('meta_description');
            $table->string('canonical_url', 500)->nullable()->after('focus_keyword');
            $table->string('meta_robots', 50)->nullable()->default('index,follow')->after('canonical_url');
            $table->string('og_title', 255)->nullable()->after('meta_robots');
            $table->string('og_description', 500)->nullable()->after('og_title');
            $table->string('og_image', 500)->nullable()->after('og_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn([
                'focus_keyword',
                'canonical_url',
                'meta_robots',
                'og_title',
                'og_description',
                'og_image',
            ]);
        });
    }
};
