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
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Internal name for the gateway
            $table->string('slug')->unique(); // URL-friendly identifier
            $table->string('display_name'); // User-facing name
            $table->text('description')->nullable(); // Description of the gateway
            $table->string('driver'); // Driver class name (stripe, clicktopay, offline, bank_transfer)
            $table->boolean('is_enabled')->default(false); // Enable/disable gateway
            $table->boolean('is_default')->default(false); // Mark as default gateway
            $table->integer('priority')->default(0); // Sorting order (lower = higher priority)
            $table->json('configuration')->nullable(); // Gateway-specific config (API keys, etc.)
            $table->boolean('test_mode')->default(false); // Test/sandbox mode
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_enabled');
            $table->index('is_default');
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
