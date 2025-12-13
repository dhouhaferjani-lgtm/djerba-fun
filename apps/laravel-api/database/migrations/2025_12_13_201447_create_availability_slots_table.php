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
        Schema::create('availability_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('availability_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('remaining_capacity');
            $table->decimal('base_price', 10, 2);
            $table->string('status'); // available, limited, sold_out, blocked
            $table->timestamps();

            $table->index(['listing_id', 'date', 'status']);
            $table->index(['date', 'status']);
            $table->unique(['listing_id', 'date', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_slots');
    }
};
