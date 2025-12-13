<?php

namespace App\Http\Resources\Agent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentBookingResource extends JsonResource
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
                'service_type' => $this->listing?->service_type,
            ],

            // Slot info
            'slot' => [
                'id' => $this->availabilitySlot?->id,
                'start_time' => $this->availabilitySlot?->start_time?->toIso8601String(),
                'end_time' => $this->availabilitySlot?->end_time?->toIso8601String(),
            ],

            // Traveler info
            'traveler' => [
                'id' => $this->user?->id,
                'name' => $this->user?->first_name . ' ' . $this->user?->last_name,
                'email' => $this->user?->email,
                'phone' => $this->user?->phone,
            ],

            'traveler_info' => $this->traveler_info,
            'special_requests' => $this->special_requests,

            // Pricing
            'pricing' => $this->pricing_snapshot,

            // Payment info
            'payment_status' => $this->payment_status,
            'payment_intents' => $this->paymentIntents?->map(fn ($intent) => [
                'id' => $intent->id,
                'amount' => $intent->amount,
                'currency' => $intent->currency,
                'status' => $intent->status,
                'gateway' => $intent->gateway,
                'created_at' => $intent->created_at->toIso8601String(),
            ])->toArray(),

            // Agent metadata
            'agent_metadata' => $this->agent_metadata,

            // Timestamps
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
