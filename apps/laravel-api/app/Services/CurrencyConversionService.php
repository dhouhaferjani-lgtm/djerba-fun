<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyConversionService
{
    /**
     * Base currency (what vendors enter prices in).
     */
    public const BASE_CURRENCY = 'TND';

    /**
     * Supported currencies for conversion.
     */
    public const SUPPORTED_CURRENCIES = ['TND', 'EUR', 'USD'];

    /**
     * Default Purchasing Power Parity adjustment factors.
     * These adjust prices to account for differences in purchasing power between countries.
     *
     * Example: PPP factor of 0.85 means international price is 15% lower than direct conversion.
     */
    protected array $defaultPPPFactors = [
        'TND_EUR' => 0.85, // Tunisia → Europe: 15% adjustment
        'TND_USD' => 0.82, // Tunisia → USA: 18% adjustment
    ];

    /**
     * Validate currency code.
     */
    protected function validateCurrency(string $currency): void
    {
        if (! in_array($currency, self::SUPPORTED_CURRENCIES)) {
            throw new \InvalidArgumentException("Unsupported currency: {$currency}. Supported currencies are: " . implode(', ', self::SUPPORTED_CURRENCIES));
        }
    }

    /**
     * Get current exchange rate from database (cached).
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency): float
    {
        $this->validateCurrency($fromCurrency);
        $this->validateCurrency($toCurrency);

        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $cacheKey = "exchange_rate:{$fromCurrency}_{$toCurrency}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($fromCurrency, $toCurrency) {
            if ($fromCurrency === self::BASE_CURRENCY) {
                // Direct conversion from TND
                $rate = ExchangeRate::where('currency', $toCurrency)
                    ->latest('created_at')
                    ->value('rate');

                return (float) ($rate ?? $this->getDefaultRate($toCurrency));
            }

            if ($toCurrency === self::BASE_CURRENCY) {
                // Reverse conversion to TND
                $rate = ExchangeRate::where('currency', $fromCurrency)
                    ->latest('created_at')
                    ->value('rate');

                if ($rate && (float) $rate > 0) {
                    return 1 / (float) $rate;
                }

                $defaultRate = $this->getDefaultRate($fromCurrency);

                return $defaultRate > 0 ? (1 / $defaultRate) : 1.0;
            }

            // Cross-currency conversion through TND
            $fromRate = $this->getExchangeRate(self::BASE_CURRENCY, $fromCurrency);
            $toRate = $this->getExchangeRate(self::BASE_CURRENCY, $toCurrency);

            // Prevent division by zero
            if ($fromRate <= 0) {
                throw new \RuntimeException("Invalid exchange rate for {$fromCurrency}: {$fromRate}");
            }

            return $toRate / $fromRate;
        });
    }

    /**
     * Get PPP adjustment factor for a currency pair.
     */
    public function getPPPFactor(string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $key = "{$fromCurrency}_{$toCurrency}";
        $cacheKey = "ppp_factor:{$key}";

        return Cache::remember($cacheKey, now()->addDays(1), function () use ($fromCurrency, $toCurrency, $key) {
            // Check database first
            if ($fromCurrency === self::BASE_CURRENCY) {
                $factor = ExchangeRate::where('currency', $toCurrency)
                    ->latest('created_at')
                    ->value('ppp_adjustment');

                if ($factor) {
                    return (float) $factor;
                }
            }

            // Fallback to defaults
            return $this->defaultPPPFactors[$key] ?? 1.0;
        });
    }

    /**
     * Convert amount with PPP adjustment.
     *
     * @param  float  $amount  Amount in source currency
     * @param  string  $fromCurrency  Source currency code
     * @param  string  $toCurrency  Target currency code
     * @param  bool  $applyPPP  Whether to apply purchasing power parity adjustment
     * @return array ['amount' => float, 'rate' => float, 'ppp_factor' => float]
     */
    public function convert(
        float $amount,
        string $fromCurrency,
        string $toCurrency,
        bool $applyPPP = true
    ): array {
        // Validate inputs
        $this->validateCurrency($fromCurrency);
        $this->validateCurrency($toCurrency);

        if ($amount < 0) {
            throw new \InvalidArgumentException("Amount cannot be negative: {$amount}");
        }

        $rate = $this->getExchangeRate($fromCurrency, $toCurrency);
        $ppFactor = $applyPPP ? $this->getPPPFactor($fromCurrency, $toCurrency) : 1.0;

        $convertedAmount = $amount * $rate * $ppFactor;

        return [
            'amount' => round($convertedAmount, 2),
            'rate' => $rate,
            'ppp_factor' => $ppFactor,
            'calculation' => sprintf(
                '%s %s × %s (rate) × %s (PPP) = %s %s',
                number_format($amount, 2),
                $fromCurrency,
                number_format($rate, 4),
                number_format($ppFactor, 4),
                number_format($convertedAmount, 2),
                $toCurrency
            ),
        ];
    }

    /**
     * Convert from TND (base currency) to target currency with PPP.
     */
    public function convertFromTND(float $amountTND, string $targetCurrency, bool $applyPPP = true): array
    {
        return $this->convert($amountTND, self::BASE_CURRENCY, $targetCurrency, $applyPPP);
    }

    /**
     * Convert to TND (base currency) from source currency.
     */
    public function convertToTND(float $amount, string $sourceCurrency): array
    {
        return $this->convert($amount, $sourceCurrency, self::BASE_CURRENCY, applyPPP: false);
    }

    /**
     * Update exchange rates from external API.
     *
     * @param  string  $apiKey  API key for exchange rate service
     * @return array Updated rates
     */
    public function updateRatesFromAPI(?string $apiKey = null): array
    {
        try {
            $apiKey = $apiKey ?? config('services.exchange_rates.api_key');

            if (! $apiKey) {
                throw new \Exception('Exchange rate API key not configured');
            }

            // Using exchangeratesapi.io or similar service
            $response = Http::get('https://api.exchangeratesapi.io/latest', [
                'access_key' => $apiKey,
                'base' => 'TND',
                'symbols' => implode(',', array_diff(self::SUPPORTED_CURRENCIES, ['TND'])),
            ]);

            if (! $response->successful()) {
                throw new \Exception('Failed to fetch exchange rates: ' . $response->body());
            }

            $data = $response->json();
            $rates = $data['rates'] ?? [];

            $updated = [];

            foreach ($rates as $currency => $rate) {
                $ppFactor = $this->defaultPPPFactors["TND_{$currency}"] ?? 1.0;

                ExchangeRate::create([
                    'currency' => $currency,
                    'rate' => $rate,
                    'ppp_adjustment' => $ppFactor,
                    'source' => 'exchangeratesapi.io',
                ]);

                $updated[$currency] = [
                    'rate' => $rate,
                    'ppp_adjustment' => $ppFactor,
                ];

                // Clear cache
                Cache::forget("exchange_rate:TND_{$currency}");
                Cache::forget("ppp_factor:TND_{$currency}");
            }

            Log::info('Exchange rates updated successfully', ['rates' => $updated]);

            return $updated;
        } catch (\Exception $e) {
            Log::error('Failed to update exchange rates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get default fallback rates (used when DB is empty).
     */
    protected function getDefaultRate(string $currency): float
    {
        $defaults = [
            'EUR' => 0.31,  // Approximate TND to EUR (Dec 2024)
            'USD' => 0.32,  // Approximate TND to USD (Dec 2024)
        ];

        return $defaults[$currency] ?? 1.0;
    }

    /**
     * Get latest exchange rate info for a currency.
     */
    public function getLatestRateInfo(string $currency): ?array
    {
        $rate = ExchangeRate::where('currency', $currency)
            ->latest('created_at')
            ->first();

        if (! $rate) {
            return null;
        }

        return [
            'currency' => $rate->currency,
            'rate' => (float) $rate->rate,
            'ppp_adjustment' => (float) $rate->ppp_adjustment,
            'source' => $rate->source,
            'updated_at' => $rate->created_at->toISOString(),
        ];
    }

    /**
     * Get all latest rates.
     */
    public function getAllRates(): array
    {
        $rates = [];

        foreach (array_diff(self::SUPPORTED_CURRENCIES, [self::BASE_CURRENCY]) as $currency) {
            $rates[$currency] = $this->getLatestRateInfo($currency);
        }

        return $rates;
    }

    /**
     * Calculate display prices for all supported currencies.
     */
    public function calculateDisplayPrices(float $basePriceTND): array
    {
        $prices = [
            'TND' => round($basePriceTND, 2),
        ];

        foreach (array_diff(self::SUPPORTED_CURRENCIES, [self::BASE_CURRENCY]) as $currency) {
            $converted = $this->convertFromTND($basePriceTND, $currency);
            $prices[$currency] = $converted['amount'];
        }

        return $prices;
    }

    /**
     * Format price for display with currency symbol.
     */
    public function formatPrice(float $amount, string $currency, string $locale = 'en'): string
    {
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($amount, $currency);
    }

    /**
     * Get price display with both TND and target currency.
     */
    public function getDualPriceDisplay(float $basePriceTND, string $targetCurrency = 'EUR', string $locale = 'en'): string
    {
        if ($targetCurrency === self::BASE_CURRENCY) {
            return $this->formatPrice($basePriceTND, 'TND', $locale);
        }

        $converted = $this->convertFromTND($basePriceTND, $targetCurrency);

        $primary = $this->formatPrice($converted['amount'], $targetCurrency, $locale);
        $secondary = $this->formatPrice($basePriceTND, 'TND', $locale);

        return sprintf('%s (~%s)', $primary, $secondary);
    }
}
