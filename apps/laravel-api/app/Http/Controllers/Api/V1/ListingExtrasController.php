<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingExtraResource;
use App\Models\Listing;
use App\Services\ExtrasService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListingExtrasController extends Controller
{
    public function __construct(
        private readonly ExtrasService $extrasService
    ) {}

    /**
     * Get available extras for a listing.
     *
     * GET /api/v1/listings/{listing}/extras
     */
    public function index(Request $request, Listing $listing): AnonymousResourceCollection
    {
        $slotId = $request->query('slot_id');
        $personTypes = $request->query('person_types', []);

        if (is_string($personTypes)) {
            $personTypes = explode(',', $personTypes);
        }

        // Get listing extras with extra relationship
        $listingExtras = $listing->listingExtras()
            ->with('extra')
            ->active()
            ->ordered()
            ->get()
            ->filter(function ($listingExtra) use ($slotId, $personTypes) {
                // Filter by active extra
                if (! $listingExtra->extra || ! $listingExtra->extra->is_active) {
                    return false;
                }

                // Filter by slot availability
                if ($slotId && ! $listingExtra->isAvailableForSlot($slotId)) {
                    return false;
                }

                // Filter by person types
                if (! empty($personTypes) && $listingExtra->available_for_person_types !== null) {
                    $matchingTypes = array_intersect(
                        array_map('strtolower', $personTypes),
                        array_map('strtolower', $listingExtra->available_for_person_types)
                    );

                    if (empty($matchingTypes)) {
                        return false;
                    }
                }

                return true;
            });

        return ListingExtraResource::collection($listingExtras);
    }

    /**
     * Calculate pricing for selected extras.
     *
     * POST /api/v1/listings/{listing}/extras/calculate
     */
    public function calculate(Request $request, Listing $listing): array
    {
        $validated = $request->validate([
            'extras' => 'required|array',
            'extras.*.id' => 'required|string|uuid',
            'extras.*.quantity' => 'required|integer|min:1',
            'person_types' => 'sometimes|array',
            'currency' => 'sometimes|string|in:TND,EUR',
        ]);

        $currency = $validated['currency'] ?? $request->header('X-Currency', 'TND');
        $personTypeBreakdown = $validated['person_types'] ?? [];

        // Validate the selection
        $errors = $this->extrasService->validateSelection(
            $listing,
            $validated['extras'],
            $personTypeBreakdown
        );

        if (! empty($errors)) {
            return [
                'valid' => false,
                'errors' => $errors,
            ];
        }

        // Calculate totals
        $calculation = $this->extrasService->calculateExtrasTotal(
            $validated['extras'],
            $personTypeBreakdown,
            $currency
        );

        return [
            'valid' => true,
            'calculation' => $calculation,
        ];
    }
}
