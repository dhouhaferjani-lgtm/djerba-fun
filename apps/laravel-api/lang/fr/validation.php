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
            'duplicate_slot' => 'Chaque créneau doit être unique. :start–:end est utilisé plusieurs fois.',
            'end_before_start' => "L'heure de fin doit être strictement postérieure à l'heure de début.",
            'overlapping' => 'Les créneaux horaires ne peuvent pas se chevaucher. :first chevauche :second.',
            'price_overrides' => [
                'invalid_shape' => 'Les prix personnalisés doivent contenir un tableau person_types.',
                'entry_invalid' => 'Chaque ligne de prix personnalisé doit référencer un type de personne par sa clé.',
                'orphan_key' => "Le type de personne « :key » n'est pas défini sur la tarification de l'annonce. Ajoutez-le d'abord à l'annonce.",
                'duplicate_key' => 'Le type de personne « :key » ne peut avoir qu\'un seul prix personnalisé par créneau.',
                'tnd_required' => 'Un prix TND positif ou nul est requis pour chaque ligne personnalisée.',
                'eur_required' => 'Un prix EUR positif ou nul est requis pour chaque ligne personnalisée.',
            ],
        ],
    ],
];
