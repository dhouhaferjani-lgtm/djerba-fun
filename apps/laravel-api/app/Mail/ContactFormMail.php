<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The sender's name.
     */
    public string $name;

    /**
     * The sender's email.
     */
    public string $email;

    /**
     * The message content (renamed to avoid conflict with Mailable).
     */
    public string $contactMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(string $name, string $email, string $message)
    {
        $this->name = $name;
        $this->email = $email;
        $this->contactMessage = $message;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->email, $this->name),
            replyTo: [new Address($this->email, $this->name)],
            subject: 'New Contact Form Submission from ' . $this->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-form',
            with: [
                'name' => $this->name,
                'email' => $this->email,
                'contactMessage' => $this->contactMessage,
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
