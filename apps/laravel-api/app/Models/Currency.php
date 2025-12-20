<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope to get only active currencies.
     */
    public static function active(): Builder
    {
        return self::query()->where('is_active', true);
    }

    /**
     * Get currency by ISO code.
     */
    public static function getByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }
}
