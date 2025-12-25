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
            // Only add session_id as cart_id and person_type_breakdown already exist
            $table->string('session_id')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_holds', function (Blueprint $table) {
            $table->dropColumn('session_id');
        });
    }
};
