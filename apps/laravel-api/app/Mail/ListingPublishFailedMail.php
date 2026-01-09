<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ListingPublishFailedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  array<string>  $errors  List of validation errors
     * @param  string|null  $editUrl  URL to edit the listing (optional, will be generated if not provided)
     */
    public function __construct(
        public Listing $listing,
        public User $vendor,
        public array $errors,
        public ?string $editUrl = null
    ) {
        $this->listing->load(['location']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $listingTitle = $this->listing->getTranslation('title', 'en') ?: 'Untitled Listing';
        if (is_array($listingTitle)) {
            $listingTitle = $listingTitle['en'] ?? reset($listingTitle) ?: 'Untitled Listing';
        }

        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Action Required: Your listing "' . $listingTitle . '" cannot be published',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $listingTitle = $this->listing->getTranslation('title', 'en') ?: 'Untitled Listing';
        if (is_array($listingTitle)) {
            $listingTitle = $listingTitle['en'] ?? reset($listingTitle) ?: 'Untitled Listing';
        }

        // Use provided URL or generate fallback
        $editUrl = $this->editUrl ?? \App\Filament\Vendor\Resources\ListingResource::getUrl('edit', ['record' => $this->listing], panel: 'vendor');

        return new Content(
            view: 'mail.listing-publish-failed',
            with: [
                'listing' => $this->listing,
                'listingTitle' => $listingTitle,
                'vendor' => $this->vendor,
                'errors' => $this->errors,
                'editUrl' => $editUrl,
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
