<?php

namespace Database\Seeders;

use App\Enums\AvailabilityRuleType;
use App\Enums\SlotStatus;
use App\Models\AvailabilityRule;
use App\Models\AvailabilitySlot;
use App\Models\Listing;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $listings = Listing::where('status', 'published')->get();

        foreach ($listings as $listing) {
            // Create a weekly availability rule (Mon-Sat, 9am and 2pm)
            $rule = AvailabilityRule::create([
                'listing_id' => $listing->id,
                'rule_type' => AvailabilityRuleType::WEEKLY,
                'days_of_week' => [1, 2, 3, 4, 5, 6], // Mon-Sat
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths(6)->toDateString(),
                'capacity' => $listing->max_group_size ?? 10,
                'is_active' => true,
            ]);

            // Generate slots for the next 3 months
            $startDate = Carbon::today();
            $endDate = Carbon::today()->addMonths(3);

            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                // Skip Sundays (day 0)
                if ($currentDate->dayOfWeek !== 0) {
                    // Morning slot at 9am
                    AvailabilitySlot::create([
                        'listing_id' => $listing->id,
                        'availability_rule_id' => $rule->id,
                        'date' => $currentDate->toDateString(),
                        'start_time' => $currentDate->copy()->setTime(9, 0),
                        'end_time' => $currentDate->copy()->setTime(12, 0),
                        'capacity' => $listing->max_group_size ?? 10,
                        'remaining_capacity' => $listing->max_group_size ?? 10,
                        'base_price' => $listing->pricing->base_price ?? $listing->pricing['base_price'] ?? 50,
                        'status' => SlotStatus::AVAILABLE,
                    ]);

                    // Afternoon slot at 2pm
                    AvailabilitySlot::create([
                        'listing_id' => $listing->id,
                        'availability_rule_id' => $rule->id,
                        'date' => $currentDate->toDateString(),
                        'start_time' => $currentDate->copy()->setTime(14, 0),
                        'end_time' => $currentDate->copy()->setTime(17, 0),
                        'capacity' => $listing->max_group_size ?? 10,
                        'remaining_capacity' => $listing->max_group_size ?? 10,
                        'base_price' => $listing->pricing->base_price ?? $listing->pricing['base_price'] ?? 50,
                        'status' => SlotStatus::AVAILABLE,
                    ]);
                }

                $currentDate->addDay();
            }
        }

        $this->command->info('Created availability rules and slots for ' . $listings->count() . ' listings');
    }
}
