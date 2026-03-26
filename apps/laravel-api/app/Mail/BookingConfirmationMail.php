<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Booking $booking
    ) {
        $this->locale($booking->locale ?? 'fr');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: __('mail.subject_booking_confirmation', ['number' => $this->booking->booking_number]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Load relationships when the job processes (after deserialization)
        // This is required because SerializesModels only stores model IDs
        $this->booking->loadMissing(['listing', 'availabilitySlot', 'user', 'coupon']);

        $locale = $this->booking->locale ?? 'fr';
        $listingTitle = __('mail.activity_fallback');
        if ($this->booking->listing) {
            $title = $this->booking->listing->getTranslation('title', $locale);
            if (is_string($title) && !empty($title)) {
                $listingTitle = $title;
            } elseif (is_array($title)) {
                $listingTitle = $title[$locale] ?? $title['en'] ?? $title['fr'] ?? reset($title) ?: __('mail.activity_fallback');
            }
        }

        return new Content(
            view: 'mail.booking-confirmation',
            with: [
                'booking' => $this->booking,
                'listing' => $this->booking->listing,
                'listingTitle' => $listingTitle,
                'slot' => $this->booking->availabilitySlot,
                'travelerInfo' => $this->booking->traveler_info,
                'magicLink' => $this->booking->getMagicLinkUrl(),
                'participantsLink' => $this->booking->getMagicLinkUrl() . '/participants',
                'vouchersLink' => $this->booking->getMagicLinkUrl() . '/vouchers',
                'magicLinkExpiresAt' => $this->booking->magic_token_expires_at?->translatedFormat(__('mail.date_format_long')),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
