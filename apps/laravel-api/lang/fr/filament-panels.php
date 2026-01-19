<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Go Adventure - French Translations for Admin & Vendor Panels
    |--------------------------------------------------------------------------
    |
    | Natural French translations using formal "vous" throughout.
    | For use with Filament admin and vendor panels.
    |
    */

    'navigation' => [
        'groups' => [
            // Admin Panel Navigation Groups
            'sales' => 'Ventes',
            'operations' => 'Opérations',
            'people' => 'Utilisateurs',
            'catalog' => 'Catalogue',
            'content' => 'Contenu',
            'marketing' => 'Marketing',
            'system' => 'Système',
            'compliance' => 'Conformité',
            'settings' => 'Paramètres',

            // Vendor Panel Navigation Groups
            'my_listings' => 'Mes activités',
            'bookings' => 'Réservations',
            'feedback' => 'Avis clients',
            'finance' => 'Finances',
            'availability' => 'Disponibilités',
            'extras' => 'Options & Suppléments',
        ],

        'items' => [
            // Admin Panel Navigation Items
            'users' => 'Utilisateurs',
            'vendor_profiles' => 'Profils prestataires',
            'vendor_kyc' => 'Vérification KYC',
            'bookings' => 'Réservations',
            'payouts' => 'Versements',
            'listings' => 'Activités',
            'availability_rules' => 'Règles de disponibilité',
            'locations' => 'Destinations',
            'blog_posts' => 'Articles de blog',
            'blog_categories' => 'Catégories de blog',
            'pages' => 'Pages',
            'travel_tips' => 'Conseils voyage',
            'coupons' => 'Codes promo',
            'custom_trip_requests' => 'Demandes sur mesure',
            'data_deletion_requests' => 'Demandes de suppression',
            'partners' => 'Partenaires API',
            'payment_gateways' => 'Passerelles de paiement',
            'platform_settings' => 'Paramètres plateforme',
            'agents' => 'Agents API',

            // Vendor Panel Navigation Items
            'my_bookings' => 'Mes réservations',
            'my_listings' => 'Mes activités',
            'extras' => 'Options & Suppléments',
            'reviews' => 'Avis reçus',
        ],
    ],

    'resources' => [
        // Resource Labels (singular/plural)
        'user' => 'Utilisateur',
        'users' => 'Utilisateurs',
        'booking' => 'Réservation',
        'bookings' => 'Réservations',
        'listing' => 'Activité',
        'listings' => 'Activités',
        'location' => 'Destination',
        'locations' => 'Destinations',
        'blog_post' => 'Article',
        'blog_posts' => 'Articles',
        'page' => 'Page',
        'pages' => 'Pages',
        'coupon' => 'Code promo',
        'coupons' => 'Codes promo',
        'payout' => 'Versement',
        'payouts' => 'Versements',
        'extra' => 'Option',
        'extras' => 'Options',
        'review' => 'Avis',
        'reviews' => 'Avis',
        'partner' => 'Partenaire',
        'partners' => 'Partenaires',
    ],

    'actions' => [
        // Common Actions
        'create' => 'Créer',
        'save' => 'Enregistrer',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
        'view' => 'Voir',
        'cancel' => 'Annuler',
        'close' => 'Fermer',
        'confirm' => 'Confirmer',
        'submit' => 'Soumettre',
        'search' => 'Rechercher',
        'filter' => 'Filtrer',
        'reset' => 'Réinitialiser',
        'export' => 'Exporter',
        'import' => 'Importer',
        'refresh' => 'Actualiser',
        'back' => 'Retour',
        'next' => 'Suivant',
        'previous' => 'Précédent',
        'finish' => 'Terminer',

        // Listing Actions
        'approve' => 'Approuver',
        'reject' => 'Rejeter',
        'archive' => 'Archiver',
        'duplicate' => 'Dupliquer',
        'publish' => 'Publier',
        'unpublish' => 'Dépublier',
        'save_draft' => 'Enregistrer le brouillon',
        'submit_for_review' => 'Soumettre pour validation',
        'approve_publish' => 'Approuver et publier',
        're_publish' => 'Republier',

        // Booking Actions
        'mark_paid' => 'Marquer comme payé',
        'mark_completed' => 'Marquer comme terminé',
        'mark_no_show' => 'Marquer comme absent',
        'partial_payment' => 'Paiement partiel',
        'refund' => 'Rembourser',
        'contact' => 'Contacter',
        'send_reminder' => 'Envoyer un rappel',
        'resend_confirmation' => 'Renvoyer la confirmation',

        // Status Actions
        'activate' => 'Activer',
        'deactivate' => 'Désactiver',
        'enable' => 'Activer',
        'disable' => 'Désactiver',
        'remove' => 'Retirer',
        'add' => 'Ajouter',
        'attach' => 'Associer',
        'detach' => 'Dissocier',

        // Vendor Actions
        'verify' => 'Vérifier',
        'reject_kyc' => 'Rejeter le KYC',
        'create_vendor_profile' => 'Créer un profil prestataire',

        // Extra Actions
        'create_from_template' => 'Créer depuis un modèle',
        'add_existing_extra' => 'Ajouter une option existante',
        'add_extra' => 'Ajouter une option',

        // Bulk Actions
        'delete_selected' => 'Supprimer la sélection',
        'archive_selected' => 'Archiver la sélection',
        'approve_selected' => 'Approuver la sélection',
        'activate_selected' => 'Activer la sélection',
        'deactivate_selected' => 'Désactiver la sélection',
    ],

    'sections' => [
        // Common Sections
        'basic_information' => 'Informations générales',
        'user_information' => 'Informations utilisateur',
        'contact_information' => 'Coordonnées',
        'address' => 'Adresse',
        'settings' => 'Paramètres',
        'metadata' => 'Métadonnées',
        'media' => 'Médias',
        'images' => 'Images',
        'content' => 'Contenu',
        'publishing' => 'Publication',
        'seo' => 'Référencement',

        // Booking Sections
        'booking_information' => 'Détails de la réservation',
        'booking_details' => 'Informations de réservation',
        'traveler_information' => 'Informations voyageur',
        'payment_information' => 'Informations de paiement',
        'cancellation' => 'Annulation',

        // Listing Sections
        'details_highlights' => 'Détails et points forts',
        'service_details' => 'Détails du service',
        'route_itinerary' => 'Itinéraire et parcours',
        'pricing_capacity' => 'Tarifs et capacité',
        'pricing' => 'Tarification',
        'availability' => 'Disponibilités',
        'schedule' => 'Planning',
        'tour_details' => 'Détails de l\'excursion',
        'event_details' => 'Détails de l\'événement',
        'meeting_point' => 'Point de rendez-vous',
        'venue' => 'Lieu de l\'événement',
        'group_size' => 'Taille du groupe',
        'person_type_pricing' => 'Tarifs par catégorie',
        'booking_settings' => 'Paramètres de réservation',
        'cancellation_policy' => 'Politique d\'annulation',
        'display_settings' => 'Paramètres d\'affichage',

        // Extra Sections
        'category_pricing' => 'Catégorie et tarification',
        'quantity_settings' => 'Paramètres de quantité',
        'inventory_management' => 'Gestion des stocks',

        // Vendor Sections
        'vendor_information' => 'Informations prestataire',
        'company_information' => 'Informations entreprise',
        'kyc_status' => 'Statut KYC',
        'payout_settings' => 'Paramètres de versement',

        // Partner Sections
        'api_configuration' => 'Configuration API',
        'permissions' => 'Permissions',
        'webhook_settings' => 'Configuration Webhook',
        'rate_limiting' => 'Limitation de requêtes',

        // Custom Trip Sections
        'request_details' => 'Détails de la demande',
        'trip_details' => 'Détails du voyage',
        'travelers' => 'Voyageurs',
        'interests' => 'Centres d\'intérêt',
        'budget_style' => 'Budget et style',
        'special_requests' => 'Demandes spéciales',
        'special_occasions' => 'Occasions spéciales',
        'processing_information' => 'Informations de traitement',
    ],

    'fields' => [
        // Identity Fields
        'id' => 'ID',
        'title' => 'Titre',
        'name' => 'Nom',
        'display_name' => 'Nom affiché',
        'first_name' => 'Prénom',
        'last_name' => 'Nom de famille',
        'full_name' => 'Nom complet',
        'email' => 'Adresse e-mail',
        'phone' => 'Téléphone',
        'password' => 'Mot de passe',
        'password_confirmation' => 'Confirmer le mot de passe',
        'avatar' => 'Photo de profil',
        'avatar_url' => 'URL de l\'avatar',

        // Content Fields
        'description' => 'Description',
        'summary' => 'Résumé',
        'short_description' => 'Description courte',
        'content' => 'Contenu',
        'excerpt' => 'Extrait',
        'body' => 'Corps du texte',
        'notes' => 'Notes',
        'message' => 'Message',

        // Identification Fields
        'slug' => 'Identifiant URL',
        'code' => 'Code',
        'reference' => 'Référence',
        'booking_number' => 'N° de réservation',

        // Status Fields
        'status' => 'Statut',
        'type' => 'Type',
        'role' => 'Rôle',
        'active' => 'Actif',
        'featured' => 'Mis en avant',
        'published' => 'Publié',
        'verified' => 'Vérifié',
        'required' => 'Obligatoire',
        'enabled' => 'Activé',

        // Date/Time Fields
        'created_at' => 'Créé le',
        'updated_at' => 'Modifié le',
        'published_at' => 'Publié le',
        'start_date' => 'Date de début',
        'end_date' => 'Date de fin',
        'start_time' => 'Heure de début',
        'end_time' => 'Heure de fin',
        'date' => 'Date',
        'time' => 'Heure',
        'event_date' => 'Date de l\'activité',
        'booked_on' => 'Réservé le',
        'expires_at' => 'Expire le',
        'valid_from' => 'Valide à partir du',
        'valid_until' => 'Valide jusqu\'au',

        // Pricing Fields
        'price' => 'Prix',
        'price_tnd' => 'Prix (TND)',
        'price_eur' => 'Prix (EUR)',
        'base_price' => 'Prix de base',
        'amount' => 'Montant',
        'total' => 'Total',
        'subtotal' => 'Sous-total',
        'discount' => 'Remise',
        'discount_amount' => 'Montant de la remise',
        'discount_percentage' => 'Pourcentage de remise',
        'currency' => 'Devise',
        'pricing_type' => 'Type de tarification',

        // Quantity Fields
        'quantity' => 'Quantité',
        'qty' => 'Qté',
        'guests' => 'Participants',
        'adults' => 'Adultes',
        'children' => 'Enfants',
        'capacity' => 'Capacité',
        'min_quantity' => 'Quantité minimum',
        'max_quantity' => 'Quantité maximum',
        'default_quantity' => 'Quantité par défaut',
        'min_group_size' => 'Taille minimum du groupe',
        'max_group_size' => 'Taille maximum du groupe',

        // Duration Fields
        'duration' => 'Durée',
        'duration_value' => 'Valeur de durée',
        'duration_unit' => 'Unité de durée',
        'hours' => 'Heures',
        'minutes' => 'Minutes',

        // Location Fields
        'location' => 'Localisation',
        'address' => 'Adresse',
        'city' => 'Ville',
        'country' => 'Pays',
        'region' => 'Région',
        'postal_code' => 'Code postal',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
        'coordinates' => 'Coordonnées',

        // Company Fields
        'company_name' => 'Nom de l\'entreprise',
        'company_type' => 'Type d\'entreprise',
        'website' => 'Site web',
        'website_url' => 'URL du site web',
        'tax_id' => 'N° de TVA',

        // Category Fields
        'category' => 'Catégorie',
        'tags' => 'Tags',

        // Inventory Fields
        'inventory' => 'Inventaire',
        'stock' => 'Stock',
        'inventory_count' => 'Stock disponible',
        'track_inventory' => 'Suivre l\'inventaire',
        'capacity_per_unit' => 'Capacité par unité',

        // Relational Fields
        'vendor' => 'Prestataire',
        'traveler' => 'Voyageur',
        'author' => 'Auteur',
        'user' => 'Utilisateur',
        'listing' => 'Activité',
        'listings' => 'Activités',
        'booking' => 'Réservation',
        'bookings' => 'Réservations',

        // Analytics Fields
        'views' => 'Vues',
        'rating' => 'Note',
        'reviews_count' => 'Nombre d\'avis',
        'bookings_count' => 'Nombre de réservations',
        'used' => 'Utilisé',
        'used_count' => 'Utilisations',

        // Days
        'days' => 'Jours',
        'days_of_week' => 'Jours de la semaine',
        'monday' => 'Lundi',
        'tuesday' => 'Mardi',
        'wednesday' => 'Mercredi',
        'thursday' => 'Jeudi',
        'friday' => 'Vendredi',
        'saturday' => 'Samedi',
        'sunday' => 'Dimanche',

        // Image Fields
        'image' => 'Image',
        'image_url' => 'URL de l\'image',
        'featured_image' => 'Image principale',
        'thumbnail' => 'Miniature',
        'gallery' => 'Galerie',

        // Other Fields
        'color' => 'Couleur',
        'icon' => 'Icône',
        'order' => 'Ordre',
        'display_order' => 'Ordre d\'affichage',
        'position' => 'Position',
    ],

    'statuses' => [
        // General Statuses
        'pending' => 'En attente',
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'completed' => 'Terminé',
        'cancelled' => 'Annulé',
        'archived' => 'Archivé',
        'rejected' => 'Rejeté',

        // Booking Statuses
        'pending_payment' => 'En attente de paiement',
        'pending_confirmation' => 'En attente de confirmation',
        'confirmed' => 'Confirmé',
        'no_show' => 'Absent',
        'refunded' => 'Remboursé',
        'partial' => 'Partiel',

        // Listing Statuses
        'draft' => 'Brouillon',
        'pending_review' => 'En cours de validation',
        'published' => 'Publié',

        // Other Statuses
        'complete' => 'Complet',
        'incomplete' => 'Incomplet',
        'auto' => 'Automatique',
        'manual' => 'Manuel',
        'verified' => 'Vérifié',
        'unverified' => 'Non vérifié',
        'processing' => 'En cours de traitement',
        'failed' => 'Échoué',
    ],

    'empty_states' => [
        'no_records' => 'Aucun enregistrement',
        'no_results' => 'Aucun résultat',

        'no_bookings' => 'Aucune réservation',
        'no_bookings_description' => 'Les réservations de vos activités apparaîtront ici.',

        'no_listings' => 'Aucune activité',
        'no_listings_description' => 'Créez votre première activité pour commencer à recevoir des réservations.',

        'no_extras' => 'Aucune option ajoutée',
        'no_extras_description' => 'Ajoutez des options comme la location d\'équipement, les repas ou l\'assurance.',

        'no_extras_created' => 'Aucune option créée',
        'no_extras_created_description' => 'Créez des options que vos clients pourront ajouter à leurs réservations.',

        'no_images' => 'Aucune image téléchargée',
        'no_images_description' => 'Ajoutez des images pour rendre votre activité plus attrayante.',

        'no_reviews' => 'Aucun avis',
        'no_reviews_description' => 'Les avis de vos clients apparaîtront ici.',

        'no_availability' => 'Aucune disponibilité configurée',
        'no_availability_description' => 'Configurez vos créneaux de disponibilité pour recevoir des réservations.',
    ],

    'helpers' => [
        'required' => 'Obligatoire',
        'optional' => 'Facultatif',
        'required_for_publishing' => 'Requis pour la publication',
        'auto_generated' => 'Généré automatiquement depuis le titre',
        'auto_generated_from_name' => 'Généré automatiquement depuis le nom',
        'leave_empty_default' => 'Laisser vide pour utiliser la valeur par défaut',
        'leave_empty_unlimited' => 'Laisser vide pour illimité',
        'url_friendly' => 'Identifiant compatible URL',
        'short_summary' => 'Résumé court',
        'max_characters' => ':count caractères maximum',
        'select_option' => 'Sélectionnez une option',
        'select_multiple' => 'Sélectionnez une ou plusieurs options',
        'no_options' => 'Aucune option disponible',
        'search_placeholder' => 'Rechercher...',
        'upload_image' => 'Cliquez pour télécharger ou glissez-déposez',
        'supported_formats' => 'Formats supportés : :formats',
        'max_file_size' => 'Taille maximum : :size',
    ],

    'modals' => [
        'are_you_sure' => 'Êtes-vous sûr ?',
        'cannot_undo' => 'Cette action est irréversible.',
        'confirm_delete' => 'Confirmer la suppression',
        'confirm_action' => 'Confirmer l\'action',

        'delete_record' => 'Supprimer cet enregistrement ?',
        'delete_record_description' => 'Cette action supprimera définitivement cet enregistrement.',

        'approve_listing' => 'Approuver cette activité ?',
        'approve_listing_description' => 'L\'activité sera publiée et visible par les voyageurs.',

        'reject_listing' => 'Rejeter cette activité ?',
        'reject_listing_description' => 'Le prestataire sera notifié du rejet.',

        'archive_listing' => 'Archiver cette activité ?',
        'archive_listing_description' => 'L\'activité ne sera plus visible mais les réservations existantes seront conservées.',

        'delete_availability' => 'Supprimer cette règle de disponibilité ?',
        'delete_availability_description' => 'Les créneaux générés par cette règle seront également supprimés.',

        'mark_paid' => 'Marquer comme payé ?',
        'mark_paid_description' => 'La réservation sera confirmée et le client sera notifié.',

        'cancel_booking' => 'Annuler cette réservation ?',
        'cancel_booking_description' => 'Le client sera notifié de l\'annulation.',

        'verify_vendor' => 'Vérifier ce prestataire ?',
        'verify_vendor_description' => 'Le prestataire pourra publier des activités.',

        'reject_kyc' => 'Rejeter la demande KYC ?',
        'reject_kyc_description' => 'Le prestataire sera notifié du rejet.',
    ],

    'notifications' => [
        'saved' => 'Enregistré avec succès',
        'created' => 'Créé avec succès',
        'updated' => 'Mis à jour avec succès',
        'deleted' => 'Supprimé avec succès',
        'archived' => 'Archivé avec succès',
        'published' => 'Publié avec succès',
        'approved' => 'Approuvé avec succès',
        'rejected' => 'Rejeté',
        'completed' => 'Opération terminée',
        'cancelled' => 'Annulé',

        'error' => 'Une erreur est survenue',
        'try_again' => 'Veuillez réessayer',
        'validation_error' => 'Veuillez corriger les erreurs ci-dessous',
        'not_found' => 'Enregistrement introuvable',
        'unauthorized' => 'Action non autorisée',

        'booking_confirmed' => 'Réservation confirmée',
        'booking_cancelled' => 'Réservation annulée',
        'payment_received' => 'Paiement reçu',
        'refund_processed' => 'Remboursement effectué',

        'listing_submitted' => 'Activité soumise pour validation',
        'listing_approved' => 'Activité approuvée et publiée',
        'listing_rejected' => 'Activité rejetée',

        'vendor_verified' => 'Prestataire vérifié',
        'vendor_rejected' => 'Prestataire rejeté',
    ],

    'days' => [
        'monday' => 'Lundi',
        'tuesday' => 'Mardi',
        'wednesday' => 'Mercredi',
        'thursday' => 'Jeudi',
        'friday' => 'Vendredi',
        'saturday' => 'Samedi',
        'sunday' => 'Dimanche',

        'mon' => 'Lun',
        'tue' => 'Mar',
        'wed' => 'Mer',
        'thu' => 'Jeu',
        'fri' => 'Ven',
        'sat' => 'Sam',
        'sun' => 'Dim',
    ],

    'time_units' => [
        'second' => 'seconde',
        'seconds' => 'secondes',
        'minute' => 'minute',
        'minutes' => 'minutes',
        'hour' => 'heure',
        'hours' => 'heures',
        'day' => 'jour',
        'days' => 'jours',
        'week' => 'semaine',
        'weeks' => 'semaines',
        'month' => 'mois',
        'months' => 'mois',
        'year' => 'an',
        'years' => 'ans',
    ],

    'filters' => [
        'all' => 'Tous',
        'active_only' => 'Actifs uniquement',
        'inactive_only' => 'Inactifs uniquement',
        'pending_only' => 'En attente uniquement',
        'published_only' => 'Publiés uniquement',
        'draft_only' => 'Brouillons uniquement',
        'archived_only' => 'Archivés uniquement',
        'featured_only' => 'Mis en avant uniquement',
        'verified_only' => 'Vérifiés uniquement',
        'date_range' => 'Période',
        'from_date' => 'À partir du',
        'to_date' => 'Jusqu\'au',
    ],

    'pagination' => [
        'showing' => 'Affichage de',
        'to' => 'à',
        'of' => 'sur',
        'results' => 'résultats',
        'per_page' => 'par page',
    ],

    'widgets' => [
        'total_bookings' => 'Total des réservations',
        'total_revenue' => 'Chiffre d\'affaires total',
        'pending_bookings' => 'Réservations en attente',
        'confirmed_bookings' => 'Réservations confirmées',
        'upcoming_bookings' => 'Réservations à venir',
        'recent_bookings' => 'Réservations récentes',
        'popular_listings' => 'Activités populaires',
        'revenue_this_month' => 'Revenus ce mois',
        'bookings_this_month' => 'Réservations ce mois',
        'average_rating' => 'Note moyenne',
        'total_reviews' => 'Total des avis',
    ],

    'misc' => [
        'yes' => 'Oui',
        'no' => 'Non',
        'or' => 'ou',
        'and' => 'et',
        'none' => 'Aucun',
        'n_a' => 'N/A',
        'unknown' => 'Inconnu',
        'other' => 'Autre',
        'loading' => 'Chargement...',
        'processing' => 'Traitement en cours...',
        'please_wait' => 'Veuillez patienter...',
        'more' => 'Plus',
        'less' => 'Moins',
        'show_more' => 'Afficher plus',
        'show_less' => 'Afficher moins',
        'view_all' => 'Voir tout',
        'learn_more' => 'En savoir plus',
    ],
];
