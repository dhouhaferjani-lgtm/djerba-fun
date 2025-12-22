<?php

declare(strict_types=1);

namespace App\Http\Resources\Partner;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerBookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'quantity' => $this->quantity,

            // Listing info
            'listing' => [
                'id' => $this->listing?->id,
                'slug' => $this->listing?->slug,
                'title' => $this->listing?->title,
                'serviceType' => $this->listing?->service_type,
            ],

            // Slot info
            'slot' => [
                'id' => $this->availabilitySlot?->id,
                'startTime' => $this->availabilitySlot?->start_time?->toIso8601String(),
                'endTime' => $this->availabilitySlot?->end_time?->toIso8601String(),
            ],

            // Traveler info
            'traveler' => [
                'id' => $this->user?->id,
                'name' => $this->user?->first_name . ' ' . $this->user?->last_name,
                'email' => $this->user?->email,
                'phone' => $this->user?->phone,
            ],

            'travelerInfo' => $this->traveler_info,
            'specialRequests' => $this->special_requests,

            // Pricing
            'pricing' => $this->pricing_snapshot,

            // Payment info
            'paymentStatus' => $this->payment_status,
            'paymentIntents' => $this->paymentIntents?->map(fn ($intent) => [
                'id' => $intent->id,
                'amount' => $intent->amount,
                'currency' => $intent->currency,
                'status' => $intent->status,
                'gateway' => $intent->gateway,
                'createdAt' => $intent->created_at->toIso8601String(),
            ])->toArray(),

            // Partner metadata
            'partnerMetadata' => $this->partner_metadata,

            // Timestamps
            'confirmedAt' => $this->confirmed_at?->toIso8601String(),
            'cancelledAt' => $this->cancelled_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
