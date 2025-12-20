<?php

namespace Database\Seeders;

use App\Models\IncomePricingConfig;
use Illuminate\Database\Seeder;

class IncomePricingConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Income parity ratio calculation (Option 2: Exchange Rate + Premium):
        // Exchange rate: 1 EUR = 3.5 TND
        // Premium for international visitors: 50% (accounts for higher purchasing power)
        // Formula: TND to EUR = (1 / 3.5) × 1.5 = 0.4286
        //
        // Example:
        // - Local price: 200 TND
        // - International price: 200 × 0.4286 = €85.72 (equivalent to 300 TND, 50% premium)
        //
        // This approach:
        // ✓ Keeps local tourism affordable
        // ✓ Adds reasonable premium for international visitors
        // ✓ Simpler than complex income parity calculations
        // ✓ Market-competitive pricing

        $configs = [
            [
                'from_currency' => 'TND',
                'to_currency' => 'EUR',
                'ratio' => 0.4286, // TND to EUR with 50% premium over exchange rate
                'tolerance_percent' => 20,
                'is_active' => true,
                'effective_from' => now()->toDateString(),
                'notes' => 'Exchange rate (1 EUR = 3.5 TND) with 50% premium for international visitors. Example: 200 TND → €85.72',
            ],
        ];

        foreach ($configs as $config) {
            IncomePricingConfig::updateOrCreate(
                [
                    'from_currency' => $config['from_currency'],
                    'to_currency' => $config['to_currency'],
                    'effective_from' => $config['effective_from'],
                ],
                $config
            );
        }
    }
}
