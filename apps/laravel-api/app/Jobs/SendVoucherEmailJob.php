<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\VoucherMail;
use App\Models\Booking;
use App\Services\VoucherPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendVoucherEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;

    public $timeout = 180; // 3 minutes for email with attachment

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Booking $booking,
        public ?string $voucherCode = null,
        public ?string $recipientEmail = null
    ) {
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(VoucherPdfService $pdfService): void
    {
        try {
            if ($this->voucherCode) {
                // Send single voucher
                $participant = $this->booking->participants()
                    ->byVoucherCode($this->voucherCode)
                    ->firstOrFail();

                $email = $this->recipientEmail ?? $participant->email ?? $this->booking->getPrimaryEmail();

                if (! $email) {
                    throw new \RuntimeException('No email address available for voucher delivery');
                }

                $pdf = $pdfService->generateSingleVoucher($participant);

                Mail::to($email)->send(new VoucherMail($this->booking, $participant, $pdf));
            } else {
                // Send all vouchers
                $email = $this->recipientEmail ?? $this->booking->getPrimaryEmail();

                if (! $email) {
                    throw new \RuntimeException('No email address available for voucher delivery');
                }

                $pdf = $pdfService->generateAllVouchers($this->booking);

                Mail::to($email)->send(new VoucherMail($this->booking, null, $pdf));
            }

            Log::info('Voucher email sent', [
                'booking_number' => $this->booking->booking_number,
                'voucher_code' => $this->voucherCode,
                'email' => $email,
            ]);
        } catch (\Exception $e) {
            Log::error('Voucher email failed', [
                'booking_number' => $this->booking->booking_number,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
