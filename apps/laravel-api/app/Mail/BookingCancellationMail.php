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

class BookingCancellationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Booking $booking
    ) {
        // Note: Don't load relationships here - they're lost after serialization
        // when using SerializesModels trait. Load them in content() instead.
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Booking Cancelled - ' . $this->booking->booking_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Load relationships when the job processes (after deserialization)
        // This is required because SerializesModels only stores model IDs
        $this->booking->loadMissing(['listing', 'availabilitySlot', 'user']);

        return new Content(
            view: 'mail.booking-cancellation',
            with: [
                'booking' => $this->booking,
                'listing' => $this->booking->listing,
                'travelerInfo' => $this->booking->traveler_info,
                'cancellationReason' => $this->booking->cancellation_reason,
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
