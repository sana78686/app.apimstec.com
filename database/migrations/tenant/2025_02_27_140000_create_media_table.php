<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('media_source');
        Schema::dropIfExists('media');

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('path')->comment('Public-relative path or full URL');
            $table->string('filename')->nullable();
            $table->string('alt_text', 500)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->string('webp_path')->nullable()->comment('Path to WebP version if created');
            $table->timestamps();
        });

        Schema::create('media_source', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('source_type', 32); // 'page', 'blog'
            $table->unsignedBigInteger('source_id');
            $table->string('usage', 32)->default('og_image'); // og_image, content
            $table->timestamps();
            $table->unique(['media_id', 'source_type', 'source_id', 'usage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_source');
        Schema::dropIfExists('media');
    }
};
