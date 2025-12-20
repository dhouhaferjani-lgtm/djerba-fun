<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\BookingParticipant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateExistingBookingsToParticipants extends Command
{
    protected $signature = 'bookings:migrate-participants {--dry-run : Show what would be done without making changes}';

    protected $description = 'Migrate existing bookings to use the new participants table';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in dry-run mode. No changes will be made.');
        }

        // Get all bookings that don't have participants yet
        $bookings = Booking::whereDoesntHave('participants')
            ->whereNotNull('travelers')
            ->orWhere(function ($query) {
                $query->whereDoesntHave('participants')
                    ->whereNotNull('traveler_info');
            })
            ->get();

        $this->info("Found {$bookings->count()} bookings to migrate.");

        $migrated = 0;
        $errors = 0;

        foreach ($bookings as $booking) {
            try {
                if (!$dryRun) {
                    DB::transaction(function () use ($booking) {
                        $this->migrateBooking($booking);
                    });
                } else {
                    $this->line("Would migrate booking {$booking->booking_number} (quantity: {$booking->quantity})");
                }
                $migrated++;
            } catch (\Exception $e) {
                $this->error("Error migrating booking {$booking->booking_number}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->info("Migration complete. Migrated: {$migrated}, Errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function migrateBooking(Booking $booking): void
    {
        // Get travelers from either travelers array or traveler_info
        $travelers = $booking->travelers ?? [];
        if (empty($travelers) && !empty($booking->traveler_info)) {
            $travelers = [$booking->traveler_info];
        }

        // Set billing contact from first traveler
        if (!empty($travelers[0])) {
            $firstTraveler = $travelers[0];
            $booking->update([
                'billing_contact' => [
                    'first_name' => $firstTraveler['first_name'] ?? null,
                    'last_name' => $firstTraveler['last_name'] ?? null,
                    'email' => $firstTraveler['email'] ?? null,
                    'phone' => $firstTraveler['phone'] ?? null,
                ],
            ]);
        }

        // Create participant records
        $breakdown = $booking->person_type_breakdown ?? [];
        $participantIndex = 0;

        if (!empty($breakdown)) {
            // Create based on person type breakdown
            foreach ($breakdown as $personType => $count) {
                for ($i = 0; $i < $count; $i++) {
                    $travelerData = $travelers[$participantIndex] ?? [];

                    BookingParticipant::create([
                        'booking_id' => $booking->id,
                        'first_name' => $travelerData['first_name'] ?? null,
                        'last_name' => $travelerData['last_name'] ?? null,
                        'email' => $travelerData['email'] ?? null,
                        'phone' => $travelerData['phone'] ?? null,
                        'person_type' => $personType,
                        'special_requests' => $travelerData['special_requests'] ?? null,
                    ]);

                    $participantIndex++;
                }
            }
        } else {
            // Create based on quantity
            for ($i = 0; $i < $booking->quantity; $i++) {
                $travelerData = $travelers[$i] ?? [];

                BookingParticipant::create([
                    'booking_id' => $booking->id,
                    'first_name' => $travelerData['first_name'] ?? null,
                    'last_name' => $travelerData['last_name'] ?? null,
                    'email' => $travelerData['email'] ?? null,
                    'phone' => $travelerData['phone'] ?? null,
                    'person_type' => $travelerData['person_type'] ?? null,
                    'special_requests' => $travelerData['special_requests'] ?? null,
                ]);
            }
        }

        $this->info("Migrated booking {$booking->booking_number} with {$participantIndex} participants");
    }
}
