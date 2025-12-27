<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomePricingConfig extends Model
{
    protected $fillable = [
        'from_currency',
        'to_currency',
        'ratio',
        'tolerance_percent',
        'is_active',
        'effective_from',
        'notes',
    ];

    protected $casts = [
        'ratio' => 'decimal:4',
        'tolerance_percent' => 'integer',
        'is_active' => 'boolean',
        'effective_from' => 'date',
    ];

    /**
     * Get the active income parity configuration for a currency pair.
     */
    public static function getActiveConfig(string $fromCurrency, string $toCurrency): ?self
    {
        return self::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->where('is_active', true)
            ->where('effective_from', '<=', now())
            ->orderBy('effective_from', 'desc')
            ->first();
    }

    /**
     * Get the active ratio for a currency pair.
     */
    public static function getActiveRatio(string $fromCurrency, string $toCurrency): ?float
    {
        $config = self::getActiveConfig($fromCurrency, $toCurrency);

        return $config ? (float) $config->ratio : null;
    }
}
