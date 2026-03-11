<?php

namespace Database\Seeders;

use App\Models\Listing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ToursItinerarySeeder extends Seeder
{
    public function run(): void
    {
        $tours = $this->getTourItineraries();

        foreach ($tours as $slug => $itinerary) {
            $listing = Listing::where('slug', $slug)->first();

            if (! $listing) {
                $this->command->warn("Tour '{$slug}' not found, skipping.");
                continue;
            }

            $listing->update([
                'itinerary' => $itinerary,
                'map_display_type' => 'markers',
            ]);

            $this->command->info("Updated itinerary for: {$slug} (" . count($itinerary) . ' stops)');
        }

        $this->command->info('Tour itineraries updated successfully!');
    }

    private function getTourItineraries(): array
    {
        return [
            // 1. Calèche - Countryside & Lagoon ride
            'horse-drawn-carriage-ride' => [
                [
                    'order' => 0,
                    'title' => ['fr' => 'Départ - Zone touristique Mezraya', 'en' => 'Start - Mezraya Tourist Zone'],
                    'description' => ['fr' => 'Point de rencontre et départ en calèche', 'en' => 'Meeting point and carriage departure'],
                    'duration' => 10,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8076, 'lng' => 10.8451],
                ],
                [
                    'order' => 1,
                    'title' => ['fr' => 'Traversée des oliveraies', 'en' => 'Olive Grove Trail'],
                    'description' => ['fr' => 'Passage à travers les anciennes oliveraies et jardins de palmiers', 'en' => 'Passage through ancient olive groves and palm gardens'],
                    'duration' => 25,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8150, 'lng' => 10.8380],
                ],
                [
                    'order' => 2,
                    'title' => ['fr' => 'Village traditionnel', 'en' => 'Traditional Village'],
                    'description' => ['fr' => 'Découverte d\'un Menzel djerbien typique avec architecture traditionnelle', 'en' => 'Discovery of a typical Djerbian Menzel with traditional architecture'],
                    'duration' => 20,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8230, 'lng' => 10.8300],
                ],
                [
                    'order' => 3,
                    'title' => ['fr' => 'La Lagune', 'en' => 'The Lagoon'],
                    'description' => ['fr' => 'Arrivée à la lagune avec vue panoramique et observation des oiseaux', 'en' => 'Arrival at the lagoon with panoramic view and bird watching'],
                    'duration' => 30,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8320, 'lng' => 10.8220],
                ],
                [
                    'order' => 4,
                    'title' => ['fr' => 'Retour - Zone touristique', 'en' => 'Return - Tourist Zone'],
                    'description' => ['fr' => 'Retour au point de départ par un chemin différent', 'en' => 'Return to starting point via a different path'],
                    'duration' => 25,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8076, 'lng' => 10.8451],
                ],
            ],

            // 2. Balade à Cheval - Horse Ride to Flamingo Island
            'balade-a-cheval-vers-lile-aux-flamants-roses' => [
                [
                    'order' => 0,
                    'title' => ['fr' => 'Départ - Zone touristique Mezraya', 'en' => 'Start - Mezraya Tourist Zone'],
                    'description' => ['fr' => 'Rencontre des chevaux et briefing sécurité', 'en' => 'Meeting the horses and safety briefing'],
                    'duration' => 10,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8076, 'lng' => 10.8451],
                ],
                [
                    'order' => 1,
                    'title' => ['fr' => 'Plage et dunes', 'en' => 'Beach and Dunes'],
                    'description' => ['fr' => 'Galop le long de la plage et traversée des dunes de sable', 'en' => 'Gallop along the beach and cross the sand dunes'],
                    'duration' => 15,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8200, 'lng' => 10.8350],
                ],
                [
                    'order' => 2,
                    'title' => ['fr' => 'Sentier côtier', 'en' => 'Coastal Trail'],
                    'description' => ['fr' => 'Promenade le long du sentier côtier avec vue sur la mer', 'en' => 'Ride along the coastal trail with sea views'],
                    'duration' => 15,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8400, 'lng' => 10.8250],
                ],
                [
                    'order' => 3,
                    'title' => ['fr' => 'Zone des flamants roses', 'en' => 'Pink Flamingo Area'],
                    'description' => ['fr' => 'Observation des flamants roses dans leur habitat naturel', 'en' => 'Observation of pink flamingos in their natural habitat'],
                    'duration' => 15,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8600, 'lng' => 10.8180],
                ],
                [
                    'order' => 4,
                    'title' => ['fr' => 'Retour au coucher du soleil', 'en' => 'Sunset Return'],
                    'description' => ['fr' => 'Retour au point de départ au coucher du soleil', 'en' => 'Return to starting point at sunset'],
                    'duration' => 15,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8076, 'lng' => 10.8451],
                ],
            ],

            // 3. Buggy - Villages & Beaches
            'balade-en-buggy-15h-3h' => [
                [
                    'order' => 0,
                    'title' => ['fr' => 'Départ - Zone touristique Mezraya', 'en' => 'Start - Mezraya Tourist Zone'],
                    'description' => ['fr' => 'Briefing sécurité et prise en main du buggy', 'en' => 'Safety briefing and buggy orientation'],
                    'duration' => 15,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8076, 'lng' => 10.8451],
                ],
                [
                    'order' => 1,
                    'title' => ['fr' => 'Menzels Djerbiens', 'en' => 'Djerbian Menzels'],
                    'description' => ['fr' => 'Traversée des villages avec architecture traditionnelle des Menzels', 'en' => 'Drive through villages with traditional Menzel architecture'],
                    'duration' => 20,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8180, 'lng' => 10.8600],
                ],
                [
                    'order' => 2,
                    'title' => ['fr' => 'Mosquée traditionnelle', 'en' => 'Traditional Mosque'],
                    'description' => ['fr' => 'Pause photo devant une mosquée typique de l\'île', 'en' => 'Photo stop at a typical island mosque'],
                    'duration' => 10,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8300, 'lng' => 10.8750],
                ],
                [
                    'order' => 3,
                    'title' => ['fr' => 'Route côtière', 'en' => 'Coastal Road'],
                    'description' => ['fr' => 'Conduite le long de la côte avec plages turquoise', 'en' => 'Drive along the coast with turquoise beaches'],
                    'duration' => 25,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8150, 'lng' => 10.9100],
                ],
                [
                    'order' => 4,
                    'title' => ['fr' => 'Plage de Sidi Mahrez', 'en' => 'Sidi Mahrez Beach'],
                    'description' => ['fr' => 'Pause à la célèbre plage de Sidi Mahrez', 'en' => 'Stop at the famous Sidi Mahrez beach'],
                    'duration' => 15,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8200, 'lng' => 10.9800],
                ],
                [
                    'order' => 5,
                    'title' => ['fr' => 'Retour - Zone touristique', 'en' => 'Return - Tourist Zone'],
                    'description' => ['fr' => 'Retour au point de départ', 'en' => 'Return to starting point'],
                    'duration' => 20,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8076, 'lng' => 10.8451],
                ],
            ],

            // 4. Quad - Dunes & Flamingo Island
            'balade-quad-15h-3h' => [
                [
                    'order' => 0,
                    'title' => ['fr' => 'Départ - Zone touristique Mezraya', 'en' => 'Start - Mezraya Tourist Zone'],
                    'description' => ['fr' => 'Briefing sécurité et prise en main du quad', 'en' => 'Safety briefing and quad orientation'],
                    'duration' => 15,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8076, 'lng' => 10.8451],
                ],
                [
                    'order' => 1,
                    'title' => ['fr' => 'Piste de sable', 'en' => 'Sandy Trail'],
                    'description' => ['fr' => 'Parcours sur piste de sable à travers la campagne', 'en' => 'Sandy trail through the countryside'],
                    'duration' => 20,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8200, 'lng' => 10.8350],
                ],
                [
                    'order' => 2,
                    'title' => ['fr' => 'Dunes légères', 'en' => 'Light Dunes'],
                    'description' => ['fr' => 'Traversée des dunes avec sensations garanties', 'en' => 'Cross the dunes with guaranteed thrills'],
                    'duration' => 20,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8400, 'lng' => 10.8200],
                ],
                [
                    'order' => 3,
                    'title' => ['fr' => 'Bord de lagune', 'en' => 'Lagoon Shore'],
                    'description' => ['fr' => 'Longez la lagune avec vue sur les flamants roses', 'en' => 'Ride along the lagoon with flamingo views'],
                    'duration' => 15,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8600, 'lng' => 10.8100],
                ],
                [
                    'order' => 4,
                    'title' => ['fr' => 'Île aux Flamants Roses', 'en' => 'Pink Flamingo Island'],
                    'description' => ['fr' => 'Pause détente au bord de l\'eau, observation des flamants roses', 'en' => 'Relaxation break by the water, flamingo watching'],
                    'duration' => 15,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8950, 'lng' => 10.8200],
                ],
                [
                    'order' => 5,
                    'title' => ['fr' => 'Retour - Zone touristique', 'en' => 'Return - Tourist Zone'],
                    'description' => ['fr' => 'Retour au point de départ par les plages', 'en' => 'Return to starting point via the beaches'],
                    'duration' => 25,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8076, 'lng' => 10.8451],
                ],
            ],

            // 5. Bateau Pirate - Marina to Flamingo Island
            'excursion-en-bateau-pirate' => [
                [
                    'order' => 0,
                    'title' => ['fr' => 'Marina Houmt Souk', 'en' => 'Marina Houmt Souk'],
                    'description' => ['fr' => 'Embarquement au port de Houmt Souk', 'en' => 'Boarding at Houmt Souk port'],
                    'duration' => 20,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8868, 'lng' => 10.8566],
                ],
                [
                    'order' => 1,
                    'title' => ['fr' => 'Navigation en mer', 'en' => 'Sea Navigation'],
                    'description' => ['fr' => 'Navigation avec spectacle pirate, musique et danse', 'en' => 'Navigation with pirate show, music and dance'],
                    'duration' => 60,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.9050, 'lng' => 10.8400],
                ],
                [
                    'order' => 2,
                    'title' => ['fr' => 'Île aux Flamants Roses', 'en' => 'Pink Flamingo Island'],
                    'description' => ['fr' => 'Arrivée à l\'île aux flamants roses, baignade et déjeuner', 'en' => 'Arrival at flamingo island, swimming and lunch'],
                    'duration' => 120,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.9200, 'lng' => 10.8100],
                ],
                [
                    'order' => 3,
                    'title' => ['fr' => 'Baignade en eau claire', 'en' => 'Clear Water Swimming'],
                    'description' => ['fr' => 'Pause baignade dans les eaux turquoise', 'en' => 'Swimming stop in turquoise waters'],
                    'duration' => 60,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.9100, 'lng' => 10.8250],
                ],
                [
                    'order' => 4,
                    'title' => ['fr' => 'Retour - Marina Houmt Souk', 'en' => 'Return - Marina Houmt Souk'],
                    'description' => ['fr' => 'Navigation retour avec animations folkloriques', 'en' => 'Return navigation with folkloric entertainment'],
                    'duration' => 60,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8868, 'lng' => 10.8566],
                ],
            ],

            // 6. Balade en Bateaux - Neptune to Seguia
            'balade-en-bateaux-1h30min' => [
                [
                    'order' => 0,
                    'title' => ['fr' => 'Neptune Watersport', 'en' => 'Neptune Watersport'],
                    'description' => ['fr' => 'Briefing sécurité et embarquement', 'en' => 'Safety briefing and boarding'],
                    'duration' => 10,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8363, 'lng' => 11.0092],
                ],
                [
                    'order' => 1,
                    'title' => ['fr' => 'Navigation côtière', 'en' => 'Coastal Navigation'],
                    'description' => ['fr' => 'Navigation le long de la côte vers Seguia', 'en' => 'Navigation along the coast toward Seguia'],
                    'duration' => 20,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8420, 'lng' => 11.0200],
                ],
                [
                    'order' => 2,
                    'title' => ['fr' => 'La Seguia', 'en' => 'La Seguia'],
                    'description' => ['fr' => 'Arrivée à la Seguia, lieu emblématique de Djerba. Observation des dauphins possible', 'en' => 'Arrival at Seguia, iconic Djerba location. Possible dolphin sighting'],
                    'duration' => 30,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8500, 'lng' => 11.0300],
                ],
                [
                    'order' => 3,
                    'title' => ['fr' => 'Pause baignade', 'en' => 'Swimming Break'],
                    'description' => ['fr' => 'Pause baignade en eaux cristallines', 'en' => 'Swimming break in crystal-clear waters'],
                    'duration' => 20,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8450, 'lng' => 11.0250],
                ],
                [
                    'order' => 4,
                    'title' => ['fr' => 'Retour - Neptune Watersport', 'en' => 'Return - Neptune Watersport'],
                    'description' => ['fr' => 'Navigation retour au point de départ', 'en' => 'Return navigation to starting point'],
                    'duration' => 15,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8363, 'lng' => 11.0092],
                ],
            ],

            // 7. Jetski 1H30 - Neptune to Seguia
            'balade-en-jetski-1h30min' => [
                [
                    'order' => 0,
                    'title' => ['fr' => 'Neptune Watersport', 'en' => 'Neptune Watersport'],
                    'description' => ['fr' => 'Instruction professionnelle et briefing sécurité', 'en' => 'Professional instruction and safety briefing'],
                    'duration' => 15,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8363, 'lng' => 11.0092],
                ],
                [
                    'order' => 1,
                    'title' => ['fr' => 'Côte Est de Djerba', 'en' => 'East Coast of Djerba'],
                    'description' => ['fr' => 'Navigation à grande vitesse le long de la côte est', 'en' => 'High-speed navigation along the east coast'],
                    'duration' => 20,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8450, 'lng' => 11.0200],
                ],
                [
                    'order' => 2,
                    'title' => ['fr' => 'La Seguia', 'en' => 'La Seguia'],
                    'description' => ['fr' => 'Exploration de la Seguia avec forte chance d\'observer des dauphins', 'en' => 'Seguia exploration with strong chance of dolphin sighting'],
                    'duration' => 25,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8550, 'lng' => 11.0350],
                ],
                [
                    'order' => 3,
                    'title' => ['fr' => 'Pause baignade', 'en' => 'Swimming Break'],
                    'description' => ['fr' => 'Pause baignade en pleine mer', 'en' => 'Open sea swimming break'],
                    'duration' => 20,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8500, 'lng' => 11.0280],
                ],
                [
                    'order' => 4,
                    'title' => ['fr' => 'Retour - Neptune Watersport', 'en' => 'Return - Neptune Watersport'],
                    'description' => ['fr' => 'Retour au point de départ', 'en' => 'Return to starting point'],
                    'duration' => 15,
                    'locationId' => null,
                    'coordinates' => ['lat' => 33.8363, 'lng' => 11.0092],
                ],
            ],
        ];
    }
}
