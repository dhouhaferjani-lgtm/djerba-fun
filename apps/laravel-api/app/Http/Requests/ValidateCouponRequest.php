<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateCouponRequest extends FormRequest
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
            'code' => ['required', 'string'],
            'listing_id' => ['required', 'uuid', 'exists:listings,id'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Please enter a coupon code.',
            'listing_id.required' => 'Listing ID is required.',
            'listing_id.exists' => 'The selected listing does not exist.',
            'amount.required' => 'Order amount is required.',
            'amount.min' => 'Order amount must be greater than 0.',
        ];
    }
}
