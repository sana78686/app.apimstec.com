<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Open Graph fields for social sharing (Facebook, X, LinkedIn).
     */
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('og_title', 255)->nullable()->after('schema_data');
            $table->string('og_description', 500)->nullable()->after('og_title');
            $table->string('og_image', 500)->nullable()->after('og_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn(['og_title', 'og_description', 'og_image']);
        });
    }
};
