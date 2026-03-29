<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Structured data for rich results: Article, FAQ, Product, Breadcrumb.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('schema_type', 50)->nullable()->after('og_image');
            $table->json('schema_data')->nullable()->after('schema_type');
        });
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('schema_type', 50)->nullable()->after('visibility');
            $table->json('schema_data')->nullable()->after('schema_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['schema_type', 'schema_data']);
        });
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn(['schema_type', 'schema_data']);
        });
    }
};
