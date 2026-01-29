<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\TurnstileToken;

class RegisterPasswordlessRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'first_name' => ['required', 'string', 'min:1', 'max:100'],
            'last_name' => ['required', 'string', 'min:1', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'preferred_locale' => ['nullable', 'string', 'in:en,fr'],
            'cf_turnstile_response' => ['nullable', new TurnstileToken($this->ip())],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'An account with this email already exists.',
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'preferred_locale.in' => 'Preferred language must be English (en) or French (fr).',
        ];
    }
}
