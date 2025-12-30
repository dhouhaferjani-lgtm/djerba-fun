<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateProfileRequest extends BaseFormRequest
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
        $user = $this->user();

        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'display_name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'preferred_locale' => ['sometimes', 'string', Rule::in(['en', 'fr', 'ar'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.string' => 'First name must be a valid text',
            'first_name.max' => 'First name cannot exceed 255 characters',
            'last_name.string' => 'Last name must be a valid text',
            'last_name.max' => 'Last name cannot exceed 255 characters',
            'display_name.string' => 'Display name must be a valid text',
            'display_name.max' => 'Display name cannot exceed 255 characters',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email address is already taken',
            'phone.max' => 'Phone number cannot exceed 20 characters',
            'preferred_locale.in' => 'Preferred locale must be one of: en, fr, ar',
        ];
    }
}
