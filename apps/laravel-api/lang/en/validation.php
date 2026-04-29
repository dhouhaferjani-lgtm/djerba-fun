<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Custom Validation Messages
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes.
    |
    */

    'turnstile_required' => 'Please complete the security verification.',
    'turnstile_failed' => 'Security verification failed. Please try again.',

    'availability_rule' => [
        'time_slots' => [
            'entry_invalid' => 'Each time slot must be an object with start_time, end_time and capacity.',
            'times_required' => 'Both start_time and end_time are required.',
            'capacity_required' => 'Capacity must be at least 1.',
            'duplicate_slot' => 'Each time slot must be unique. :start–:end was used more than once.',
            'end_before_start' => 'End time must be strictly after start time.',
            'overlapping' => 'Time slots cannot overlap. :first overlaps with :second.',
            'price_overrides' => [
                'invalid_shape' => 'Price overrides must be an object containing a person_types array.',
                'entry_invalid' => 'Each price override row must reference a person-type by key.',
                'orphan_key' => "The person-type \":key\" is not defined on the listing's pricing. Add it to the listing first.",
                'duplicate_key' => 'The person-type ":key" cannot have more than one price override per slot.',
                'tnd_required' => 'A non-negative TND price is required for every override row.',
                'eur_required' => 'A non-negative EUR price is required for every override row.',
            ],
        ],
    ],
];
