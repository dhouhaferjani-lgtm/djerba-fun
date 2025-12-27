<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyBillingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hold_id' => ['required', 'uuid', 'exists:booking_holds,id'],
            'billing_address' => ['required', 'array'],
            'billing_address.country_code' => ['required', 'string', 'size:2'],
            'billing_address.city' => ['nullable', 'string', 'max:255'],
            'billing_address.postal_code' => ['nullable', 'string', 'max:20'],
            'billing_address.address_line1' => ['nullable', 'string', 'max:255'],
            'billing_address.address_line2' => ['nullable', 'string', 'max:255'],
        ];
    }
}
