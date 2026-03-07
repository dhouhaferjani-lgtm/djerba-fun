<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Jobs\SendVoucherEmailJob;
use App\Models\Booking;
use App\Services\VoucherPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Controller for vendor voucher operations.
 *
 * Vendors can download and email vouchers for bookings associated with their listings.
 */
class VendorVoucherController extends Controller
{
    public function __construct(
        private VoucherPdfService $pdfService
    ) {}

    /**
     * Download all vouchers for a booking as PDF.
     */
    public function download(Request $request, Booking $booking): Response|JsonResponse
    {
        // Authorize: vendor must own the listing
        $this->authorizeVendorAccess($booking);

        if (! $booking->canGenerateVouchers()) {
            return response()->json([
                'message' => 'Vouchers are not available yet. Booking must be confirmed and participant names complete.',
            ], 422);
        }

        $pdf = $this->pdfService->generateAllVouchers($booking);
        $filename = $this->pdfService->getFilename($booking);

        return response($pdf)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Email all vouchers to customer.
     */
    public function email(Request $request, Booking $booking): JsonResponse
    {
        // Authorize: vendor must own the listing
        $this->authorizeVendorAccess($booking);

        if (! $booking->canGenerateVouchers()) {
            return response()->json([
                'message' => 'Vouchers are not available yet. Booking must be confirmed and participant names complete.',
            ], 422);
        }

        $email = $request->input('email') ?? $booking->getPrimaryEmail();

        if (! $email) {
            return response()->json([
                'message' => 'No email address available. Please provide an email address.',
            ], 422);
        }

        // Queue email job (sends all vouchers to the specified email)
        SendVoucherEmailJob::dispatch($booking, null, $email);

        return response()->json([
            'message' => 'Vouchers will be sent to '.$email,
            'email' => $email,
            'voucher_count' => $booking->participants()->count(),
        ]);
    }

    /**
     * Verify that the authenticated vendor owns the listing associated with this booking.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function authorizeVendorAccess(Booking $booking): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(401, 'Authentication required');
        }

        // Verify user has a vendor profile (is a vendor)
        if (! $user->vendorProfile) {
            abort(403, 'You must be a vendor to access this resource');
        }

        // Load the listing relationship if not already loaded
        $booking->loadMissing('listing');

        // vendor_id on listings refers to User ID, not VendorProfile ID
        if (! $booking->listing || $booking->listing->vendor_id !== $user->id) {
            abort(403, 'You can only access vouchers for bookings associated with your own listings');
        }
    }
}
