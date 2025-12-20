<?php

namespace App\Services;

use App\Models\IncomePricingConfig;

class IncomePricingService
{
    /**
     * Calculate the expected EUR price based on TND price using income parity.
     */
    public function calculateExpectedPrice(float $tndPrice, string $fromCurrency = 'TND', string $toCurrency = 'EUR'): float
    {
        $ratio = $this->getParityRatio($fromCurrency, $toCurrency);

        if ($ratio === null) {
            // Fallback to simple calculation if no parity config exists
            // Default ratio: 1 TND ≈ €0.13 (income parity adjusted)
            $ratio = 0.1286;
        }

        return round($tndPrice * $ratio, 2);
    }

    /**
     * Validate pricing and return validation result with suggestions.
     */
    public function validatePricing(
        float $tndPrice,
        float $eurPrice,
        string $fromCurrency = 'TND',
        string $toCurrency = 'EUR'
    ): array {
        $config = IncomePricingConfig::getActiveConfig($fromCurrency, $toCurrency);

        if (!$config) {
            return [
                'is_valid' => true,
                'message' => 'No income parity configuration found',
                'suggested_eur' => $this->calculateExpectedPrice($tndPrice, $fromCurrency, $toCurrency),
            ];
        }

        $expectedEur = $this->calculateExpectedPrice($tndPrice, $fromCurrency, $toCurrency);
        $tolerance = $config->tolerance_percent;

        $lowerBound = $expectedEur * (1 - $tolerance / 100);
        $upperBound = $expectedEur * (1 + $tolerance / 100);

        $isValid = $eurPrice >= $lowerBound && $eurPrice <= $upperBound;

        if (!$isValid) {
            $percentageDiff = abs((($eurPrice - $expectedEur) / $expectedEur) * 100);
            $message = sprintf(
                'EUR price is %.1f%% %s the income parity suggestion (€%.2f). Consider adjusting to improve accessibility.',
                $percentageDiff,
                $eurPrice > $expectedEur ? 'above' : 'below',
                $expectedEur
            );

            return [
                'is_valid' => false,
                'message' => $message,
                'suggested_eur' => $expectedEur,
                'tolerance_percent' => $tolerance,
                'lower_bound' => round($lowerBound, 2),
                'upper_bound' => round($upperBound, 2),
            ];
        }

        return [
            'is_valid' => true,
            'message' => 'Prices are within income parity tolerance',
            'suggested_eur' => $expectedEur,
            'tolerance_percent' => $tolerance,
        ];
    }

    /**
     * Get the active parity ratio for a currency pair.
     */
    public function getParityRatio(string $fromCurrency = 'TND', string $toCurrency = 'EUR'): ?float
    {
        return IncomePricingConfig::getActiveRatio($fromCurrency, $toCurrency);
    }

    /**
     * Check if pricing is within tolerance.
     */
    public function isWithinTolerance(
        float $tndPrice,
        float $eurPrice,
        string $fromCurrency = 'TND',
        string $toCurrency = 'EUR'
    ): bool {
        $validation = $this->validatePricing($tndPrice, $eurPrice, $fromCurrency, $toCurrency);
        return $validation['is_valid'];
    }

    /**
     * Get all available currency pairs with parity configurations.
     */
    public function getAvailablePairs(): array
    {
        return IncomePricingConfig::where('is_active', true)
            ->where('effective_from', '<=', now())
            ->get()
            ->map(function ($config) {
                return [
                    'from' => $config->from_currency,
                    'to' => $config->to_currency,
                    'ratio' => (float) $config->ratio,
                    'tolerance' => $config->tolerance_percent,
                ];
            })
            ->toArray();
    }
}
