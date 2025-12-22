<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PartnerPaymentController extends Controller
{
    /**
     * Initiate a payment to settle partner balance.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function initiate(Request $request)
    {
        $partner = $request->attributes->get('partner');

        // Check permission
        if (!$partner->hasPermission('payments:create') && !$partner->hasPermission('*')) {
            abort(403, 'Partner does not have permission to initiate payments');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|in:EUR,USD,GBP,CAD',
            'payment_method' => 'required|string|in:bank_transfer,credit_card,paypal,stripe',
            'payment_reference' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $currentBalance = $partner->getCurrentBalance();

        // Validate amount doesn't exceed balance
        if ($validated['amount'] > $currentBalance) {
            throw ValidationException::withMessages([
                'amount' => ['Payment amount cannot exceed current balance.'],
            ]);
        }

        // Create payment transaction
        $transaction = DB::transaction(function () use ($partner, $validated, $currentBalance) {
            $currency = $validated['currency'] ?? 'EUR';
            $newBalance = $currentBalance - $validated['amount'];

            $transaction = PartnerTransaction::create([
                'partner_id' => $partner->id,
                'type' => 'payment',
                'amount' => $validated['amount'],
                'currency' => $currency,
                'balance_after' => $newBalance,
                'description' => 'Payment to platform',
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'],
                'metadata' => [
                    'notes' => $validated['notes'] ?? null,
                    'ip_address' => request()->ip(),
                ],
                'processed_at' => now(),
            ]);

            // Note: In production, this would integrate with actual payment gateway
            // For now, we're recording the payment intent
            // Admin would manually verify and mark as processed

            return $transaction;
        });

        return response()->json([
            'message' => 'Payment initiated successfully',
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'balance_after' => $transaction->balance_after,
                'payment_method' => $transaction->payment_method,
                'payment_reference' => $transaction->payment_reference,
                'status' => 'pending_verification',
                'created_at' => $transaction->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Get payment history.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function history(Request $request)
    {
        $partner = $request->attributes->get('partner');

        // Check permission
        if (!$partner->hasPermission('payments:read') && !$partner->hasPermission('*')) {
            abort(403, 'Partner does not have permission to view payment history');
        }

        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PartnerTransaction::where('partner_id', $partner->id)
            ->where('type', 'payment')
            ->latest();

        // Filter by date range
        if (isset($validated['from_date'])) {
            $query->where('created_at', '>=', $validated['from_date']);
        }

        if (isset($validated['to_date'])) {
            $query->where('created_at', '<=', $validated['to_date']);
        }

        $payments = $query->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'data' => $payments->items(),
            'pagination' => [
                'total' => $payments->total(),
                'per_page' => $payments->perPage(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem(),
            ],
        ]);
    }
}
