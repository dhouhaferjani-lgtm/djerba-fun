<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
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
            'hold_id' => ['required', 'uuid', 'exists:booking_holds,id'],
            'session_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get the session ID from request or generate one.
     */
    public function getSessionId(): ?string
    {
        if (auth()->check()) {
            return null;
        }

        return $this->validated('session_id');
    }
}
