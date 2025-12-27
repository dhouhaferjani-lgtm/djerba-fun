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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('currency', 3); // EUR, USD, etc.
            $table->decimal('rate', 10, 6); // Exchange rate (e.g., 0.310000)
            $table->decimal('ppp_adjustment', 5, 4)->default(1.0000); // PPP factor (e.g., 0.8500)
            $table->string('source')->default('manual'); // API source or 'manual'
            $table->timestamps();

            // Indexes for performance
            $table->index('currency');
            $table->index('created_at'); // For fetching latest rates
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
