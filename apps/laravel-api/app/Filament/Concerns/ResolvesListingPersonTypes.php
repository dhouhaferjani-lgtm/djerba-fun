<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\Listing;
use Closure;
use Illuminate\Support\Str;

/**
 * Resolves a parent listing's pricing.person_types[] into an options array
 * suitable for a Filament Select::options() callback, with locale-aware
 * label fallback.
 *
 * Used by the AvailabilityRule resource (admin + vendor) for the per-slot
 * price-override Select. Lives in a trait so the two panels can't drift
 * apart again — the previous implementation walked relative form-state
 * paths inside a doubly-nested Repeater and broke silently.
 */
trait ResolvesListingPersonTypes
{
    /**
     * Build a [key => label] options array from the listing's
     * pricing.person_types[]. Returns an empty array when the listing
     * is unknown or has no person types defined.
     *
     * The optional $listingQuery closure lets callers scope the lookup —
     * e.g. the Vendor panel passes a query that restricts to the current
     * vendor's listings, so vendors can't read another vendor's pricing.
     *
     * @param  ?Closure  $listingQuery  fn (): \Illuminate\Database\Eloquent\Builder
     * @return array<string, string>
     */
    public static function personTypeOptionsFromListing(
        ?int $listingId,
        ?Closure $listingQuery = null,
    ): array {
        if ($listingId === null) {
            return [];
        }

        $query = $listingQuery !== null ? $listingQuery() : Listing::query();
        $listing = $query->find($listingId);

        if ($listing === null) {
            return [];
        }

        $personTypes = data_get($listing->pricing, 'person_types', []);

        if (! is_array($personTypes)) {
            return [];
        }

        $options = [];

        foreach ($personTypes as $personType) {
            if (! is_array($personType)) {
                continue;
            }
            $key = $personType['key'] ?? null;

            if (! is_string($key) || $key === '') {
                continue;
            }
            $options[$key] = static::resolvePersonTypeLabel($personType);
        }

        return $options;
    }

    /**
     * Resolve the display label for a single person_types[] entry.
     *
     * Order: $pt['label'][currentLocale] → ['en'] → ['fr'] → first non-empty
     * value in the label array → Str::ucfirst($pt['key']) as a last resort.
     *
     * @param  array<string, mixed>  $personType
     */
    public static function resolvePersonTypeLabel(array $personType): string
    {
        $key = is_string($personType['key'] ?? null) ? $personType['key'] : '';
        $fallback = $key !== '' ? Str::ucfirst($key) : '';

        $label = $personType['label'] ?? null;

        if (is_string($label) && $label !== '') {
            return $label;
        }

        if (is_array($label)) {
            $locale = app()->getLocale();
            $picked = $label[$locale] ?? $label['en'] ?? $label['fr'] ?? null;

            if (! is_string($picked) || $picked === '') {
                foreach ($label as $candidate) {
                    if (is_string($candidate) && $candidate !== '') {
                        $picked = $candidate;
                        break;
                    }
                }
            }

            if (is_string($picked) && $picked !== '') {
                return $picked;
            }
        }

        return $fallback;
    }
}
