<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MagicLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Booking $booking
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Access Your Booking #{$this->booking->booking_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.magic-link',
            with: [
                'booking' => $this->booking,
                'magicLink' => $this->booking->getMagicLinkUrl(),
                'participantsLink' => $this->booking->getMagicLinkUrl() . '/participants',
                'vouchersLink' => $this->booking->getMagicLinkUrl() . '/vouchers',
                'expiresAt' => $this->booking->magic_token_expires_at?->format('F j, Y'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
