<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends BaseFormRequest
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
            'session_id' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'payment_data' => ['nullable', 'array'],
            'payment_data.card_number' => ['required_if:payment_method,card', 'string'],
            'payment_data.card_holder' => ['required_if:payment_method,card', 'string'],
            'payment_data.expiry_month' => ['required_if:payment_method,card', 'integer', 'min:1', 'max:12'],
            'payment_data.expiry_year' => ['required_if:payment_method,card', 'integer', 'min:' . date('Y')],
            'payment_data.cvv' => ['required_if:payment_method,card', 'string', 'size:3'],
            'payment_data.instructions' => ['nullable', 'string'],
            'payment_data.expected_date' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => 'Payment method is required.',
            'payment_method.enum' => 'Invalid payment method selected.',
            'payment_data.card_number.required_if' => 'Card number is required for card payments.',
            'payment_data.card_holder.required_if' => 'Card holder name is required.',
            'payment_data.expiry_month.required_if' => 'Card expiry month is required.',
            'payment_data.expiry_year.required_if' => 'Card expiry year is required.',
            'payment_data.cvv.required_if' => 'CVV is required for card payments.',
        ];
    }
}
