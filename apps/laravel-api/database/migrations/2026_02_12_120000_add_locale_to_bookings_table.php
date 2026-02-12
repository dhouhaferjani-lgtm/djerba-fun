<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('locale', 5)->default('fr')->after('currency');
        });

        // Backfill existing bookings from user's preferred_locale
        DB::statement("
            UPDATE bookings
            SET locale = COALESCE(
                (SELECT preferred_locale FROM users WHERE users.id = bookings.user_id),
                'fr'
            )
        ");
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
