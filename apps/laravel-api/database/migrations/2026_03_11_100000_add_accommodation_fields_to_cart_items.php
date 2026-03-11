<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds accommodation-specific fields to cart_items for nightly pricing support.
     */
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            // Accommodation booking dates
            $table->date('check_in_date')->nullable()->after('slot_end');
            $table->date('check_out_date')->nullable()->after('check_in_date');

            // Number of nights (cached for display and calculation)
            $table->integer('nights')->nullable()->after('check_out_date');

            // Nightly rate at time of cart creation (price snapshot)
            $table->decimal('nightly_rate', 10, 2)->nullable()->after('nights');

            // Pricing model: 'per_person' (default) or 'per_night' (accommodation)
            $table->string('pricing_model', 20)->default('per_person')->after('nightly_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_date',
                'check_out_date',
                'nights',
                'nightly_rate',
                'pricing_model',
            ]);
        });
    }
};
