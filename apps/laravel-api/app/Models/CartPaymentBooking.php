<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CartPaymentBooking extends Pivot
{
    use HasUuids;

    protected $table = 'cart_payment_bookings';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'cart_payment_id',
        'booking_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }
}
