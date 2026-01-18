<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fixes 505 error when creating listings - the alt column was NOT NULL
     * but the form allowed empty values, causing PostgreSQL constraint violation.
     */
    public function up(): void
    {
        Schema::table('listing_media', function (Blueprint $table) {
            $table->string('alt', 200)->nullable()->default('')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listing_media', function (Blueprint $table) {
            $table->string('alt', 200)->nullable(false)->default(null)->change();
        });
    }
};
