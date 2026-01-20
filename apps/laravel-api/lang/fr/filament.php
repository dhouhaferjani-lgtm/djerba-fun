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
        'preview' => 'Aperçu',
        'close' => 'Fermer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Preview
    |--------------------------------------------------------------------------
    */
    'preview' => [
        'no_title' => 'Pas encore de titre',
        'no_content' => 'Pas encore de contenu. Commencez à écrire pour voir l\'aperçu.',
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

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Resource
    |--------------------------------------------------------------------------
    */
    'payment_gateway' => [
        // Sections
        'gateway_information' => 'Informations de la Passerelle',
        'gateway_configuration' => 'Configuration de la Passerelle',
        'driver_configuration' => 'Configuration Spécifique au Driver',

        // Labels
        'name' => 'Nom',
        'slug' => 'Slug',
        'display_name' => 'Nom d\'Affichage',
        'description' => 'Description',
        'driver' => 'Driver',
        'priority' => 'Priorité',
        'enabled' => 'Activé',
        'is_default' => 'Par Défaut',
        'set_as_default' => 'Définir par Défaut',
        'test_mode' => 'Mode Test',

        // Driver options
        'driver_stripe' => 'Stripe',
        'driver_clicktopay' => 'Click to Pay (Visa)',
        'driver_offline' => 'Paiement Hors Ligne',
        'driver_bank_transfer' => 'Virement Bancaire',
        'driver_mock' => 'Mock (Test)',

        // Stripe configuration
        'publishable_key' => 'Clé Publique',
        'secret_key' => 'Clé Secrète',
        'webhook_secret' => 'Secret du Webhook',

        // Click to Pay configuration
        'merchant_id' => 'ID Marchand',
        'api_key' => 'Clé API',
        'shared_secret' => 'Secret Partagé',

        // Bank Transfer configuration
        'bank_name' => 'Nom de la Banque',
        'account_number' => 'Numéro de Compte',
        'routing_number' => 'Numéro de Routage',
        'iban' => 'IBAN',
        'swift_code' => 'Code SWIFT/BIC',
        'payment_instructions' => 'Instructions de Paiement',

        // Helper texts
        'name_helper' => 'Identifiant interne pour la passerelle',
        'slug_helper' => 'Identifiant URL',
        'display_name_helper' => 'Nom visible par les utilisateurs',
        'driver_helper' => 'Le driver de passerelle de paiement à utiliser',
        'priority_helper' => 'Les nombres plus bas apparaissent en premier',
        'enabled_helper' => 'Activer ou désactiver cette passerelle de paiement',
        'default_helper' => 'Définir comme passerelle de paiement par défaut',
        'test_mode_helper' => 'Activer le mode test/sandbox',
        'secret_key_helper' => 'Les clés secrètes ne peuvent pas être révélées pour des raisons de sécurité',
        'webhook_secret_helper' => 'Les secrets de webhook ne peuvent pas être révélés pour des raisons de sécurité',
        'api_key_helper' => 'Les clés API ne peuvent pas être révélées pour des raisons de sécurité',
        'shared_secret_helper' => 'Les secrets partagés ne peuvent pas être révélés pour des raisons de sécurité',
        'offline_instructions_helper' => 'Instructions pour les clients payant hors ligne',

        // Actions
        'set_default_action' => 'Définir par Défaut',
        'test_connection' => 'Tester',

        // Modals
        'set_default_heading' => 'Définir comme Passerelle par Défaut',
        'set_default_description' => 'Définir :name comme passerelle de paiement par défaut ? Cela supprimera toute autre passerelle par défaut.',
        'test_connection_heading' => 'Tester la Connexion de la Passerelle',
        'test_connection_description' => 'Ceci tentera de valider la configuration de la passerelle.',
        'delete_heading' => 'Supprimer la Passerelle de Paiement',
        'delete_description' => 'Êtes-vous sûr de vouloir supprimer cette passerelle de paiement ? Cela peut affecter la fonctionnalité de paiement.',

        // Notifications
        'connection_passed' => 'Test de connexion réussi',
        'connection_failed' => 'Test de connexion échoué',
        'cannot_disable_default' => 'Impossible de désactiver la passerelle par défaut. Définissez d\'abord une autre passerelle par défaut.',
        'cannot_delete_default' => 'Impossible de supprimer la passerelle par défaut. Définissez d\'abord une autre passerelle par défaut.',

        // Bulk actions
        'enable_selected' => 'Activer la Sélection',
        'disable_selected' => 'Désactiver la Sélection',

        // Filters
        'filter_driver' => 'Driver',
        'filter_enabled' => 'Activé',
        'filter_enabled_all' => 'Toutes les passerelles',
        'filter_enabled_only' => 'Activées uniquement',
        'filter_disabled_only' => 'Désactivées uniquement',
        'filter_test_mode' => 'Mode Test',
        'filter_all_modes' => 'Tous les modes',
        'filter_test_only' => 'Mode test',
        'filter_live_only' => 'Mode production',

        // Empty state
        'empty_heading' => 'Aucune passerelle de paiement configurée',
        'empty_description' => 'Ajoutez votre première passerelle de paiement pour commencer à accepter les paiements.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Deletion Request Resource
    |--------------------------------------------------------------------------
    */
    'data_deletion' => [
        // Sections
        'request_details' => 'Détails de la Demande',
        'processing_information' => 'Informations de Traitement',

        // Labels
        'email' => 'Email',
        'status' => 'Statut',
        'user_reason' => 'Raison de l\'Utilisateur',
        'admin_notes' => 'Notes de l\'Admin',
        'requested_at' => 'Demandé le',
        'processed_at' => 'Traité le',
        'processed_by' => 'Traité par',
        'data_deleted' => 'Résumé des Données Supprimées',
        'user' => 'Utilisateur',
        'reason' => 'Raison',

        // Status options
        'status_pending' => 'En Attente',
        'status_processing' => 'En Cours',
        'status_completed' => 'Terminé',
        'status_rejected' => 'Rejeté',

        // Actions
        'process' => 'Traiter',
        'complete' => 'Terminer',
        'reject' => 'Rejeter',

        // Modals
        'complete_heading' => 'Terminer la Demande de Suppression',
        'complete_description' => 'Ceci marquera la demande comme terminée. Assurez-vous d\'avoir supprimé toutes les données de l\'utilisateur.',
        'reject_heading' => 'Rejeter la Demande de Suppression',
        'notes_placeholder' => 'Décrivez quelles données ont été supprimées...',
        'rejection_reason' => 'Raison du Rejet',

        // Placeholders
        'guest' => 'Invité',
        'not_processed' => 'Non traité',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Trip Request Resource
    |--------------------------------------------------------------------------
    */
    'custom_trip' => [
        // Sections
        'request_information' => 'Informations de la Demande',
        'contact_information' => 'Coordonnées',
        'trip_details' => 'Détails du Voyage',
        'travelers' => 'Voyageurs',
        'interests' => 'Centres d\'Intérêt',
        'budget_style' => 'Budget & Style',
        'special_requests' => 'Demandes Spéciales',
        'special_occasions' => 'Occasions Spéciales',
        'metadata' => 'Métadonnées',

        // Labels
        'reference' => 'Référence',
        'status' => 'Statut',
        'submitted' => 'Soumis',
        'language' => 'Langue',
        'name' => 'Nom',
        'email' => 'Email',
        'phone' => 'Téléphone',
        'whatsapp' => 'WhatsApp',
        'country' => 'Pays',
        'preferred_contact' => 'Contact Préféré',
        'travel_dates' => 'Dates de Voyage',
        'duration' => 'Durée',
        'flexible_dates' => 'Dates Flexibles',
        'adults' => 'Adultes',
        'children' => 'Enfants',
        'total_travelers' => 'Total Voyageurs',
        'selected_interests' => 'Centres d\'Intérêt Sélectionnés',
        'budget_per_person' => 'Budget par Personne',
        'estimated_total' => 'Budget Total Estimé',
        'accommodation_style' => 'Style d\'Hébergement',
        'travel_pace' => 'Rythme de Voyage',
        'notes' => 'Notes',
        'occasions' => 'Occasions',
        'ip_address' => 'Adresse IP',
        'user_agent' => 'Agent Utilisateur',
        'newsletter_consent' => 'Consentement Newsletter',
        'assigned_agent' => 'Agent Assigné',
        'traveler_name' => 'Nom du Voyageur',
        'budget' => 'Budget',
        'created_at' => 'Créé le',

        // Table labels
        'budget_display' => 'Budget',

        // Language options
        'lang_en' => 'Anglais',
        'lang_fr' => 'Français',

        // Yes/No
        'yes' => 'Oui',
        'no' => 'Non',

        // Interest options
        'interest_history_culture' => 'Histoire & Culture',
        'interest_desert_adventures' => 'Aventures dans le Désert',
        'interest_beach_relaxation' => 'Plage & Détente',
        'interest_food_gastronomy' => 'Gastronomie',
        'interest_hiking_nature' => 'Randonnée & Nature',
        'interest_photography' => 'Photographie',
        'interest_local_festivals' => 'Festivals Locaux',
        'interest_star_wars_sites' => 'Sites Star Wars',

        // Accommodation style
        'style_budget' => 'Économique',
        'style_mid_range' => 'Milieu de Gamme',
        'style_luxury' => 'Luxe',
        'style_not_specified' => 'Non spécifié',

        // Travel pace
        'pace_relaxed' => 'Détendu',
        'pace_moderate' => 'Modéré',
        'pace_active' => 'Actif',
        'pace_not_specified' => 'Non spécifié',

        // Duration format
        'days' => ':count jours',

        // Actions
        'mark_contacted' => 'Marquer Contacté',

        // Placeholders
        'no_special_requests' => 'Aucune demande spéciale',
        'not_provided' => 'Non fourni',
        'not_assigned' => 'Non assigné',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Widgets
    |--------------------------------------------------------------------------
    */
    'widgets' => [
        'total_users' => 'Total Utilisateurs',
        'total_listings' => 'Total Annonces',
        'total_bookings' => 'Total Réservations',
        'total_revenue' => 'Revenu Total',
        'active_platform_users' => 'Utilisateurs actifs',
        'published' => ':count publiées',
        'confirmed' => ':count confirmées',
        'this_month' => ':amount ce mois',
        'suspicious_activities' => 'Activités Suspectes',
        'suspicious_activities_desc' => 'Réservations et activités récentes signalées pour examen',
        'customer' => 'Client',
        'listing' => 'Annonce',
        'enabled_payment_gateways' => 'Passerelles de paiement activées',
    ],

    /*
    |--------------------------------------------------------------------------
    | Partner Resource
    |--------------------------------------------------------------------------
    */
    'partner' => [
        // Sections
        'partner_information' => 'Informations du Partenaire',
        'contact_information' => 'Coordonnées',
        'permissions' => 'Permissions',
        'webhooks' => 'Webhooks',
        'security' => 'Sécurité',
        'metadata' => 'Métadonnées',
        'api_credentials' => 'Identifiants API',

        // Labels
        'partner_name' => 'Nom du Partenaire',
        'company_name' => 'Nom de l\'Entreprise',
        'company_type' => 'Type d\'Entreprise',
        'partner_tier' => 'Niveau Partenaire',
        'kyc_status' => 'Statut KYC',
        'active' => 'Actif',
        'sandbox_mode' => 'Mode Sandbox',
        'rate_limit' => 'Limite de Requêtes (par minute)',
        'website_url' => 'URL du Site Web',
        'contact_email' => 'Email de Contact',
        'contact_phone' => 'Téléphone de Contact',
        'description' => 'Description',
        'webhook_url' => 'URL du Webhook',
        'webhook_secret' => 'Secret du Webhook',
        'ip_whitelist' => 'Liste Blanche IP',
        'api_key_expiration' => 'Expiration de la Clé API',
        'additional_metadata' => 'Métadonnées Supplémentaires',
        'key' => 'Clé',
        'value' => 'Valeur',
        'add_metadata' => 'Ajouter des métadonnées',
        'api_key_current' => 'Clé API (Actuelle)',

        // Partner tier options
        'tier_standard' => 'Standard',
        'tier_premium' => 'Premium',
        'tier_enterprise' => 'Entreprise',

        // KYC status options
        'kyc_pending' => 'En Attente',
        'kyc_under_review' => 'En Cours d\'Examen',
        'kyc_approved' => 'Approuvé',
        'kyc_rejected' => 'Rejeté',

        // Table columns
        'name' => 'Nom',
        'company' => 'Entreprise',
        'tier' => 'Niveau',
        'sandbox' => 'Sandbox',
        'last_used' => 'Dernière Utilisation',
        'created' => 'Créé',
        'all_permissions' => 'Toutes',
        'never' => 'Jamais',
        'req_min' => 'req/min',

        // Filters
        'active_status' => 'Statut d\'Activité',
        'all_partners' => 'Tous les partenaires',
        'active_only' => 'Actifs uniquement',
        'inactive_only' => 'Inactifs uniquement',

        // Actions
        'view_logs' => 'Voir les Logs',

        // Helpers
        'sandbox_mode_helper' => 'Les partenaires en mode sandbox ne peuvent accéder qu\'aux données de test',
        'permissions_placeholder' => 'Entrez les permissions (ex: listings:read, bookings:create)',
        'permissions_helper' => 'Utilisez * pour toutes les permissions, ou le format ressource:action',
        'webhook_url_helper' => 'Les événements seront envoyés à cette URL',
        'webhook_secret_helper' => 'Utilisé pour vérifier les signatures des webhooks',
        'ip_whitelist_placeholder' => 'Ajouter des adresses IP',
        'ip_whitelist_helper' => 'Laisser vide pour autoriser toutes les IP',
        'api_key_expiration_helper' => 'Laisser vide pour aucune expiration',
        'api_key_info' => 'Les identifiants API ont été générés automatiquement lors de la création de ce partenaire. Les identifiants ont été affichés une seule fois dans une notification et ne peuvent pas être récupérés. Pour générer de nouveaux identifiants, utilisez la commande CLI : php artisan partner:create',
        'api_key_helper' => 'Ceci est la clé API actuelle. Le secret ne peut pas être affiché pour des raisons de sécurité.',
        'company_type_placeholder' => 'ex: Hôtel, Tour Opérateur, Agence de Voyage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Availability Rule Resource
    |--------------------------------------------------------------------------
    */
    'availability_rule' => [
        // Sections
        'basic_information' => 'Informations de Base',
        'schedule' => 'Horaire',
        'capacity_pricing' => 'Capacité & Tarification',

        // Labels
        'listing' => 'Annonce',
        'rule_type' => 'Type de Règle',
        'active' => 'Actif',
        'days_of_week' => 'Jours de la Semaine',
        'start_time' => 'Heure de Début',
        'end_time' => 'Heure de Fin',
        'start_date' => 'Date de Début',
        'end_date' => 'Date de Fin',
        'capacity' => 'Capacité',
        'price_override' => 'Prix Personnalisé',

        // Days
        'sunday' => 'Dimanche',
        'monday' => 'Lundi',
        'tuesday' => 'Mardi',
        'wednesday' => 'Mercredi',
        'thursday' => 'Jeudi',
        'friday' => 'Vendredi',
        'saturday' => 'Samedi',

        // Table columns
        'type' => 'Type',
        'time' => 'Horaire',
        'date_range' => 'Période',
        'from' => 'À partir de',
        'ongoing' => 'En cours',
        'created' => 'Créé',

        // Filters
        'all_rules' => 'Toutes les règles',
        'active_only' => 'Actives uniquement',
        'inactive_only' => 'Inactives uniquement',

        // Modals
        'delete_heading' => 'Supprimer la Règle de Disponibilité',
        'delete_description' => 'Êtes-vous sûr de vouloir supprimer cette règle de disponibilité ? Cette action est irréversible.',

        // Helpers
        'price_override_helper' => 'Laisser vide pour utiliser le prix de base de l\'annonce',
    ],

    /*
    |--------------------------------------------------------------------------
    | Travel Tip Resource
    |--------------------------------------------------------------------------
    */
    'travel_tip' => [
        // Sections
        'tip_content' => 'Contenu du Conseil',
        'settings' => 'Paramètres',

        // Tabs
        'translations' => 'Traductions',
        'english' => 'Anglais',
        'french' => 'Français',

        // Labels
        'content_english' => 'Contenu (Anglais)',
        'content_french' => 'Contenu (Français)',
        'active' => 'Actif',
        'display_order' => 'Ordre d\'Affichage',

        // Table columns
        'id' => 'ID',
        'content_en' => 'Contenu (EN)',
        'order' => 'Ordre',
        'updated' => 'Mis à jour',

        // Helpers
        'active_helper' => 'Seuls les conseils actifs seront affichés sur le site',
        'display_order_helper' => 'Les nombres plus bas apparaissent en premier',
    ],

    /*
    |--------------------------------------------------------------------------
    | Blog Category Resource
    |--------------------------------------------------------------------------
    */
    'blog_category' => [
        // Labels
        'name' => 'Nom',
        'slug' => 'Slug',
        'description' => 'Description',
        'color' => 'Couleur',
        'sort_order' => 'Ordre de Tri',
        'created_at' => 'Créé le',
        'updated_at' => 'Mis à jour le',
    ],

    /*
    |--------------------------------------------------------------------------
    | Page Resource
    |--------------------------------------------------------------------------
    */
    'page' => [
        // Labels
        'page' => 'Page',
        'pages' => 'Pages',

        // Tabs
        'general' => 'Général',
        'content' => 'Contenu',
        'overview' => 'Aperçu',
        'seo' => 'SEO',
        'advanced' => 'Avancé',

        // Table columns
        'created' => 'Créé',
        'updated' => 'Mis à jour',
        'code' => 'Code',

        // Status
        'draft' => 'Brouillon',
        'published' => 'Publié',

        // Search results
        'intro' => 'Introduction',
        'status' => 'Statut',

        // Helpers
        'required_for_publishing' => 'Requis pour la publication',
    ],
];
