<?php

namespace App\Jobs;

use App\Enums\AvailabilityRuleType;
use App\Enums\SlotStatus;
use App\Models\AvailabilityRule;
use App\Models\AvailabilitySlot;
use App\Models\Listing;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateAvailabilityJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Listing $listing,
        public Carbon $startDate,
        public Carbon $endDate,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get all active rules for this listing
        $rules = $this->listing->availabilityRules()
            ->active()
            ->get();

        // Generate slots for each day in the range
        $period = CarbonPeriod::create($this->startDate, $this->endDate);

        foreach ($period as $date) {
            $this->generateSlotsForDate($date, $rules);
        }
    }

    /**
     * Generate availability slots for a specific date.
     */
    protected function generateSlotsForDate(Carbon $date, $rules): void
    {
        foreach ($rules as $rule) {
            // Skip if rule is not valid for this date
            if (! $rule->isValidForDate($date)) {
                continue;
            }

            // Handle blocked dates
            if ($rule->rule_type === AvailabilityRuleType::BLOCKED_DATES) {
                $this->blockSlot($date, $rule);
                continue;
            }

            // Create or update availability slot
            $this->createOrUpdateSlot($date, $rule);
        }
    }

    /**
     * Create or update an availability slot.
     */
    protected function createOrUpdateSlot(Carbon $date, AvailabilityRule $rule): void
    {
        $startTime = $rule->start_time ?: now()->startOfDay();
        $endTime = $rule->end_time ?: now()->endOfDay();

        // Get base price from listing pricing or rule override
        $basePrice = $rule->price_override ?? $this->getListingBasePrice();

        AvailabilitySlot::updateOrCreate(
            [
                'listing_id' => $this->listing->id,
                'date' => $date->toDateString(),
                'start_time' => $startTime->format('H:i:s'),
            ],
            [
                'availability_rule_id' => $rule->id,
                'end_time' => $endTime->format('H:i:s'),
                'capacity' => $rule->capacity,
                // remaining_capacity is now computed via accessor, not stored
                'base_price' => $basePrice,
                'status' => SlotStatus::AVAILABLE,
            ]
        );
    }

    /**
     * Block a slot for a specific date.
     */
    protected function blockSlot(Carbon $date, AvailabilityRule $rule): void
    {
        AvailabilitySlot::updateOrCreate(
            [
                'listing_id' => $this->listing->id,
                'date' => $date->toDateString(),
                'start_time' => ($rule->start_time ?: now()->startOfDay())->format('H:i:s'),
            ],
            [
                'availability_rule_id' => $rule->id,
                'end_time' => ($rule->end_time ?: now()->endOfDay())->format('H:i:s'),
                'capacity' => 0,
                // remaining_capacity is computed via accessor (will be 0 since capacity is 0)
                'base_price' => 0,
                'status' => SlotStatus::BLOCKED,
            ]
        );
    }

    /**
     * Get the base price from the listing.
     */
    protected function getListingBasePrice(): float
    {
        $pricing = $this->listing->pricing;

        // Extract base price from pricing array
        // Assuming pricing structure: ['adult' => ['amount' => 100, ...], ...]
        if (is_array($pricing) && isset($pricing['adult']['amount'])) {
            return (float) $pricing['adult']['amount'];
        }

        return 0.0;
    }
}
