<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PaymentIntentResource extends BaseResource
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
            'bookingId' => $this->booking_id,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'paymentMethod' => $this->payment_method->value,
            'paymentMethodLabel' => $this->payment_method->label(),
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'gateway' => $this->gateway,
            'gatewayId' => $this->gateway_id,
            'metadata' => is_array($this->metadata) ? $this->toCamelCase($this->metadata) : $this->metadata,
            'paidAt' => $this->paid_at?->toIso8601String(),
            'failedAt' => $this->failed_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
