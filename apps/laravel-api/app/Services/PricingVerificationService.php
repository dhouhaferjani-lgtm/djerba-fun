<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BookingHold;
use App\Models\Listing;

/**
 * Service for verifying pricing accuracy and detecting price changes during checkout.
 *
 * This service is crucial for PPP (Purchasing Power Parity) pricing transparency,
 * ensuring customers are informed when billing address differs from browsing country.
 */
class PricingVerificationService
{
    public function __construct(
        private readonly GeoPricingService $geoPricingService,
        private readonly PriceCalculationService $priceCalculationService
    ) {}

    /**
     * Verify if the billing address would result in a price change.
     *
     * This method compares the original held price (based on browsing country)
     * with the final price (based on billing address) and determines if
     * disclosure is required to the customer.
     *
     * @param  BookingHold  $hold  The booking hold with original pricing
     * @param  array  $billingAddress  Billing address containing country_code
     * @param  array  $extras  Selected extras (optional)
     * @return array{
     *     price_changed: bool,
     *     original_currency: string,
     *     original_price: float,
     *     final_currency: string,
     *     final_price: float,
     *     disclosure_required: bool,
     *     disclosure_message: string|null,
     *     browse_country: string,
     *     billing_country: string
     * }
     */
    public function verifyBillingAddress(
        BookingHold $hold,
        array $billingAddress,
        array $extras = []
    ): array {
        // Extract countries
        $browseCountry = $hold->pricing_country_code ?? 'FR';
        $billingCountry = strtoupper($billingAddress['country_code'] ?? 'FR');

        // Get currencies
        $browseCurrency = $hold->currency ?? $this->geoPricingService->getCurrencyForCountry($browseCountry);
        $billingCurrency = $this->geoPricingService->getCurrencyForCountry($billingCountry);

        // Original price from hold (ensure it's a float)
        $originalPrice = (float) ($hold->price_snapshot ?? 0);

        // Calculate final price based on billing country
        $listing = $hold->listing;
        $finalPrice = $this->calculateFinalPrice($listing, $hold, $billingCurrency, $extras);

        // Determine if price changed
        $priceChanged = $browseCountry !== $billingCountry
            && $browseCurrency !== $billingCurrency
            && abs($originalPrice - $finalPrice) > 0.01; // Account for floating point precision

        // Disclosure required if price changed
        $disclosureRequired = $priceChanged;

        return [
            'price_changed' => $priceChanged,
            'original_currency' => $browseCurrency,
            'original_price' => $originalPrice,
            'final_currency' => $billingCurrency,
            'final_price' => $finalPrice,
            'disclosure_required' => $disclosureRequired,
            'disclosure_message' => $disclosureRequired
                ? $this->generateDisclosureMessage($browseCurrency, $originalPrice, $billingCurrency, $finalPrice)
                : null,
            'browse_country' => $browseCountry,
            'billing_country' => $billingCountry,
        ];
    }

    /**
     * Calculate the final price based on billing country currency.
     *
     * @param  Listing  $listing  The listing being booked
     * @param  BookingHold  $hold  The booking hold
     * @param  string  $currency  Currency to calculate in
     * @param  array  $extras  Selected extras
     * @return float Final calculated price
     */
    protected function calculateFinalPrice(
        Listing $listing,
        BookingHold $hold,
        string $currency,
        array $extras = []
    ): float {
        $personTypeBreakdown = $hold->person_type_breakdown ?? [];

        if (! empty($personTypeBreakdown)) {
            // Use detailed breakdown calculation
            $calculation = $this->priceCalculationService->calculateTotal(
                $listing,
                $personTypeBreakdown,
                $currency
            );
            $baseTotal = $calculation['total'];
        } else {
            // Use simple quantity-based calculation
            $calculation = $this->priceCalculationService->calculateSimpleTotal(
                $listing,
                $hold->quantity,
                $currency
            );
            $baseTotal = $calculation['total'];
        }

        // Add extras if provided
        $extrasTotal = 0;

        if (! empty($extras)) {
            foreach ($extras as $extra) {
                $extrasTotal += (float) ($extra['price'] ?? 0) * (int) ($extra['quantity'] ?? 1);
            }
        }

        return $baseTotal + $extrasTotal;
    }

