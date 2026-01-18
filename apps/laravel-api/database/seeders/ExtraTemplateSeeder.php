<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ExtraCategory;
use App\Enums\ExtraPricingType;
use App\Models\ExtraTemplate;
use Illuminate\Database\Seeder;

class ExtraTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates default extra templates that vendors can clone.
     * These are common extras found across tourism platforms.
     */
    public function run(): void
    {
        $templates = [
            // ===== MEALS =====
            [
                'name' => [
                    'en' => 'Breakfast',
                    'fr' => 'Petit-déjeuner',
                ],
                'description' => [
                    'en' => 'Start your day with a delicious breakfast included with your tour.',
                    'fr' => 'Commencez votre journée avec un délicieux petit-déjeuner inclus avec votre excursion.',
                ],
                'short_description' => [
                    'en' => 'Morning breakfast included',
                    'fr' => 'Petit-déjeuner inclus',
                ],
                'icon' => 'heroicon-o-cake',
                'pricing_type' => ExtraPricingType::PER_PERSON_TYPE,
                'suggested_price_tnd' => 25.00,
                'suggested_price_eur' => 7.50,
                'person_type_prices' => [
                    'adult' => ['tnd' => 25.00, 'eur' => 7.50],
                    'child' => ['tnd' => 15.00, 'eur' => 4.50],
                    'infant' => ['tnd' => 0.00, 'eur' => 0.00],
                ],
                'category' => ExtraCategory::MEAL,
                'min_quantity' => 0,
                'max_quantity' => null,
                'track_inventory' => false,
                'display_order' => 10,
            ],
            [
                'name' => [
                    'en' => 'Lunch Package',
                    'fr' => 'Formule Déjeuner',
                ],
                'description' => [
                    'en' => 'Enjoy a hearty lunch during your adventure.',
                    'fr' => 'Profitez d\'un déjeuner copieux pendant votre aventure.',
                ],
                'short_description' => [
                    'en' => 'Lunch included',
                    'fr' => 'Déjeuner inclus',
                ],
                'icon' => 'heroicon-o-cake',
                'pricing_type' => ExtraPricingType::PER_PERSON_TYPE,
                'suggested_price_tnd' => 35.00,
                'suggested_price_eur' => 10.50,
                'person_type_prices' => [
                    'adult' => ['tnd' => 35.00, 'eur' => 10.50],
                    'child' => ['tnd' => 20.00, 'eur' => 6.00],
                    'infant' => ['tnd' => 0.00, 'eur' => 0.00],
                ],
                'category' => ExtraCategory::MEAL,
                'min_quantity' => 0,
                'max_quantity' => null,
                'track_inventory' => false,
                'display_order' => 11,
            ],
            [
                'name' => [
                    'en' => 'Dinner',
                    'fr' => 'Dîner',
                ],
                'description' => [
                    'en' => 'End your day with a traditional dinner experience.',
                    'fr' => 'Terminez votre journée par un dîner traditionnel.',
                ],
                'short_description' => [
                    'en' => 'Evening dinner included',
                    'fr' => 'Dîner inclus',
                ],
                'icon' => 'heroicon-o-cake',
                'pricing_type' => ExtraPricingType::PER_PERSON_TYPE,
                'suggested_price_tnd' => 45.00,
                'suggested_price_eur' => 13.50,
                'person_type_prices' => [
                    'adult' => ['tnd' => 45.00, 'eur' => 13.50],
                    'child' => ['tnd' => 25.00, 'eur' => 7.50],
                    'infant' => ['tnd' => 0.00, 'eur' => 0.00],
                ],
                'category' => ExtraCategory::MEAL,
                'min_quantity' => 0,
                'max_quantity' => null,
                'track_inventory' => false,
                'display_order' => 12,
            ],

            // ===== TRANSPORT =====
            [
                'name' => [
                    'en' => 'Airport Transfer',
                    'fr' => 'Transfert Aéroport',
                ],
                'description' => [
                    'en' => 'Private transfer from/to the airport in a comfortable vehicle.',
                    'fr' => 'Transfert privé depuis/vers l\'aéroport dans un véhicule confortable.',
                ],
                'short_description' => [
                    'en' => 'Airport pickup/dropoff',
                    'fr' => 'Transfert aéroport',
                ],
                'icon' => 'heroicon-o-truck',
                'pricing_type' => ExtraPricingType::PER_BOOKING,
                'suggested_price_tnd' => 80.00,
                'suggested_price_eur' => 24.00,
                'person_type_prices' => null,
                'category' => ExtraCategory::TRANSPORT,
                'min_quantity' => 0,
                'max_quantity' => 1,
                'capacity_per_unit' => 4,
                'track_inventory' => true,
                'display_order' => 20,
            ],
            [
                'name' => [
                    'en' => 'Hotel Pickup',
                    'fr' => 'Transfert Hôtel',
                ],
                'description' => [
                    'en' => 'Convenient pickup from your hotel to the meeting point.',
                    'fr' => 'Transfert pratique depuis votre hôtel jusqu\'au point de rencontre.',
                ],
                'short_description' => [
                    'en' => 'Hotel pickup service',
                    'fr' => 'Service de transfert hôtel',
                ],
                'icon' => 'heroicon-o-truck',
                'pricing_type' => ExtraPricingType::PER_BOOKING,
                'suggested_price_tnd' => 40.00,
                'suggested_price_eur' => 12.00,
                'person_type_prices' => null,
                'category' => ExtraCategory::TRANSPORT,
                'min_quantity' => 0,
                'max_quantity' => 1,
                'capacity_per_unit' => 4,
                'track_inventory' => false,
                'display_order' => 21,
            ],

            // ===== EQUIPMENT =====
            [
                'name' => [
                    'en' => 'Bicycle Rental',
                    'fr' => 'Location de Vélo',
                ],
                'description' => [
                    'en' => 'Rent a quality bicycle for your adventure. Includes helmet and lock.',
                    'fr' => 'Louez un vélo de qualité pour votre aventure. Casque et antivol inclus.',
                ],
                'short_description' => [
                    'en' => 'Bike with helmet & lock',
                    'fr' => 'Vélo avec casque et antivol',
                ],
                'icon' => 'heroicon-o-wrench-screwdriver',
                'pricing_type' => ExtraPricingType::PER_UNIT,
                'suggested_price_tnd' => 30.00,
                'suggested_price_eur' => 9.00,
                'person_type_prices' => null,
                'category' => ExtraCategory::EQUIPMENT,
                'min_quantity' => 0,
                'max_quantity' => 10,
                'track_inventory' => true,
                'display_order' => 30,
            ],
            [
                'name' => [
                    'en' => 'Snorkeling Gear',
                    'fr' => 'Équipement de Snorkeling',
                ],
                'description' => [
                    'en' => 'Complete snorkeling set: mask, snorkel, and fins.',
                    'fr' => 'Kit de snorkeling complet : masque, tuba et palmes.',
                ],
                'short_description' => [
                    'en' => 'Mask, snorkel & fins',
                    'fr' => 'Masque, tuba et palmes',
                ],
                'icon' => 'heroicon-o-wrench-screwdriver',
                'pricing_type' => ExtraPricingType::PER_UNIT,
                'suggested_price_tnd' => 20.00,
                'suggested_price_eur' => 6.00,
                'person_type_prices' => null,
                'category' => ExtraCategory::EQUIPMENT,
                'min_quantity' => 0,
                'max_quantity' => 20,
                'track_inventory' => true,
                'display_order' => 31,
            ],
            [
                'name' => [
                    'en' => 'Camera/Photo Package',
                    'fr' => 'Forfait Photo/Caméra',
                ],
                'description' => [
                    'en' => 'Professional photos of your experience, delivered digitally.',
                    'fr' => 'Photos professionnelles de votre expérience, livrées en format numérique.',
                ],
                'short_description' => [
                    'en' => 'Professional photos',
                    'fr' => 'Photos professionnelles',
                ],
                'icon' => 'heroicon-o-camera',
                'pricing_type' => ExtraPricingType::PER_BOOKING,
                'suggested_price_tnd' => 50.00,
                'suggested_price_eur' => 15.00,
                'person_type_prices' => null,
                'category' => ExtraCategory::EQUIPMENT,
                'min_quantity' => 0,
                'max_quantity' => 1,
                'track_inventory' => false,
                'display_order' => 32,
            ],

            // ===== INSURANCE =====
            [
                'name' => [
                    'en' => 'Travel Insurance',
                    'fr' => 'Assurance Voyage',
                ],
                'description' => [
                    'en' => 'Comprehensive travel insurance covering accidents and medical emergencies.',
                    'fr' => 'Assurance voyage complète couvrant les accidents et urgences médicales.',
                ],
                'short_description' => [
                    'en' => 'Accident & medical coverage',
                    'fr' => 'Couverture accidents et médicale',
                ],
                'icon' => 'heroicon-o-shield-check',
                'pricing_type' => ExtraPricingType::PER_PERSON,
                'suggested_price_tnd' => 15.00,
                'suggested_price_eur' => 4.50,
                'person_type_prices' => null,
                'category' => ExtraCategory::INSURANCE,
                'min_quantity' => 0,
                'max_quantity' => null,
                'track_inventory' => false,
                'display_order' => 40,
            ],
            [
                'name' => [
                    'en' => 'Cancellation Insurance',
                    'fr' => 'Assurance Annulation',
                ],
                'description' => [
                    'en' => 'Get a full refund if you need to cancel for any reason.',
                    'fr' => 'Obtenez un remboursement complet en cas d\'annulation pour toute raison.',
                ],
                'short_description' => [
                    'en' => 'Free cancellation',
                    'fr' => 'Annulation gratuite',
                ],
                'icon' => 'heroicon-o-shield-check',
                'pricing_type' => ExtraPricingType::PER_PERSON,
                'suggested_price_tnd' => 10.00,
                'suggested_price_eur' => 3.00,
                'person_type_prices' => null,
                'category' => ExtraCategory::INSURANCE,
                'min_quantity' => 0,
                'max_quantity' => null,
                'track_inventory' => false,
                'display_order' => 41,
            ],

            // ===== UPGRADES =====
            [
                'name' => [
                    'en' => 'Professional Guide',
                    'fr' => 'Guide Professionnel',
                ],
                'description' => [
                    'en' => 'Private professional guide dedicated to your group.',
                    'fr' => 'Guide professionnel privé dédié à votre groupe.',
                ],
                'short_description' => [
                    'en' => 'Private guide for your group',
                    'fr' => 'Guide privé pour votre groupe',
                ],
                'icon' => 'heroicon-o-user-circle',
                'pricing_type' => ExtraPricingType::PER_BOOKING,
                'suggested_price_tnd' => 100.00,
                'suggested_price_eur' => 30.00,
                'person_type_prices' => null,
                'category' => ExtraCategory::UPGRADE,
                'min_quantity' => 0,
                'max_quantity' => 1,
                'track_inventory' => false,
                'display_order' => 50,
            ],
            [
                'name' => [
                    'en' => 'VIP Upgrade',
                    'fr' => 'Upgrade VIP',
                ],
                'description' => [
                    'en' => 'Premium experience with priority access and exclusive perks.',
                    'fr' => 'Expérience premium avec accès prioritaire et avantages exclusifs.',
                ],
                'short_description' => [
                    'en' => 'Priority access & perks',
                    'fr' => 'Accès prioritaire et avantages',
                ],
                'icon' => 'heroicon-o-star',
                'pricing_type' => ExtraPricingType::PER_PERSON,
                'suggested_price_tnd' => 50.00,
                'suggested_price_eur' => 15.00,
                'person_type_prices' => null,
                'category' => ExtraCategory::UPGRADE,
                'min_quantity' => 0,
                'max_quantity' => null,
                'track_inventory' => false,
                'display_order' => 51,
            ],
        ];

        foreach ($templates as $template) {
            // Convert enums to their string values for storage
            $data = $template;
            $data['pricing_type'] = $template['pricing_type']->value;
            $data['category'] = $template['category']->value;

            ExtraTemplate::updateOrCreate(
                ['name->en' => $template['name']['en']],
                $data
            );
        }
    }
}
