<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Booking;
use App\Models\BookingParticipant;
use App\Models\PlatformSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VoucherMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    protected PlatformSettings $settings;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Booking $booking,
        public ?BookingParticipant $participant,
        public string $pdfContent
    ) {
        $this->booking->load(['listing', 'availabilitySlot']);
        $this->settings = PlatformSettings::instance();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->participant
            ? "Your Voucher - {$this->booking->booking_number}"
            : "Your Vouchers - {$this->booking->booking_number}";

        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.voucher',
            with: [
                'booking' => $this->booking,
                'participant' => $this->participant,
                'listing' => $this->booking->listing,
                'slot' => $this->booking->availabilitySlot,
                'isSingleVoucher' => $this->participant !== null,
                'platformName' => $this->settings->getTranslation('platform_name', app()->getLocale()) ?? 'Go Adventure',
                'logoUrl' => $this->settings->logo_light_url,
                'colors' => [
                    'primary' => $this->settings->brand_color_primary ?? '#0D642E',
                    'accent' => $this->settings->brand_color_accent ?? '#8BC34A',
                    'cream' => $this->settings->brand_color_cream ?? '#f5f0d1',
                ],
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $filename = $this->participant
            ? "voucher-{$this->participant->voucher_code}.pdf"
            : "vouchers-{$this->booking->booking_number}.pdf";

        return [
            Attachment::fromData(fn () => $this->pdfContent, $filename)
                ->withMime('application/pdf'),
        ];
    }
}
