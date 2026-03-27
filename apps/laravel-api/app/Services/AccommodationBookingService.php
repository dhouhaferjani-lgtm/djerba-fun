<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SlotStatus;
use App\Models\AvailabilitySlot;
use App\Models\Listing;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AccommodationBookingService
{
    /**
     * Validate a date range booking for an accommodation listing.
     *
     * @param  Listing  $listing  The accommodation listing
     * @param  Carbon  $checkIn  Check-in date
     * @param  Carbon  $checkOut  Check-out date
     * @param  int  $guests  Number of guests
     * @return array{valid: bool, message: string|null, nights: int}
     */
    public function validateDateRange(Listing $listing, Carbon $checkIn, Carbon $checkOut, int $guests = 1): array
    {
        $nights = $checkIn->diffInDays($checkOut);

        // Same-day selection = 1 night minimum (user selected same date for check-in/check-out)
        if ($nights === 0 && $checkIn->isSameDay($checkOut)) {
            $nights = 1;
        }

        // Validate minimum nights
        $minimumNights = $listing->minimum_nights ?? 1;
        if ($nights < $minimumNights) {
            return [
                'valid' => false,
                'message' => "Minimum stay is {$minimumNights} nights",
                'nights' => $nights,
            ];
        }

        // Validate maximum nights
        $maximumNights = $listing->maximum_nights;
        if ($maximumNights && $nights > $maximumNights) {
            return [
                'valid' => false,
                'message' => "Maximum stay is {$maximumNights} nights",
                'nights' => $nights,
            ];
        }

        // Validate guests
        $maxGuests = $listing->max_guests ?? $listing->max_group_size ?? 10;
        if ($guests > $maxGuests) {
            return [
                'valid' => false,
                'message' => "Maximum guests is {$maxGuests}",
                'nights' => $nights,
            ];
        }

        // Check availability for all dates in the range
        $blockedDates = $this->getBlockedDates($listing, $checkIn, $checkOut);
        if (count($blockedDates) > 0) {
            $firstBlocked = $blockedDates[0]->format('Y-m-d');

            return [
                'valid' => false,
                'message' => "Property is not available on {$firstBlocked}",
                'nights' => $nights,
                'blocked_dates' => $blockedDates,
            ];
        }

        return [
            'valid' => true,
            'message' => null,
            'nights' => $nights,
        ];
    }

    /**
     * Get blocked dates within a date range.
     *
     * @param  Listing  $listing  The accommodation listing
     * @param  Carbon  $start  Start date (check-in)
     * @param  Carbon  $end  End date (check-out, exclusive)
     * @return array<Carbon> Array of blocked dates
     */
    public function getBlockedDates(Listing $listing, Carbon $start, Carbon $end): array
    {
        // Handle same-day booking (1 night): only check the start date
        if ($start->isSameDay($end)) {
            $end = $start->copy()->addDay();
        }

        // Get all nights in the range (check-out date is exclusive)
        $period = CarbonPeriod::create($start, $end->copy()->subDay());
        $blockedDates = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');

            $slot = AvailabilitySlot::query()
                ->where('listing_id', $listing->id)
                ->whereDate('date', $dateStr)
                ->first();

            // For accommodations: if no slot exists, create one with default availability.
            // Unlike tours/events which need explicit time slots, accommodations are
            // available every day by default unless explicitly blocked.
            if (! $slot) {
                $slot = AvailabilitySlot::create([
                    'listing_id' => $listing->id,
                    'date' => $date->toDateString(),
                    'start_time' => '14:00:00', // Default check-in time
                    'end_time' => '11:00:00',   // Default check-out time (next day)
                    'capacity' => 1,             // 1 property unit
                    'remaining_capacity' => 1,
                    'status' => SlotStatus::AVAILABLE,
                    'currency' => 'EUR',
                ]);
            }

            // Use computed accessor for capacity check
            if (! $slot->isBookable()) {
                $blockedDates[] = $date->copy();
            }
        }

        return $blockedDates;
    }

    /**
     * Calculate the total price for an accommodation booking.
     *
     * @param  Listing  $listing  The accommodation listing
     * @param  int  $nights  Number of nights
     * @param  string  $currency  Currency code (TND or EUR)
     * @return array{subtotal: float, total: float, currency: string, nightly_rate: float, nights: int}
     */
    public function calculatePrice(Listing $listing, int $nights, string $currency = 'EUR'): array
    {
        $nightlyRate = $currency === 'TND'
            ? ($listing->nightly_price_tnd ?? 0)
            : ($listing->nightly_price_eur ?? 0);

        $subtotal = $nightlyRate * $nights;

        return [
            'subtotal' => $subtotal,
            'total' => $subtotal, // Can add cleaning fee, service fee, etc. here
            'currency' => $currency,
            'nightly_rate' => $nightlyRate,
            'nights' => $nights,
        ];
    }

    /**
     * Find or create a slot for the check-in date.
     * For accommodations, we use the check-in date's slot.
     *
     * @param  Listing  $listing  The accommodation listing
     * @param  Carbon  $checkIn  Check-in date
     * @return AvailabilitySlot|null
     */
    public function findSlotForCheckIn(Listing $listing, Carbon $checkIn): ?AvailabilitySlot
    {
        $dateStr = $checkIn->format('Y-m-d');

        $slot = AvailabilitySlot::query()
            ->where('listing_id', $listing->id)
            ->whereDate('date', $dateStr)
            ->first();

        // For accommodations: if no slot exists, create one with default availability.
        if (! $slot) {
            $slot = AvailabilitySlot::create([
                'listing_id' => $listing->id,
                'date' => $checkIn->toDateString(),
                'start_time' => '14:00:00',
                'end_time' => '11:00:00',
                'capacity' => 1,
                'remaining_capacity' => 1,
                'status' => SlotStatus::AVAILABLE,
                'currency' => 'EUR',
            ]);
        }

        // Return only if slot is bookable (uses computed accessor for real-time capacity)
        return $slot->isBookable() ? $slot : null;
    }

    /**
     * Check if a listing uses per-night pricing.
     */
    public function isPerNightPricing(Listing $listing): bool
    {
        return $listing->pricing_model === 'per_night' ||
               $listing->service_type?->value === 'accommodation';
    }
}
