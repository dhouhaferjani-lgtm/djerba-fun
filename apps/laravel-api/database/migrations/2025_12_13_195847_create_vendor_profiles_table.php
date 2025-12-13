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
        Schema::create('vendor_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('company_name', 200);
            $table->string('company_type'); // individual, company, agency
            $table->string('tax_id')->nullable();
            $table->string('kyc_status')->default('pending')->index();
            $table->string('commission_tier')->default('standard');
            $table->string('payout_account_id')->nullable();
            $table->text('description')->nullable();
            $table->string('website_url')->nullable();
            $table->string('phone')->nullable();
            $table->json('address')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('kyc_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_profiles');
    }
};
