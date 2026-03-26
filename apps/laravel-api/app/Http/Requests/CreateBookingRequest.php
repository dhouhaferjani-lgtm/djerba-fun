<?php

declare(strict_types=1);

namespace App\Http\Requests;

class CreateBookingRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'hold_id' => ['required', 'exists:booking_holds,id'],
            'session_id' => ['nullable', 'string', 'max:255'],
            'coupon_code' => ['nullable', 'string', 'max:50'],

            // Support both legacy single traveler_info and new travelers array
            'traveler_info' => ['required_without:travelers', 'nullable', 'array'],
            'traveler_info.first_name' => ['required_with:traveler_info', 'string', 'max:255'],
            'traveler_info.last_name' => ['required_with:traveler_info', 'string', 'max:255'],
            'traveler_info.email' => ['required_with:traveler_info', 'email', 'max:255'],
            'traveler_info.phone' => ['required_with:traveler_info', 'string', 'max:50', 'regex:/^\+?[\d\s\-\(\)\.]{8,20}$/'],
            'traveler_info.special_requests' => ['nullable', 'string', 'max:1000'],

            // New: Array of travelers for multi-guest bookings (email-only checkout)
            'travelers' => ['required_without:traveler_info', 'nullable', 'array', 'min:1'],
            'travelers.*.first_name' => ['nullable', 'string', 'max:255'],
            'travelers.*.last_name' => ['nullable', 'string', 'max:255'],
            'travelers.*.email' => ['required', 'email', 'max:255'],
            'travelers.*.phone' => ['nullable', 'string', 'max:50', 'regex:/^\+?[\d\s\-\(\)\.]{8,20}$/'],
            'travelers.*.person_type' => ['nullable', 'string', 'in:adult,child,infant'],
            'travelers.*.special_requests' => ['nullable', 'string', 'max:1000'],

            // Billing address fields (can be part of traveler data or separate)
            'billing_country_code' => ['nullable', 'string', 'size:2'],
            'billing_city' => ['nullable', 'string', 'max:100'],
            'billing_postal_code' => ['nullable', 'string', 'max:20'],
            'billing_address_line1' => ['nullable', 'string', 'max:255'],
            'billing_address_line2' => ['nullable', 'string', 'max:255'],
            'pricing_disclosure_accepted' => ['nullable', 'boolean'],

            // Extras selection - id is the listing_extra_id (not extra_id)
            'extras' => ['nullable', 'array'],
            'extras.*.id' => ['required', 'uuid', 'exists:listing_extras,id'],
            'extras.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get the travelers array (normalized from either format).
     * If billing address fields are provided, merge them into the primary traveler.
     */
    public function getTravelers(): array
    {
        // If travelers array is provided, use it
        if ($this->has('travelers') && is_array($this->travelers) && count($this->travelers) > 0) {
            $travelers = $this->travelers;
        } elseif ($this->has('traveler_info') && is_array($this->traveler_info)) {
            // Otherwise, convert legacy traveler_info to array format
            $travelers = [$this->traveler_info];
        } else {
            return [];
        }

        // Merge billing address into primary traveler if provided
        if ($this->hasBillingAddress()) {
            $travelers[0]['billing_address'] = $this->getBillingAddress();
        }

        return $travelers;
    }

    /**
     * Check if billing address fields are provided.
     */
    public function hasBillingAddress(): bool
    {
        return $this->has('billing_country_code') ||
               $this->has('billing_city') ||
               $this->has('billing_postal_code') ||
               $this->has('billing_address_line1') ||
               $this->has('billing_address_line2');
    }

    /**
     * Get billing address as array.
     */
    public function getBillingAddress(): array
    {
        return [
            'country_code' => $this->input('billing_country_code'),
            'city' => $this->input('billing_city'),
            'postal_code' => $this->input('billing_postal_code'),
            'address_line1' => $this->input('billing_address_line1'),
            'address_line2' => $this->input('billing_address_line2'),
        ];
    }

    /**
     * Get the primary traveler (first in array or legacy traveler_info).
     */
    public function getPrimaryTraveler(): ?array
    {
        $travelers = $this->getTravelers();

        return $travelers[0] ?? null;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'hold_id.required' => 'A valid booking hold is required.',
            'hold_id.exists' => 'The specified booking hold does not exist or has expired.',
            'traveler_info.required_without' => 'Traveler information is required.',
            'travelers.required_without' => 'At least one traveler is required.',
            'travelers.*.email.required' => 'Email is required for all travelers.',
            'travelers.*.email.email' => 'Please provide a valid email address.',
            'traveler_info.phone.regex' => 'Please enter a valid phone number (e.g., +216 52 665 202).',
            'travelers.*.phone.regex' => 'Please enter a valid phone number (e.g., +216 52 665 202).',
        ];
    }
}
