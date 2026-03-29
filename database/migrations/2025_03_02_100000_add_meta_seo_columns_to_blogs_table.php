<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add same SEO meta fields as pages: meta_title, meta_description, canonical_url, meta_robots.
     */
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('meta_title', 255)->nullable()->after('schema_data');
            $table->string('meta_description', 500)->nullable()->after('meta_title');
            $table->string('canonical_url', 500)->nullable()->after('meta_description');
            $table->string('meta_robots', 50)->nullable()->default('index,follow')->after('canonical_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'canonical_url', 'meta_robots']);
        });
    }
};
