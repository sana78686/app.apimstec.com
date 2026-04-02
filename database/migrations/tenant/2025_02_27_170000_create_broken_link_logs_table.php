<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broken_link_logs', function (Blueprint $table) {
            $table->id();
            $table->string('path', 191)->unique()->comment('Request path that returned 404');
            $table->unsignedInteger('hit_count')->default(1);
            $table->string('referer', 500)->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('resolved_at')->nullable()->comment('Set when a 301 redirect is created for this path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broken_link_logs');
    }
};
