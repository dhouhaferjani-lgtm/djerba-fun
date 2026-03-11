<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class CreateHoldRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Allows both authenticated users and guests (with session_id).
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
            'slot_id' => [
                'required',
                'integer',
                Rule::exists('availability_slots', 'id')
                    ->where('listing_id', $this->route('listing')->id),
            ],
            // Quantity is required for standard bookings (tours, events, nautical)
            // Not required for accommodation bookings (use guests instead) or when person_types is provided
            'quantity' => [
                Rule::requiredIf(fn () => ! $this->filled('check_in_date') && ! $this->filled('person_types')),
                'nullable',
                'integer',
                'min:1',
            ],
            'session_id' => ['nullable', 'string', 'max:255'],
            // Person type breakdown (optional - will use quantity if not provided)
            'person_types' => ['nullable', 'array'],
            'person_types.adult' => ['nullable', 'integer', 'min:0'],
            'person_types.child' => ['nullable', 'integer', 'min:0'],
            'person_types.infant' => ['nullable', 'integer', 'min:0'],
            // Extras (optional - selected extras with quantities)
            'extras' => ['nullable', 'array'],
            'extras.*.id' => ['required', 'string'],
            'extras.*.quantity' => ['required', 'integer', 'min:1'],
            // Accommodation-specific fields
            'check_in_date' => ['nullable', 'date', 'after_or_equal:today'],
            'check_out_date' => ['nullable', 'date', 'after_or_equal:check_in_date'],
            'guests' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Get the total quantity from either quantity field or person_types breakdown.
     */
    public function getTotalQuantity(): int
    {
        if ($this->has('person_types') && is_array($this->person_types)) {
            return array_sum($this->person_types);
        }

        return (int) $this->quantity;
    }

    /**
     * Get the person type breakdown, or null if not provided.
     */
    public function getPersonTypeBreakdown(): ?array
    {
        if ($this->has('person_types') && is_array($this->person_types)) {
            // Filter out zero values for cleaner storage
            return array_filter($this->person_types, fn ($qty) => $qty > 0);
        }

        return null;
    }

    /**
     * Check if this is an accommodation booking (has date range).
     */
    public function isAccommodationBooking(): bool
    {
        return $this->filled('check_in_date') && $this->filled('check_out_date');
    }

    /**
     * Get check-in date as Carbon instance.
     */
    public function getCheckInDate(): ?\Carbon\Carbon
    {
        return $this->filled('check_in_date')
            ? \Carbon\Carbon::parse($this->check_in_date)
            : null;
    }

    /**
     * Get check-out date as Carbon instance.
     */
    public function getCheckOutDate(): ?\Carbon\Carbon
    {
        return $this->filled('check_out_date')
            ? \Carbon\Carbon::parse($this->check_out_date)
            : null;
    }

    /**
     * Get number of nights for accommodation booking.
     * Same-day selection (check-in = check-out) is treated as 1 night.
     */
    public function getNights(): int
    {
        $checkIn = $this->getCheckInDate();
        $checkOut = $this->getCheckOutDate();

        if (! $checkIn || ! $checkOut) {
            return 0;
        }

        $nights = $checkIn->diffInDays($checkOut);

        // Same-day selection = 1 night minimum
        return max(1, $nights);
    }

    /**
     * Get guest count for accommodation booking.
     */
    public function getGuestCount(): int
    {
        return (int) ($this->guests ?? 1);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'slot_id.exists' => 'The selected time slot is not available for this listing.',
            'quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}
