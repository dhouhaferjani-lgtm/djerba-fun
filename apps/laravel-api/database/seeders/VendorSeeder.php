<?php

namespace Database\Seeders;

use App\Enums\KycStatus;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'display_name' => 'Admin User',
            'email' => 'admin@djerba.fun',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Create main vendor
        $vendor = User::create([
            'display_name' => 'Djerba Fun',
            'email' => 'vendor@djerba.fun',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'vendor',
            'status' => 'active',
        ]);

        VendorProfile::create([
            'user_id' => $vendor->id,
            'company_name' => 'Djerba Fun',
            'company_type' => 'company',
            'tax_id' => 'TN123456789',
            'kyc_status' => KycStatus::VERIFIED,
            'commission_tier' => 'premium',
            'description' => 'Leading eco-tourism operator in Tunisia, offering authentic adventures and cultural experiences since 2015.',
            'website_url' => 'https://djerbafun.com',
            'phone' => '+216 71 123 456',
            'address' => [
                'street' => '15 Avenue Habib Bourguiba',
                'city' => 'Tunis',
                'postal_code' => '1000',
                'country' => 'TN',
            ],
            'verified_at' => now(),
        ]);

        // Create additional vendors
        $vendors = [
            [
                'display_name' => 'Sahara Dreams',
                'email' => 'sahara@example.tn',
                'company_name' => 'Sahara Dreams Tours',
                'description' => 'Specialist in desert adventures and Berber cultural experiences.',
            ],
            [
                'display_name' => 'Mediterranean Escape',
                'email' => 'med@example.tn',
                'company_name' => 'Mediterranean Escape',
                'description' => 'Coastal tours and island experiences along Tunisia\'s beautiful coastline.',
            ],
        ];

        foreach ($vendors as $vendorData) {
            $user = User::create([
                'display_name' => $vendorData['display_name'],
                'email' => $vendorData['email'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'vendor',
                'status' => 'active',
            ]);

            VendorProfile::create([
                'user_id' => $user->id,
                'company_name' => $vendorData['company_name'],
                'company_type' => 'company',
                'kyc_status' => KycStatus::VERIFIED,
                'commission_tier' => 'standard',
                'description' => $vendorData['description'],
                'verified_at' => now(),
            ]);
        }
    }
}
