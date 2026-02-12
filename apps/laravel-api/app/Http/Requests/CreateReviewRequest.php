<?php

declare(strict_types=1);

namespace App\Http\Requests;

class CreateReviewRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:5'],
            'pros' => ['nullable', 'array'],
            'pros.*' => ['string', 'max:255'],
            'cons' => ['nullable', 'array'],
            'cons.*' => ['string', 'max:255'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['string', 'url'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'Please provide a rating.',
            'rating.min' => 'Rating must be at least 1 star.',
            'rating.max' => 'Rating cannot exceed 5 stars.',
            'content.required' => 'Please write a review.',
            'content.min' => 'Review must be at least 5 characters.',
            'photos.max' => 'You can upload a maximum of 5 photos.',
        ];
    }
}
