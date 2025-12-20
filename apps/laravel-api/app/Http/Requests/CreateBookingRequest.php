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

            // Support both legacy single traveler_info and new travelers array
            'traveler_info' => ['required_without:travelers', 'nullable', 'array'],
            'traveler_info.first_name' => ['required_with:traveler_info', 'string', 'max:255'],
            'traveler_info.last_name' => ['required_with:traveler_info', 'string', 'max:255'],
            'traveler_info.email' => ['required_with:traveler_info', 'email', 'max:255'],
            'traveler_info.phone' => ['required_with:traveler_info', 'string', 'max:50'],
            'traveler_info.special_requests' => ['nullable', 'string', 'max:1000'],

            // New: Array of travelers for multi-guest bookings
            'travelers' => ['required_without:traveler_info', 'nullable', 'array', 'min:1'],
            'travelers.*.first_name' => ['required', 'string', 'max:255'],
            'travelers.*.last_name' => ['required', 'string', 'max:255'],
            'travelers.*.email' => ['nullable', 'email', 'max:255'],
            'travelers.*.phone' => ['nullable', 'string', 'max:50'],
            'travelers.*.person_type' => ['nullable', 'string', 'in:adult,child,infant'],
            'travelers.*.special_requests' => ['nullable', 'string', 'max:1000'],

            'extras' => ['nullable', 'array'],
            'extras.*.name' => ['required', 'string', 'max:255'],
            'extras.*.price' => ['required', 'numeric', 'min:0'],
            'extras.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get the travelers array (normalized from either format).
     *
     * @return array
     */
    public function getTravelers(): array
    {
        // If travelers array is provided, use it
        if ($this->has('travelers') && is_array($this->travelers) && count($this->travelers) > 0) {
            return $this->travelers;
        }

        // Otherwise, convert legacy traveler_info to array format
        if ($this->has('traveler_info') && is_array($this->traveler_info)) {
            return [$this->traveler_info];
        }

        return [];
    }

    /**
     * Get the primary traveler (first in array or legacy traveler_info).
     *
     * @return array|null
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
            'travelers.*.first_name.required' => 'First name is required for all travelers.',
            'travelers.*.last_name.required' => 'Last name is required for all travelers.',
        ];
    }
}
