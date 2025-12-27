<?php

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
        Schema::table('booking_holds', function (Blueprint $table) {
            // Currency code (ISO 4217: EUR, TND, USD, etc.)
            $table->string('currency', 3)->default('USD')->after('slot_id');

            // Price snapshot at time of hold creation
            $table->decimal('price_snapshot', 10, 2)->nullable()->after('currency');

            // Country code used for pricing (ISO 3166-1 alpha-2)
            $table->string('pricing_country_code', 2)->nullable()->after('price_snapshot');

            // Source of pricing determination: 'ip_geo', 'user_selection', 'billing_address'
            $table->string('pricing_source', 20)->default('ip_geo')->after('pricing_country_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_holds', function (Blueprint $table) {
            $table->dropColumn([
                'currency',
                'price_snapshot',
                'pricing_country_code',
                'pricing_source',
            ]);
        });
    }
};
