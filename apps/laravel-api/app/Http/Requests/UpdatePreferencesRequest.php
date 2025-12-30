<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends BaseFormRequest
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
            'locale' => ['sometimes', 'string', Rule::in(['en', 'fr', 'ar'])],
            'currency' => ['sometimes', 'string', 'size:3'],
            'notifications' => ['sometimes', 'array'],
            'notifications.email_notifications' => ['sometimes', 'boolean'],
            'notifications.marketing_emails' => ['sometimes', 'boolean'],
            'notifications.booking_reminders' => ['sometimes', 'boolean'],
            'notifications.review_reminders' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'locale.in' => 'Locale must be one of: en, fr, ar',
            'currency.size' => 'Currency code must be exactly 3 characters',
            'notifications.array' => 'Notifications must be an object',
            'notifications.email_notifications.boolean' => 'Email notifications must be true or false',
            'notifications.marketing_emails.boolean' => 'Marketing emails must be true or false',
            'notifications.booking_reminders.boolean' => 'Booking reminders must be true or false',
            'notifications.review_reminders.boolean' => 'Review reminders must be true or false',
        ];
    }
}
