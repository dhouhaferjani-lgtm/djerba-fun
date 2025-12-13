<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'booking_id' => $this->booking_id,
            'listing_id' => $this->listing_id,
            'user' => [
                'id' => $this->user->id,
                'display_name' => $this->user->display_name,
                'avatar_url' => $this->user->avatar_url,
            ],
            'rating' => $this->rating,
            'title' => $this->title,
            'content' => $this->content,
            'pros' => $this->pros,
            'cons' => $this->cons,
            'photos' => $this->photos,
            'is_verified_booking' => $this->is_verified_booking,
            'helpful_count' => $this->helpful_count,
            'reply' => $this->when($this->reply, function () {
                return [
                    'id' => $this->reply->id,
                    'vendor' => [
                        'id' => $this->reply->vendor->id,
                        'display_name' => $this->reply->vendor->display_name,
                    ],
                    'content' => $this->reply->content,
                    'created_at' => $this->reply->created_at->toIso8601String(),
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
