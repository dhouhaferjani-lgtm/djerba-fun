<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Cart is public (supports guest checkout)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Primary contact (person paying)
            'primary_contact' => ['sometimes', 'array'],
            'primary_contact.first_name' => ['required_with:primary_contact', 'string', 'max:255'],
            'primary_contact.last_name' => ['required_with:primary_contact', 'string', 'max:255'],
            'primary_contact.email' => ['required_with:primary_contact', 'email', 'max:255'],
            'primary_contact.phone' => ['nullable', 'string', 'max:50'],

            // Guest names (only if listing requires it)
            'guest_names' => ['sometimes', 'array'],
            'guest_names.*' => ['required_with:guest_names', 'array'],
            'guest_names.*.first_name' => ['required', 'string', 'max:255'],
            'guest_names.*.last_name' => ['required', 'string', 'max:255'],
            'guest_names.*.person_type' => ['nullable', 'string', 'max:50'],

            // Extras
            'extras' => ['sometimes', 'array'],
            'extras.*.id' => ['required_with:extras', 'string'],
            'extras.*.name' => ['required_with:extras', 'string', 'max:255'],
            'extras.*.price' => ['required_with:extras', 'numeric', 'min:0'],
            'extras.*.quantity' => ['required_with:extras', 'integer', 'min:1'],

            // Session ID for guest checkout
            'session_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get the primary contact from the request.
     */
    public function getPrimaryContact(): ?array
    {
        return $this->validated('primary_contact');
    }

    /**
     * Get the guest names from the request.
     */
    public function getGuestNames(): ?array
    {
        return $this->validated('guest_names');
    }

    /**
     * Get the extras from the request.
     */
    public function getExtras(): ?array
    {
        return $this->validated('extras');
    }
}
