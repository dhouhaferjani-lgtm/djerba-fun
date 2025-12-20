<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\ListingFaq;
use Illuminate\Database\Seeder;

class ListingFaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $listings = Listing::all();

        foreach ($listings as $listing) {
            // Common FAQs for all listings
            $commonFaqs = [
                [
                    'question' => [
                        'en' => 'What should I bring?',
                        'fr' => 'Que dois-je apporter ?',
                    ],
                    'answer' => [
                        'en' => 'Please bring comfortable walking shoes, water, sunscreen, and a hat. We recommend bringing a camera to capture the beautiful scenery.',
                        'fr' => 'Veuillez apporter des chaussures de marche confortables, de l\'eau, de la crème solaire et un chapeau. Nous recommandons d\'apporter un appareil photo pour capturer les beaux paysages.',
                    ],
                    'order' => 0,
                ],
                [
                    'question' => [
                        'en' => 'Is this activity suitable for children?',
                        'fr' => 'Cette activité convient-elle aux enfants ?',
                    ],
                    'answer' => [
                        'en' => 'Yes, this activity is family-friendly and suitable for children aged 6 and above. Children must be accompanied by an adult at all times.',
                        'fr' => 'Oui, cette activité est familiale et convient aux enfants de 6 ans et plus. Les enfants doivent être accompagnés d\'un adulte en tout temps.',
                    ],
                    'order' => 1,
                ],
                [
                    'question' => [
                        'en' => 'What happens in case of bad weather?',
                        'fr' => 'Que se passe-t-il en cas de mauvais temps ?',
                    ],
                    'answer' => [
                        'en' => 'In case of severe weather conditions, we will contact you 24 hours in advance to reschedule or offer a full refund. Light rain does not typically affect the tour.',
                        'fr' => 'En cas de conditions météorologiques sévères, nous vous contacterons 24 heures à l\'avance pour reprogrammer ou offrir un remboursement complet. Une pluie légère n\'affecte généralement pas la visite.',
                    ],
                    'order' => 2,
                ],
                [
                    'question' => [
                        'en' => 'Are meals included?',
                        'fr' => 'Les repas sont-ils inclus ?',
                    ],
                    'answer' => [
                        'en' => 'Lunch and refreshments are included in the tour price. Please inform us of any dietary restrictions or allergies when booking.',
                        'fr' => 'Le déjeuner et les rafraîchissements sont inclus dans le prix de la visite. Veuillez nous informer de toute restriction alimentaire ou allergie lors de la réservation.',
                    ],
                    'order' => 3,
                ],
            ];

            // Tour-specific FAQs
            if ($listing->service_type->value === 'tour') {
                $commonFaqs[] = [
                    'question' => [
                        'en' => 'How difficult is this tour?',
                        'fr' => 'Quelle est la difficulté de cette visite ?',
                    ],
                    'answer' => [
                        'en' => 'This tour has a moderate difficulty level. You should have a basic fitness level and be comfortable walking for extended periods.',
                        'fr' => 'Cette visite a un niveau de difficulté modéré. Vous devez avoir un niveau de forme physique de base et être à l\'aise pour marcher pendant de longues périodes.',
                    ],
                    'order' => 4,
                ];
            }

            // Event-specific FAQs
            if ($listing->service_type->value === 'event') {
                $commonFaqs[] = [
                    'question' => [
                        'en' => 'Can I get a refund if I can\'t attend?',
                        'fr' => 'Puis-je obtenir un remboursement si je ne peux pas assister ?',
                    ],
                    'answer' => [
                        'en' => 'Refunds are available up to 48 hours before the event. Please check our cancellation policy for more details.',
                        'fr' => 'Les remboursements sont disponibles jusqu\'à 48 heures avant l\'événement. Veuillez consulter notre politique d\'annulation pour plus de détails.',
                    ],
                    'order' => 4,
                ];
            }

            foreach ($commonFaqs as $faq) {
                ListingFaq::create([
                    'listing_id' => $listing->id,
                    'question' => $faq['question'],
                    'answer' => $faq['answer'],
                    'order' => $faq['order'],
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('ListingFaq seeder completed! Created FAQs for ' . $listings->count() . ' listings.');
    }
}
