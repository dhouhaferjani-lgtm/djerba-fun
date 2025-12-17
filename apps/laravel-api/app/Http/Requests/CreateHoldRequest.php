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
            // Either quantity or person_types is required
            'quantity' => ['required_without:person_types', 'nullable', 'integer', 'min:1'],
            'session_id' => ['nullable', 'string', 'max:255'],
            // Person type breakdown (optional - will use quantity if not provided)
            'person_types' => ['nullable', 'array'],
            'person_types.adult' => ['nullable', 'integer', 'min:0'],
            'person_types.child' => ['nullable', 'integer', 'min:0'],
            'person_types.infant' => ['nullable', 'integer', 'min:0'],
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
            return array_filter($this->person_types, fn($qty) => $qty > 0);
        }

        return null;
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
