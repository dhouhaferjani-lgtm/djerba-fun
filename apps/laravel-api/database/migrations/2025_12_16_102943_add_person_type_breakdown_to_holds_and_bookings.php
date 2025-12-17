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
        Schema::table('booking_holds', function (Blueprint $table) {
            // JSON field to store person type breakdown: {"adult": 2, "child": 1, "infant": 0}
            $table->json('person_type_breakdown')->nullable()->after('quantity');
        });

        Schema::table('bookings', function (Blueprint $table) {
            // JSON field to store person type breakdown: {"adult": 2, "child": 1, "infant": 0}
            $table->json('person_type_breakdown')->nullable()->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_holds', function (Blueprint $table) {
            $table->dropColumn('person_type_breakdown');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('person_type_breakdown');
        });
    }
};
