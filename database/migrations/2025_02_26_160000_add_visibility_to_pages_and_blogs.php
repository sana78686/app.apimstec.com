<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Visibility controls indexing: draft → noindex, private → hidden, published → index allowed.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('visibility', 20)->default('published')->after('is_published');
        });
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('visibility', 20)->default('draft')->after('is_published');
        });

        // Backfill: is_published true → published, false → draft
        DB::table('pages')->where('is_published', true)->update(['visibility' => 'published']);
        DB::table('pages')->where('is_published', false)->update(['visibility' => 'draft']);
        DB::table('blogs')->where('is_published', true)->update(['visibility' => 'published']);
        DB::table('blogs')->where('is_published', false)->update(['visibility' => 'draft']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
    }
};
