<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Listing;
use Illuminate\Database\Seeder;

class ListingSafetyAccessibilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $listings = Listing::all();

        foreach ($listings as $listing) {
            // Safety information
            $safetyInfo = [
                'required_fitness_level' => [
                    'en' => 'Moderate fitness level required',
                    'fr' => 'Niveau de forme physique modéré requis',
                ],
                'minimum_age' => 6,
                'maximum_age' => null,
                'insurance_required' => false,
                'not_suitable_for' => [
                    [
                        'en' => 'Pregnant women',
                        'fr' => 'Femmes enceintes',
                    ],
                    [
                        'en' => 'People with mobility issues',
                        'fr' => 'Personnes à mobilité réduite',
                    ],
                    [
                        'en' => 'People with heart conditions',
                        'fr' => 'Personnes souffrant de problèmes cardiaques',
                    ],
                ],
                'safety_equipment_provided' => [
                    [
                        'en' => 'First aid kit',
                        'fr' => 'Trousse de premiers secours',
                    ],
                    [
                        'en' => 'Safety helmets (if required)',
                        'fr' => 'Casques de sécurité (si nécessaire)',
                    ],
                    [
                        'en' => 'Life jackets (for water activities)',
                        'fr' => 'Gilets de sauvetage (pour les activités aquatiques)',
                    ],
                ],
            ];

            // Accessibility information
            $accessibilityInfo = [
                'wheelchair_accessible' => false,
                'mobility_aid_accessible' => false,
                'accessible_parking' => true,
                'accessible_restrooms' => true,
                'service_animals_allowed' => true,
                'accessibility_notes' => [
                    'en' => 'While the starting point has accessible parking and restrooms, the tour route includes uneven terrain and stairs that may not be suitable for wheelchairs or mobility aids.',
                    'fr' => 'Bien que le point de départ dispose d\'un parking et de toilettes accessibles, l\'itinéraire de la visite comprend un terrain inégal et des escaliers qui peuvent ne pas convenir aux fauteuils roulants ou aux aides à la mobilité.',
                ],
            ];

            // Difficulty details for tours
            if ($listing->service_type->value === 'tour') {
                $difficultyDetails = [
                    'description' => [
                        'en' => 'Moderate trail with some steep sections',
                        'fr' => 'Sentier modéré avec quelques sections raides',
                    ],
                    'terrain_type' => [
                        'en' => 'Mixed terrain: paved paths, dirt trails, and rocky sections',
                        'fr' => 'Terrain mixte : chemins pavés, sentiers de terre et sections rocheuses',
                    ],
                    'elevation_gain_meters' => 250,
                    'technical_difficulty' => [
                        'en' => 'Beginner to intermediate',
                        'fr' => 'Débutant à intermédiaire',
                    ],
                    'physical_intensity' => [
                        'en' => 'Moderate - regular breaks provided',
                        'fr' => 'Modérée - pauses régulières prévues',
                    ],
                ];

                $listing->update([
                    'safety_info' => $safetyInfo,
                    'accessibility_info' => $accessibilityInfo,
                    'difficulty_details' => $difficultyDetails,
                ]);
            } else {
                // Events don't need difficulty details
                $listing->update([
                    'safety_info' => $safetyInfo,
                    'accessibility_info' => $accessibilityInfo,
                ]);
            }
        }

        $this->command->info('ListingSafetyAccessibility seeder completed! Updated ' . $listings->count() . ' listings.');
    }
}
