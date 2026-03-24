<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add homepage_sections JSON column to platform_settings table.
     * This stores the order and visibility of homepage sections.
     *
     * Structure:
     * {
     *   "sections": [
     *     { "id": "hero", "enabled": true, "order": 0 },
     *     { "id": "marketing_mosaic", "enabled": true, "order": 1 },
     *     ...
     *   ]
     * }
     */
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->json('homepage_sections')->nullable()->after('testimonials');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn('homepage_sections');
        });
    }
};
