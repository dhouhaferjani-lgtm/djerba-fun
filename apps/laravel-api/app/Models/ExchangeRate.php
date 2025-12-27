<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'currency',
        'rate',
        'ppp_adjustment',
        'source',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'ppp_adjustment' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the latest rate for a currency.
     */
    public static function getLatest(string $currency): ?self
    {
        return static::where('currency', $currency)
            ->latest('created_at')
            ->first();
    }

    /**
     * Get current effective rate with PPP.
     */
    public function getEffectiveRate(): float
    {
        return (float) $this->rate * (float) $this->ppp_adjustment;
    }
}
