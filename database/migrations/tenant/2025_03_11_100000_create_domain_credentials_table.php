<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 255);
            $table->string('email_username', 255)->nullable();
            $table->text('email_password')->nullable();
            $table->string('plesk_username', 255)->nullable();
            $table->text('plesk_password')->nullable();
            $table->string('website_username', 255)->nullable();
            $table->text('website_password')->nullable();
            $table->string('portal_url', 2048)->nullable();
            $table->string('portal_username', 255)->nullable();
            $table->text('portal_password')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_credentials');
    }
};
