<?php

declare(strict_types=1);

namespace App\Filament\Vendor\Pages;

use App\Enums\BookingStatus;
use App\Filament\Concerns\SafeTranslation;
use App\Models\BookingParticipant;
use App\Models\Listing;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class CheckInScanner extends Page
{
    use SafeTranslation;

    protected static ?string $navigationIcon = null;

    protected static string $view = 'filament.vendor.pages.check-in-scanner';

    protected static ?string $title = 'Check-In Scanner';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav.bookings');
    }

    public static function getNavigationLabel(): string
    {
        return 'Check-In Scanner';
    }

    protected static ?int $navigationSort = 50;

    public ?string $selectedListingId = null;

    public ?string $selectedDate = null;

    public ?string $scannedCode = '';

    public ?array $scanResult = null;

    public array $recentScans = [];

    public array $checkInStats = ['checkedIn' => 0, 'total' => 0];

    /**
     * Get the vendor's listings for the filter dropdown.
     */
    public function getVendorListingsProperty(): array
    {
        return Listing::where('vendor_id', auth()->id())
            ->whereIn('status', ['published', 'archived'])
            ->orderBy('title')
            ->get()
            ->map(fn (Listing $listing) => [
                'id' => $listing->id,
                'title' => self::extractTranslation($listing->title),
            ])
            ->toArray();
    }

    /**
     * Get upcoming dates for the selected listing.
     */
    public function getUpcomingDatesProperty(): array
    {
        if (! $this->selectedListingId) {
            return [];
        }

        return \App\Models\AvailabilitySlot::where('listing_id', $this->selectedListingId)
            ->where('date', '>=', now()->subDay())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($slot) => [
                'value' => $slot->date->toDateString() . '|' . ($slot->start_time ? $slot->start_time->format('H:i') : ''),
                'label' => $slot->date->format('D, M d, Y') . ($slot->start_time ? ' - ' . $slot->start_time->format('H:i') : ''),
            ])
            ->unique('value')
            ->values()
            ->toArray();
    }

    /**
     * Called when listing filter changes.
     */
    public function updatedSelectedListingId(): void
    {
        $this->selectedDate = null;
        $this->refreshStats();
    }

    /**
     * Called when date filter changes.
     */
    public function updatedSelectedDate(): void
    {
        $this->refreshStats();
    }

    /**
     * Look up a voucher code and return validation result.
     */
    public function lookupVoucher(string $code): void
    {
        $code = trim($code);

        if (empty($code)) {
            $this->scanResult = [
                'status' => 'INVALID_CODE',
                'message' => 'Please scan or enter a voucher code',
            ];

            return;
        }

        $participant = BookingParticipant::byVoucherCode($code)
            ->with([
                'booking.listing',
                'booking.availabilitySlot',
                'booking.activeBookingExtras.extra',
            ])
            ->first();

        if (! $participant) {
            $this->scanResult = [
                'status' => 'INVALID_CODE',
                'message' => 'Invalid voucher code — not found in the system',
                'code' => $code,
            ];
            $this->addToRecentScans($code, 'INVALID_CODE', null);

            return;
        }

        $booking = $participant->booking;
        $listing = $booking?->listing;

        // Verify vendor owns this listing
        if (! $listing || $listing->vendor_id !== auth()->id()) {
            $this->scanResult = [
                'status' => 'INVALID_CODE',
                'message' => 'This voucher belongs to a different vendor',
                'code' => $code,
            ];
            $this->addToRecentScans($code, 'INVALID_CODE', null);

            return;
        }

        $listingTitle = self::extractTranslation($listing->title);
        $slot = $booking->availabilitySlot;
        $eventDate = $slot?->date?->format('D, M d, Y');
        $eventTime = $slot?->start_time?->format('H:i');

        // Check booking status
        if ($booking->status !== BookingStatus::CONFIRMED) {
            $this->scanResult = [
                'status' => 'NOT_CONFIRMED',
                'message' => 'Booking is not confirmed (status: ' . $booking->status->label() . ')',
                'code' => $code,
                'participantName' => $participant->full_name,
                'listingTitle' => $listingTitle,
            ];
            $this->addToRecentScans($code, 'NOT_CONFIRMED', $participant->full_name);

            return;
        }

        // Check if this is for a different listing than the filter
        if ($this->selectedListingId && $listing->id != $this->selectedListingId) {
            $this->scanResult = [
                'status' => 'WRONG_EVENT',
                'message' => 'This voucher is for a different activity: ' . $listingTitle,
                'code' => $code,
                'participantName' => $participant->full_name,
                'listingTitle' => $listingTitle,
                'eventDate' => $eventDate,
                'eventTime' => $eventTime,
            ];
            $this->addToRecentScans($code, 'WRONG_EVENT', $participant->full_name);

            return;
        }

        // Check if this is for a different date than the filter
        if ($this->selectedDate && $slot) {
            $filterParts = explode('|', $this->selectedDate);
            $filterDate = $filterParts[0] ?? null;
            $filterTime = $filterParts[1] ?? null;

            $slotDate = $slot->date?->toDateString();
            $slotTime = $slot->start_time?->format('H:i');

            if ($filterDate && $slotDate !== $filterDate) {
                $this->scanResult = [
                    'status' => 'WRONG_DATE',
                    'message' => 'This voucher is for a different date: ' . $eventDate . ($eventTime ? ' at ' . $eventTime : ''),
                    'code' => $code,
                    'participantName' => $participant->full_name,
                    'listingTitle' => $listingTitle,
                    'eventDate' => $eventDate,
                    'eventTime' => $eventTime,
                ];
                $this->addToRecentScans($code, 'WRONG_DATE', $participant->full_name);

                return;
            }

            if ($filterTime && $slotTime && $filterTime !== $slotTime) {
                $this->scanResult = [
                    'status' => 'WRONG_DATE',
                    'message' => 'This voucher is for a different time slot: ' . ($eventTime ?? 'N/A'),
                    'code' => $code,
                    'participantName' => $participant->full_name,
                    'listingTitle' => $listingTitle,
                    'eventDate' => $eventDate,
                    'eventTime' => $eventTime,
                ];
                $this->addToRecentScans($code, 'WRONG_DATE', $participant->full_name);

                return;
            }
        }

        // Already checked in?
        if ($participant->checked_in) {
            $this->scanResult = [
                'status' => 'ALREADY_CHECKED_IN',
                'message' => 'Already checked in at ' . $participant->checked_in_at?->format('H:i'),
                'code' => $code,
                'participantId' => $participant->id,
                'participantName' => $participant->full_name,
                'personType' => $participant->person_type,
                'badgeNumber' => $participant->formatted_badge_number,
                'bookingNumber' => $booking->booking_number,
                'listingTitle' => $listingTitle,
                'eventDate' => $eventDate,
                'eventTime' => $eventTime,
                'checkedInAt' => $participant->checked_in_at?->format('H:i'),
            ];
            $this->addToRecentScans($code, 'ALREADY_CHECKED_IN', $participant->full_name);

            return;
        }

        // Build extras summary
        $extras = $booking->activeBookingExtras->map(fn ($be) => [
            'name' => self::extractTranslation($be->extra_name),
            'quantity' => $be->quantity,
        ])->toArray();

        // Valid participant — ready for check-in
        $this->scanResult = [
            'status' => 'VALID',
            'message' => 'Valid participant — Ready to check in',
            'code' => $code,
            'participantId' => $participant->id,
            'participantName' => $participant->full_name ?: 'Name not entered',
            'personType' => $participant->person_type,
            'badgeNumber' => $participant->formatted_badge_number,
            'bookingNumber' => $booking->booking_number,
            'listingTitle' => $listingTitle,
            'eventDate' => $eventDate,
            'eventTime' => $eventTime,
            'quantity' => $booking->quantity,
            'extras' => $extras,
        ];
        $this->addToRecentScans($code, 'VALID', $participant->full_name);
    }

    /**
     * Perform check-in for a participant.
     */
    public function performCheckIn(string $participantId): void
    {
        $participant = BookingParticipant::with('booking.listing')
            ->find($participantId);

        if (! $participant) {
            $this->scanResult = [
                'status' => 'INVALID_CODE',
                'message' => 'Participant not found',
            ];

            return;
        }

        // Re-verify vendor ownership
        if ($participant->booking?->listing?->vendor_id !== auth()->id()) {
            return;
        }

        $participant->checkIn();

        // Update the scan result to reflect checked-in state
        $this->scanResult['status'] = 'CHECKED_IN_SUCCESS';
        $this->scanResult['message'] = 'Successfully checked in!';
        $this->scanResult['checkedInAt'] = now()->format('H:i');

        // Update recent scans
        $this->updateRecentScanStatus($participant->voucher_code, 'CHECKED_IN');

        $this->refreshStats();
    }

    /**
     * Undo a check-in.
     */
    public function undoCheckIn(string $participantId): void
    {
        $participant = BookingParticipant::with('booking.listing')
            ->find($participantId);

        if (! $participant) {
            return;
        }

        // Re-verify vendor ownership
        if ($participant->booking?->listing?->vendor_id !== auth()->id()) {
            return;
        }

        $participant->undoCheckIn();

        // Update the scan result
        if ($this->scanResult && ($this->scanResult['participantId'] ?? null) === $participantId) {
            $this->scanResult['status'] = 'VALID';
            $this->scanResult['message'] = 'Check-in reversed — participant can be checked in again';
            unset($this->scanResult['checkedInAt']);
        }

        $this->updateRecentScanStatus($participant->voucher_code, 'UNDO');

        $this->refreshStats();
    }

    /**
     * Handle manual voucher code input.
     */
    public function manualLookup(): void
    {
        if (! empty($this->scannedCode)) {
            $this->lookupVoucher($this->scannedCode);
        }
    }

    /**
     * Clear the current scan result.
     */
    public function clearResult(): void
    {
        $this->scanResult = null;
        $this->scannedCode = '';
    }

    /**
     * Refresh check-in stats based on current filters.
     */
    public function refreshStats(): void
    {
        $query = BookingParticipant::query()
            ->whereHas('booking', function ($q) {
                $q->where('status', BookingStatus::CONFIRMED->value);
                $q->whereHas('listing', function ($lq) {
                    $lq->where('vendor_id', auth()->id());

                    if ($this->selectedListingId) {
                        $lq->where('id', $this->selectedListingId);
                    }
                });

                if ($this->selectedDate) {
                    $filterParts = explode('|', $this->selectedDate);
                    $filterDate = $filterParts[0] ?? null;
                    $filterTime = $filterParts[1] ?? null;

                    if ($filterDate) {
                        $q->whereHas('availabilitySlot', function ($sq) use ($filterDate, $filterTime) {
                            $sq->whereDate('date', $filterDate);

                            if ($filterTime) {
                                $sq->whereTime('start_time', $filterTime . ':00');
                            }
                        });
                    }
                }
            });

        $total = $query->count();
        $checkedIn = (clone $query)->where('checked_in', true)->count();

        $this->checkInStats = [
            'checkedIn' => $checkedIn,
            'total' => $total,
        ];
    }

    /**
     * Add a scan to the recent scans list.
     */
    private function addToRecentScans(string $code, string $status, ?string $name): void
    {
        array_unshift($this->recentScans, [
            'code' => $code,
            'status' => $status,
            'name' => $name ?: '-',
            'time' => now()->format('H:i:s'),
        ]);

        // Keep only last 20
        $this->recentScans = array_slice($this->recentScans, 0, 20);
    }

    /**
     * Update the status of a recent scan entry.
     */
    private function updateRecentScanStatus(string $code, string $newStatus): void
    {
        foreach ($this->recentScans as $i => $scan) {
            if ($scan['code'] === $code) {
                $this->recentScans[$i]['status'] = $newStatus;
                break;
            }
        }
    }
}
