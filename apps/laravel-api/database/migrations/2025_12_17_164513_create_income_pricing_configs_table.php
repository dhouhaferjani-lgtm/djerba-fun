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
        Schema::create('income_pricing_configs', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('ratio', 10, 4);
            $table->integer('tolerance_percent')->default(20);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['from_currency', 'to_currency', 'is_active']);
            $table->index('effective_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_pricing_configs');
    }
};
