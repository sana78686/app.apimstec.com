<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rename visibility values to the simplified 3-state model:
 *   published  → visible   (live on frontend, indexed)
 *   private    → disabled  (hidden from frontend, noindex)
 *   draft      → draft     (no change)
 *
 * Also syncs is_published: only 'visible' means is_published = true.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['pages', 'blogs'] as $table) {
            DB::table($table)->where('visibility', 'published')->update([
                'visibility'   => 'visible',
                'is_published' => true,
            ]);
            DB::table($table)->where('visibility', 'private')->update([
                'visibility'   => 'disabled',
                'is_published' => false,
            ]);
            DB::table($table)->where('visibility', 'draft')->update([
                'is_published' => false,
            ]);
        }
    }

    public function down(): void
    {
        foreach (['pages', 'blogs'] as $table) {
            DB::table($table)->where('visibility', 'visible')->update([
                'visibility'   => 'published',
                'is_published' => true,
            ]);
            DB::table($table)->where('visibility', 'disabled')->update([
                'visibility'   => 'private',
                'is_published' => false,
            ]);
        }
    }
};
