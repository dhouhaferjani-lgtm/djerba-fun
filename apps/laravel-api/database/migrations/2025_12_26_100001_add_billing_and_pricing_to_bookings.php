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
        Schema::table('bookings', function (Blueprint $table) {
            // Billing address fields - collected at checkout for PPP pricing
            $table->string('billing_country_code', 2)->nullable()->after('total_amount');
            $table->string('billing_city')->nullable()->after('billing_country_code');
            $table->string('billing_postal_code', 20)->nullable()->after('billing_city');
            $table->text('billing_address_line1')->nullable()->after('billing_postal_code');
            $table->text('billing_address_line2')->nullable()->after('billing_address_line1');

            // Pricing snapshot - stores complete pricing context when price differs from base price
            // Structure: {
            //   'original_price': 150.00,
            //   'discounted_price': 75.00,
            //   'currency': 'TND',
            //   'country_code': 'TN',
            //   'pricing_source': 'billing_address',
            //   'ppp_index': 0.50,
            //   'price_changed': true
            // }
            $table->json('pricing_snapshot')->nullable()->after('billing_address_line2');

            // Flag indicating if customer acknowledged price change (e.g., from EUR to TND)
            $table->boolean('pricing_disclosed')->default(false)->after('pricing_snapshot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'billing_country_code',
                'billing_city',
                'billing_postal_code',
                'billing_address_line1',
                'billing_address_line2',
                'pricing_snapshot',
                'pricing_disclosed',
            ]);
        });
    }
};
