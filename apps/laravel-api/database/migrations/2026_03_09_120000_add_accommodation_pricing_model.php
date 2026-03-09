<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds per-night pricing model for accommodations.
     * - pricing_model: 'per_person' (default for tours/events), 'per_night' (for accommodations), 'per_booking'
     * - nightly_price_tnd/eur: Flat rate per night for the whole property
     * - minimum_nights/maximum_nights: Stay duration constraints
     */
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            // Pricing model type - determines how pricing is calculated
            $table->string('pricing_model')->default('per_person')->after('pricing');

            // Per-night pricing for accommodations (whole property rental)
            $table->decimal('nightly_price_tnd', 10, 2)->nullable()->after('pricing_model');
            $table->decimal('nightly_price_eur', 10, 2)->nullable()->after('nightly_price_tnd');

            // Stay duration constraints
            $table->integer('minimum_nights')->default(1)->after('nightly_price_eur');
            $table->integer('maximum_nights')->nullable()->after('minimum_nights');

            // Index for filtering by pricing model
            $table->index('pricing_model');
        });

        // Set existing accommodations to per_night pricing model
        DB::table('listings')
            ->where('service_type', 'accommodation')
            ->update(['pricing_model' => 'per_night']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex(['pricing_model']);
            $table->dropColumn([
                'pricing_model',
                'nightly_price_tnd',
                'nightly_price_eur',
                'minimum_nights',
                'maximum_nights',
            ]);
        });
    }
};
