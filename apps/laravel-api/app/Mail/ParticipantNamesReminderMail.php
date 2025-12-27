<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParticipantNamesReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Booking $booking,
        public int $daysUntilActivity = 0
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $urgent = $this->daysUntilActivity <= 7;
        $subject = $urgent
            ? "Action Required: Participant Names Needed - {$this->booking->booking_number}"
            : "Reminder: Add Participant Names - {$this->booking->booking_number}";

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        // Get magic link for the booking
        $magicToken = $this->booking->magic_token;
        $participantsLink = $magicToken
            ? "{$frontendUrl}/bookings/magic/{$magicToken}/participants"
            : "{$frontendUrl}/dashboard/bookings/{$this->booking->id}/participants";

        return new Content(
            view: 'mail.participant-names-reminder',
            with: [
                'booking' => $this->booking,
                'listing' => $this->booking->listing,
                'slot' => $this->booking->availabilitySlot,
                'participantsLink' => $participantsLink,
                'daysUntilActivity' => $this->daysUntilActivity,
                'isUrgent' => $this->daysUntilActivity <= 7,
                'travelerName' => $this->booking->billing_contact['first_name'] ?? 'Adventurer',
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
