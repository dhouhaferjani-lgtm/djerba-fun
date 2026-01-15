<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for vendor voucher operations.
 */
class VendorVoucherController extends Controller
{
    /**
     * Download voucher for a booking.
     */
    public function download(Request $request, Booking $booking): JsonResponse
    {
        // TODO: Implement voucher download
        return response()->json([
            'message' => 'Voucher download not yet implemented',
        ], 501);
    }

    /**
     * Email voucher to customer.
     */
    public function email(Request $request, Booking $booking): JsonResponse
    {
        // TODO: Implement voucher email
        return response()->json([
            'message' => 'Voucher email not yet implemented',
        ], 501);
    }
}
