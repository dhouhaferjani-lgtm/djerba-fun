<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PlatformSettings;
use Illuminate\Console\Command;

class SeedDestinationContentCommand extends Command
{
    protected $signature = 'destinations:seed-content {--force : Overwrite existing content}';

    protected $description = 'Seed destination informative content (highlights, facts, POIs, SEO) into CMS';

    public function handle(): int
    {
        $settings = PlatformSettings::first();

        if (! $settings) {
            $this->error('No platform settings found. Please create settings first.');

            return self::FAILURE;
        }

        $destinations = $settings->featured_destinations ?? [];

        if (empty($destinations)) {
            $this->error('No featured destinations found. Please add destinations in the admin panel first.');

            return self::FAILURE;
        }

        $contentMap = $this->getContentMap();
        $updated = 0;

        foreach ($destinations as $index => $dest) {
            $slug = $dest['id'] ?? '';
            $name = $dest['name'] ?? $slug;

            // Resolve data key (CMS slugs may differ from content keys)
            $dataKey = $this->resolveDataKey($slug, $name);

            if (! isset($contentMap[$dataKey])) {
                $this->warn("No content data for destination '{$name}' (slug: {$slug}, key: {$dataKey}). Skipping.");

                continue;
            }

            $content = $contentMap[$dataKey];
            $force = $this->option('force');

            // Only seed fields that are empty (unless --force)
            if ($force || empty($dest['highlights'])) {
                $destinations[$index]['highlights'] = $content['highlights'];
            }
            if ($force || empty($dest['key_facts'])) {
                $destinations[$index]['key_facts'] = $content['key_facts'];
            }
            if ($force || empty($dest['points_of_interest'])) {
                $destinations[$index]['points_of_interest'] = $content['points_of_interest'];
            }
            if ($force || empty($dest['seo_text_en'])) {
                $destinations[$index]['seo_text_en'] = $content['seo_text_en'];
            }
            if ($force || empty($dest['seo_text_fr'])) {
                $destinations[$index]['seo_text_fr'] = $content['seo_text_fr'];
            }
            if ($force || empty($dest['seo_title_en'])) {
                $destinations[$index]['seo_title_en'] = $content['seo_title_en'];
            }
            if ($force || empty($dest['seo_title_fr'])) {
                $destinations[$index]['seo_title_fr'] = $content['seo_title_fr'];
            }
            if ($force || empty($dest['seo_description_en'])) {
                $destinations[$index]['seo_description_en'] = $content['seo_description_en'];
            }
            if ($force || empty($dest['seo_description_fr'])) {
                $destinations[$index]['seo_description_fr'] = $content['seo_description_fr'];
            }

            $updated++;
            $this->info("Seeded content for '{$name}' (key: {$dataKey})");
        }

        $settings->featured_destinations = $destinations;
        $settings->save();

        $this->info("Done. Updated {$updated} destination(s).");

        return self::SUCCESS;
    }

    private function resolveDataKey(string $slug, ?string $name): string
    {
        $map = [
            'djerba' => 'djerba',
            'dhaher' => 'dhaher',
            'desert' => 'desert',
            'houmet-souk' => 'djerba',
            'guellala' => 'dhaher',
            'lile-des-flamants-roses' => 'desert',
        ];

        if (isset($map[$slug])) {
            return $map[$slug];
        }

        $lower = strtolower($name ?? '');
        if (str_contains($lower, 'djerba')) {
            return 'djerba';
        }
        if (str_contains($lower, 'dhaher') || str_contains($lower, 'dahar')) {
            return 'dhaher';
        }
        if (str_contains($lower, 'desert') || str_contains($lower, 'désert') || str_contains($lower, 'sahara')) {
            return 'desert';
        }

        return $slug;
    }

