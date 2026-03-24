<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PlatformSettings;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestimonialSeeder extends Seeder
{
    /**
     * Seed the testimonials table.
     *
     * Migration strategy:
     * 1. If testimonials table already has data, skip (idempotent)
     * 2. If platform_settings has testimonials, migrate them to the new table
     * 3. If no existing data, seed default testimonials
     */
    public function run(): void
    {
        // Skip if testimonials already exist (idempotent)
        if (Testimonial::exists()) {
            $this->command->info('Testimonials already exist, skipping...');

            return;
        }

        // Try to migrate from platform_settings first
        $migrated = $this->migrateFromPlatformSettings();

        if ($migrated) {
            $this->command->info('Migrated testimonials from platform settings successfully!');

            return;
        }

        // No existing data, seed defaults
        $this->seedDefaultTestimonials();
        $this->command->info('Seeded default testimonials successfully!');
    }

    /**
     * Migrate testimonials from platform_settings JSON to testimonials table.
     */
    protected function migrateFromPlatformSettings(): bool
    {
        $platformSettings = PlatformSettings::first();

        if (! $platformSettings) {
            return false;
        }

        $existingTestimonials = $platformSettings->testimonials ?? [];

        if (empty($existingTestimonials)) {
            return false;
        }

        foreach ($existingTestimonials as $index => $testimonial) {
            Testimonial::create([
                'uuid' => (string) Str::uuid(),
                'name' => $testimonial['name'] ?? 'Anonymous',
                'photo' => $testimonial['photo'] ?? null,
                'text' => [
                    'fr' => $testimonial['text_fr'] ?? '',
                    'en' => $testimonial['text_en'] ?? '',
                ],
                'rating' => 5, // Default to 5 stars for migrated testimonials
                'location' => null, // Not present in old format
                'activity' => null, // Not present in old format
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }

        $this->command->info('Migrated '.count($existingTestimonials).' testimonials from platform settings.');

        return true;
    }

    /**
     * Seed default testimonials for new installations.
     */
    protected function seedDefaultTestimonials(): void
    {
        $testimonials = [
            [
                'name' => 'Nathalie D.',
                'photo' => null, // Will use default avatar
                'text' => [
                    'fr' => 'Une expérience exceptionnelle ! L\'équipe était très professionnelle et le tour du désert était magique. Je recommande vivement pour découvrir Djerba autrement.',
                    'en' => 'An exceptional experience! The team was very professional and the desert tour was magical. I highly recommend it for discovering Djerba differently.',
                ],
                'rating' => 5,
                'location' => 'Paris, France',
                'activity' => 'Tour du Désert',
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'name' => 'Pierre M.',
                'photo' => null,
                'text' => [
                    'fr' => 'Le jet ski était incroyable ! Les instructeurs sont patients et l\'équipement est de qualité. Les plages de Djerba sont magnifiques vues depuis la mer.',
                    'en' => 'The jet ski was incredible! The instructors are patient and the equipment is top quality. The beaches of Djerba are beautiful seen from the sea.',
                ],
                'rating' => 5,
                'location' => 'Lyon, France',
                'activity' => 'Jet Ski',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Marie L.',
                'photo' => null,
                'text' => [
                    'fr' => 'Nous avons passé un séjour merveilleux. L\'hébergement était parfait et les excursions bien organisées. Une vraie découverte de la culture locale.',
                    'en' => 'We had a wonderful stay. The accommodation was perfect and the excursions well organized. A true discovery of the local culture.',
                ],
                'rating' => 5,
                'location' => 'Bruxelles, Belgique',
                'activity' => 'Séjour Culturel',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Jean-Philippe R.',
                'photo' => null,
                'text' => [
                    'fr' => 'Superbe sortie en bateau pour observer les flamants roses. Le guide connaissait parfaitement la région et partageait sa passion avec enthousiasme.',
                    'en' => 'Great boat trip to observe the flamingos. The guide knew the region perfectly and shared his passion with enthusiasm.',
                ],
                'rating' => 5,
                'location' => 'Marseille, France',
                'activity' => 'Excursion Flamants Roses',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Sophie B.',
                'photo' => null,
                'text' => [
                    'fr' => 'Le parasailing était une expérience à couper le souffle ! Vue panoramique sur toute l\'île. L\'équipe met vraiment en confiance pour les débutants.',
                    'en' => 'The parasailing was a breathtaking experience! Panoramic view of the entire island. The team really puts beginners at ease.',
                ],
                'rating' => 5,
                'location' => 'Genève, Suisse',
                'activity' => 'Parasailing',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::create([
                'uuid' => (string) Str::uuid(),
                'name' => $testimonial['name'],
                'photo' => $testimonial['photo'],
                'text' => $testimonial['text'],
                'rating' => $testimonial['rating'],
                'location' => $testimonial['location'],
                'activity' => $testimonial['activity'],
                'is_active' => $testimonial['is_active'],
                'sort_order' => $testimonial['sort_order'],
            ]);
        }
    }
}
