<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CartResource extends BaseResource
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
            'userId' => $this->user_id,
            'sessionId' => $this->session_id,
            'status' => $this->status,
            'expiresAt' => $this->expires_at?->toIso8601String(),
            'expiresInSeconds' => $this->expires_at
                ? max(0, $this->expires_at->getTimestamp() - now()->getTimestamp())
                : 0,
            'isExpired' => $this->hasExpired(),
            'isActive' => $this->isActive(),
            'isEmpty' => $this->isEmpty(),
            'itemCount' => $this->getItemCount(),
            'totalGuests' => $this->getTotalGuests(),
            'subtotal' => $this->getSubtotal(),
            'currency' => $this->getCurrency(),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
        ];
    }
}