    /**
     * Generate a customer-facing disclosure message about price changes.
     *
     * This message is shown when billing address country differs from browsing country
     * and results in a different currency/price.
     *
     * @param  string  $originalCurrency  Currency shown during browsing
     * @param  float  $originalPrice  Price shown during browsing
     * @param  string  $finalCurrency  Currency based on billing address
     * @param  float  $finalPrice  Final price to be charged
     * @return string Disclosure message
     */
    protected function generateDisclosureMessage(
        string $originalCurrency,
        float $originalPrice,
        string $finalCurrency,
        float $finalPrice
    ): string {
        $priceDiff = $finalPrice - $originalPrice;
        $isIncrease = $priceDiff > 0;

        $formattedOriginal = $this->formatPrice($originalPrice, $originalCurrency);
        $formattedFinal = $this->formatPrice($finalPrice, $finalCurrency);

        if ($isIncrease) {
            return sprintf(
                'Price adjusted from %s to %s based on your billing country. We use region-based pricing to ensure fair access worldwide.',
                $formattedOriginal,
                $formattedFinal
            );
        } else {
            return sprintf(
                'Good news! Your final price is %s (originally shown as %s) based on your billing country. We use region-based pricing to ensure fair access worldwide.',
                $formattedFinal,
                $formattedOriginal
            );
        }
    }

    /**
     * Format price with currency symbol.
     *
     * @param  float  $price  Price amount
     * @param  string  $currency  Currency code (TND or EUR)
     * @return string Formatted price string
     */
    protected function formatPrice(float $price, string $currency): string
    {
        $symbols = [
            'TND' => 'TND',
            'EUR' => '€',
            'USD' => '$',
        ];

        $symbol = $symbols[$currency] ?? $currency;

        // Format based on currency
        if ($currency === 'TND') {
            // No decimals for TND
            return number_format($price, 0) . ' ' . $symbol;
        } else {
            // 2 decimals for EUR/USD
            return $symbol . number_format($price, 2);
        }
    }

    /**
     * Check if a booking hold's price is still valid.
     *
     * This verifies that the listing's current pricing matches the held price.
     * Useful for detecting if prices changed while customer was checking out.
     *
     * @param  BookingHold  $hold  The booking hold to verify
     * @return array{valid: bool, current_price: float, held_price: float, message: string|null}
     */
    public function verifyHoldPrice(BookingHold $hold): array
    {
        $listing = $hold->listing;
        $currency = $hold->currency;
        $heldPrice = $hold->price_snapshot ?? 0;

        // Recalculate current price
        $currentPrice = $this->calculateFinalPrice($listing, $hold, $currency);

        $isValid = abs($currentPrice - $heldPrice) < 0.01; // Account for floating point precision

        return [
            'valid' => $isValid,
            'current_price' => $currentPrice,
            'held_price' => $heldPrice,
            'message' => ! $isValid
                ? sprintf(
                    'Price has changed from %s to %s. Please review the updated price.',
                    $this->formatPrice($heldPrice, $currency),
                    $this->formatPrice($currentPrice, $currency)
                )
                : null,
        ];
    }

    /**
     * Determine if pricing disclosure is required based on country change.
     *
     * Simple helper method to check if billing country differs from browsing country
     * in a way that would affect pricing.
     *
     * @param  string  $browseCountry  Country code during browsing
     * @param  string  $billingCountry  Country code from billing address
     * @return bool True if disclosure required
     */
    public function requiresDisclosure(string $browseCountry, string $billingCountry): bool
    {
        $browseCurrency = $this->geoPricingService->getCurrencyForCountry($browseCountry);
        $billingCurrency = $this->geoPricingService->getCurrencyForCountry($billingCountry);

        return $browseCountry !== $billingCountry && $browseCurrency !== $billingCurrency;
    }
}
