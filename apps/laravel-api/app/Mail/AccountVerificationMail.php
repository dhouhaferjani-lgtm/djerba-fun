<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountVerificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public string $token,
        public int $claimableBookingsCount = 0
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your Email - Go Adventure',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $frontendUrl = config('app.frontend_url', 'https://dev.go-adventure.net');
        $verificationLink = "{$frontendUrl}/auth/verified?token={$this->token}";

        return new Content(
            view: 'mail.account-verification',
            with: [
                'user' => $this->user,
                'verificationLink' => $verificationLink,
                'claimableBookingsCount' => $this->claimableBookingsCount,
                'expiresInHours' => 24,
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
