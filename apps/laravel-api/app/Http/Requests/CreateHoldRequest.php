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
            'quantity' => ['required', 'integer', 'min:1'],
            'session_id' => ['nullable', 'string', 'max:255'],
        ];
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
