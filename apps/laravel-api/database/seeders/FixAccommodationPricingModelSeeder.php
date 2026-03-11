<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fix accommodation listings that have incorrect or missing pricing_model.
 *
 * This seeder updates all accommodation listings to use 'per_night' pricing model.
 * Run this to fix existing data where pricing_model wasn't set correctly.
 *
 * Usage: php artisan db:seed --class=FixAccommodationPricingModelSeeder
 */
class FixAccommodationPricingModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $affected = DB::table('listings')
            ->where('service_type', 'accommodation')
            ->where(function ($query) {
                $query->whereNull('pricing_model')
                    ->orWhere('pricing_model', '!=', 'per_night');
            })
            ->update(['pricing_model' => 'per_night']);

        $this->command->info("Fixed {$affected} accommodation listing(s) with incorrect pricing_model.");
    }
}
