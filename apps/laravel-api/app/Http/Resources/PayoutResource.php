<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
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
            'vendor_id' => $this->vendor_id,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'payout_method' => $this->payout_method->value,
            'reference' => $this->reference,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'notes' => $this->when($request->user()?->isAdmin(), $this->notes),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
