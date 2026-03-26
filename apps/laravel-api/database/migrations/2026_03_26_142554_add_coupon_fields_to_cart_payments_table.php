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
        Schema::table('cart_payments', function (Blueprint $table) {
            $table->foreignUuid('coupon_id')
                ->nullable()
                ->after('cart_id')
                ->constrained()
                ->nullOnDelete();
            $table->string('coupon_code', 50)->nullable()->after('coupon_id');
            $table->decimal('discount_amount', 10, 2)->nullable()->after('coupon_code');
            $table->decimal('original_amount', 10, 2)->nullable()->after('discount_amount');
            $table->json('coupon_application_details')->nullable()->after('original_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_payments', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn([
                'coupon_id',
                'coupon_code',
                'discount_amount',
                'original_amount',
                'coupon_application_details',
            ]);
        });
    }
};
