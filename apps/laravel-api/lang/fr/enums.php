<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Go Adventure - French Enum Translations
    |--------------------------------------------------------------------------
    |
    | French translations for all PHP enums used in the application.
    |
    */

    'booking_status' => [
        'pending_payment' => 'En attente de paiement',
        'pending_confirmation' => 'En attente de confirmation',
        'confirmed' => 'Confirmé',
        'cancelled' => 'Annulé',
        'completed' => 'Terminé',
        'no_show' => 'Absent',
        'refund_requested' => 'Remboursement demandé',
        'refunded' => 'Remboursé',
    ],

    'listing_status' => [
        'draft' => 'Brouillon',
        'pending_review' => 'En cours de validation',
        'published' => 'Publié',
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'archived' => 'Archivé',
        'rejected' => 'Rejeté',
    ],

    'service_type' => [
        'tour' => 'Excursion',
        'event' => 'Événement',
        'activity' => 'Activité',
    ],

    'user_role' => [
        'admin' => 'Administrateur',
        'vendor' => 'Prestataire',
        'traveler' => 'Voyageur',
        'agent' => 'Agent',
    ],

    'user_status' => [
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'suspended' => 'Suspendu',
        'pending' => 'En attente',
    ],

    'extra_category' => [
        'equipment' => 'Équipement',
        'meal' => 'Repas',
        'insurance' => 'Assurance',
        'upgrade' => 'Surclassement',
        'merchandise' => 'Marchandise',
        'transport' => 'Transport',
        'accessibility' => 'Accessibilité',
        'other' => 'Autre',
    ],

    'pricing_type' => [
        'per_person' => 'Par personne',
        'per_booking' => 'Par réservation',
        'per_unit' => 'Par unité',
        'per_person_type' => 'Par catégorie de personne',
    ],

    'difficulty_level' => [
        'easy' => 'Facile',
        'moderate' => 'Modéré',
        'challenging' => 'Difficile',
        'expert' => 'Expert',
    ],

    'kyc_status' => [
        'pending' => 'En attente',
        'submitted' => 'Soumis',
        'verified' => 'Vérifié',
        'rejected' => 'Rejeté',
    ],

    'payout_status' => [
        'pending' => 'En attente',
        'processing' => 'En cours',
        'completed' => 'Effectué',
        'failed' => 'Échoué',
        'cancelled' => 'Annulé',
    ],

    'availability_rule_type' => [
        'recurring' => 'Récurrent',
        'specific_date' => 'Date spécifique',
        'blackout' => 'Indisponible',
    ],

    'discount_type' => [
        'percentage' => 'Pourcentage',
        'fixed_amount' => 'Montant fixe',
    ],

    'payment_status' => [
        'pending' => 'En attente',
        'processing' => 'En cours',
        'succeeded' => 'Réussi',
        'failed' => 'Échoué',
        'cancelled' => 'Annulé',
        'refunded' => 'Remboursé',
        'partially_refunded' => 'Partiellement remboursé',
    ],

    'payment_method' => [
        'card' => 'Carte bancaire',
        'bank_transfer' => 'Virement bancaire',
        'cash' => 'Espèces',
        'paypal' => 'PayPal',
        'stripe' => 'Stripe',
    ],

    'booking_extra_status' => [
        'active' => 'Actif',
        'cancelled' => 'Annulé',
        'refunded' => 'Remboursé',
    ],

    'custom_trip_status' => [
        'new' => 'Nouvelle',
        'contacted' => 'Contacté',
        'proposal_sent' => 'Proposition envoyée',
        'confirmed' => 'Confirmée',
        'completed' => 'Terminée',
        'cancelled' => 'Annulée',
        'archived' => 'Archivée',
    ],

    'partner_tier' => [
        'basic' => 'Standard',
        'premium' => 'Premium',
        'enterprise' => 'Entreprise',
    ],

    'event_type' => [
        'festival' => 'Festival',
        'concert' => 'Concert',
        'workshop' => 'Atelier',
        'exhibition' => 'Exposition',
        'sports' => 'Sport',
        'food_drink' => 'Gastronomie',
        'cultural' => 'Culturel',
        'other' => 'Autre',
    ],

    'accommodation_style' => [
        'budget' => 'Économique',
        'mid_range' => 'Milieu de gamme',
        'luxury' => 'Luxe',
        'local' => 'Local / Authentique',
    ],

    'travel_pace' => [
        'relaxed' => 'Tranquille',
        'moderate' => 'Modéré',
        'active' => 'Actif',
        'intensive' => 'Intensif',
    ],

    'duration_unit' => [
        'minutes' => 'Minutes',
        'hours' => 'Heures',
        'days' => 'Jours',
    ],

    'distance_unit' => [
        'km' => 'Kilomètres',
        'miles' => 'Miles',
        'meters' => 'Mètres',
    ],

    'company_type' => [
        'individual' => 'Auto-entrepreneur',
        'company' => 'Société',
        'association' => 'Association',
    ],

    'review_status' => [
        'pending' => 'En attente de modération',
        'approved' => 'Approuvé',
        'rejected' => 'Rejeté',
    ],

    'data_deletion_status' => [
        'pending' => 'En attente',
        'processing' => 'En cours de traitement',
        'completed' => 'Terminé',
        'rejected' => 'Rejeté',
    ],

    'contact_method' => [
        'email' => 'E-mail',
        'phone' => 'Téléphone',
        'whatsapp' => 'WhatsApp',
    ],

    'special_occasion' => [
        'anniversary' => 'Anniversaire',
        'birthday' => 'Anniversaire de naissance',
        'honeymoon' => 'Lune de miel',
        'graduation' => 'Remise de diplôme',
        'retirement' => 'Retraite',
        'wedding' => 'Mariage',
        'other' => 'Autre',
    ],

    'inventory_action_type' => [
        'adjustment' => 'Ajustement',
        'reservation' => 'Réservation',
        'release' => 'Libération',
        'sale' => 'Vente',
        'return' => 'Retour',
    ],
];
