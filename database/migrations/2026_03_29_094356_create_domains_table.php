<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stored on the MASTER database (mysql connection).
     * Each row represents one website/domain whose content database
     * the CMS can dynamically connect to.
     */
    public function up(): void
    {
        Schema::connection('mysql')->create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Display name, e.g. "CompressPDF"
            $table->string('domain')->unique();              // e.g. "compresspdf.id"
            $table->string('frontend_url')->nullable();      // e.g. "https://compresspdf.id"
            $table->string('db_host')->default('127.0.0.1');
            $table->unsignedSmallInteger('db_port')->default(3306);
            $table->string('db_name');
            $table->string('db_username');
            $table->text('db_password');                    // stored encrypted via Laravel encrypt()
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);  // the primary site for this CMS install
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('domains');
    }
};
