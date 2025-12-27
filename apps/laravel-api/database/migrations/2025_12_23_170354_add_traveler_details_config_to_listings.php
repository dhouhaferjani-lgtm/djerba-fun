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
        Schema::table('listings', function (Blueprint $table) {
            // NOTE: require_traveler_names already exists (added by create_carts_table migration)
            // We only need to add the timing configuration

            // When to prompt for names: 'immediate' or 'before_activity'
            // immediate = prompt right after payment
            // before_activity = can be done anytime before activity date
            $table->enum('traveler_names_timing', ['immediate', 'before_activity'])
                ->default('before_activity')
                ->after('require_traveler_names');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('traveler_names_timing');
        });
    }
};
