<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stores 301 redirects when slugs/URLs change (SEO-friendly URL manager).
     */
    public function up(): void
    {
        if (Schema::hasTable('redirects')) {
            return;
        }
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path', 191)->unique()->comment('Path or slug to redirect from (e.g. old-slug or blog/old-slug)');
            $table->string('to_path', 500)->comment('Path or slug to redirect to');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
