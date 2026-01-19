<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Navigation Groups
    |--------------------------------------------------------------------------
    */
    'nav' => [
        'sales' => 'Ventes',
        'operations' => 'Opérations',
        'people' => 'Personnes',
        'catalog' => 'Catalogue',
        'content' => 'Contenu',
        'marketing' => 'Marketing',
        'system' => 'Système',
        'compliance' => 'Conformité',
        'settings' => 'Paramètres',
        // Vendor Panel
        'my_listings' => 'Mes Annonces',
        'bookings' => 'Réservations',
        'feedback' => 'Avis',
        'finance' => 'Finance',
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Labels
    |--------------------------------------------------------------------------
    */
    'resources' => [
        'blog_posts' => 'Articles de Blog',
        'blog_categories' => 'Catégories de Blog',
        'users' => 'Utilisateurs',
        'vendor_kyc' => 'KYC Vendeurs',
        'vendor_profile' => 'Profil Vendeur',
        'vendor_profiles' => 'Profils Vendeurs',
        'listings' => 'Annonces',
        'bookings' => 'Réservations',
        'coupons' => 'Coupons',
        'payouts' => 'Paiements',
        'locations' => 'Emplacements',
        'availability_rules' => 'Règles de Disponibilité',
        'agents' => 'Agents',
        'reviews' => 'Avis',
        'extras' => 'Suppléments',
        'partners' => 'Partenaires',
        'pages' => 'Pages',
        'travel_tips' => 'Conseils de Voyage',
        'custom_trip_requests' => 'Demandes de Voyage Sur Mesure',
        'data_deletion_requests' => 'Demandes de Suppression',
        'payment_gateways' => 'Passerelles de Paiement',
    ],

    /*
    |--------------------------------------------------------------------------
    | Section Titles
    |--------------------------------------------------------------------------
    */
    'sections' => [
        'content' => 'Contenu',
        'metadata' => 'Métadonnées',
        'media' => 'Média',
        'publishing' => 'Publication',
        'seo' => 'SEO',
        'basic_information' => 'Informations de Base',
        'discount_settings' => 'Paramètres de Réduction',
        'validity_usage' => 'Validité & Utilisation',
        'restrictions' => 'Restrictions',
        'user_information' => 'Informations Utilisateur',
        'booking_information' => 'Informations de Réservation',
        'pricing' => 'Tarification',
        'traveler_information' => 'Informations du Voyageur',
        'cancellation' => 'Annulation',
        'listing_information' => 'Informations de l\'Annonce',
        'location' => 'Emplacement',
        'description' => 'Description',
        'pricing_capacity' => 'Tarification & Capacité',
        'moderation' => 'Modération',
        'vendor_information' => 'Informations du Vendeur',
        'contact_information' => 'Coordonnées',
        'address' => 'Adresse',
        'kyc_status' => 'Statut KYC',
        'payout_information' => 'Informations de Paiement',
        'additional_information' => 'Informations Complémentaires',
        'location_information' => 'Informations de l\'Emplacement',
        'geographic_information' => 'Informations Géographiques',
        'map_coordinates' => 'Coordonnées de la Carte',
        'statistics' => 'Statistiques',
    ],

    /*
    |--------------------------------------------------------------------------
    | Common Labels
    |--------------------------------------------------------------------------
    */
    'labels' => [
        // General
        'active' => 'Actif',
        'status' => 'Statut',
        'name' => 'Nom',
        'description' => 'Description',
        'created_at' => 'Créé le',
        'updated_at' => 'Mis à jour le',
        'created' => 'Créé',
        'type' => 'Type',
        'notes' => 'Notes',
        'reference' => 'Référence',
        'currency' => 'Devise',
        'amount' => 'Montant',

        // Blog
        'title' => 'Titre',
        'slug' => 'Slug',
        'excerpt' => 'Extrait',
        'content' => 'Contenu',
        'author' => 'Auteur',
        'category' => 'Catégorie',
        'tags' => 'Tags',
        'featured_image' => 'Image à la Une',
        'publish_date' => 'Date de Publication',
        'feature_on_homepage' => 'Afficher sur la Page d\'Accueil',
        'seo_title' => 'Titre SEO',
        'seo_description' => 'Description SEO',
        'image' => 'Image',
        'featured' => 'À la Une',
        'views' => 'Vues',
        'published' => 'Publié',

        // Coupon
        'code' => 'Code',
        'discount_type' => 'Type de Réduction',
        'discount_value' => 'Valeur de Réduction',
        'minimum_order' => 'Commande Minimum',
        'maximum_discount' => 'Réduction Maximum',
        'valid_from' => 'Valide à partir de',
        'valid_until' => 'Valide jusqu\'au',
        'usage_limit' => 'Limite d\'Utilisation',
        'usage_count' => 'Nombre d\'Utilisations',
        'used' => 'Utilisé',
        'listing_ids' => 'IDs des Annonces',
        'user_ids' => 'IDs des Utilisateurs',

        // User
        'display_name' => 'Nom d\'Affichage',
        'email' => 'Email',
        'password' => 'Mot de Passe',
        'role' => 'Rôle',
        'avatar_url' => 'URL de l\'Avatar',
        'email_verified_at' => 'Email Vérifié le',
        'verified' => 'Vérifié',

        // Booking
        'booking_number' => 'Numéro de Réservation',
        'booking_hash' => 'Réservation #',
        'traveler' => 'Voyageur',
        'traveler_details' => 'Détails du Voyageur',
        'field' => 'Champ',
        'value' => 'Valeur',
        'extras_addons' => 'Extras/Suppléments',
        'cancellation_reason' => 'Raison d\'Annulation',
        'cancelled_at' => 'Annulé le',
        'confirmed_at' => 'Confirmé le',
        'booked_on' => 'Réservé le',
        'confirmed' => 'Confirmé',
        'participant_names' => 'Noms des Participants',
        'linked_to_account' => 'Lié au Compte',
        'link_method' => 'Méthode de Liaison',
        'quantity' => 'Quantité',
        'qty' => 'Qté',
        'total_amount' => 'Montant Total',
        'from' => 'De',
        'until' => 'Jusqu\'à',

        // Listing
        'title_english' => 'Titre (Anglais)',
        'title_french' => 'Titre (Français)',
        'summary_english' => 'Résumé (Anglais)',
        'description_english' => 'Description (Anglais)',
        'base_price' => 'Prix de Base (centimes)',
        'min_group_size' => 'Taille Min du Groupe',
        'max_group_size' => 'Taille Max du Groupe',
        'rejection_reason' => 'Raison du Rejet',
        'vendor' => 'Vendeur',
        'price' => 'Prix',
        'rating' => 'Note',
        'pending_review' => 'En Attente de Révision',

        // Vendor
        'company_name' => 'Nom de l\'Entreprise',
        'company_type' => 'Type d\'Entreprise',
        'tax_id' => 'Numéro Fiscal / TVA',
        'phone' => 'Numéro de Téléphone',
        'website' => 'Site Web',
        'business_description' => 'Description de l\'Entreprise',
        'street_address' => 'Adresse',
        'city' => 'Ville',
        'postal_code' => 'Code Postal',
        'country' => 'Pays',
        'commission_tier' => 'Niveau de Commission',
        'payout_account_id' => 'ID du Compte de Paiement',
        'verified_at' => 'Vérifié le',
        'company' => 'Entreprise',
        'tier' => 'Niveau',
        'joined' => 'Inscrit le',

        // Payout
        'payout_method' => 'Méthode de Paiement',
        'transaction_reference' => 'Référence de Transaction',
        'processed_at' => 'Traité le',
        'failure_reason' => 'Raison de l\'Échec',

        // Additional
        'additional_notes' => 'Notes Supplémentaires',
        'required_documents' => 'Documents Requis',
        'new_commission_tier' => 'Nouveau Niveau de Commission',

        // Location
        'image_url' => 'URL de l\'Image',
        'address' => 'Adresse',
        'region' => 'Région/État',
        'timezone' => 'Fuseau Horaire',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
        'number_of_listings' => 'Nombre d\'Annonces',
        'listings' => 'Annonces',
    ],

    /*
    |--------------------------------------------------------------------------
    | Options / Choices
    |--------------------------------------------------------------------------
    */
    'options' => [
        // Status
        'draft' => 'Brouillon',
        'published' => 'Publié',
        'scheduled' => 'Programmé',
        'archived' => 'Archivé',
        'pending' => 'En Attente',
        'processing' => 'En Cours',
        'completed' => 'Terminé',
        'failed' => 'Échoué',
        'rejected' => 'Rejeté',

        // Discount Type
        'percentage' => 'Pourcentage',
        'fixed_amount' => 'Montant Fixe',

        // Company Type
        'individual' => 'Individuel / Auto-entrepreneur',
        'company_llc' => 'Entreprise / SARL',
        'nonprofit' => 'Organisation à But Non Lucratif',
        'government' => 'Entité Gouvernementale',

        // Commission Tier
        'standard' => 'Standard (15%)',
        'silver' => 'Argent (12%)',
        'gold' => 'Or (10%)',
        'platinum' => 'Platine (8%)',
        'standard_label' => 'Standard',
        'silver_label' => 'Argent',
        'gold_label' => 'Or',
        'platinum_label' => 'Platine',

        // Payout Method
        'bank_transfer' => 'Virement Bancaire',
        'paypal' => 'PayPal',

        // Service Type
        'tours' => 'Tours',
        'events' => 'Événements',

        // Participant Status
        'not_required' => 'Non Requis',
        'partial' => 'Partiel',
        'complete' => 'Complet',

        // Link Method
        'auto' => 'Auto',
        'manual' => 'Manuel',
        'claimed' => 'Réclamé',
        'na' => 'N/A',

        // Documents
        'id_proof' => 'Pièce d\'Identité (Passeport/CNI)',
        'business_license' => 'Licence Commerciale',
        'tax_certificate' => 'Certificat Fiscal',
        'bank_statement' => 'Relevé Bancaire',
        'insurance' => 'Assurance Responsabilité',
        'address_proof' => 'Justificatif de Domicile',
    ],

    /*
    |--------------------------------------------------------------------------
    | Helper Texts
    |--------------------------------------------------------------------------
    */
    'helpers' => [
        'slug_auto_generated' => 'Généré automatiquement à partir du titre. Modifiez pour personnaliser.',
        'excerpt_auto_generated' => 'Résumé court (généré automatiquement si laissé vide)',
        'show_on_homepage' => 'Afficher cet article sur la page d\'accueil',
        'seo_title_max' => 'Max 60 caractères (par défaut: titre de l\'article)',
        'seo_description_max' => 'Max 160 caractères (par défaut: extrait)',
        'add_tags' => 'Ajouter des tags',
        'minimum_order_helper' => 'Montant de commande minimum requis pour utiliser ce coupon',
        'maximum_discount_helper' => 'Montant de réduction maximum (pour les réductions en pourcentage)',
        'usage_limit_helper' => 'Laisser vide pour une utilisation illimitée',
        'usage_count_helper' => 'Nombre de fois que ce coupon a été utilisé',
        'listing_ids_helper' => 'Laisser vide pour appliquer à toutes les annonces. Entrez les UUIDs pour restreindre.',
        'user_ids_helper' => 'Laisser vide pour appliquer à tous les utilisateurs. Entrez les UUIDs pour restreindre.',
        'traveler_info_warning' => 'Les informations de contact sont protégées. Consultez uniquement si nécessaire pour le support client.',
        'sensitive_info_warning' => 'Contient des informations personnelles sensibles - à traiter avec précaution',
        'rejection_reason_helper' => 'Si vous rejetez, fournissez une raison pour le vendeur.',
        'commission_tier_helper' => 'Taux de commission prélevé sur les réservations',
        'payout_account_helper' => 'Compte Stripe Connect ou référence de compte bancaire',
        'transaction_reference_helper' => 'Numéro de référence de la transaction',
        'document_request_helper' => 'Ceci sera envoyé au vendeur.',
        'rejection_shared_helper' => 'Ceci sera partagé avec le vendeur.',
        'enter_transaction_reference' => 'Entrez le numéro de référence de la transaction',
        'slug_url_friendly' => 'Identifiant URL (généré automatiquement à partir du nom)',
        'description_rich' => 'Description complète pour les pages de destination',
        'image_url_helper' => 'URL complète de l\'image principale (par ex. Unsplash ou téléchargée sur MinIO)',
        'listings_count_helper' => 'Calculé automatiquement selon les annonces publiées',
    ],

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */
    'actions' => [
        'cancel' => 'Annuler',
        'mark_no_show' => 'Marquer Absent',
        'mark_completed' => 'Marquer Terminé',
        'approve_publish' => 'Approuver & Publier',
        'reject' => 'Rejeter',
        'archive' => 'Archiver',
        'republish' => 'Republier',
        'approve_selected' => 'Approuver la Sélection',
        'archive_selected' => 'Archiver la Sélection',
        'verify_vendor' => 'Vérifier le Vendeur',
        'reject_kyc' => 'Rejeter KYC',
        'request_documents' => 'Demander des Documents',
        'update_commission_tier' => 'Modifier le Niveau de Commission',
        'verify_selected' => 'Vérifier la Sélection',
        'approve' => 'Approuver',
        'complete' => 'Terminer',
        'fail' => 'Échouer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Modal Headings & Descriptions
    |--------------------------------------------------------------------------
    */
    'modals' => [
        'delete_coupon' => 'Supprimer le Coupon',
        'delete_coupon_description' => 'Êtes-vous sûr de vouloir supprimer ce coupon ? Les réservations actives utilisant ce coupon ne seront pas affectées.',
        'approve_listing' => 'Approuver l\'Annonce',
        'approve_listing_description' => 'Ceci publiera l\'annonce et la rendra visible aux voyageurs.',
        'reject_listing' => 'Rejeter l\'Annonce',
        'reject_listing_description' => 'Le vendeur sera notifié du rejet.',
        'verify_vendor' => 'Vérifier le Vendeur',
        'verify_vendor_description' => 'Ceci marquera le vendeur comme vérifié et lui permettra de publier des annonces.',
        'reject_kyc' => 'Rejeter la Demande KYC',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'listing_approved' => 'Annonce Approuvée',
        'listing_approved_body' => 'L\'annonce a été publiée.',
        'listing_rejected' => 'Annonce Rejetée',
        'listing_rejected_body' => 'L\'annonce a été rejetée.',
        'listing_archived' => 'Annonce Archivée',
        'listing_republished' => 'Annonce Republiée',
        'cannot_publish' => 'Impossible de Publier l\'Annonce',
        'missing_fields' => 'Champs requis manquants : :fields',
        'vendor_verified' => 'Vendeur Vérifié',
        'vendor_verified_body' => 'Le vendeur a été vérifié avec succès.',
        'kyc_rejected' => 'KYC Rejeté',
        'document_request_sent' => 'Demande de Documents Envoyée',
        'document_request_sent_body' => 'Le vendeur a été notifié de soumettre des documents supplémentaires.',
        'commission_tier_updated' => 'Niveau de Commission Mis à Jour',
        'vendors_verified' => ':count vendeurs vérifiés',
        'listings_approved' => ':approved annonces approuvées',
        'listings_skipped' => ':approved annonces approuvées, :skipped ignorées (données incomplètes)',
        'listings_archived' => ':count annonces archivées',
        'payout_approved' => 'Paiement approuvé',
        'payout_approved_body' => 'Le paiement est maintenant en cours de traitement.',
        'payout_completed' => 'Paiement terminé',
        'payout_completed_body' => 'Le paiement a été marqué comme terminé.',
        'payout_failed' => 'Paiement échoué',
        'payout_failed_body' => 'Le paiement a été marqué comme échoué.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Errors
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'english_title_required' => 'Le titre anglais est requis',
        'english_summary_required' => 'Le résumé anglais est requis',
        'pricing_required' => 'Les informations de tarification sont requises',
        'location_required' => 'L\'emplacement est requis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tooltips / Badges
    |--------------------------------------------------------------------------
    */
    'tooltips' => [
        'pending_review' => 'En attente de révision',
        'pending_kyc_review' => 'En attente de vérification KYC',
        'not_verified' => 'Non vérifié',
        'upcoming_confirmed_bookings' => 'Réservations confirmées à venir',
    ],

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */
    'filters' => [
        'pending_review' => 'En Attente de Révision',
        'pending_kyc_review' => 'En Attente de Vérification KYC',
        'verified_only' => 'Vérifiés Uniquement',
        'has_listings' => 'Avec Annonces',
        'has_coordinates' => 'Avec Coordonnées',
    ],

    /*
    |--------------------------------------------------------------------------
    | Empty States
    |--------------------------------------------------------------------------
    */
    'empty_states' => [
        'no_locations' => 'Aucun emplacement pour l\'instant',
        'create_first_location' => 'Créez votre première destination pour organiser les annonces',
    ],
];
