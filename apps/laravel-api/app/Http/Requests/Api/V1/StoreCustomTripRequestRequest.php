<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\CustomTripRequest;
use App\Rules\TurnstileToken;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomTripRequestRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            // Trip Details
            'travel_dates' => ['required', 'array'],
            'travel_dates.start' => ['required', 'date', 'after_or_equal:today'],
            'travel_dates.end' => ['required', 'date', 'after:travel_dates.start'],
            'travel_dates.flexible' => ['sometimes', 'boolean'],

            'travelers' => ['required', 'array'],
            'travelers.adults' => ['required', 'integer', 'min:1', 'max:20'],
            'travelers.children' => ['sometimes', 'integer', 'min:0', 'max:10'],

            'duration_days' => ['required', 'integer', 'min:3', 'max:21'],

            // Preferences
            'interests' => ['required', 'array', 'min:1', 'max:5'],
            'interests.*' => ['required', 'string', Rule::in(CustomTripRequest::INTERESTS)],

            'budget' => ['required', 'array'],
            'budget.per_person' => ['required', 'integer', 'min:500', 'max:10000'],
            'budget.currency' => ['sometimes', 'string', 'in:TND,EUR,USD'],

            'accommodation_style' => [
                'required',
                'string',
                Rule::in([
                    CustomTripRequest::STYLE_BUDGET,
                    CustomTripRequest::STYLE_MID_RANGE,
                    CustomTripRequest::STYLE_LUXURY,
                ]),
            ],

            'travel_pace' => [
                'required',
                'string',
                Rule::in([
                    CustomTripRequest::PACE_RELAXED,
                    CustomTripRequest::PACE_MODERATE,
                    CustomTripRequest::PACE_ACTIVE,
                ]),
            ],

            'special_occasions' => ['sometimes', 'array'],
            'special_occasions.*' => ['string', 'in:honeymoon,birthday,anniversary,other'],

            // Contact Details
            'contact' => ['required', 'array'],
            'contact.name' => ['required', 'string', 'min:2', 'max:255'],
            'contact.email' => ['required', 'email', 'max:255'],
            'contact.phone' => ['required', 'string', 'min:8', 'max:50'],
            'contact.whatsapp' => ['sometimes', 'nullable', 'string', 'max:50'],
            'contact.country' => ['required', 'string', 'size:2'],
            'contact.preferred_method' => ['required', 'string', 'in:email,phone,whatsapp'],

            'special_requests' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'newsletter_consent' => ['sometimes', 'boolean'],
            'locale' => ['sometimes', 'string', 'in:en,fr'],

            // Cloudflare Turnstile bot protection
            'cf_turnstile_response' => ['nullable', new TurnstileToken($this->ip())],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'travel_dates.start.after_or_equal' => 'The travel start date must be today or a future date.',
            'travel_dates.end.after' => 'The travel end date must be after the start date.',
            'travelers.adults.min' => 'At least 1 adult is required.',
            'interests.min' => 'Please select at least 1 interest.',
            'interests.max' => 'Please select at most 5 interests.',
            'budget.per_person.min' => 'Minimum budget is 500 TND per person.',
            'budget.per_person.max' => 'Maximum budget is 10,000 TND per person.',
            'contact.name.min' => 'Name must be at least 2 characters.',
            'contact.email.email' => 'Please enter a valid email address.',
            'contact.phone.min' => 'Please enter a valid phone number.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure default values
        if (!$this->has('travelers.children')) {
            $this->merge([
                'travelers' => array_merge($this->input('travelers', []), ['children' => 0]),
            ]);
        }

        if (!$this->has('newsletter_consent')) {
            $this->merge(['newsletter_consent' => false]);
        }

        if (!$this->has('locale')) {
            $this->merge(['locale' => 'en']);
        }
    }
}
