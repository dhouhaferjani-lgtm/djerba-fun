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
            // Accommodation fields
            $table->integer('bedrooms')->nullable()->after('meals_included');
            $table->integer('bathrooms')->nullable()->after('bedrooms');
            $table->integer('max_guests')->nullable()->after('bathrooms');
            $table->integer('property_size')->nullable()->after('max_guests')->comment('Size in square meters');
            $table->time('check_in_time')->nullable()->after('property_size');
            $table->time('check_out_time')->nullable()->after('check_in_time');
            $table->json('house_rules')->nullable()->after('check_out_time')->comment('Translatable house rules');
            $table->json('amenities')->nullable()->after('house_rules')->comment('Array of amenity keys');

            // Nautical fields
            $table->string('boat_name')->nullable()->after('amenities');
            $table->decimal('boat_length', 5, 2)->nullable()->after('boat_name')->comment('Length in meters');
            $table->integer('boat_capacity')->nullable()->after('boat_length')->comment('Passenger capacity');
            $table->integer('boat_year')->nullable()->after('boat_capacity')->comment('Year built');
            $table->boolean('license_required')->default(false)->after('boat_year');
            $table->string('license_type')->nullable()->after('license_required');
            $table->json('equipment_included')->nullable()->after('license_type')->comment('Array of equipment keys');
            $table->boolean('crew_included')->default(false)->after('equipment_included');
            $table->boolean('fuel_included')->default(false)->after('crew_included');
            $table->integer('min_rental_hours')->nullable()->after('fuel_included');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            // Drop accommodation fields
            $table->dropColumn([
                'bedrooms',
                'bathrooms',
                'max_guests',
                'property_size',
                'check_in_time',
                'check_out_time',
                'house_rules',
                'amenities',
            ]);

            // Drop nautical fields
            $table->dropColumn([
                'boat_name',
                'boat_length',
                'boat_capacity',
                'boat_year',
                'license_required',
                'license_type',
                'equipment_included',
                'crew_included',
                'fuel_included',
                'min_rental_hours',
            ]);
        });
    }
};
