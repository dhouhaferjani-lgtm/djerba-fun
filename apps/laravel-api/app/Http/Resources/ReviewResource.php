<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
class ReviewResource extends BaseResource
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
            'listingId' => $this->listing_id,
            'user' => [
                'id' => $this->user->id,
                'displayName' => $this->user->display_name,
                'avatarUrl' => $this->user->avatar_url,
            ],
            'rating' => $this->rating,
            'title' => $this->title,
            'content' => $this->content,
            'pros' => is_array($this->pros) ? $this->toCamelCase($this->pros) : $this->pros,
            'cons' => is_array($this->cons) ? $this->toCamelCase($this->cons) : $this->cons,
            'photos' => is_array($this->photos) ? $this->toCamelCase($this->photos) : $this->photos,
            'isVerifiedBooking' => $this->is_verified_booking,
            'helpfulCount' => $this->helpful_count,
            'reply' => $this->when($this->reply, function () {
                return [
                    'id' => $this->reply->id,
                    'vendor' => [
                        'id' => $this->reply->vendor->id,
                        'displayName' => $this->reply->vendor->display_name,
                    ],
                    'content' => $this->reply->content,
                    'createdAt' => $this->reply->created_at->toIso8601String(),
                ];
            }),
            'createdAt' => $this->created_at->toIso8601String(),
            'publishedAt' => $this->published_at?->toIso8601String(),
        ];
    }
}
