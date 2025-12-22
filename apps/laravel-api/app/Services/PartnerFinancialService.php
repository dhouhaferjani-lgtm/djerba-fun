<?php

namespace App\Services;

use App\Enums\PartnerTransactionType;
use App\Models\Booking;
use App\Models\Partner;
use App\Models\PartnerTransaction;
use Illuminate\Support\Facades\DB;

class PartnerFinancialService
{
    /**
     * Create a charge transaction when a booking is created.
     *
     * @param Booking $booking
     * @param Partner $partner
     * @return PartnerTransaction
     */
    public function createChargeForBooking(Booking $booking, Partner $partner): PartnerTransaction
    {
        return DB::transaction(function () use ($booking, $partner) {
            $amount = $booking->pricing_snapshot['total'] ?? 0;
            $currency = $booking->pricing_snapshot['currency'] ?? 'EUR';

            $currentBalance = $partner->getCurrentBalance();
            $newBalance = $currentBalance + $amount;

            return PartnerTransaction::create([
                'partner_id' => $partner->id,
                'booking_id' => $booking->id,
                'type' => PartnerTransactionType::CHARGE->value,
                'amount' => $amount,
                'currency' => $currency,
                'balance_after' => $newBalance,
                'description' => "Booking charge for #{$booking->id}",
                'metadata' => [
                    'booking_id' => $booking->id,
                    'listing_id' => $booking->listing_id,
                    'listing_title' => $booking->listing->title ?? null,
                ],
            ]);
        });
    }

    /**
     * Create a refund transaction when a booking is cancelled.
     *
     * @param Booking $booking
     * @param Partner $partner
     * @return PartnerTransaction
     */
    public function createRefundForBooking(Booking $booking, Partner $partner): PartnerTransaction
    {
        return DB::transaction(function () use ($booking, $partner) {
            $amount = $booking->pricing_snapshot['total'] ?? 0;
            $currency = $booking->pricing_snapshot['currency'] ?? 'EUR';

            $currentBalance = $partner->getCurrentBalance();
            $newBalance = $currentBalance - $amount;

            return PartnerTransaction::create([
                'partner_id' => $partner->id,
                'booking_id' => $booking->id,
                'type' => PartnerTransactionType::REFUND->value,
                'amount' => $amount,
                'currency' => $currency,
                'balance_after' => $newBalance,
                'description' => "Booking refund for #{$booking->id}",
                'metadata' => [
                    'booking_id' => $booking->id,
                    'listing_id' => $booking->listing_id,
                    'original_charge_date' => $booking->created_at->toISOString(),
                ],
            ]);
        });
    }

    /**
     * Create a payment transaction when partner pays platform.
     *
     * @param Partner $partner
     * @param float $amount
     * @param string $currency
     * @param string $paymentMethod
     * @param string $paymentReference
     * @param array $metadata
     * @return PartnerTransaction
     */
    public function createPayment(
        Partner $partner,
        float $amount,
        string $currency = 'EUR',
        string $paymentMethod = 'bank_transfer',
        string $paymentReference = '',
        array $metadata = []
    ): PartnerTransaction {
        return DB::transaction(function () use ($partner, $amount, $currency, $paymentMethod, $paymentReference, $metadata) {
            $currentBalance = $partner->getCurrentBalance();
            $newBalance = $currentBalance - $amount;

            return PartnerTransaction::create([
                'partner_id' => $partner->id,
                'type' => PartnerTransactionType::PAYMENT->value,
                'amount' => $amount,
                'currency' => $currency,
                'balance_after' => $newBalance,
                'description' => 'Payment to platform',
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentReference,
                'metadata' => $metadata,
                'processed_at' => now(),
            ]);
        });
    }

    /**
     * Create an adjustment transaction (manual admin adjustment).
     *
     * @param Partner $partner
     * @param float $amount
     * @param string $currency
     * @param string $description
     * @param array $metadata
     * @return PartnerTransaction
     */
    public function createAdjustment(
        Partner $partner,
        float $amount,
        string $currency = 'EUR',
        string $description = 'Manual adjustment',
        array $metadata = []
    ): PartnerTransaction {
        return DB::transaction(function () use ($partner, $amount, $currency, $description, $metadata) {
            $currentBalance = $partner->getCurrentBalance();

            // Positive amount increases balance, negative decreases
            $newBalance = $currentBalance + $amount;

            return PartnerTransaction::create([
                'partner_id' => $partner->id,
                'type' => PartnerTransactionType::ADJUSTMENT->value,
                'amount' => abs($amount),
                'currency' => $currency,
                'balance_after' => $newBalance,
                'description' => $description,
                'metadata' => array_merge($metadata, [
                    'adjustment_direction' => $amount >= 0 ? 'increase' : 'decrease',
                ]),
                'processed_at' => now(),
            ]);
        });
    }

    /**
     * Calculate total revenue for a partner in a date range.
     *
     * @param Partner $partner
     * @param \DateTime|null $fromDate
     * @param \DateTime|null $toDate
     * @return float
     */
    public function calculateRevenue(Partner $partner, ?\DateTime $fromDate = null, ?\DateTime $toDate = null): float
    {
        $query = PartnerTransaction::where('partner_id', $partner->id)
            ->where('type', PartnerTransactionType::CHARGE->value);

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }

        return $query->sum('amount');
    }

    /**
     * Get transaction summary for a partner.
     *
     * @param Partner $partner
     * @return array
     */
    public function getTransactionSummary(Partner $partner): array
    {
        $charges = PartnerTransaction::where('partner_id', $partner->id)
            ->where('type', PartnerTransactionType::CHARGE->value)
            ->sum('amount');

        $payments = PartnerTransaction::where('partner_id', $partner->id)
            ->where('type', PartnerTransactionType::PAYMENT->value)
            ->sum('amount');

        $refunds = PartnerTransaction::where('partner_id', $partner->id)
            ->where('type', PartnerTransactionType::REFUND->value)
            ->sum('amount');

        $adjustments = PartnerTransaction::where('partner_id', $partner->id)
            ->where('type', PartnerTransactionType::ADJUSTMENT->value)
            ->sum(DB::raw("CASE WHEN metadata->>'adjustment_direction' = 'increase' THEN amount ELSE -amount END"));

        $currentBalance = $partner->getCurrentBalance();

        return [
            'total_charges' => round($charges, 2),
            'total_payments' => round($payments, 2),
            'total_refunds' => round($refunds, 2),
            'total_adjustments' => round($adjustments, 2),
            'current_balance' => round($currentBalance, 2),
            'currency' => 'EUR', // TODO: Make configurable
        ];
    }

    /**
     * Check if partner can make a payment of given amount.
     *
     * @param Partner $partner
     * @param float $amount
     * @return bool
     */
    public function canMakePayment(Partner $partner, float $amount): bool
    {
        $currentBalance = $partner->getCurrentBalance();

        return $amount > 0 && $amount <= $currentBalance;
    }
}
