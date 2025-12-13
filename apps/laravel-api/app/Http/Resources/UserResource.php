<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'email' => $this->email,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'displayName' => $this->display_name,
            'avatarUrl' => $this->avatar_url,
            'emailVerifiedAt' => $this->email_verified_at?->toIso8601String(),
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),

            // Include profile data based on role
            'travelerProfile' => $this->whenLoaded('travelerProfile', function () {
                return $this->travelerProfile ? new TravelerProfileResource($this->travelerProfile) : null;
            }),
            'vendorProfile' => $this->whenLoaded('vendorProfile', function () {
                return $this->vendorProfile ? new VendorProfileResource($this->vendorProfile) : null;
            }),
        ];
    }
}
