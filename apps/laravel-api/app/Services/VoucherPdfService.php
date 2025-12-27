<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingParticipant;
use App\Models\PlatformSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class VoucherPdfService
{
    protected PlatformSettings $settings;

    public function __construct()
    {
        $this->settings = PlatformSettings::instance();
    }

    /**
     * Generate PDF for a single voucher.
     */
    public function generateSingleVoucher(BookingParticipant $participant): string
    {
        $booking = $participant->booking;
        $listing = $booking->listing;
        $slot = $booking->availabilitySlot;

        // Generate QR code as base64 image
        $qrCode = $this->generateQrCode($participant->voucher_code);

        $data = [
            'participant' => $participant,
            'booking' => $booking,
            'listing' => $listing,
            'slot' => $slot,
            'qrCode' => $qrCode,
            'platformName' => $this->settings->getTranslation('platform_name', app()->getLocale()) ?? 'Go Adventure',
            'logoUrl' => $this->settings->logo_light_url,
            'colors' => [
                'primary' => $this->settings->brand_color_primary ?? '#0D642E',
                'accent' => $this->settings->brand_color_accent ?? '#8BC34A',
                'cream' => $this->settings->brand_color_cream ?? '#f5f0d1',
            ],
        ];

        $pdf = Pdf::loadView('pdf.voucher', $data);

        return $pdf->output();
    }

    /**
     * Generate combined PDF with all vouchers for a booking.
     */
    public function generateAllVouchers(Booking $booking): string
    {
        $participants = $booking->participants()
            ->with(['booking.listing', 'booking.availabilitySlot'])
            ->orderBy('badge_number')
            ->orderBy('created_at')
            ->get();

        $vouchers = [];

        foreach ($participants as $participant) {
            $vouchers[] = [
                'participant' => $participant,
                'qrCode' => $this->generateQrCode($participant->voucher_code),
            ];
        }

        $data = [
            'vouchers' => $vouchers,
            'booking' => $booking,
            'listing' => $booking->listing,
            'slot' => $booking->availabilitySlot,
            'platformName' => $this->settings->getTranslation('platform_name', app()->getLocale()) ?? 'Go Adventure',
            'logoUrl' => $this->settings->logo_light_url,
            'colors' => [
                'primary' => $this->settings->brand_color_primary ?? '#0D642E',
                'accent' => $this->settings->brand_color_accent ?? '#8BC34A',
                'cream' => $this->settings->brand_color_cream ?? '#f5f0d1',
            ],
        ];

        $pdf = Pdf::loadView('pdf.vouchers-batch', $data);

        return $pdf->output();
    }

    /**
     * Generate QR code as base64 data URI.
     */
    private function generateQrCode(string $voucherCode): string
    {
        // Use SVG format instead of PNG to avoid imagick dependency
        $qrCode = QrCode::format('svg')
            ->size(200)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($voucherCode);

        // Convert HtmlString to string before encoding
        return 'data:image/svg+xml;base64,' . base64_encode((string) $qrCode);
    }

    /**
     * Save PDF to storage and return path.
     */
    public function savePdf(string $pdfContent, string $filename): string
    {
        $path = "vouchers/{$filename}";
        Storage::disk('local')->put($path, $pdfContent);

        return $path;
    }

    /**
     * Get filename for voucher PDF.
     */
    public function getFilename(Booking $booking, ?string $voucherCode = null): string
    {
        $base = "voucher-{$booking->booking_number}";

        if ($voucherCode) {
            return "{$base}-{$voucherCode}.pdf";
        }

        return "{$base}-all.pdf";
    }
}