    private function getContentMap(): array
    {
        return [
            'djerba' => [
                'seo_title_en' => 'Djerba, Tunisia — UNESCO Island, Beaches & Culture | Evasion Djerba',
                'seo_title_fr' => 'Djerba, Tunisie — Île UNESCO, Plages & Culture | Evasion Djerba',
                'seo_description_en' => 'Explore Djerba, the largest island in North Africa. UNESCO World Heritage Site with 20+ beaches, El Ghriba Synagogue, Djerbahood street art, and Houmt Souk markets. Book authentic tours and activities.',
                'seo_description_fr' => "Explorez Djerba, la plus grande île d'Afrique du Nord. Site du patrimoine mondial UNESCO avec plus de 20 plages, la synagogue de la Ghriba, le street art Djerbahood et les marchés de Houmt Souk. Réservez des tours et activités authentiques.",
                'seo_text_en' => 'Djerba, the largest island in North Africa at 514 km², has been a crossroads of civilizations for over 3,000 years. Known to ancient geographers as the "Land of the Lotus Eaters" from Homer\'s Odyssey, the island earned UNESCO World Heritage status in 2023 for its exceptional testimony to a settlement pattern of an island territory, reflecting the coexistence of diverse religious and cultural communities. Today, Djerba enchants visitors with its golden sand beaches stretching over 20 km, the mesmerizing Djerbahood street art village with 250+ murals, the ancient El Ghriba Synagogue dating back 2,600 years, and the bustling souks of Houmt Souk where artisan traditions have continued since Roman times. From kitesurfing and scuba diving to pottery workshops in Guellala and flamingo watching at Ras Rmel, Djerba offers a uniquely diverse island experience in the heart of the Mediterranean.',
                'seo_text_fr' => "Djerba, la plus grande île d'Afrique du Nord avec ses 514 km², est un carrefour de civilisations depuis plus de 3 000 ans. Connue des géographes anciens comme la « Terre des Lotophages » de l'Odyssée d'Homère, l'île a obtenu le statut de patrimoine mondial de l'UNESCO en 2023 pour son témoignage exceptionnel d'un modèle d'implantation sur un territoire insulaire, reflétant la coexistence de communautés religieuses et culturelles diverses. Aujourd'hui, Djerba enchante les visiteurs avec ses plages de sable doré s'étendant sur plus de 20 km, le fascinant village de street art Djerbahood avec plus de 250 fresques, l'ancienne synagogue de la Ghriba vieille de 2 600 ans, et les souks animés de Houmt Souk où les traditions artisanales perdurent depuis l'époque romaine. Du kitesurf et de la plongée sous-marine aux ateliers de poterie à Guellala et à l'observation des flamants roses à Ras Rmel, Djerba offre une expérience insulaire d'une diversité unique au cœur de la Méditerranée.",
                'highlights' => [
                    ['icon' => 'waves', 'title_en' => 'Beaches & Turquoise Waters', 'title_fr' => 'Plages & Eaux Turquoise', 'description_en' => 'Over 20 km of golden sand beaches fringed with palm trees along crystal-clear Mediterranean waters. From the lively Sidi Mahrez beach to the secluded coves of Sidi Jmour, Djerba offers some of the finest coastal stretches in North Africa.', 'description_fr' => "Plus de 20 km de plages de sable doré bordées de palmiers le long des eaux cristallines de la Méditerranée. De la plage animée de Sidi Mahrez aux criques isolées de Sidi Jmour, Djerba offre parmi les plus beaux littoraux d'Afrique du Nord."],
                    ['icon' => 'landmark', 'title_en' => 'UNESCO Heritage & El Ghriba', 'title_fr' => 'Patrimoine UNESCO & La Ghriba', 'description_en' => 'Designated a UNESCO World Heritage Site in 2023, Djerba is home to the El Ghriba Synagogue — over 2,600 years old and one of the oldest in the world. The island blends Berber, Arab, Jewish, and Mediterranean cultural influences across centuries of coexistence.', 'description_fr' => "Inscrite au patrimoine mondial de l'UNESCO en 2023, Djerba abrite la synagogue de la Ghriba — vieille de plus de 2 600 ans, l'une des plus anciennes au monde. L'île mêle influences berbères, arabes, juives et méditerranéennes à travers des siècles de coexistence."],
                    ['icon' => 'palette', 'title_en' => 'Djerbahood Street Art', 'title_fr' => 'Street Art Djerbahood', 'description_en' => 'The village of Erriadh has been transformed into an extraordinary open-air museum: over 250 murals created by 150 international artists using 4,500+ spray paint cans. A unique fusion of contemporary art and traditional Djerbian architecture.', 'description_fr' => "Le village d'Erriadh a été transformé en un extraordinaire musée à ciel ouvert : plus de 250 fresques créées par 150 artistes internationaux utilisant plus de 4 500 bombes de peinture. Une fusion unique entre art contemporain et architecture djerbienne traditionnelle."],
                    ['icon' => 'shopping-bag', 'title_en' => 'Houmt Souk Markets', 'title_fr' => 'Marchés de Houmt Souk', 'description_en' => "Djerba's main town has been a thriving trading port since Roman times. Wander through its labyrinthine souks filled with handwoven textiles, silver jewelry, and spices, then visit the 15th-century Borj El Kebir fortress overlooking the fishing harbor.", 'description_fr' => "La ville principale de Djerba est un port commercial florissant depuis l'époque romaine. Flânez dans ses souks labyrinthiques remplis de textiles tissés à la main, bijoux en argent et épices, puis visitez la forteresse Borj El Kebir du XVe siècle surplombant le port de pêche."],
                    ['icon' => 'utensils-crossed', 'title_en' => 'Island Gastronomy', 'title_fr' => 'Gastronomie Insulaire', 'description_en' => 'Savor Djerba\'s unique culinary heritage: fresh-caught Mediterranean seafood, the iconic couscous au poisson, stone-pressed Djerbian olive oil, and traditional "ftayer" pastries. Each dish reflects the island\'s multicultural history.', 'description_fr' => "Savourez l'héritage culinaire unique de Djerba : fruits de mer frais de la Méditerranée, l'emblématique couscous au poisson, huile d'olive djerbienne pressée à la pierre et pâtisseries traditionnelles « ftayer ». Chaque plat reflète l'histoire multiculturelle de l'île."],
                    ['icon' => 'bird', 'title_en' => 'Flamingo Island', 'title_fr' => "L'Île aux Flamants Roses", 'description_en' => 'The Ras Rmel peninsula, known as "Flamingo Island," becomes a spectacular natural sanctuary from October to February when hundreds of pink flamingos migrate to wade in the surrounding lagoon — a breathtaking sight accessible by boat from Houmt Souk.', 'description_fr' => "La péninsule de Ras Rmel, connue sous le nom d'« Île aux Flamants Roses », devient un sanctuaire naturel spectaculaire d'octobre à février lorsque des centaines de flamants roses migrent pour se poser dans la lagune environnante — un spectacle à couper le souffle accessible en bateau depuis Houmt Souk."],
                ],
                'key_facts' => [
                    ['icon' => 'ruler', 'label_en' => 'Area', 'label_fr' => 'Superficie', 'value' => '514 km²'],
                    ['icon' => 'star', 'label_en' => 'UNESCO', 'label_fr' => 'UNESCO', 'value' => '2023'],
                    ['icon' => 'users', 'label_en' => 'Population', 'label_fr' => 'Population', 'value' => '~163 000'],
                    ['icon' => 'calendar', 'label_en' => 'Best season', 'label_fr' => 'Meilleure saison', 'value' => 'Avr — Oct'],
                    ['icon' => 'waves', 'label_en' => 'Beaches', 'label_fr' => 'Plages', 'value' => '20+'],
                ],
                'points_of_interest' => [
                    ['name_en' => 'El Ghriba Synagogue', 'name_fr' => 'Synagogue de la Ghriba', 'description_en' => 'One of the oldest synagogues in the world, dating back over 2,600 years. An annual pilgrimage draws visitors from across the globe to this sacred site decorated with stunning blue ceramic tiles.', 'description_fr' => "L'une des plus anciennes synagogues au monde, datant de plus de 2 600 ans. Un pèlerinage annuel attire des visiteurs du monde entier vers ce site sacré décoré de magnifiques carreaux de céramique bleue."],
                    ['name_en' => 'Djerbahood, Erriadh', 'name_fr' => 'Djerbahood, Erriadh', 'description_en' => 'An open-air street art museum in the heart of Erriadh village. Over 250 murals by 150 artists from 30+ countries transform whitewashed walls into a breathtaking gallery that blends contemporary art with traditional architecture.', 'description_fr' => "Un musée de street art à ciel ouvert au cœur du village d'Erriadh. Plus de 250 fresques par 150 artistes de plus de 30 pays transforment les murs blanchis en une galerie à couper le souffle mêlant art contemporain et architecture traditionnelle."],
                    ['name_en' => 'Houmt Souk & Borj El Kebir', 'name_fr' => 'Houmt Souk & Borj El Kebir', 'description_en' => "Djerba's vibrant capital since Roman times. Explore the labyrinthine souks, haggle for silver jewelry and handwoven textiles, visit the 15th-century fortress, and watch the colorful fishing boats in the historic harbor.", 'description_fr' => "La vibrante capitale de Djerba depuis l'époque romaine. Explorez les souks labyrinthiques, marchandez bijoux en argent et textiles tissés à la main, visitez la forteresse du XVe siècle et admirez les bateaux de pêche colorés dans le port historique."],
                    ['name_en' => 'Guellala Pottery Village', 'name_fr' => 'Village des Potiers de Guellala', 'description_en' => 'Home to nearly 500 potters who have worked with clay since Roman times. Visit underground workshops where artisans craft jars, vases, and decorative pieces using techniques unchanged for centuries.', 'description_fr' => "Foyer de près de 500 potiers qui travaillent l'argile depuis l'époque romaine. Visitez des ateliers souterrains où les artisans façonnent jarres, vases et pièces décoratives selon des techniques inchangées depuis des siècles."],
                ],
            ],
            'dhaher' => [
                'seo_title_en' => 'Dahar Region, Tunisia — Berber Villages, Trekking & Star Wars Sites | Evasion Djerba',
                'seo_title_fr' => 'Région du Dahar, Tunisie — Villages Berbères, Randonnée & Sites Star Wars | Evasion Djerba',
                'seo_description_en' => 'Discover the Dahar highlands of southern Tunisia. Trek the 194 km Great Dahar Crossing, visit Chenini & Douiret troglodyte villages, explore Star Wars filming locations at Ksar Ouled Soltane, and find dinosaur footprints.',
                'seo_description_fr' => 'Découvrez les hauts plateaux du Dahar dans le sud tunisien. Parcourez les 194 km de la Grande Traversée du Dahar, visitez les villages troglodytiques de Chenini et Douiret, explorez les lieux de tournage Star Wars à Ksar Ouled Soltane.',
                'seo_text_en' => "The Dahar region is southern Tunisia's best-kept secret — a vast, rugged highland stretching from Matmata to Tataouine, home to some of the most extraordinary landscapes and cultural heritage in North Africa. The indigenous Amazigh (Berber) people have inhabited these mountains for millennia, carving spectacular troglodyte dwellings into the rock and building fortified granaries (ksour) to protect their harvests. The newly established Great Dahar Crossing — a 194 km hiking trail across 12 stages — reveals ancient villages like Chenini and Douiret perched dramatically on hilltops, dinosaur footprints near Ghomrassen, and the iconic ksour that inspired George Lucas to name his fictional planet 'Tatooine' after the real city of Tataouine. This is authentic Tunisia: untouched by mass tourism, rich in heritage, and breathtaking in its raw natural beauty.",
                'seo_text_fr' => "La région du Dahar est le secret le mieux gardé du sud tunisien — un vaste plateau escarpé s'étendant de Matmata à Tataouine, abritant certains des paysages et patrimoines culturels les plus extraordinaires d'Afrique du Nord. Le peuple autochtone amazigh (berbère) habite ces montagnes depuis des millénaires, creusant des habitations troglodytiques spectaculaires dans la roche et construisant des greniers fortifiés (ksour) pour protéger leurs récoltes. La Grande Traversée du Dahar nouvellement établie — un sentier de randonnée de 194 km en 12 étapes — révèle des villages anciens comme Chenini et Douiret perchés de manière spectaculaire sur des collines, des empreintes de dinosaures près de Ghomrassen, et les ksour emblématiques qui ont inspiré George Lucas pour nommer sa planète fictive « Tatooine » d'après la vraie ville de Tataouine. C'est la Tunisie authentique : préservée du tourisme de masse, riche en patrimoine et époustouflante par sa beauté naturelle brute.",
                'highlights' => [
                    ['icon' => 'mountain', 'title_en' => 'The Great Dahar Crossing', 'title_fr' => 'La Grande Traversée du Dahar', 'description_en' => 'The Great Dahar Crossing is a 194 km hiking route across 12 stages through the remote highlands of southern Tunisia. Starting from Tamazrat, the trail winds through stony desert, over ochre-coloured peaks, through ancient Amazigh villages surrounded by olive groves and date palms.', 'description_fr' => "La Grande Traversée du Dahar est un itinéraire de randonnée de 194 km en 12 étapes à travers les hauts plateaux reculés du sud tunisien. Partant de Tamazrat, le sentier serpente à travers le désert pierreux, par-dessus des pics ocre, à travers d'anciens villages amazighs entourés d'oliviers et de palmiers dattiers."],
                    ['icon' => 'home', 'title_en' => 'Chenini & Douiret', 'title_fr' => 'Chenini & Douiret', 'description_en' => 'Two iconic hilltop Berber villages perched on dramatic rock formations just 18 km from Tataouine. Trek the ancient path connecting them in 2.5 hours. Sleep in traditional troglodyte cave-rooms at family-run gîtes in Douiret, and discover the ancient white mosque crowning Chenini.', 'description_fr' => "Deux villages berbères emblématiques perchés sur des formations rocheuses spectaculaires à seulement 18 km de Tataouine. Parcourez l'ancien sentier qui les relie en 2h30. Dormez dans des chambres troglodytiques traditionnelles dans des gîtes familiaux à Douiret, et découvrez l'ancienne mosquée blanche qui couronne Chenini."],
                    ['icon' => 'users', 'title_en' => 'Amazigh Heritage', 'title_fr' => 'Patrimoine Amazigh', 'description_en' => "The Dahar is the heartland of Tunisia's indigenous Amazigh (Berber) people. Discover underground troglodyte dwellings carved into rock faces — a testament to humanity's harmonious relationship with nature. Experience the warm hospitality of communities preserving traditions passed down through millennia.", 'description_fr' => "Le Dahar est le berceau du peuple amazigh (berbère) autochtone de Tunisie. Découvrez des habitations troglodytiques souterraines creusées dans la roche — un témoignage de la relation harmonieuse de l'humanité avec la nature. Vivez l'hospitalité chaleureuse de communautés préservant des traditions transmises depuis des millénaires."],
                    ['icon' => 'film', 'title_en' => 'Ksour & Star Wars', 'title_fr' => 'Ksour & Star Wars', 'description_en' => 'The fortified granaries (ksour) of the Dahar region served as iconic Star Wars filming locations. Ksar Hadada and Ksar Ouled Soltane — with their multi-story ghorfas — became the villages of Tatooine. The planet itself was named after the real town of Tataouine. Chenini, Ghomrassen, and Guermessa also appear as moon names in the saga.', 'description_fr' => 'Les greniers fortifiés (ksour) de la région du Dahar ont servi de décors emblématiques pour Star Wars. Ksar Hadada et Ksar Ouled Soltane — avec leurs ghorfas à plusieurs étages — sont devenus les villages de Tatooine. La planète elle-même porte le nom de la vraie ville de Tataouine. Chenini, Ghomrassen et Guermessa apparaissent également comme noms de lunes dans la saga.'],
                    ['icon' => 'eye', 'title_en' => 'Geological Wonders', 'title_fr' => 'Merveilles Géologiques', 'description_en' => 'The Dahar region is a geological treasure trove: discover dinosaur footprints near Ghomrassen, ancient cave paintings, and dramatic gorges carved by millennia of erosion. The ochre, rose, and amber rock formations create landscapes that feel like another planet.', 'description_fr' => "La région du Dahar est un trésor géologique : découvrez des empreintes de dinosaures près de Ghomrassen, des peintures rupestres anciennes et des gorges spectaculaires creusées par des millénaires d'érosion. Les formations rocheuses ocre, rose et ambrées créent des paysages qui semblent venir d'une autre planète."],
                    ['icon' => 'compass', 'title_en' => 'Panoramic Summits', 'title_fr' => 'Sommets Panoramiques', 'description_en' => 'Scale the rugged peaks of the Dahar for breathtaking 360° panoramas over vast arid valleys, ancient stone villages, and the distant shimmer of the Sahara. The citadel villages of Guermessa and Ras El Oued offer some of the most spectacular viewpoints in all of Tunisia.', 'description_fr' => "Gravissez les pics escarpés du Dahar pour des panoramas à 360° époustouflants sur de vastes vallées arides, d'anciens villages de pierre et le scintillement lointain du Sahara. Les villages-citadelles de Guermessa et Ras El Oued offrent parmi les points de vue les plus spectaculaires de toute la Tunisie."],
                ],
                'key_facts' => [
                    ['icon' => 'ruler', 'label_en' => 'Trail length', 'label_fr' => 'Longueur du sentier', 'value' => '194 km'],
                    ['icon' => 'mountain', 'label_en' => 'Stages', 'label_fr' => 'Étapes', 'value' => '12'],
                    ['icon' => 'home', 'label_en' => 'Villages', 'label_fr' => 'Villages', 'value' => '5+'],
                    ['icon' => 'calendar', 'label_en' => 'Best season', 'label_fr' => 'Meilleure saison', 'value' => 'Oct — Avr'],
                    ['icon' => 'film', 'label_en' => 'Star Wars sites', 'label_fr' => 'Sites Star Wars', 'value' => '3+'],
                ],
                'points_of_interest' => [
                    ['name_en' => 'Chenini', 'name_fr' => 'Chenini', 'description_en' => 'A spectacular hilltop Berber village 18 km from Tataouine, crowned by an ancient white mosque. Its troglodyte dwellings cascade down the rocky hillside, offering panoramic views across the Dahar landscape. Considered a Star Wars scouting location for Mos Espa.', 'description_fr' => "Un spectaculaire village berbère perché à 18 km de Tataouine, couronné par une ancienne mosquée blanche. Ses habitations troglodytiques s'étagent le long de la colline rocheuse, offrant des vues panoramiques sur le paysage du Dahar. Considéré comme lieu de repérage Star Wars pour Mos Espa."],
                    ['name_en' => 'Douiret', 'name_fr' => 'Douiret', 'description_en' => 'A stunning mountaintop village offering a unique experience: sleep in traditional troglodyte cave-rooms at family-run gîtes. Connected to Chenini by an ancient 2.5-hour walking path that crosses dramatic desert landscapes.', 'description_fr' => "Un superbe village au sommet d'une montagne offrant une expérience unique : dormir dans des chambres troglodytiques traditionnelles dans des gîtes familiaux. Relié à Chenini par un ancien sentier de 2h30 qui traverse des paysages désertiques spectaculaires."],
                    ['name_en' => 'Ksar Ouled Soltane', 'name_fr' => 'Ksar Ouled Soltane', 'description_en' => 'An iconic 4-story fortified granary (ksar) with perfectly preserved multi-level ghorfas (storage rooms). This magnificent structure served as a Star Wars filming set for the slave quarters on Tatooine and remains one of the most photogenic sites in Tunisia.', 'description_fr' => "Un ksar emblématique à 4 étages avec des ghorfas (salles de stockage) à plusieurs niveaux parfaitement conservées. Cette magnifique structure a servi de décor Star Wars pour les quartiers des esclaves sur Tatooine et reste l'un des sites les plus photogéniques de Tunisie."],
                    ['name_en' => 'Ghomrassen', 'name_fr' => 'Ghomrassen', 'description_en' => 'Home to some of the most remarkable geological discoveries in the region, including dinosaur footprints and ancient cave paintings. The town serves as a base for exploring the surrounding geological wonders of the Dahar.', 'description_fr' => 'Foyer de certaines des découvertes géologiques les plus remarquables de la région, notamment des empreintes de dinosaures et des peintures rupestres anciennes. La ville sert de base pour explorer les merveilles géologiques environnantes du Dahar.'],
                ],
            ],
            'desert' => [
                'seo_title_en' => 'Tunisian Sahara Desert — Douz, Ksar Ghilane & Chott el Jerid | Evasion Djerba',
                'seo_title_fr' => 'Désert du Sahara Tunisien — Douz, Ksar Ghilane & Chott el Jerid | Evasion Djerba',
                'seo_description_en' => 'Explore the Tunisian Sahara: camel treks in Douz, hot springs at Ksar Ghilane oasis, the vast Chott el Jerid salt lake, and Star Wars filming locations near Tozeur. Book desert camps, 4x4 adventures, and more.',
                'seo_description_fr' => "Explorez le Sahara tunisien : méharées à Douz, sources chaudes à l'oasis de Ksar Ghilane, le vaste lac salé Chott el Jerid et les lieux de tournage Star Wars près de Tozeur. Réservez camps désertiques, aventures en 4x4 et plus.",
                'seo_text_en' => "The Tunisian Sahara is a land of extraordinary contrasts — from the towering golden dunes of the Grand Erg Oriental to the surreal salt flats of Chott el Jerid (the largest salt lake in the Sahara at 7,000 km²), from the lush palm oases of Tozeur with its 200,000 date palms to the remote hot springs of Ksar Ghilane. This is where ancient caravan routes once connected sub-Saharan Africa to the Mediterranean, and where the International Festival of the Sahara has celebrated nomadic Bedouin heritage in Douz every December since 1910. George Lucas chose this landscape for some of Star Wars' most iconic scenes — the Lars Homestead and Mos Espa sets still stand near Nefta and Chott el Jerid. Today, adventurers can explore the desert on camel treks, 4x4 expeditions, quad bikes, hot air balloons, and even sand skiing, then retire to luxury desert camps under some of the clearest night skies on Earth.",
                'seo_text_fr' => "Le Sahara tunisien est une terre de contrastes extraordinaires — des imposantes dunes dorées du Grand Erg Oriental aux étendues surréalistes de sel du Chott el Jerid (le plus grand lac salé du Sahara avec 7 000 km²), des oasis luxuriantes de palmiers de Tozeur avec ses 200 000 dattiers aux sources chaudes reculées de Ksar Ghilane. C'est ici que d'anciennes routes caravanières reliaient autrefois l'Afrique subsaharienne à la Méditerranée, et où le Festival International du Sahara célèbre le patrimoine nomade bédouin à Douz chaque décembre depuis 1910. George Lucas a choisi ce paysage pour certaines des scènes les plus emblématiques de Star Wars — les décors de la ferme des Lars et de Mos Espa se dressent toujours près de Nefta et du Chott el Jerid. Aujourd'hui, les aventuriers peuvent explorer le désert en méharée, expéditions en 4x4, quad, montgolfière et même ski sur sable, puis se retirer dans des camps de luxe sous certains des ciels nocturnes les plus clairs de la planète.",
                'highlights' => [
                    ['icon' => 'compass', 'title_en' => 'Douz: Gateway to the Sahara', 'title_fr' => 'Douz : Porte du Sahara', 'description_en' => 'Known as the "Gateway to the Sahara," Douz is where the dunes begin. Every December, thousands flock to the International Festival of the Sahara — a celebration of nomadic heritage since 1910 featuring camel races, traditional music, and Bedouin culture. From here, embark on dune excursions, sand skiing, and hot air balloon rides.', 'description_fr' => "Connue comme la « Porte du Sahara », Douz est là où les dunes commencent. Chaque décembre, des milliers de personnes affluent au Festival International du Sahara — une célébration du patrimoine nomade depuis 1910 avec courses de chameaux, musique traditionnelle et culture bédouine. D'ici, partez pour des excursions dans les dunes, du ski sur sable et des vols en montgolfière."],
                    ['icon' => 'droplets', 'title_en' => 'Ksar Ghilane Oasis', 'title_fr' => 'Oasis de Ksar Ghilane', 'description_en' => "Tunisia's most southerly oasis, on the edge of the Grand Erg Oriental. Bathe in a natural sulfur-rich hot spring that flows year-round in the heart of the desert. Nearby stand the ruins of Tisavar, an ancient Roman fort. Luxury desert camps offer comfortable tented accommodation under the stars.", 'description_fr' => "L'oasis la plus méridionale de Tunisie, en bordure du Grand Erg Oriental. Baignez-vous dans une source chaude naturelle riche en soufre qui coule toute l'année au cœur du désert. À proximité se dressent les ruines de Tisavar, un ancien fort romain. Des camps de luxe offrent un hébergement confortable sous les étoiles."],
                    ['icon' => 'footprints', 'title_en' => 'Camel Treks & Dune Expeditions', 'title_fr' => 'Méharées & Expéditions dans les Dunes', 'description_en' => 'Cross the towering golden dunes of the Grand Erg Oriental on unforgettable camel expeditions. From half-day sunset rides to multi-day desert crossings, feel the ancient rhythm of the Sahara. 4x4 adventures, quad biking, and sandboarding are also available for thrill-seekers.', 'description_fr' => "Traversez les imposantes dunes dorées du Grand Erg Oriental lors d'expéditions chamelières inoubliables. Des balades au coucher du soleil aux traversées de plusieurs jours, ressentez le rythme ancestral du Sahara. Aventures en 4x4, quad et sandboard sont également disponibles pour les amateurs de sensations fortes."],
                    ['icon' => 'layers', 'title_en' => 'Chott el Jerid Salt Lake', 'title_fr' => 'Lac Salé Chott el Jerid', 'description_en' => "The largest salt lake in the Sahara at over 7,000 km² — 1.5x bigger than the Great Salt Lake of Utah. This vast, surreal landscape creates stunning mirages and changes color with the seasons, from brilliant white to shimmering pink and violet. Star Wars' Lars Homestead was filmed on its northwestern edge near Nefta.", 'description_fr' => "Le plus grand lac salé du Sahara avec plus de 7 000 km² — 1,5 fois plus grand que le Grand Lac Salé de l'Utah. Ce vaste paysage surréaliste crée des mirages époustouflants et change de couleur au fil des saisons, du blanc éclatant au rose et violet chatoyants. La ferme des Lars dans Star Wars a été filmée sur son bord nord-ouest près de Nefta."],
                    ['icon' => 'moon', 'title_en' => 'Starlit Desert Camps', 'title_fr' => 'Camps sous les Étoiles', 'description_en' => 'Spend magical nights under the clearest skies on Earth in traditional desert bivouacs. With zero light pollution, the Milky Way blazes overhead while you enjoy campfire stories, Bedouin tea ceremonies, and traditional desert cuisine. Luxury tented camps offer real beds and all comforts amidst the wilderness.', 'description_fr' => "Passez des nuits magiques sous les ciels les plus clairs de la planète dans des bivouacs désertiques traditionnels. Sans aucune pollution lumineuse, la Voie Lactée brille au-dessus de vous tandis que vous profitez d'histoires autour du feu, de cérémonies de thé bédouin et de cuisine traditionnelle du désert. Des camps de luxe offrent de vrais lits et tout le confort au cœur de la nature sauvage."],
                    ['icon' => 'tree-palm', 'title_en' => 'Tozeur & Nefta Oases', 'title_fr' => 'Oasis de Tozeur & Nefta', 'description_en' => 'Explore the lush palm groves of Tozeur, home to over 200,000 date palms and the stunning mountain oasis of Chebika and Tamerza. Nearby Nefta, known as the "little Kairouan" for its many mosques, features the famous Corbeille — a natural amphitheater filled with palms. Star Wars\' Mos Espa set lies just north of Nefta.', 'description_fr' => 'Explorez les palmeraies luxuriantes de Tozeur, abritant plus de 200 000 palmiers dattiers et les superbes oasis de montagne de Chebika et Tamerza. Nefta, surnommée la « petite Kairouan » pour ses nombreuses mosquées, abrite la célèbre Corbeille — un amphithéâtre naturel rempli de palmiers. Le décor de Mos Espa de Star Wars se trouve juste au nord de Nefta.'],
                ],
                'key_facts' => [
                    ['icon' => 'layers', 'label_en' => 'Salt lake', 'label_fr' => 'Lac salé', 'value' => '7 000 km²'],
                    ['icon' => 'map-pin', 'label_en' => 'Gateway', 'label_fr' => 'Porte du Sahara', 'value' => 'Douz'],
                    ['icon' => 'droplets', 'label_en' => 'Hot springs', 'label_fr' => 'Sources chaudes', 'value' => 'Ksar Ghilane'],
                    ['icon' => 'calendar', 'label_en' => 'Best season', 'label_fr' => 'Meilleure saison', 'value' => 'Oct — Avr'],
                    ['icon' => 'film', 'label_en' => 'Star Wars', 'label_fr' => 'Star Wars', 'value' => 'Mos Espa'],
                ],
                'points_of_interest' => [
                    ['name_en' => 'Ksar Ghilane', 'name_fr' => 'Ksar Ghilane', 'description_en' => "Tunisia's most remote desert oasis, featuring a natural sulfur-rich hot spring flowing year-round, ruins of the ancient Roman fort Tisavar, and comfortable desert camps. A 4x4 adventure through shifting dunes leads to this magical haven on the edge of the Grand Erg Oriental.", 'description_fr' => "L'oasis la plus reculée de Tunisie, avec une source chaude naturelle riche en soufre coulant toute l'année, les ruines de l'ancien fort romain Tisavar et des camps de désert confortables. Une aventure en 4x4 à travers les dunes mouvantes mène à ce havre magique en bordure du Grand Erg Oriental."],
                    ['name_en' => 'Chott el Jerid', 'name_fr' => 'Chott el Jerid', 'description_en' => "The largest salt lake in the Sahara at over 7,000 km² — a vast, otherworldly expanse that creates spectacular mirages and changes color with the seasons. Star Wars' Lars Homestead was filmed on its northwestern shore near Nefta.", 'description_fr' => "Le plus grand lac salé du Sahara avec plus de 7 000 km² — une vaste étendue d'un autre monde qui crée des mirages spectaculaires et change de couleur au fil des saisons. La ferme des Lars de Star Wars a été filmée sur sa rive nord-ouest près de Nefta."],
                    ['name_en' => 'Douz', 'name_fr' => 'Douz', 'description_en' => 'The "Gateway to the Sahara" and host of the legendary International Festival of the Sahara every December since 1910. This vibrant town is the starting point for camel excursions, dune buggy rides, sand skiing, and hot air balloon flights over the desert.', 'description_fr' => 'La « Porte du Sahara » et hôte du légendaire Festival International du Sahara chaque décembre depuis 1910. Cette ville vibrante est le point de départ pour des excursions en chameau, balades en buggy, ski sur sable et vols en montgolfière au-dessus du désert.'],
                    ['name_en' => 'Tozeur & Chebika', 'name_fr' => 'Tozeur & Chebika', 'description_en' => "Tozeur boasts over 200,000 date palms and serves as a gateway to the stunning mountain oases of Chebika and Tamerza, with their cascading waterfalls and canyon scenery. Visit the Dar Cherait Museum and explore Star Wars' Mos Espa set just north of neighboring Nefta.", 'description_fr' => "Tozeur compte plus de 200 000 palmiers dattiers et sert de porte d'entrée vers les superbes oasis de montagne de Chebika et Tamerza, avec leurs cascades et leurs paysages de canyon. Visitez le Musée Dar Cherait et explorez le décor de Mos Espa de Star Wars juste au nord de la voisine Nefta."],
                ],
            ],
        ];
    }
}
