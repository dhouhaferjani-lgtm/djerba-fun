<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            // Event of the Year - Featured event promo banner
            $table->json('event_of_year_title')->nullable()->after('vendor_payout_delay_days');
            $table->json('event_of_year_description')->nullable()->after('event_of_year_title');
            $table->string('event_of_year_link')->nullable()->after('event_of_year_description');
            $table->string('event_of_year_tag')->nullable()->after('event_of_year_link');
            $table->boolean('event_of_year_enabled')->default(true)->after('event_of_year_tag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn([
                'event_of_year_title',
                'event_of_year_description',
                'event_of_year_link',
                'event_of_year_tag',
                'event_of_year_enabled',
            ]);
        });
    }
};
