<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Rules\TurnstileToken;
use Illuminate\Validation\Rule;

class RegisterRequest extends BaseFormRequest
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::enum(UserRole::class)],
            'display_name' => ['required', 'string', 'min:1', 'max:100'],

            // Traveler-specific fields
            'first_name' => ['required_if:role,traveler', 'string', 'max:100'],
            'last_name' => ['required_if:role,traveler', 'string', 'max:100'],
            'phone' => ['nullable', 'string'],
            'preferred_locale' => ['nullable', 'string', 'in:en,fr'],

            // Vendor-specific fields
            'company_name' => ['required_if:role,vendor', 'string', 'max:200'],
            'company_type' => ['required_if:role,vendor', 'string', 'in:individual,company,agency'],
            'tax_id' => ['nullable', 'string'],

            // Turnstile bot protection
            'cf_turnstile_response' => ['nullable', new TurnstileToken($this->ip())],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'company_name.required_if' => 'Company name is required for vendor accounts.',
            'company_type.required_if' => 'Company type is required for vendor accounts.',
            'first_name.required_if' => 'First name is required for traveler accounts.',
            'last_name.required_if' => 'Last name is required for traveler accounts.',
        ];
    }
}
