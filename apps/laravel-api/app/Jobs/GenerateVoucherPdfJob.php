<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Booking;
use App\Services\VoucherPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateVoucherPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $tries = 3;

    public $timeout = 120; // 2 minutes for PDF generation

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Booking $booking,
        public ?string $voucherCode = null
    ) {
        // Queue on 'default' queue with low priority
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(VoucherPdfService $pdfService): void
    {
        try {
            if ($this->voucherCode) {
                // Generate single voucher
                $participant = $this->booking->participants()
                    ->byVoucherCode($this->voucherCode)
                    ->firstOrFail();

                $pdf = $pdfService->generateSingleVoucher($participant);
                $filename = $pdfService->getFilename($this->booking, $this->voucherCode);
            } else {
                // Generate all vouchers
                $pdf = $pdfService->generateAllVouchers($this->booking);
                $filename = $pdfService->getFilename($this->booking);
            }

            // Save to storage
            $pdfService->savePdf($pdf, $filename);

            Log::info('Voucher PDF generated', [
                'booking_number' => $this->booking->booking_number,
                'voucher_code' => $this->voucherCode,
                'filename' => $filename,
            ]);
        } catch (\Exception $e) {
            Log::error('Voucher PDF generation failed', [
                'booking_number' => $this->booking->booking_number,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }
}
