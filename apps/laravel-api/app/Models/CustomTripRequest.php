<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CustomTripRequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CustomTripRequest extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'reference',
        'status',
        'travel_start_date',
        'travel_end_date',
        'dates_flexible',
        'adults',
        'children',
        'duration_days',
        'interests',
        'budget_per_person',
        'budget_currency',
        'accommodation_style',
        'travel_pace',
        'special_occasions',
        'special_requests',
        'contact_name',
        'contact_email',
        'contact_phone',
        'contact_whatsapp',
        'contact_country',
        'preferred_contact_method',
        'newsletter_consent',
        'locale',
        'ip_address',
        'user_agent',
        'assigned_agent_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => CustomTripRequestStatus::class,
            'travel_start_date' => 'date',
            'travel_end_date' => 'date',
            'dates_flexible' => 'boolean',
            'adults' => 'integer',
            'children' => 'integer',
            'duration_days' => 'integer',
            'interests' => 'array',
            'budget_per_person' => 'integer',
            'special_occasions' => 'array',
            'newsletter_consent' => 'boolean',
        ];
    }

    /**
     * Status constants (deprecated - use CustomTripRequestStatus enum instead)
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_PROPOSAL = 'proposal';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    /**
     * Accommodation style constants
     */
    public const STYLE_BUDGET = 'budget';
    public const STYLE_MID_RANGE = 'mid-range';
    public const STYLE_LUXURY = 'luxury';

    /**
     * Travel pace constants
     */
    public const PACE_RELAXED = 'relaxed';
    public const PACE_MODERATE = 'moderate';
    public const PACE_ACTIVE = 'active';

    /**
     * Interest constants
     */
    public const INTERESTS = [
        'history-culture',
        'desert-adventures',
        'beach-relaxation',
        'food-gastronomy',
        'hiking-nature',
        'photography',
        'local-festivals',
        'star-wars-sites',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (CustomTripRequest $request) {
            if (empty($request->reference)) {
                $request->reference = self::generateReference();
            }
        });
    }

    /**
     * Generate a unique reference number
     */
    public static function generateReference(): string
    {
        $year = date('Y');
        $number = str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

        return "GA-{$year}-{$number}";
    }

    /**
     * Get the total number of travelers
     */
    public function getTotalTravelersAttribute(): int
    {
        return $this->adults + $this->children;
    }

    /**
     * Get the assigned agent
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    /**
     * Check if the request is pending
     */
    public function isPending(): bool
    {
        return $this->status === CustomTripRequestStatus::PENDING;
    }

    /**
     * Check if the request is active (not cancelled or completed)
     */
    public function isActive(): bool
    {
        return !in_array($this->status, [CustomTripRequestStatus::CANCELLED, CustomTripRequestStatus::COMPLETED], true);
    }

    /**
     * Mark as contacted
     */
    public function markAsContacted(): void
    {
        $this->update(['status' => CustomTripRequestStatus::CONTACTED]);
    }

    /**
     * Mark as proposal sent
     */
    public function markAsProposal(): void
    {
        $this->update(['status' => CustomTripRequestStatus::PROPOSAL]);
    }

    /**
     * Mark as confirmed
     */
    public function markAsConfirmed(): void
    {
        $this->update(['status' => CustomTripRequestStatus::CONFIRMED]);
    }

    /**
     * Mark as cancelled
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => CustomTripRequestStatus::CANCELLED]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(): void
    {
        $this->update(['status' => CustomTripRequestStatus::COMPLETED]);
    }

    /**
     * Assign to an agent
     */
    public function assignTo(User $agent): void
    {
        $this->update([
            'assigned_agent_id' => $agent->id,
        ]);
    }

    /**
     * Get estimated total budget
     */
    public function getEstimatedTotalBudgetAttribute(): int
    {
        return $this->budget_per_person * $this->total_travelers;
    }

    /**
     * Scope to filter by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', CustomTripRequestStatus::PENDING);
    }

    /**
     * Scope to filter active requests
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [CustomTripRequestStatus::CANCELLED, CustomTripRequestStatus::COMPLETED]);
    }
}
