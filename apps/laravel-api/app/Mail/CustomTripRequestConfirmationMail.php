<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\CustomTripRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomTripRequestConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public CustomTripRequest $customTripRequest
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: 'Your Custom Trip Request - ' . $this->customTripRequest->reference,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $travelDates = $this->customTripRequest->travel_start_date->format('F j, Y')
            . ' - ' . $this->customTripRequest->travel_end_date->format('F j, Y');

        $totalTravelers = $this->customTripRequest->adults + $this->customTripRequest->children;
        $travelerSummary = $this->customTripRequest->adults . ' adult' . ($this->customTripRequest->adults > 1 ? 's' : '');
        if ($this->customTripRequest->children > 0) {
            $travelerSummary .= ', ' . $this->customTripRequest->children . ' child' . ($this->customTripRequest->children > 1 ? 'ren' : '');
        }

        // Format interests as readable list
        $interests = is_array($this->customTripRequest->interests)
            ? implode(', ', array_map(fn($i) => ucwords(str_replace('_', ' ', $i)), $this->customTripRequest->interests))
            : '';

        return new Content(
            view: 'mail.custom-trip-request-confirmation',
            with: [
                'customTripRequest' => $this->customTripRequest,
                'contactName' => $this->customTripRequest->contact_name,
                'reference' => $this->customTripRequest->reference,
                'travelDates' => $travelDates,
                'datesFlexible' => $this->customTripRequest->dates_flexible,
                'durationDays' => $this->customTripRequest->duration_days,
                'totalTravelers' => $totalTravelers,
                'travelerSummary' => $travelerSummary,
                'interests' => $interests,
                'budget' => $this->customTripRequest->budget_per_person,
                'budgetCurrency' => $this->customTripRequest->budget_currency,
                'accommodationStyle' => ucwords(str_replace('_', ' ', $this->customTripRequest->accommodation_style ?? '')),
                'travelPace' => ucwords(str_replace('_', ' ', $this->customTripRequest->travel_pace ?? '')),
                'specialRequests' => $this->customTripRequest->special_requests,
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
