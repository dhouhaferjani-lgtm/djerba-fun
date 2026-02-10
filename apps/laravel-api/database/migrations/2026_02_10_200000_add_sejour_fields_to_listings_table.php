<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->string('accommodation_type')->nullable()->after('has_elevation_profile');
            $table->json('meals_included')->nullable()->after('accommodation_type');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['accommodation_type', 'meals_included']);
        });
    }
};
