<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Resolves the optional vendor-supplied "unit_label" stored inside
 * listings.pricing JSON. Mirrors apps/web/src/lib/utils/pricing-unit-label.ts —
 * keep the two helpers in lockstep.
 *
 * Inputs accept either snake_case or camelCase keys for the wrapping field
 * (unit_label vs unitLabel) so callers don't have to normalize first.
 */
final class PricingUnitLabel
{
    /**
     * Return the localized suffix, or null if the vendor has not set one.
     *
     * Fallback order: requested locale → any other non-empty locale → null.
     * Whitespace-only strings are treated as empty.
     *
     * @param  array<string, mixed>  $pricing  the listings.pricing array
     */
    public static function resolve(array $pricing, string $locale): ?string
    {
        $map = self::toArray($pricing);

        if ($map === null) {
            return null;
        }

        $candidate = $map[$locale] ?? null;
        if (is_string($candidate) && trim($candidate) !== '') {
            return trim($candidate);
        }

        foreach ($map as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * Return the cleaned translatable map (whitespace dropped, empty entries
     * removed), or null if nothing usable. Used by ListingResource to emit
     * unitLabel to the frontend without leaking blank locales.
     *
     * @param  array<string, mixed>  $pricing
     * @return array<string, string>|null
     */
    public static function toArray(array $pricing): ?array
    {
        $raw = $pricing['unit_label'] ?? $pricing['unitLabel'] ?? null;

        if (! is_array($raw)) {
            return null;
        }

        $clean = [];
        foreach ($raw as $locale => $value) {
            if (! is_string($locale) || ! is_string($value)) {
                continue;
            }
            $trimmed = trim($value);
            if ($trimmed !== '') {
                $clean[$locale] = $trimmed;
            }
        }

        return $clean === [] ? null : $clean;
    }
}
