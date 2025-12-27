<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class TravelerProfileResource extends BaseResource
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
            'userId' => $this->user->uuid,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'phone' => $this->phone,
            'defaultCurrency' => $this->default_currency,
            'preferredLocale' => $this->preferred_locale,
            'documents' => is_array($this->documents) ? $this->toCamelCase($this->documents) : ($this->documents ?? []),
        ];
    }
}
