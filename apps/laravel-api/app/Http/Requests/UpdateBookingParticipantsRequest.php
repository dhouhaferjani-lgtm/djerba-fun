<?php

declare(strict_types=1);

namespace App\Http\Requests;

class UpdateBookingParticipantsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'participants' => ['required', 'array', 'min:1'],
            'participants.*.id' => ['required', 'uuid', 'exists:booking_participants,id'],
            'participants.*.first_name' => ['required', 'string', 'max:100'],
            'participants.*.last_name' => ['required', 'string', 'max:100'],
            'participants.*.email' => ['nullable', 'email', 'max:255'],
            'participants.*.phone' => ['nullable', 'string', 'max:50'],
            'session_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'participants.required' => 'At least one participant is required.',
            'participants.*.id.required' => 'Participant ID is required.',
            'participants.*.id.exists' => 'One or more participants do not exist.',
            'participants.*.first_name.required' => 'First name is required for all participants.',
            'participants.*.last_name.required' => 'Last name is required for all participants.',
            'participants.*.email.email' => 'Please provide a valid email address.',
        ];
    }
}
