<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'partner_id',
        'action',
        'request_data',
        'response_status',
        'ip_address',
        'user_agent',
        'duration_ms',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_status' => 'integer',
        'duration_ms' => 'integer',
    ];

    /**
     * Get the partner that owns the audit log.
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Scope a query to only include logs for a specific action.
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to only include logs within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include failed requests.
     */
    public function scopeFailed($query)
    {
        return $query->where('response_status', '>=', 400);
    }
}
