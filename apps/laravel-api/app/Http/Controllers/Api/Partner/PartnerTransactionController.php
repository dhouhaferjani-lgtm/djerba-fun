<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Models\PartnerTransaction;
use Illuminate\Http\Request;

class PartnerTransactionController extends Controller
{
    /**
     * List all transactions for this partner.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $partner = $request->attributes->get('partner');

        // Check permission
        if (! $partner->hasPermission('transactions:read') && ! $partner->hasPermission('*')) {
            abort(403, 'Partner does not have permission to view transactions');
        }

        $validated = $request->validate([
            'type' => 'nullable|string|in:charge,payment,refund,adjustment',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PartnerTransaction::where('partner_id', $partner->id)
            ->with('booking:id,listing_id')
            ->latest();

        // Filter by type
        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        // Filter by date range
        if (isset($validated['from_date'])) {
            $query->where('created_at', '>=', $validated['from_date']);
        }

        if (isset($validated['to_date'])) {
            $query->where('created_at', '<=', $validated['to_date']);
        }

        $transactions = $query->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'data' => $transactions->items(),
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ],
        ]);
    }

    /**
     * Get a specific transaction.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, PartnerTransaction $transaction)
    {
        $partner = $request->attributes->get('partner');

        // Check permission
        if (! $partner->hasPermission('transactions:read') && ! $partner->hasPermission('*')) {
            abort(403, 'Partner does not have permission to view transactions');
        }

        // Verify this transaction belongs to this partner
        if ($transaction->partner_id !== $partner->id) {
            abort(404, 'Transaction not found');
        }

        $transaction->load('booking:id,listing_id,partner_metadata');

        return response()->json([
            'data' => $transaction,
        ]);
    }
}
