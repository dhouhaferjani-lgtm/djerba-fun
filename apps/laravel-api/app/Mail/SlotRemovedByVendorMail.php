<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\BookingHold;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notify a customer that their pending hold was cancelled because the vendor
 * removed the underlying availability slot from the rule.
 *
 * Sent during the AvailabilityRule smart-diff cleanup path. Carries enough
 * context (listing title, original date/time) for the customer to re-book
 * an alternative slot.
 */
class SlotRemovedByVendorMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly BookingHold $hold,
        public readonly string $listingTitle,
        public readonly string $slotDate,
        public readonly string $slotStartTime,
        public readonly string $slotEndTime,
    ) {
        $locale = $hold->user?->preferred_locale ?? 'fr';
        $this->locale($locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: __('mail.subject_slot_removed_by_vendor', [
                'listing' => $this->listingTitle,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.slot-removed-by-vendor',
            with: [
                'hold' => $this->hold,
                'listingTitle' => $this->listingTitle,
                'slotDate' => $this->slotDate,
                'slotStartTime' => $this->slotStartTime,
                'slotEndTime' => $this->slotEndTime,
            ],
        );
    }
}
