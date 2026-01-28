<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;

class BookingParticipantResource extends BaseResource
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
            'voucherCode' => $this->voucher_code,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'fullName' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'personType' => $this->person_type,
            'specialRequests' => $this->special_requests,
            'checkedIn' => $this->checked_in,
            'checkedInAt' => $this->checked_in_at?->toIso8601String(),
            'isComplete' => $this->isComplete(),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
