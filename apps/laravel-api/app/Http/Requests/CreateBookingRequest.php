<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
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
            'traveler_info' => ['required', 'array'],
            'traveler_info.firstName' => ['required', 'string', 'max:255'],
            'traveler_info.lastName' => ['required', 'string', 'max:255'],
            'traveler_info.email' => ['required', 'email', 'max:255'],
            'traveler_info.phone' => ['required', 'string', 'max:50'],
            'traveler_info.notes' => ['nullable', 'string', 'max:1000'],
            'extras' => ['nullable', 'array'],
            'extras.*.name' => ['required', 'string', 'max:255'],
            'extras.*.price' => ['required', 'numeric', 'min:0'],
            'extras.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'hold_id.required' => 'A valid booking hold is required.',
            'hold_id.exists' => 'The specified booking hold does not exist or has expired.',
            'traveler_info.required' => 'Traveler information is required.',
            'traveler_info.firstName.required' => 'First name is required.',
            'traveler_info.lastName.required' => 'Last name is required.',
            'traveler_info.email.required' => 'Email address is required.',
            'traveler_info.email.email' => 'Please provide a valid email address.',
            'traveler_info.phone.required' => 'Phone number is required.',
        ];
    }
}
