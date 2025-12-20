<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BookingParticipant extends Model
{
    use HasFactory, HasUuids;

    /**
     * Voucher code prefix.
     */
    public const VOUCHER_PREFIX = 'VOC';

    protected $fillable = [
        'booking_id',
        'voucher_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'person_type',
        'special_requests',
        'checked_in',
        'checked_in_at',
    ];

    protected function casts(): array
    {
        return [
            'checked_in' => 'boolean',
            'checked_in_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (BookingParticipant $participant) {
            if (empty($participant->voucher_code)) {
                $participant->voucher_code = self::generateVoucherCode();
            }
        });
    }

    /**
     * Generate a unique voucher code.
     */
    public static function generateVoucherCode(): string
    {
        do {
            $code = self::VOUCHER_PREFIX . '-' . strtoupper(Str::random(10));
        } while (self::where('voucher_code', $code)->exists());

        return $code;
    }

    /**
     * Get the booking this participant belongs to.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the full name of the participant.
     */
    public function getFullNameAttribute(): ?string
    {
        if ($this->first_name || $this->last_name) {
            return trim("{$this->first_name} {$this->last_name}");
        }

        return null;
    }

    /**
     * Check if participant details have been filled.
     */
    public function isComplete(): bool
    {
        return !empty($this->first_name) && !empty($this->last_name);
    }

    /**
     * Mark participant as checked in.
     */
    public function checkIn(): void
    {
        $this->update([
            'checked_in' => true,
            'checked_in_at' => now(),
        ]);
    }

    /**
     * Undo check-in.
     */
    public function undoCheckIn(): void
    {
        $this->update([
            'checked_in' => false,
            'checked_in_at' => null,
        ]);
    }

    /**
     * Get QR code data for this voucher.
     */
    public function getQrCodeData(): string
    {
        return $this->voucher_code;
    }

    /**
     * Scope to find by voucher code.
     */
    public function scopeByVoucherCode($query, string $code)
    {
        return $query->where('voucher_code', $code);
    }

    /**
     * Scope to get incomplete participants.
     */
    public function scopeIncomplete($query)
    {
        return $query->whereNull('first_name')->orWhereNull('last_name');
    }

    /**
     * Scope to get checked-in participants.
     */
    public function scopeCheckedIn($query)
    {
        return $query->where('checked_in', true);
    }

    /**
     * Scope to get not checked-in participants.
     */
    public function scopeNotCheckedIn($query)
    {
        return $query->where('checked_in', false);
    }
}
