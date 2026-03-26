<?php

namespace App\Console\Commands;

use App\Enums\HoldStatus;
use App\Enums\ServiceType;
use App\Jobs\CalculateAvailabilityJob;
use App\Models\AvailabilitySlot;
use App\Models\BookingHold;
use App\Models\Listing;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FixAccommodationAvailabilityCommand extends Command
{
    protected $signature = 'accommodation:fix-availability
                            {--listing= : Specific listing slug to fix}
                            {--diagnose : Only diagnose, do not fix}
                            {--regenerate : Regenerate availability slots}';

    protected $description = 'Diagnose and fix accommodation availability issues (stale holds, slot status)';

    public function handle(): int
    {
        $this->info('=== Accommodation Availability Fix ===');
        $this->newLine();

        // Step 1: Expire all stale holds globally
        $this->expireStaleHolds();

        // Step 2: Get accommodation listings to check
        $listings = $this->getListingsToCheck();

        if ($listings->isEmpty()) {
            $this->warn('No accommodation listings found.');

            return self::FAILURE;
        }

        // Step 3: Diagnose and fix each listing
        foreach ($listings as $listing) {
            $this->diagnoseListing($listing);
        }

        $this->newLine();
        $this->info('=== Done ===');

        return self::SUCCESS;
    }

    private function expireStaleHolds(): void
    {
        $this->info('Checking for stale holds...');

        $staleCount = BookingHold::where('status', HoldStatus::ACTIVE)
            ->where('expires_at', '<', now())
            ->count();

        if ($staleCount === 0) {
            $this->info('  No stale holds found.');

            return;
        }

        if ($this->option('diagnose')) {
            $this->warn("  Found {$staleCount} stale holds (diagnose mode - not fixing)");

            return;
        }

        $expired = BookingHold::where('status', HoldStatus::ACTIVE)
            ->where('expires_at', '<', now())
            ->update(['status' => HoldStatus::EXPIRED]);

        $this->info("  Expired {$expired} stale holds.");
    }

    private function getListingsToCheck()
    {
        $query = Listing::where('service_type', ServiceType::ACCOMMODATION);

        if ($slug = $this->option('listing')) {
            $query->where('slug', $slug);
        }

        return $query->get();
    }

    private function diagnoseListing(Listing $listing): void
    {
        $title = is_array($listing->title)
            ? ($listing->title['fr'] ?? $listing->title['en'] ?? 'Untitled')
            : $listing->title;

        $this->newLine();
        $this->info("Listing: {$title} (ID: {$listing->id}, slug: {$listing->slug})");

        // Check slots for next 30 days
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(30);

        $slots = AvailabilitySlot::where('listing_id', $listing->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->orderBy('date')
            ->get();

        if ($slots->isEmpty()) {
            $this->warn('  No slots found for next 30 days.');

            if ($this->option('regenerate') && ! $this->option('diagnose')) {
                $this->regenerateSlots($listing);
            }

            return;
        }

        // Count slot states
        $bookable = 0;
        $notBookable = 0;
        $problemSlots = [];

        foreach ($slots as $slot) {
            if ($slot->isBookable()) {
                $bookable++;
            } else {
                $notBookable++;
                $problemSlots[] = $slot;
            }
        }

        $this->info("  Total slots: {$slots->count()} | Bookable: {$bookable} | Not Bookable: {$notBookable}");

        // Show problem slots
        if ($notBookable > 0) {
            $this->warn('  Problem slots:');
            foreach (array_slice($problemSlots, 0, 10) as $slot) {
                $this->line("    - {$slot->date}: status={$slot->status->value}, capacity={$slot->capacity}, remaining={$slot->remaining_capacity}");
            }

            if (count($problemSlots) > 10) {
                $this->line('    ... and '.(count($problemSlots) - 10).' more');
            }
        }

        // Check active holds for this listing
        $activeHolds = BookingHold::where('listing_id', $listing->id)
            ->where('status', HoldStatus::ACTIVE)
            ->count();

        if ($activeHolds > 0) {
            $this->info("  Active holds: {$activeHolds}");
        }

        // Regenerate if requested
        if ($this->option('regenerate') && ! $this->option('diagnose') && $notBookable > 0) {
            $this->regenerateSlots($listing);
        }
    }

    private function regenerateSlots(Listing $listing): void
    {
        $this->info('  Regenerating availability slots...');

        CalculateAvailabilityJob::dispatchSync(
            $listing,
            Carbon::today(),
            Carbon::today()->addMonths(3)
        );

        $this->info('  Done regenerating slots.');
    }
}
