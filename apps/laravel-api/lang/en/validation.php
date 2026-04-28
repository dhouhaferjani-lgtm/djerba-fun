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
            'duplicate_start_time' => 'Each time slot in a rule must start at a unique time. The time :time was used more than once.',
            'end_before_start' => 'End time must be strictly after start time.',
            'overlapping' => 'Time slots cannot overlap. :first overlaps with :second.',
        ],
    ],
];
