<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingExtraStatus;
use App\Enums\ExtraPricingType;
use App\Enums\InventoryChangeType;
use App\Models\Booking;
use App\Models\BookingExtra;
use App\Models\Extra;
use App\Models\Listing;
use App\Models\ListingExtra;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExtrasService
{
    /**
     * Get available extras for a listing.
     *
     * @param Listing $listing
     * @param string|null $slotId Filter by slot availability
     * @param array $personTypes Filter by person types in booking
     * @param string $locale Locale for translatable fields
     * @return Collection
     */
    public function getAvailableExtras(
        Listing $listing,
        ?string $slotId = null,
        array $personTypes = [],
        string $locale = 'en'
    ): Collection {
        return $listing->listingExtras()
            ->with('extra')
            ->active()
            ->ordered()
            ->get()
            ->filter(function (ListingExtra $listingExtra) use ($slotId, $personTypes) {
                // Check if extra is active
                if (!$listingExtra->extra || !$listingExtra->extra->is_active) {
                    return false;
                }

                // Check slot availability
                if ($slotId && !$listingExtra->isAvailableForSlot($slotId)) {
                    return false;
                }

                // Check person type availability (at least one must match)
                if (!empty($personTypes) && $listingExtra->available_for_person_types !== null) {
                    $matchingTypes = array_intersect(
                        array_map('strtolower', $personTypes),
                        array_map('strtolower', $listingExtra->available_for_person_types)
                    );
                    if (empty($matchingTypes)) {
                        return false;
                    }
                }

                return true;
            })
            ->map(fn (ListingExtra $le) => $this->formatForBookingFlow($le, $locale));
    }

    /**
     * Format a listing extra for the booking flow.
     */
    public function formatForBookingFlow(ListingExtra $listingExtra, string $locale = 'en'): array
    {
        $extra = $listingExtra->extra;

        return [
            'id' => $listingExtra->id,
            'extraId' => $extra->id,
            'name' => $extra->getTranslation('name', $locale),
            'description' => $extra->getTranslation('description', $locale),
            'shortDescription' => $extra->getTranslation('short_description', $locale),
            'imageUrl' => $extra->image_url,
            'pricingType' => $extra->pricing_type->value,
            'category' => $extra->category?->value,
            'priceTnd' => $listingExtra->getEffectivePrice('TND'),
            'priceEur' => $listingExtra->getEffectivePrice('EUR'),
            'personTypePrices' => $listingExtra->getEffectivePersonTypePrices(),
            'minQuantity' => $listingExtra->getEffectiveMinQuantity(),
            'maxQuantity' => $listingExtra->getEffectiveMaxQuantity(),
            'isRequired' => $listingExtra->getEffectiveIsRequired(),
            'isFeatured' => $listingExtra->is_featured,
            'allowQuantityChange' => $extra->allow_quantity_change,
            'trackInventory' => $extra->track_inventory,
            'inventoryCount' => $extra->inventory_count,
            'hasAvailableInventory' => !$extra->track_inventory || ($extra->inventory_count ?? 0) > 0,
        ];
    }

    /**
     * Calculate pricing for selected extras.
     *
     * @param array $selectedExtras Array of ['id' => listing_extra_id, 'quantity' => int]
     * @param array $personTypeBreakdown ['adult' => 2, 'child' => 1]
     * @param string $currency 'TND' or 'EUR'
     * @return array
     */
    public function calculateExtrasTotal(
        array $selectedExtras,
        array $personTypeBreakdown,
        string $currency
    ): array {
        $items = [];
        $subtotal = 0;

        foreach ($selectedExtras as $selection) {
            $listingExtra = ListingExtra::with('extra')->find($selection['id']);
            if (!$listingExtra || !$listingExtra->extra) {
                continue;
            }

            $quantity = $selection['quantity'] ?? 1;
            $extra = $listingExtra->extra;

            // Calculate using the listing extra's calculation method
            $calculation = $listingExtra->calculateTotal(
                $quantity,
                $personTypeBreakdown,
                $currency
            );

            $items[] = [
                'listingExtraId' => $listingExtra->id,
                'extraId' => $extra->id,
                'name' => $extra->name,
                'quantity' => $quantity,
                'pricingType' => $extra->pricing_type->value,
                'unitPrice' => $calculation['unit_price'],
                'subtotal' => $calculation['subtotal'],
                'breakdown' => $calculation['breakdown'] ?? null,
                'calculation' => $calculation['calculation'],
            ];

            $subtotal += $calculation['subtotal'];
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'currency' => $currency,
            'itemCount' => count($items),
        ];
    }

    /**
     * Validate extras selection (inventory, required extras, etc.)
     */
    public function validateSelection(
        Listing $listing,
        array $selectedExtras,
        array $personTypeBreakdown = []
    ): array {
        $errors = [];
        $availableExtras = $listing->listingExtras()
            ->with('extra')
            ->active()
            ->get()
            ->keyBy('id');

        // Check for required extras
        $requiredExtras = $availableExtras->filter(fn ($le) => $le->getEffectiveIsRequired());
        foreach ($requiredExtras as $required) {
            $found = collect($selectedExtras)->first(fn ($s) => $s['id'] === $required->id);
            if (!$found) {
                $errors[] = [
                    'field' => 'extras',
                    'message' => "Extra '{$required->extra->getTranslation('name', 'en')}' is required.",
                    'extraId' => $required->extra_id,
                ];
            }
        }

        // Validate each selected extra
        foreach ($selectedExtras as $selection) {
            $listingExtra = $availableExtras->get($selection['id']);

            if (!$listingExtra) {
                $errors[] = [
                    'field' => 'extras',
                    'message' => "Extra not available for this listing.",
                    'id' => $selection['id'],
                ];
                continue;
            }

            $extra = $listingExtra->extra;
            $quantity = $selection['quantity'] ?? 1;

            // Check quantity limits
            $minQty = $listingExtra->getEffectiveMinQuantity();
            $maxQty = $listingExtra->getEffectiveMaxQuantity();

            if ($quantity < $minQty) {
                $errors[] = [
                    'field' => 'extras',
                    'message' => "Minimum quantity for '{$extra->getTranslation('name', 'en')}' is {$minQty}.",
                    'extraId' => $extra->id,
                ];
            }

            if ($maxQty !== null && $quantity > $maxQty) {
                $errors[] = [
                    'field' => 'extras',
                    'message' => "Maximum quantity for '{$extra->getTranslation('name', 'en')}' is {$maxQty}.",
                    'extraId' => $extra->id,
                ];
            }

            // Check inventory
            if ($extra->track_inventory && !$extra->hasAvailableInventory($quantity)) {
                $errors[] = [
                    'field' => 'extras',
                    'message' => "Insufficient inventory for '{$extra->getTranslation('name', 'en')}'. Only {$extra->inventory_count} available.",
                    'extraId' => $extra->id,
                ];
            }
        }

        return $errors;
    }

    /**
     * Create booking extras from selection.
     */
    public function createBookingExtras(
        Booking $booking,
        array $selectedExtras,
        array $personTypeBreakdown,
        string $currency,
        bool $reserveInventory = true
    ): Collection {
        $bookingExtras = collect();

        foreach ($selectedExtras as $selection) {
            $listingExtra = ListingExtra::with('extra')->find($selection['id']);
            if (!$listingExtra || !$listingExtra->extra) {
                continue;
            }

            $extra = $listingExtra->extra;
            $quantity = $selection['quantity'] ?? 1;

            // Calculate pricing
            $calculation = $listingExtra->calculateTotal($quantity, $personTypeBreakdown, $currency);

            // Create booking extra with price snapshot
            $bookingExtra = BookingExtra::create([
                'booking_id' => $booking->id,
                'extra_id' => $extra->id,
                'listing_extra_id' => $listingExtra->id,
                'quantity' => $quantity,
                'pricing_type' => $extra->pricing_type->value,
                'unit_price_tnd' => $listingExtra->getEffectivePrice('TND'),
                'unit_price_eur' => $listingExtra->getEffectivePrice('EUR'),
                'person_type_breakdown' => $calculation['breakdown'] ?? null,
                'subtotal_tnd' => $currency === 'TND' ? $calculation['subtotal'] : $this->convertToTnd($calculation['subtotal']),
                'subtotal_eur' => $currency === 'EUR' ? $calculation['subtotal'] : $this->convertToEur($calculation['subtotal']),
                'extra_name' => $extra->name,
                'extra_category' => $extra->category?->value,
                'inventory_reserved' => false,
                'status' => BookingExtraStatus::ACTIVE->value,
            ]);

            // Reserve inventory if needed
            if ($reserveInventory && $extra->track_inventory) {
                $reserved = $extra->reserveInventory($quantity, $booking);
                if ($reserved) {
                    $bookingExtra->update(['inventory_reserved' => true]);
                }
            }

            $bookingExtras->push($bookingExtra);
        }

        return $bookingExtras;
    }

    /**
     * Release inventory for cancelled/refunded booking extras.
     */
    public function releaseBookingExtrasInventory(Booking $booking, ?User $user = null): void
    {
        foreach ($booking->bookingExtras as $bookingExtra) {
            if ($bookingExtra->inventory_reserved && $bookingExtra->extra) {
                $bookingExtra->extra->releaseInventory(
                    $bookingExtra->quantity,
                    $booking,
                    $user
                );
                $bookingExtra->update(['inventory_reserved' => false]);
            }
        }
    }

    /**
     * Get extras summary for check-in display.
     */
    public function getCheckInSummary(Booking $booking, string $locale = 'en'): array
    {
        return $booking->activeBookingExtras
            ->map(fn (BookingExtra $be) => $be->getSummary($locale))
            ->toArray();
    }

    /**
     * Cancel a specific booking extra.
     */
    public function cancelBookingExtra(BookingExtra $bookingExtra, ?User $user = null): void
    {
        DB::transaction(function () use ($bookingExtra, $user) {
            // Release inventory if reserved
            if ($bookingExtra->inventory_reserved && $bookingExtra->extra) {
                $bookingExtra->extra->releaseInventory(
                    $bookingExtra->quantity,
                    $bookingExtra->booking,
                    $user
                );
            }

            $bookingExtra->update([
                'status' => BookingExtraStatus::CANCELLED->value,
                'inventory_reserved' => false,
            ]);
        });
    }

    /**
     * Get low inventory extras for a vendor.
     */
    public function getLowInventoryExtras(int $vendorId, int $threshold = 5): Collection
    {
        return Extra::forVendor($vendorId)
            ->lowInventory($threshold)
            ->active()
            ->get();
    }

    /**
     * Simple currency conversion (placeholder - should use real rates).
     */
    private function convertToTnd(float $eurAmount): float
    {
        // Approximate rate - should be fetched from a currency service
        return $eurAmount * 3.3;
    }

    private function convertToEur(float $tndAmount): float
    {
        // Approximate rate - should be fetched from a currency service
        return $tndAmount / 3.3;
    }
}
