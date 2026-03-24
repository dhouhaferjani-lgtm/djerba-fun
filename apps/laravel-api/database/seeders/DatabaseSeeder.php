<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Platform-wide settings (must be first - other seeders may depend on it)
            PlatformSettingsSeeder::class,

            // Core reference data
            CurrencySeeder::class,
            IncomePricingConfigSeeder::class,
            PaymentGatewaySeeder::class,
            TagSeeder::class,

            // Users (admin, vendors, test traveler)
            VendorSeeder::class,

            // Location and category data
            LocationSeeder::class,
            BlogCategorySeeder::class,

            // Content
            ListingSeeder::class,
            HikingTourWithMapSeeder::class,
            TravelTipSeeder::class,

            // Testimonials (independent from platform settings)
            TestimonialSeeder::class,

            // CMS Navigation
            MenuSeeder::class,
        ]);
    }
}
