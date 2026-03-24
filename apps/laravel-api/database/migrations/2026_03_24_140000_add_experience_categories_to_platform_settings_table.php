<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the experience_categories JSON column to store dynamic
     * homepage experience category cards. This replaces the hardcoded
     * service types (tour, nautical, accommodation, event) with
     * admin-configurable categories.
     */
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->json('experience_categories')->nullable()->after('experience_categories_subtitle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn('experience_categories');
        });
    }
};
