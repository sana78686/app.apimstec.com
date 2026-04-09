<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_sections', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 8)->default('en')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('layout', 32)->default('cards'); // cards | paragraphs | mixed
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('content_section_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_section_id')->constrained('content_sections')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('item_type', 32)->default('card'); // card | paragraph
            $table->string('media_type', 32)->default('none'); // none | number | icon | image
            $table->string('media_value', 255)->nullable(); // "1", "fa-solid fa-file", image URL/path
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['content_section_id', 'sort_order'], 'content_section_items_section_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_section_items');
        Schema::dropIfExists('content_sections');
    }
};

