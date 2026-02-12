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
        $this->locale($vendor->preferred_locale ?? 'fr');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $locale = app()->getLocale();
        $listingTitle = $this->listing->getTranslation('title', $locale) ?: __('mail.untitled_listing');
        if (is_array($listingTitle)) {
            $listingTitle = $listingTitle[$locale] ?? $listingTitle['en'] ?? reset($listingTitle) ?: __('mail.untitled_listing');
        }

        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: __('mail.subject_listing_failed', ['title' => $listingTitle]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $locale = app()->getLocale();
        $listingTitle = $this->listing->getTranslation('title', $locale) ?: __('mail.untitled_listing');
        if (is_array($listingTitle)) {
            $listingTitle = $listingTitle[$locale] ?? $listingTitle['en'] ?? reset($listingTitle) ?: __('mail.untitled_listing');
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
