<?php

namespace Database\Seeders;

use App\Enums\AvailabilityRuleType;
use App\Models\AvailabilityRule;
use App\Models\Listing;
use Illuminate\Database\Seeder;

class AvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates a single weekly rule with TWO time_slots entries (09:00 and 14:00)
     * per published listing. CalculateAvailabilityJob (triggered by the rule's
     * `saved` observer) materialises the AvailabilitySlot rows automatically —
     * no manual slot inserts needed.
     */
    public function run(): void
    {
        $listings = Listing::where('status', 'published')->get();

        foreach ($listings as $listing) {
            $capacity = (int) ($listing->max_group_size ?? 10);

            AvailabilityRule::create([
                'listing_id' => $listing->id,
                'rule_type' => AvailabilityRuleType::WEEKLY,
                'days_of_week' => [1, 2, 3, 4, 5, 6], // Mon-Sat
                'time_slots' => [
                    ['start_time' => '09:00:00', 'end_time' => '12:00:00', 'capacity' => $capacity],
                    ['start_time' => '14:00:00', 'end_time' => '17:00:00', 'capacity' => $capacity],
                ],
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(6)->toDateString(),
                'is_active' => true,
            ]);
        }

        $this->command->info('Created multi-time-slot weekly rules for ' . $listings->count() . ' listings');
    }
}
