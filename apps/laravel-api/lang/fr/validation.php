<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Messages de validation personnalisés
    |--------------------------------------------------------------------------
    |
    | Vous pouvez spécifier des messages de validation personnalisés ici.
    |
    */

    'turnstile_required' => 'Veuillez compléter la vérification de sécurité.',
    'turnstile_failed' => 'La vérification de sécurité a échoué. Veuillez réessayer.',

    'availability_rule' => [
        'time_slots' => [
            'entry_invalid' => 'Chaque créneau horaire doit être un objet contenant start_time, end_time et capacity.',
            'times_required' => "L'heure de début et l'heure de fin sont obligatoires.",
            'capacity_required' => 'La capacité doit être au moins 1.',
            'duplicate_start_time' => "Chaque créneau d'une règle doit commencer à une heure unique. L'heure :time est utilisée plusieurs fois.",
            'end_before_start' => "L'heure de fin doit être strictement postérieure à l'heure de début.",
            'overlapping' => 'Les créneaux horaires ne peuvent pas se chevaucher. :first chevauche :second.',
        ],
    ],
];
