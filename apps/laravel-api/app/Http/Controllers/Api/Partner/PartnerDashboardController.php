<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\PartnerTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartnerDashboardController extends Controller
{
    /**
     * Get partner analytics dashboard data.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function analytics(Request $request)
    {
        $partner = $request->attributes->get('partner');

        // Check permission
        if (!$partner->hasPermission('analytics:read') && !$partner->hasPermission('*')) {
            abort(403, 'Partner does not have permission to view analytics');
        }

        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $fromDate = $validated['from_date'] ?? now()->subDays(30);
        $toDate = $validated['to_date'] ?? now();

        // Total bookings
        $totalBookings = Booking::where('partner_id', $partner->id)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->count();

        // Bookings by status
        $bookingsByStatus = Booking::where('partner_id', $partner->id)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Total revenue (from pricing_snapshot)
        $totalRevenue = Booking::where('partner_id', $partner->id)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->whereIn('status', ['confirmed', 'completed'])
            ->get()
            ->sum(function ($booking) {
                return $booking->pricing_snapshot['total'] ?? 0;
            });

        // Average booking value
        $avgBookingValue = $totalBookings > 0 ? $totalRevenue / $totalBookings : 0;

        // Top listings by bookings
        $topListings = Booking::where('partner_id', $partner->id)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->select('listing_id', DB::raw('count(*) as booking_count'))
            ->with('listing:id,title')
            ->groupBy('listing_id')
            ->orderByDesc('booking_count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'listing_id' => $item->listing_id,
                    'listing_title' => $item->listing->title ?? 'Unknown',
                    'booking_count' => $item->booking_count,
                ];
            });

        // Bookings over time (daily aggregation)
        $bookingsOverTime = Booking::where('partner_id', $partner->id)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count'),
                DB::raw('SUM(CAST(pricing_snapshot->>\'total\' AS DECIMAL(10,2))) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'summary' => [
                'total_bookings' => $totalBookings,
                'total_revenue' => round($totalRevenue, 2),
                'avg_booking_value' => round($avgBookingValue, 2),
                'bookings_by_status' => $bookingsByStatus,
            ],
            'top_listings' => $topListings,
            'bookings_over_time' => $bookingsOverTime,
        ]);
    }

    /**
     * Get partner current balance.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function balance(Request $request)
    {
        $partner = $request->attributes->get('partner');

        // Check permission
        if (!$partner->hasPermission('balance:read') && !$partner->hasPermission('*')) {
            abort(403, 'Partner does not have permission to view balance');
        }

        $currentBalance = $partner->getCurrentBalance();

        // Get recent transactions
        $recentTransactions = PartnerTransaction::where('partner_id', $partner->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'balance_after' => $transaction->balance_after,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at->toISOString(),
                ];
            });

        // Get total charges (amount owed to platform)
        $totalCharges = PartnerTransaction::where('partner_id', $partner->id)
            ->where('type', 'charge')
            ->sum('amount');

        // Get total payments (amount paid to platform)
        $totalPayments = PartnerTransaction::where('partner_id', $partner->id)
            ->where('type', 'payment')
            ->sum('amount');

        // Get total refunds (reduces amount owed)
        $totalRefunds = PartnerTransaction::where('partner_id', $partner->id)
            ->where('type', 'refund')
            ->sum('amount');

        return response()->json([
            'current_balance' => round($currentBalance, 2),
            'currency' => 'EUR', // TODO: Make this configurable
            'total_charges' => round($totalCharges, 2),
            'total_payments' => round($totalPayments, 2),
            'total_refunds' => round($totalRefunds, 2),
            'recent_transactions' => $recentTransactions,
        ]);
    }
}
