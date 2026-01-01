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
            // =========================================================================
            // Mock Gateway (Development)
            // =========================================================================
            $table->boolean('mock_gateway_enabled')->default(false)
                ->after('enabled_payment_methods');

            // =========================================================================
            // Stripe Payment Gateway
            // =========================================================================
            $table->string('stripe_publishable_key')->nullable()
                ->after('mock_gateway_enabled');
            $table->text('stripe_secret_key')->nullable() // Encrypted
                ->after('stripe_publishable_key');
            $table->text('stripe_webhook_secret')->nullable() // Encrypted
                ->after('stripe_secret_key');

            // =========================================================================
            // Click to Pay (Tunisia)
            // =========================================================================
            $table->string('clicktopay_merchant_id')->nullable()
                ->after('stripe_webhook_secret');
            $table->text('clicktopay_api_key')->nullable() // Encrypted
                ->after('clicktopay_merchant_id');
            $table->text('clicktopay_secret_key')->nullable() // Encrypted
                ->after('clicktopay_api_key');
            $table->boolean('clicktopay_test_mode')->default(true)
                ->after('clicktopay_secret_key');

            // =========================================================================
            // Bank Transfer Settings
            // =========================================================================
            $table->string('bank_transfer_bank_name')->nullable()
                ->after('clicktopay_test_mode');
            $table->string('bank_transfer_account_holder')->nullable()
                ->after('bank_transfer_bank_name');
            $table->string('bank_transfer_account_number')->nullable()
                ->after('bank_transfer_account_holder');
            $table->string('bank_transfer_iban')->nullable()
                ->after('bank_transfer_account_number');
            $table->string('bank_transfer_swift_bic')->nullable()
                ->after('bank_transfer_iban');
            $table->text('bank_transfer_instructions')->nullable()
                ->after('bank_transfer_swift_bic');

            // =========================================================================
            // Offline/Manual Payments
            // =========================================================================
            $table->boolean('offline_payments_enabled')->default(true)
                ->after('bank_transfer_instructions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table) {
            $table->dropColumn([
                'mock_gateway_enabled',
                'stripe_publishable_key',
                'stripe_secret_key',
                'stripe_webhook_secret',
                'clicktopay_merchant_id',
                'clicktopay_api_key',
                'clicktopay_secret_key',
                'clicktopay_test_mode',
                'bank_transfer_bank_name',
                'bank_transfer_account_holder',
                'bank_transfer_account_number',
                'bank_transfer_iban',
                'bank_transfer_swift_bic',
                'bank_transfer_instructions',
                'offline_payments_enabled',
            ]);
        });
    }
};
