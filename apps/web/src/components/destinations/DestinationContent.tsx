'use client';

import { motion, type Variants } from 'framer-motion';
import { useTranslations } from 'next-intl';
import Image from 'next/image';
import Link from 'next/link';
import { useState, useEffect } from 'react';
import {
  Waves,
  Landmark,
  UtensilsCrossed,
  Mountain,
  Users,
  Eye,
  Compass,
  Moon,
  TreePalm,
  Sparkles,
  Map,
  Tent,
  Palette,
  ShoppingBag,
  Bird,
  Home,
  Film,
  Droplets,
  Footprints,
  Layers,
  MapPin,
  Calendar,
  Ruler,
  Star,
  Info,
} from 'lucide-react';
import { ListingCard } from '@/components/molecules/ListingCard';
import { DestinationMapSection } from '@/components/maps/DestinationMapSection';
import type { Locale } from '@/i18n/routing';
import type { ListingSummary } from '@go-adventure/schemas';

// --- Types ---

interface Location {
  id: string;
  name: string;
  slug: string;
  description: string | null;
  latitude: number | null;
  longitude: number | null;
  imageUrl: string | null;
  listingsCount: number;
  city: string | null;
  region: string | null;
  country: string;
}

interface CmsHighlight {
  icon: string;
  title_en: string;
  title_fr: string;
  description_en: string;
  description_fr: string;
}

interface CmsKeyFact {
  icon: string;
  label_en: string;
  label_fr: string;
  value: string;
}

interface CmsGalleryImage {
  image: string;
  alt_en: string;
  alt_fr: string;
  caption_en?: string;
  caption_fr?: string;
}

interface CmsPointOfInterest {
  name_en: string;
  name_fr: string;
  description_en: string;
  description_fr: string;
}

interface CmsDestination {
  id: string;
  name: string;
  description_en: string;
  description_fr: string;
  image: string;
  link?: string;
  seo_title_en?: string;
  seo_title_fr?: string;
  seo_description_en?: string;
  seo_description_fr?: string;
  seo_text_en?: string;
  seo_text_fr?: string;
  highlights?: CmsHighlight[];
  key_facts?: CmsKeyFact[];
  gallery?: CmsGalleryImage[];
  points_of_interest?: CmsPointOfInterest[];
}

// --- Icon string → component mapping (CMS stores icon names as strings) ---

const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
  waves: Waves,
  landmark: Landmark,
  mountain: Mountain,
  compass: Compass,
  users: Users,
  eye: Eye,
  moon: Moon,
  'tree-palm': TreePalm,
  sparkles: Sparkles,
  map: Map,
  tent: Tent,
  palette: Palette,
  'shopping-bag': ShoppingBag,
  bird: Bird,
  home: Home,
  film: Film,
  droplets: Droplets,
  footprints: Footprints,
  layers: Layers,
  'map-pin': MapPin,
  calendar: Calendar,
  ruler: Ruler,
  star: Star,
  'utensils-crossed': UtensilsCrossed,
  info: Info,
};

interface DestinationContentProps {
  locale: string;
  slug: string;
  cmsDestination: CmsDestination | null;
  location: Location | null;
  listings: ListingSummary[];
}

// --- Highlights Data (6 per destination) ---

interface Highlight {
  icon: React.ComponentType<{ className?: string }>;
  titleEn: string;
  titleFr: string;
  descEn: string;
  descFr: string;
}

const destinationHighlights: Record<string, Highlight[]> = {
  djerba: [
    {
      icon: Waves,
      titleEn: 'Beaches & Turquoise Waters',
      titleFr: 'Plages & Eaux Turquoise',
      descEn:
        'Over 20 km of golden sand beaches fringed with palm trees along crystal-clear Mediterranean waters. From the lively Sidi Mahrez beach to the secluded coves of Sidi Jmour, Djerba offers some of the finest coastal stretches in North Africa.',
      descFr:
        "Plus de 20 km de plages de sable doré bordées de palmiers le long des eaux cristallines de la Méditerranée. De la plage animée de Sidi Mahrez aux criques isolées de Sidi Jmour, Djerba offre parmi les plus beaux littoraux d'Afrique du Nord.",
    },
    {
      icon: Landmark,
      titleEn: 'UNESCO Heritage & El Ghriba',
      titleFr: 'Patrimoine UNESCO & La Ghriba',
      descEn:
        'Designated a UNESCO World Heritage Site in 2023, Djerba is home to the El Ghriba Synagogue — over 2,600 years old and one of the oldest in the world. The island blends Berber, Arab, Jewish, and Mediterranean cultural influences across centuries of coexistence.',
      descFr:
        "Inscrite au patrimoine mondial de l'UNESCO en 2023, Djerba abrite la synagogue de la Ghriba — vieille de plus de 2 600 ans, l'une des plus anciennes au monde. L'île mêle influences berbères, arabes, juives et méditerranéennes à travers des siècles de coexistence.",
    },
    {
      icon: Palette,
      titleEn: 'Djerbahood Street Art',
      titleFr: 'Street Art Djerbahood',
      descEn:
        'The village of Erriadh has been transformed into an extraordinary open-air museum: over 250 murals created by 150 international artists using 4,500+ spray paint cans. A unique fusion of contemporary art and traditional Djerbian architecture.',
      descFr:
        "Le village d'Erriadh a été transformé en un extraordinaire musée à ciel ouvert : plus de 250 fresques créées par 150 artistes internationaux utilisant plus de 4 500 bombes de peinture. Une fusion unique entre art contemporain et architecture djerbienne traditionnelle.",
    },
    {
      icon: ShoppingBag,
      titleEn: 'Houmt Souk Markets',
      titleFr: 'Marchés de Houmt Souk',
      descEn:
        "Djerba's main town has been a thriving trading port since Roman times. Wander through its labyrinthine souks filled with handwoven textiles, silver jewelry, and spices, then visit the 15th-century Borj El Kebir fortress overlooking the fishing harbor.",
      descFr:
        "La ville principale de Djerba est un port commercial florissant depuis l'époque romaine. Flânez dans ses souks labyrinthiques remplis de textiles tissés à la main, bijoux en argent et épices, puis visitez la forteresse Borj El Kebir du XVe siècle surplombant le port de pêche.",
    },
    {
      icon: UtensilsCrossed,
      titleEn: 'Island Gastronomy',
      titleFr: 'Gastronomie Insulaire',
      descEn:
        'Savor Djerba\'s unique culinary heritage: fresh-caught Mediterranean seafood, the iconic couscous au poisson, stone-pressed Djerbian olive oil, and traditional "ftayer" pastries. Each dish reflects the island\'s multicultural history.',
      descFr:
        "Savourez l'héritage culinaire unique de Djerba : fruits de mer frais de la Méditerranée, l'emblématique couscous au poisson, huile d'olive djerbienne pressée à la pierre et pâtisseries traditionnelles « ftayer ». Chaque plat reflète l'histoire multiculturelle de l'île.",
    },
    {
      icon: Bird,
      titleEn: 'Flamingo Island',
      titleFr: "L'Île aux Flamants Roses",
      descEn:
        'The Ras Rmel peninsula, known as "Flamingo Island," becomes a spectacular natural sanctuary from October to February when hundreds of pink flamingos migrate to wade in the surrounding lagoon — a breathtaking sight accessible by boat from Houmt Souk.',
      descFr:
        "La péninsule de Ras Rmel, connue sous le nom d'« Île aux Flamants Roses », devient un sanctuaire naturel spectaculaire d'octobre à février lorsque des centaines de flamants roses migrent pour se poser dans la lagune environnante — un spectacle à couper le souffle accessible en bateau depuis Houmt Souk.",
    },
  ],
  dhaher: [
    {
      icon: Mountain,
      titleEn: 'The Great Dahar Crossing',
      titleFr: 'La Grande Traversée du Dahar',
      descEn:
        'The Great Dahar Crossing is a 194 km hiking route across 12 stages through the remote highlands of southern Tunisia. Starting from Tamazrat, the trail winds through stony desert, over ochre-coloured peaks, through ancient Amazigh villages surrounded by olive groves and date palms.',
      descFr:
        "La Grande Traversée du Dahar est un itinéraire de randonnée de 194 km en 12 étapes à travers les hauts plateaux reculés du sud tunisien. Partant de Tamazrat, le sentier serpente à travers le désert pierreux, par-dessus des pics ocre, à travers d'anciens villages amazighs entourés d'oliviers et de palmiers dattiers.",
    },
    {
      icon: Home,
      titleEn: 'Chenini & Douiret',
      titleFr: 'Chenini & Douiret',
      descEn:
        'Two iconic hilltop Berber villages perched on dramatic rock formations just 18 km from Tataouine. Trek the ancient path connecting them in 2.5 hours. Sleep in traditional troglodyte cave-rooms at family-run gîtes in Douiret, and discover the ancient white mosque crowning Chenini.',
      descFr:
        "Deux villages berbères emblématiques perchés sur des formations rocheuses spectaculaires à seulement 18 km de Tataouine. Parcourez l'ancien sentier qui les relie en 2h30. Dormez dans des chambres troglodytiques traditionnelles dans des gîtes familiaux à Douiret, et découvrez l'ancienne mosquée blanche qui couronne Chenini.",
    },
    {
      icon: Users,
      titleEn: 'Amazigh Heritage',
      titleFr: 'Patrimoine Amazigh',
      descEn:
        "The Dahar is the heartland of Tunisia's indigenous Amazigh (Berber) people. Discover underground troglodyte dwellings carved into rock faces — a testament to humanity's harmonious relationship with nature. Experience the warm hospitality of communities preserving traditions passed down through millennia.",
      descFr:
        "Le Dahar est le berceau du peuple amazigh (berbère) autochtone de Tunisie. Découvrez des habitations troglodytiques souterraines creusées dans la roche — un témoignage de la relation harmonieuse de l'humanité avec la nature. Vivez l'hospitalité chaleureuse de communautés préservant des traditions transmises depuis des millénaires.",
    },
    {
      icon: Film,
      titleEn: 'Ksour & Star Wars',
      titleFr: 'Ksour & Star Wars',
      descEn:
        'The fortified granaries (ksour) of the Dahar region served as iconic Star Wars filming locations. Ksar Hadada and Ksar Ouled Soltane — with their multi-story ghorfas — became the villages of Tatooine. The planet itself was named after the real town of Tataouine. Chenini, Ghomrassen, and Guermessa also appear as moon names in the saga.',
      descFr:
        'Les greniers fortifiés (ksour) de la région du Dahar ont servi de décors emblématiques pour Star Wars. Ksar Hadada et Ksar Ouled Soltane — avec leurs ghorfas à plusieurs étages — sont devenus les villages de Tatooine. La planète elle-même porte le nom de la vraie ville de Tataouine. Chenini, Ghomrassen et Guermessa apparaissent également comme noms de lunes dans la saga.',
    },
    {
      icon: Eye,
      titleEn: 'Geological Wonders',
      titleFr: 'Merveilles Géologiques',
      descEn:
        'The Dahar region is a geological treasure trove: discover dinosaur footprints near Ghomrassen, ancient cave paintings, and dramatic gorges carved by millennia of erosion. The ochre, rose, and amber rock formations create landscapes that feel like another planet.',
      descFr:
        "La région du Dahar est un trésor géologique : découvrez des empreintes de dinosaures près de Ghomrassen, des peintures rupestres anciennes et des gorges spectaculaires creusées par des millénaires d'érosion. Les formations rocheuses ocre, rose et ambrées créent des paysages qui semblent venir d'une autre planète.",
    },
    {
      icon: Compass,
      titleEn: 'Panoramic Summits',
      titleFr: 'Sommets Panoramiques',
      descEn:
        'Scale the rugged peaks of the Dahar for breathtaking 360° panoramas over vast arid valleys, ancient stone villages, and the distant shimmer of the Sahara. The citadel villages of Guermessa and Ras El Oued offer some of the most spectacular viewpoints in all of Tunisia.',
      descFr:
        "Gravissez les pics escarpés du Dahar pour des panoramas à 360° époustouflants sur de vastes vallées arides, d'anciens villages de pierre et le scintillement lointain du Sahara. Les villages-citadelles de Guermessa et Ras El Oued offrent parmi les points de vue les plus spectaculaires de toute la Tunisie.",
    },
  ],
  desert: [
    {
      icon: Compass,
      titleEn: 'Douz: Gateway to the Sahara',
      titleFr: 'Douz : Porte du Sahara',
      descEn:
        'Known as the "Gateway to the Sahara," Douz is where the dunes begin. Every December, thousands flock to the International Festival of the Sahara — a celebration of nomadic heritage since 1910 featuring camel races, traditional music, and Bedouin culture. From here, embark on dune excursions, sand skiing, and hot air balloon rides.',
      descFr:
        "Connue comme la « Porte du Sahara », Douz est là où les dunes commencent. Chaque décembre, des milliers de personnes affluent au Festival International du Sahara — une célébration du patrimoine nomade depuis 1910 avec courses de chameaux, musique traditionnelle et culture bédouine. D'ici, partez pour des excursions dans les dunes, du ski sur sable et des vols en montgolfière.",
    },
    {
      icon: Droplets,
      titleEn: 'Ksar Ghilane Oasis',
      titleFr: 'Oasis de Ksar Ghilane',
      descEn:
        "Tunisia's most southerly oasis, on the edge of the Grand Erg Oriental. Bathe in a natural sulfur-rich hot spring that flows year-round in the heart of the desert. Nearby stand the ruins of Tisavar, an ancient Roman fort. Luxury desert camps offer comfortable tented accommodation under the stars.",
      descFr:
        "L'oasis la plus méridionale de Tunisie, en bordure du Grand Erg Oriental. Baignez-vous dans une source chaude naturelle riche en soufre qui coule toute l'année au cœur du désert. À proximité se dressent les ruines de Tisavar, un ancien fort romain. Des camps de luxe offrent un hébergement confortable sous les étoiles.",
    },
    {
      icon: Footprints,
      titleEn: 'Camel Treks & Dune Expeditions',
      titleFr: 'Méharées & Expéditions dans les Dunes',
      descEn:
        'Cross the towering golden dunes of the Grand Erg Oriental on unforgettable camel expeditions. From half-day sunset rides to multi-day desert crossings, feel the ancient rhythm of the Sahara. 4x4 adventures, quad biking, and sandboarding are also available for thrill-seekers.',
      descFr:
        "Traversez les imposantes dunes dorées du Grand Erg Oriental lors d'expéditions chamelières inoubliables. Des balades au coucher du soleil aux traversées de plusieurs jours, ressentez le rythme ancestral du Sahara. Aventures en 4x4, quad et sandboard sont également disponibles pour les amateurs de sensations fortes.",
    },
    {
      icon: Layers,
      titleEn: 'Chott el Jerid Salt Lake',
      titleFr: 'Lac Salé Chott el Jerid',
      descEn:
        "The largest salt lake in the Sahara at over 7,000 km² — 1.5x bigger than the Great Salt Lake of Utah. This vast, surreal landscape creates stunning mirages and changes color with the seasons, from brilliant white to shimmering pink and violet. Star Wars' Lars Homestead was filmed on its northwestern edge near Nefta.",
      descFr:
        "Le plus grand lac salé du Sahara avec plus de 7 000 km² — 1,5 fois plus grand que le Grand Lac Salé de l'Utah. Ce vaste paysage surréaliste crée des mirages époustouflants et change de couleur au fil des saisons, du blanc éclatant au rose et violet chatoyants. La ferme des Lars dans Star Wars a été filmée sur son bord nord-ouest près de Nefta.",
    },
    {
      icon: Moon,
      titleEn: 'Starlit Desert Camps',
      titleFr: 'Camps sous les Étoiles',
      descEn:
        'Spend magical nights under the clearest skies on Earth in traditional desert bivouacs. With zero light pollution, the Milky Way blazes overhead while you enjoy campfire stories, Bedouin tea ceremonies, and traditional desert cuisine. Luxury tented camps offer real beds and all comforts amidst the wilderness.',
      descFr:
        "Passez des nuits magiques sous les ciels les plus clairs de la planète dans des bivouacs désertiques traditionnels. Sans aucune pollution lumineuse, la Voie Lactée brille au-dessus de vous tandis que vous profitez d'histoires autour du feu, de cérémonies de thé bédouin et de cuisine traditionnelle du désert. Des camps de luxe offrent de vrais lits et tout le confort au cœur de la nature sauvage.",
    },
    {
      icon: TreePalm,
      titleEn: 'Tozeur & Nefta Oases',
      titleFr: 'Oasis de Tozeur & Nefta',
      descEn:
        'Explore the lush palm groves of Tozeur, home to over 200,000 date palms and the stunning mountain oasis of Chebika and Tamerza. Nearby Nefta, known as the "little Kairouan" for its many mosques, features the famous Corbeille — a natural amphitheater filled with palms. Star Wars\' Mos Espa set lies just north of Nefta.',
      descFr:
        'Explorez les palmeraies luxuriantes de Tozeur, abritant plus de 200 000 palmiers dattiers et les superbes oasis de montagne de Chebika et Tamerza. Nefta, surnommée la « petite Kairouan » pour ses nombreuses mosquées, abrite la célèbre Corbeille — un amphithéâtre naturel rempli de palmiers. Le décor de Mos Espa de Star Wars se trouve juste au nord de Nefta.',
    },
  ],
};

const fallbackHighlights: Highlight[] = [
  {
    icon: Sparkles,
    titleEn: 'Natural Wonders',
    titleFr: 'Merveilles Naturelles',
    descEn: 'Discover breathtaking landscapes and untouched natural beauty.',
    descFr: 'Découvrez des paysages à couper le souffle et une beauté naturelle préservée.',
  },
  {
    icon: Landmark,
    titleEn: 'Rich Culture',
    titleFr: 'Culture Riche',
    descEn: 'Immerse yourself in centuries of tradition, art, and local heritage.',
    descFr: "Plongez dans des siècles de tradition, d'art et de patrimoine local.",
  },
  {
    icon: Map,
    titleEn: 'Epic Adventures',
    titleFr: 'Aventures Épiques',
    descEn: 'Unforgettable experiences that will create memories for a lifetime.',
    descFr: 'Des expériences inoubliables qui créeront des souvenirs pour toute une vie.',
  },
];

// --- Key Facts Data ---

interface KeyFact {
  icon: React.ComponentType<{ className?: string }>;
  labelEn: string;
  labelFr: string;
  value: string;
}

const destinationFacts: Record<string, KeyFact[]> = {
  djerba: [
    { icon: Ruler, labelEn: 'Area', labelFr: 'Superficie', value: '514 km²' },
    { icon: Star, labelEn: 'UNESCO', labelFr: 'UNESCO', value: '2023' },
    { icon: Users, labelEn: 'Population', labelFr: 'Population', value: '~163 000' },
    { icon: Calendar, labelEn: 'Best season', labelFr: 'Meilleure saison', value: 'Avr — Oct' },
    { icon: Waves, labelEn: 'Beaches', labelFr: 'Plages', value: '20+' },
  ],
  dhaher: [
    { icon: Ruler, labelEn: 'Trail length', labelFr: 'Longueur du sentier', value: '194 km' },
    { icon: Mountain, labelEn: 'Stages', labelFr: 'Étapes', value: '12' },
    { icon: Home, labelEn: 'Villages', labelFr: 'Villages', value: '5+' },
    { icon: Calendar, labelEn: 'Best season', labelFr: 'Meilleure saison', value: 'Oct — Avr' },
    { icon: Film, labelEn: 'Star Wars sites', labelFr: 'Sites Star Wars', value: '3+' },
  ],
  desert: [
    { icon: Layers, labelEn: 'Salt lake', labelFr: 'Lac salé', value: '7 000 km²' },
    { icon: MapPin, labelEn: 'Gateway', labelFr: 'Porte du Sahara', value: 'Douz' },
    { icon: Droplets, labelEn: 'Hot springs', labelFr: 'Sources chaudes', value: 'Ksar Ghilane' },
    { icon: Calendar, labelEn: 'Best season', labelFr: 'Meilleure saison', value: 'Oct — Avr' },
    { icon: Film, labelEn: 'Star Wars', labelFr: 'Star Wars', value: 'Mos Espa' },
  ],
};

// --- Photo Gallery Data ---

interface GalleryImage {
  src: string;
  altEn: string;
  altFr: string;
  captionEn: string;
  captionFr: string;
}

const destinationGallery: Record<string, GalleryImage[]> = {
  djerba: [
    {
      src: '/images/destinations/djerba.jpg',
      altEn: 'Djerba sunset with traditional boat',
      altFr: 'Coucher de soleil à Djerba avec bateau traditionnel',
      captionEn: 'Golden sunset over the Djerba coast',
      captionFr: 'Coucher de soleil doré sur la côte de Djerba',
    },
    {
      src: '/images/destinations/houmet-souk.jpg',
      altEn: 'Traditional artisan in Houmt Souk',
      altFr: 'Artisan traditionnel à Houmt Souk',
      captionEn: 'Master artisan at work in Houmt Souk',
      captionFr: 'Maître artisan au travail à Houmt Souk',
    },
    {
      src: '/images/destinations/guellala.jpg',
      altEn: 'Heritage architecture in Guellala, Djerba',
      altFr: 'Architecture patrimoniale à Guellala, Djerba',
      captionEn: 'Historic church in the village of Guellala',
      captionFr: 'Église historique dans le village de Guellala',
    },
    {
      src: '/images/destinations/ile-flamants-roses.jpg',
      altEn: 'Mediterranean coast near Flamingo Island, Djerba',
      altFr: "Côte méditerranéenne près de l'Île aux Flamants Roses, Djerba",
      captionEn: 'Rocky Mediterranean shores near Ras Rmel',
      captionFr: 'Rivages rocheux méditerranéens près de Ras Rmel',
    },
    {
      src: '/images/destinations/sidi-jmour.jpg',
      altEn: 'Mountain biking on Sidi Jmour trail, Djerba',
      altFr: 'VTT sur le sentier de Sidi Jmour, Djerba',
      captionEn: 'Cycling adventure through Sidi Jmour',
      captionFr: 'Aventure à vélo à travers Sidi Jmour',
    },
  ],
  dhaher: [
    {
      src: '/images/destinations/tataouine.jpg',
      altEn: 'Berber hilltop village in the Dahar region, Tunisia',
      altFr: 'Village berbère perché dans la région du Dahar, Tunisie',
      captionEn: 'Ancient Berber village carved into the Dahar hillside',
      captionFr: 'Village berbère ancien sculpté dans la colline du Dahar',
    },
  ],
  desert: [
    {
      src: '/images/destinations/douz.webp',
      altEn: 'Camel caravan crossing Sahara dunes near Douz, Tunisia',
      altFr: 'Caravane de chameaux traversant les dunes du Sahara près de Douz, Tunisie',
      captionEn: 'Camel caravan crossing the golden dunes of the Grand Erg Oriental',
      captionFr: 'Caravane de chameaux traversant les dunes dorées du Grand Erg Oriental',
    },
    {
      src: '/images/destinations/tozeur.jpg',
      altEn: 'Mountain oasis with palm trees near Tozeur, Tunisia',
      altFr: 'Oasis de montagne avec palmiers près de Tozeur, Tunisie',
      captionEn: 'Lush mountain oasis of Chebika near Tozeur',
      captionFr: 'Oasis de montagne luxuriante de Chebika près de Tozeur',
    },
  ],
};

// --- Must-See Places Data ---

interface PointOfInterest {
  nameEn: string;
  nameFr: string;
  descEn: string;
  descFr: string;
}

const destinationPOIs: Record<string, PointOfInterest[]> = {
  djerba: [
    {
      nameEn: 'El Ghriba Synagogue',
      nameFr: 'Synagogue de la Ghriba',
      descEn:
        'One of the oldest synagogues in the world, dating back over 2,600 years. An annual pilgrimage draws visitors from across the globe to this sacred site decorated with stunning blue ceramic tiles.',
      descFr:
        "L'une des plus anciennes synagogues au monde, datant de plus de 2 600 ans. Un pèlerinage annuel attire des visiteurs du monde entier vers ce site sacré décoré de magnifiques carreaux de céramique bleue.",
    },
    {
      nameEn: 'Djerbahood, Erriadh',
      nameFr: 'Djerbahood, Erriadh',
      descEn:
        'An open-air street art museum in the heart of Erriadh village. Over 250 murals by 150 artists from 30+ countries transform whitewashed walls into a breathtaking gallery that blends contemporary art with traditional architecture.',
      descFr:
        "Un musée de street art à ciel ouvert au cœur du village d'Erriadh. Plus de 250 fresques par 150 artistes de plus de 30 pays transforment les murs blanchis en une galerie à couper le souffle mêlant art contemporain et architecture traditionnelle.",
    },
    {
      nameEn: 'Houmt Souk & Borj El Kebir',
      nameFr: 'Houmt Souk & Borj El Kebir',
      descEn:
        "Djerba's vibrant capital since Roman times. Explore the labyrinthine souks, haggle for silver jewelry and handwoven textiles, visit the 15th-century fortress, and watch the colorful fishing boats in the historic harbor.",
      descFr:
        "La vibrante capitale de Djerba depuis l'époque romaine. Explorez les souks labyrinthiques, marchandez bijoux en argent et textiles tissés à la main, visitez la forteresse du XVe siècle et admirez les bateaux de pêche colorés dans le port historique.",
    },
    {
      nameEn: 'Guellala Pottery Village',
      nameFr: 'Village des Potiers de Guellala',
      descEn:
        'Home to nearly 500 potters who have worked with clay since Roman times. Visit underground workshops where artisans craft jars, vases, and decorative pieces using techniques unchanged for centuries.',
      descFr:
        "Foyer de près de 500 potiers qui travaillent l'argile depuis l'époque romaine. Visitez des ateliers souterrains où les artisans façonnent jarres, vases et pièces décoratives selon des techniques inchangées depuis des siècles.",
    },
  ],
  dhaher: [
    {
      nameEn: 'Chenini',
      nameFr: 'Chenini',
      descEn:
        'A spectacular hilltop Berber village 18 km from Tataouine, crowned by an ancient white mosque. Its troglodyte dwellings cascade down the rocky hillside, offering panoramic views across the Dahar landscape. Considered a Star Wars scouting location for Mos Espa.',
      descFr:
        "Un spectaculaire village berbère perché à 18 km de Tataouine, couronné par une ancienne mosquée blanche. Ses habitations troglodytiques s'étagent le long de la colline rocheuse, offrant des vues panoramiques sur le paysage du Dahar. Considéré comme lieu de repérage Star Wars pour Mos Espa.",
    },
    {
      nameEn: 'Douiret',
      nameFr: 'Douiret',
      descEn:
        'A stunning mountaintop village offering a unique experience: sleep in traditional troglodyte cave-rooms at family-run gîtes. Connected to Chenini by an ancient 2.5-hour walking path that crosses dramatic desert landscapes.',
      descFr:
        "Un superbe village au sommet d'une montagne offrant une expérience unique : dormir dans des chambres troglodytiques traditionnelles dans des gîtes familiaux. Relié à Chenini par un ancien sentier de 2h30 qui traverse des paysages désertiques spectaculaires.",
    },
    {
      nameEn: 'Ksar Ouled Soltane',
      nameFr: 'Ksar Ouled Soltane',
      descEn:
        'An iconic 4-story fortified granary (ksar) with perfectly preserved multi-level ghorfas (storage rooms). This magnificent structure served as a Star Wars filming set for the slave quarters on Tatooine and remains one of the most photogenic sites in Tunisia.',
      descFr:
        "Un ksar emblématique à 4 étages avec des ghorfas (salles de stockage) à plusieurs niveaux parfaitement conservées. Cette magnifique structure a servi de décor Star Wars pour les quartiers des esclaves sur Tatooine et reste l'un des sites les plus photogéniques de Tunisie.",
    },
    {
      nameEn: 'Ghomrassen',
      nameFr: 'Ghomrassen',
      descEn:
        'Home to some of the most remarkable geological discoveries in the region, including dinosaur footprints and ancient cave paintings. The town serves as a base for exploring the surrounding geological wonders of the Dahar.',
      descFr:
        'Foyer de certaines des découvertes géologiques les plus remarquables de la région, notamment des empreintes de dinosaures et des peintures rupestres anciennes. La ville sert de base pour explorer les merveilles géologiques environnantes du Dahar.',
    },
  ],
  desert: [
    {
      nameEn: 'Ksar Ghilane',
      nameFr: 'Ksar Ghilane',
      descEn:
        "Tunisia's most remote desert oasis, featuring a natural sulfur-rich hot spring flowing year-round, ruins of the ancient Roman fort Tisavar, and comfortable desert camps. A 4x4 adventure through shifting dunes leads to this magical haven on the edge of the Grand Erg Oriental.",
      descFr:
        "L'oasis la plus reculée de Tunisie, avec une source chaude naturelle riche en soufre coulant toute l'année, les ruines de l'ancien fort romain Tisavar et des camps de désert confortables. Une aventure en 4x4 à travers les dunes mouvantes mène à ce havre magique en bordure du Grand Erg Oriental.",
    },
    {
      nameEn: 'Chott el Jerid',
      nameFr: 'Chott el Jerid',
      descEn:
        "The largest salt lake in the Sahara at over 7,000 km² — a vast, otherworldly expanse that creates spectacular mirages and changes color with the seasons. Star Wars' Lars Homestead was filmed on its northwestern shore near Nefta.",
      descFr:
        "Le plus grand lac salé du Sahara avec plus de 7 000 km² — une vaste étendue d'un autre monde qui crée des mirages spectaculaires et change de couleur au fil des saisons. La ferme des Lars de Star Wars a été filmée sur sa rive nord-ouest près de Nefta.",
    },
    {
      nameEn: 'Douz',
      nameFr: 'Douz',
      descEn:
        'The "Gateway to the Sahara" and host of the legendary International Festival of the Sahara every December since 1910. This vibrant town is the starting point for camel excursions, dune buggy rides, sand skiing, and hot air balloon flights over the desert.',
      descFr:
        'La « Porte du Sahara » et hôte du légendaire Festival International du Sahara chaque décembre depuis 1910. Cette ville vibrante est le point de départ pour des excursions en chameau, balades en buggy, ski sur sable et vols en montgolfière au-dessus du désert.',
    },
    {
      nameEn: 'Tozeur & Chebika',
      nameFr: 'Tozeur & Chebika',
      descEn:
        "Tozeur boasts over 200,000 date palms and serves as a gateway to the stunning mountain oases of Chebika and Tamerza, with their cascading waterfalls and canyon scenery. Visit the Dar Cherait Museum and explore Star Wars' Mos Espa set just north of neighboring Nefta.",
      descFr:
        "Tozeur compte plus de 200 000 palmiers dattiers et sert de porte d'entrée vers les superbes oasis de montagne de Chebika et Tamerza, avec leurs cascades et leurs paysages de canyon. Visitez le Musée Dar Cherait et explorez le décor de Mos Espa de Star Wars juste au nord de la voisine Nefta.",
    },
  ],
};

// --- SEO Long Descriptions ---

const destinationSeoText: Record<string, { en: string; fr: string }> = {
  djerba: {
    en: 'Djerba, the largest island in North Africa at 514 km², has been a crossroads of civilizations for over 3,000 years. Known to ancient geographers as the "Land of the Lotus Eaters" from Homer\'s Odyssey, the island earned UNESCO World Heritage status in 2023 for its exceptional testimony to a settlement pattern of an island territory, reflecting the coexistence of diverse religious and cultural communities. Today, Djerba enchants visitors with its golden sand beaches stretching over 20 km, the mesmerizing Djerbahood street art village with 250+ murals, the ancient El Ghriba Synagogue dating back 2,600 years, and the bustling souks of Houmt Souk where artisan traditions have continued since Roman times. From kitesurfing and scuba diving to pottery workshops in Guellala and flamingo watching at Ras Rmel, Djerba offers a uniquely diverse island experience in the heart of the Mediterranean.',
    fr: "Djerba, la plus grande île d'Afrique du Nord avec ses 514 km², est un carrefour de civilisations depuis plus de 3 000 ans. Connue des géographes anciens comme la « Terre des Lotophages » de l'Odyssée d'Homère, l'île a obtenu le statut de patrimoine mondial de l'UNESCO en 2023 pour son témoignage exceptionnel d'un modèle d'implantation sur un territoire insulaire, reflétant la coexistence de communautés religieuses et culturelles diverses. Aujourd'hui, Djerba enchante les visiteurs avec ses plages de sable doré s'étendant sur plus de 20 km, le fascinant village de street art Djerbahood avec plus de 250 fresques, l'ancienne synagogue de la Ghriba vieille de 2 600 ans, et les souks animés de Houmt Souk où les traditions artisanales perdurent depuis l'époque romaine. Du kitesurf et de la plongée sous-marine aux ateliers de poterie à Guellala et à l'observation des flamants roses à Ras Rmel, Djerba offre une expérience insulaire d'une diversité unique au cœur de la Méditerranée.",
  },
  dhaher: {
    en: "The Dahar region is southern Tunisia's best-kept secret — a vast, rugged highland stretching from Matmata to Tataouine, home to some of the most extraordinary landscapes and cultural heritage in North Africa. The indigenous Amazigh (Berber) people have inhabited these mountains for millennia, carving spectacular troglodyte dwellings into the rock and building fortified granaries (ksour) to protect their harvests. The newly established Great Dahar Crossing — a 194 km hiking trail across 12 stages — reveals ancient villages like Chenini and Douiret perched dramatically on hilltops, dinosaur footprints near Ghomrassen, and the iconic ksour that inspired George Lucas to name his fictional planet 'Tatooine' after the real city of Tataouine. This is authentic Tunisia: untouched by mass tourism, rich in heritage, and breathtaking in its raw natural beauty.",
    fr: "La région du Dahar est le secret le mieux gardé du sud tunisien — un vaste plateau escarpé s'étendant de Matmata à Tataouine, abritant certains des paysages et patrimoines culturels les plus extraordinaires d'Afrique du Nord. Le peuple autochtone amazigh (berbère) habite ces montagnes depuis des millénaires, creusant des habitations troglodytiques spectaculaires dans la roche et construisant des greniers fortifiés (ksour) pour protéger leurs récoltes. La Grande Traversée du Dahar nouvellement établie — un sentier de randonnée de 194 km en 12 étapes — révèle des villages anciens comme Chenini et Douiret perchés de manière spectaculaire sur des collines, des empreintes de dinosaures près de Ghomrassen, et les ksour emblématiques qui ont inspiré George Lucas pour nommer sa planète fictive « Tatooine » d'après la vraie ville de Tataouine. C'est la Tunisie authentique : préservée du tourisme de masse, riche en patrimoine et époustouflante par sa beauté naturelle brute.",
  },
  desert: {
    en: "The Tunisian Sahara is a land of extraordinary contrasts — from the towering golden dunes of the Grand Erg Oriental to the surreal salt flats of Chott el Jerid (the largest salt lake in the Sahara at 7,000 km²), from the lush palm oases of Tozeur with its 200,000 date palms to the remote hot springs of Ksar Ghilane. This is where ancient caravan routes once connected sub-Saharan Africa to the Mediterranean, and where the International Festival of the Sahara has celebrated nomadic Bedouin heritage in Douz every December since 1910. George Lucas chose this landscape for some of Star Wars' most iconic scenes — the Lars Homestead and Mos Espa sets still stand near Nefta and Chott el Jerid. Today, adventurers can explore the desert on camel treks, 4x4 expeditions, quad bikes, hot air balloons, and even sand skiing, then retire to luxury desert camps under some of the clearest night skies on Earth.",
    fr: "Le Sahara tunisien est une terre de contrastes extraordinaires — des imposantes dunes dorées du Grand Erg Oriental aux étendues surréalistes de sel du Chott el Jerid (le plus grand lac salé du Sahara avec 7 000 km²), des oasis luxuriantes de palmiers de Tozeur avec ses 200 000 dattiers aux sources chaudes reculées de Ksar Ghilane. C'est ici que d'anciennes routes caravanières reliaient autrefois l'Afrique subsaharienne à la Méditerranée, et où le Festival International du Sahara célèbre le patrimoine nomade bédouin à Douz chaque décembre depuis 1910. George Lucas a choisi ce paysage pour certaines des scènes les plus emblématiques de Star Wars — les décors de la ferme des Lars et de Mos Espa se dressent toujours près de Nefta et du Chott el Jerid. Aujourd'hui, les aventuriers peuvent explorer le désert en méharée, expéditions en 4x4, quad, montgolfière et même ski sur sable, puis se retirer dans des camps de luxe sous certains des ciels nocturnes les plus clairs de la planète.",
  },
};

// --- Animation Variants ---

const fadeUp: Variants = {
  hidden: { opacity: 0, y: 30 },
  visible: { opacity: 1, y: 0 },
};

const staggerContainer: Variants = {
  hidden: {},
  visible: {
    transition: { staggerChildren: 0.15 },
  },
};

const cardVariant: Variants = {
  hidden: { opacity: 0, y: 40 },
  visible: {
    opacity: 1,
    y: 0,
    transition: { duration: 0.5, ease: 'easeOut' },
  },
};

// --- Typewriter Hook ---

function useTypewriter(text: string, speed = 40, delay = 500) {
  const [displayed, setDisplayed] = useState('');
  const [started, setStarted] = useState(false);

  useEffect(() => {
    const delayTimer = setTimeout(() => setStarted(true), delay);
    return () => clearTimeout(delayTimer);
  }, [delay]);

  useEffect(() => {
    if (!started) return;
    if (displayed.length >= text.length) return;

    const timer = setTimeout(() => {
      setDisplayed(text.slice(0, displayed.length + 1));
    }, speed);
    return () => clearTimeout(timer);
  }, [started, displayed, text, speed]);

  return { displayed, isComplete: displayed.length >= text.length };
}

// --- Styles ---

const snakeLineCSS = `
@keyframes slideLine {
  0% { top: 0; left: 0; width: 0; height: 2px; }
  25% { top: 0; left: 0; width: 100%; height: 2px; }
  25.1% { top: 0; left: auto; right: 0; width: 2px; height: 0; }
  50% { top: 0; right: 0; width: 2px; height: 100%; }
  50.1% { top: auto; bottom: 0; right: 0; width: 0; height: 2px; }
  75% { bottom: 0; right: 0; width: 100%; height: 2px; }
  75.1% { bottom: 0; left: 0; width: 2px; height: 0; }
  100% { bottom: auto; top: 0; left: 0; width: 2px; height: 100%; }
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-20px); }
}

@keyframes slowSpin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
`;

// --- Slug Resolution ---
// CMS destination IDs may not match our data keys (e.g. "houmet-souk" → "djerba")
// This map resolves any CMS slug or name to the correct data key.

const slugToDataKey: Record<string, string> = {
  // Direct matches
  djerba: 'djerba',
  dhaher: 'dhaher',
  desert: 'desert',
  // CMS IDs (from admin panel Featured Destinations)
  'houmet-souk': 'djerba',
  guellala: 'dhaher', // CMS uses "guellala" id for Dhaher destination
  'lile-des-flamants-roses': 'desert', // CMS uses "lile-des-flamants-roses" id for Desert
};

// Also try matching by CMS destination name
function resolveDataKey(slug: string, cmsName?: string | null): string {
  if (slugToDataKey[slug]) return slugToDataKey[slug];
  // Try matching by name
  const lowerName = (cmsName ?? '').toLowerCase();
  if (lowerName.includes('djerba')) return 'djerba';
  if (lowerName.includes('dhaher') || lowerName.includes('dahar')) return 'dhaher';
  if (lowerName.includes('desert') || lowerName.includes('désert') || lowerName.includes('sahara'))
    return 'desert';
  return slug;
}

// --- Component ---

export function DestinationContent({
  locale,
  slug,
  cmsDestination,
  location,
  listings,
}: DestinationContentProps) {
  const t = useTranslations('destinations');
  const isFr = locale === 'fr';

  const displayName = cmsDestination?.name ?? location?.name ?? slug;
  const displayDescription = isFr
    ? (cmsDestination?.description_fr ?? location?.description)
    : (cmsDestination?.description_en ?? location?.description);
  const displayImage = cmsDestination?.image ?? location?.imageUrl;
  const locationMeta = location
    ? [location.city, location.region, location.country].filter(Boolean).join(', ')
    : null;
  const listingsCount = location?.listingsCount ?? listings.length;
  const center: [number, number] | undefined =
    location?.latitude && location?.longitude ? [location.latitude, location.longitude] : undefined;

  const dataKey = resolveDataKey(slug, cmsDestination?.name);

  // CMS data takes priority, hardcoded data serves as fallback
  const highlights: Highlight[] = cmsDestination?.highlights?.length
    ? cmsDestination.highlights.map((h) => ({
        icon: iconMap[h.icon] || Sparkles,
        titleEn: h.title_en,
        titleFr: h.title_fr,
        descEn: h.description_en,
        descFr: h.description_fr,
      }))
    : (destinationHighlights[dataKey] ?? fallbackHighlights);

  const facts: KeyFact[] | undefined = cmsDestination?.key_facts?.length
    ? cmsDestination.key_facts.map((f) => ({
        icon: iconMap[f.icon] || Info,
        labelEn: f.label_en,
        labelFr: f.label_fr,
        value: f.value,
      }))
    : destinationFacts[dataKey];

  const gallery: GalleryImage[] | undefined = cmsDestination?.gallery?.length
    ? cmsDestination.gallery.map((g) => ({
        src: g.image,
        altEn: g.alt_en,
        altFr: g.alt_fr,
        captionEn: g.caption_en ?? '',
        captionFr: g.caption_fr ?? '',
      }))
    : destinationGallery[dataKey];

  const pois: PointOfInterest[] | undefined = cmsDestination?.points_of_interest?.length
    ? cmsDestination.points_of_interest.map((p) => ({
        nameEn: p.name_en,
        nameFr: p.name_fr,
        descEn: p.description_en,
        descFr: p.description_fr,
      }))
    : destinationPOIs[dataKey];

  const seoText: { en: string; fr: string } | undefined =
    cmsDestination?.seo_text_en || cmsDestination?.seo_text_fr
      ? { en: cmsDestination.seo_text_en ?? '', fr: cmsDestination.seo_text_fr ?? '' }
      : destinationSeoText[dataKey];

  const comingSoonText = isFr ? 'Prêt pour l\u2019aventure ?' : 'Ready for Adventure?';
  const { displayed: typedHeading, isComplete: typingDone } = useTypewriter(
    comingSoonText,
    50,
    800
  );

  return (
    <>
      <style dangerouslySetInnerHTML={{ __html: snakeLineCSS }} />

      {/* ===== HERO SECTION ===== */}
      <section className="relative h-[500px] overflow-hidden">
        {displayImage ? (
          <motion.div
            className="absolute inset-0 bg-cover bg-center scale-105"
            style={{ backgroundImage: `url(${displayImage})` }}
            initial={{ scale: 1.1 }}
            animate={{ scale: 1.05 }}
            transition={{ duration: 8, ease: 'easeOut' }}
          />
        ) : (
          <div className="absolute inset-0 bg-gradient-to-br from-primary to-primary-light" />
        )}
        <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/40 to-black/20" />

        <div className="relative container mx-auto px-4 h-full flex flex-col justify-end pb-12">
          {/* Breadcrumb */}
          <motion.nav
            className="mb-6 text-white/60 text-sm flex items-center gap-2"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 0.2 }}
          >
            <Link href={`/${locale}`} className="hover:text-white transition-colors">
              {t('breadcrumb_home')}
            </Link>
            <span>/</span>
            <span className="text-white/80">{t('breadcrumb_destinations')}</span>
            <span>/</span>
            <span className="text-white">{displayName}</span>
          </motion.nav>

          <motion.h1
            className="text-4xl md:text-6xl font-bold text-white mb-4 font-display"
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.3 }}
          >
            {displayName}
          </motion.h1>

          {displayDescription && (
            <motion.p
              className="text-lg md:text-xl text-white/90 max-w-2xl"
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.5 }}
            >
              {displayDescription}
            </motion.p>
          )}

          <motion.div
            className="flex items-center gap-4 text-white/70 mt-4"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            transition={{ delay: 0.7 }}
          >
            {locationMeta && (
              <span className="flex items-center gap-2">
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                  />
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                </svg>
                {locationMeta}
              </span>
            )}
            {listingsCount > 0 && (
              <span className="flex items-center gap-2">
                <Tent className="w-4 h-4" />
                {listingsCount} {listingsCount === 1 ? 'experience' : 'experiences'}
              </span>
            )}
          </motion.div>
        </div>
      </section>

      {/* ===== KEY FACTS BAR ===== */}
      {facts && (
        <motion.section
          className="bg-primary/5 border-b border-primary/10"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          transition={{ delay: 0.8, duration: 0.5 }}
        >
          <div className="container mx-auto px-4 py-5">
            <div className="flex items-center gap-2 mb-3">
              <Info className="w-4 h-4 text-primary" />
              <h2 className="text-sm font-semibold text-primary uppercase tracking-wider">
                {t('key_facts')}
              </h2>
            </div>
            <div className="flex flex-wrap gap-4 md:gap-8">
              {facts.map((fact, i) => {
                const FactIcon = fact.icon;
                return (
                  <motion.div
                    key={i}
                    className="flex items-center gap-3 bg-white rounded-xl px-4 py-3 shadow-sm"
                    initial={{ opacity: 0, y: 10 }}
                    animate={{ opacity: 1, y: 0 }}
                    transition={{ delay: 0.9 + i * 0.1 }}
                  >
                    <FactIcon className="w-5 h-5 text-primary shrink-0" />
                    <div>
                      <p className="text-xs text-neutral-500">
                        {isFr ? fact.labelFr : fact.labelEn}
                      </p>
                      <p className="text-sm font-bold text-neutral-900">{fact.value}</p>
                    </div>
                  </motion.div>
                );
              })}
            </div>
          </div>
        </motion.section>
      )}

      {/* ===== HIGHLIGHTS SECTION (6 cards) ===== */}
      <section className="py-16 md:py-20">
        <div className="container mx-auto px-4">
          <motion.h2
            className="text-3xl md:text-4xl font-bold text-center mb-12 font-display"
            variants={fadeUp}
            initial="hidden"
            whileInView="visible"
            viewport={{ once: true, margin: '-50px' }}
            transition={{ duration: 0.5 }}
          >
            {t('highlights_title')}
          </motion.h2>

          <motion.div
            className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
            variants={staggerContainer}
            initial="hidden"
            whileInView="visible"
            viewport={{ once: true, margin: '-50px' }}
          >
            {highlights.map((h, i) => {
              const Icon = h.icon;
              return (
                <motion.div
                  key={i}
                  className="relative group rounded-2xl bg-white p-8 shadow-md hover:shadow-xl transition-shadow duration-300 overflow-hidden"
                  variants={cardVariant}
                >
                  {/* Snake line border */}
                  <div
                    className="absolute inset-0 rounded-2xl pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                    style={{
                      background: 'transparent',
                    }}
                  >
                    <div
                      className="absolute bg-primary rounded-full"
                      style={{
                        animation: 'slideLine 6s linear infinite',
                      }}
                    />
                  </div>

                  <div className="w-14 h-14 bg-primary/10 rounded-xl flex items-center justify-center mb-5 group-hover:bg-primary/20 transition-colors duration-300">
                    <Icon className="w-7 h-7 text-primary" />
                  </div>
                  <h3 className="text-xl font-bold mb-3 text-neutral-900">
                    {isFr ? h.titleFr : h.titleEn}
                  </h3>
                  <p className="text-neutral-600 leading-relaxed text-sm">
                    {isFr ? h.descFr : h.descEn}
                  </p>
                </motion.div>
              );
            })}
          </motion.div>
        </div>
      </section>

      {/* ===== PHOTO GALLERY SECTION ===== */}
      {gallery && gallery.length > 0 && (
        <section className="py-12 md:py-16 bg-neutral-50">
          <div className="container mx-auto px-4">
            <motion.h2
              className="text-3xl md:text-4xl font-bold text-center mb-10 font-display"
              variants={fadeUp}
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, margin: '-50px' }}
              transition={{ duration: 0.5 }}
            >
              {t('photo_gallery')}
            </motion.h2>

            <motion.div
              className={`grid gap-4 ${
                gallery.length === 1
                  ? 'grid-cols-1'
                  : gallery.length === 2
                    ? 'grid-cols-1 md:grid-cols-2'
                    : 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
              }`}
              variants={staggerContainer}
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, margin: '-30px' }}
            >
              {gallery.map((img, i) => (
                <motion.div
                  key={i}
                  className={`relative overflow-hidden rounded-2xl group ${
                    gallery.length === 1 ? 'h-[400px]' : 'h-[280px]'
                  } ${gallery.length >= 5 && i === 0 ? 'md:col-span-2 md:h-[350px]' : ''}`}
                  variants={cardVariant}
                >
                  <Image
                    src={img.src}
                    alt={isFr ? img.altFr : img.altEn}
                    fill
                    className="object-cover transition-transform duration-500 group-hover:scale-105"
                    sizes={
                      gallery.length === 1
                        ? '100vw'
                        : gallery.length === 2
                          ? '(max-width: 768px) 100vw, 50vw'
                          : '(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 33vw'
                    }
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                  <p className="absolute bottom-4 left-4 right-4 text-white text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity duration-300 translate-y-2 group-hover:translate-y-0">
                    {isFr ? img.captionFr : img.captionEn}
                  </p>
                </motion.div>
              ))}
            </motion.div>
          </div>
        </section>
      )}

      {/* ===== SEO DESCRIPTION SECTION (cream bg) ===== */}
      <motion.section
        className="py-16 bg-[#f5f0d1]"
        initial={{ opacity: 0 }}
        whileInView={{ opacity: 1 }}
        viewport={{ once: true, margin: '-50px' }}
        transition={{ duration: 0.6 }}
      >
        <div className="container mx-auto px-4 max-w-4xl">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true }}
            transition={{ duration: 0.5, delay: 0.2 }}
          >
            <h2 className="text-2xl md:text-3xl font-bold mb-8 font-display text-neutral-900 text-center">
              {t('description_title')}
            </h2>
            <div className="relative">
              <span className="absolute -top-6 -left-4 text-6xl text-primary/20 font-serif leading-none">
                &ldquo;
              </span>
              <p className="text-lg md:text-xl text-neutral-700 leading-relaxed italic text-center">
                {seoText ? (isFr ? seoText.fr : seoText.en) : displayDescription}
              </p>
              <span className="absolute -bottom-8 -right-4 text-6xl text-primary/20 font-serif leading-none">
                &rdquo;
              </span>
            </div>
          </motion.div>
        </div>
      </motion.section>

      {/* ===== MUST-SEE PLACES SECTION ===== */}
      {pois && pois.length > 0 && (
        <section className="py-16 md:py-20">
          <div className="container mx-auto px-4">
            <motion.h2
              className="text-3xl md:text-4xl font-bold text-center mb-12 font-display"
              variants={fadeUp}
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, margin: '-50px' }}
              transition={{ duration: 0.5 }}
            >
              {t('must_see_places')}
            </motion.h2>

            <motion.div
              className="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-5xl mx-auto"
              variants={staggerContainer}
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, margin: '-30px' }}
            >
              {pois.map((poi, i) => (
                <motion.div
                  key={i}
                  className="bg-white rounded-2xl p-6 shadow-md hover:shadow-lg transition-shadow duration-300 border border-neutral-100"
                  variants={cardVariant}
                >
                  <div className="flex items-start gap-4">
                    <div className="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center shrink-0 mt-1">
                      <MapPin className="w-5 h-5 text-primary" />
                    </div>
                    <div>
                      <h3 className="text-lg font-bold text-neutral-900 mb-2">
                        {isFr ? poi.nameFr : poi.nameEn}
                      </h3>
                      <p className="text-neutral-600 text-sm leading-relaxed">
                        {isFr ? poi.descFr : poi.descEn}
                      </p>
                    </div>
                  </div>
                </motion.div>
              ))}
            </motion.div>
          </div>
        </section>
      )}

      {/* ===== MAP SECTION ===== */}
      {listings.length > 0 && center && (
        <DestinationMapSection listings={listings} locale={locale as Locale} center={center} />
      )}

      {/* ===== LISTINGS or COMING SOON ===== */}
      {listings.length > 0 ? (
        <section className="py-12">
          <div className="container mx-auto px-4">
            <motion.div
              className="flex justify-between items-center mb-8"
              variants={fadeUp}
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true }}
              transition={{ duration: 0.5 }}
            >
              <h2 className="text-3xl font-bold">
                {t('available_experiences')} ({listingsCount})
              </h2>
            </motion.div>
            <motion.div
              className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6"
              variants={staggerContainer}
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, margin: '-30px' }}
            >
              {listings.map((listing) => (
                <motion.div key={listing.id} variants={cardVariant}>
                  <ListingCard listing={listing} locale={locale} />
                </motion.div>
              ))}
            </motion.div>
          </div>
        </section>
      ) : (
        /* ===== COMING SOON — Animated Section ===== */
        <section className="relative py-20 md:py-28 overflow-hidden">
          {/* Green gradient background */}
          <div className="absolute inset-0 bg-gradient-to-br from-primary/5 via-primary/10 to-primary-light/10" />

          {/* Decorative floating blobs */}
          <div
            className="absolute top-10 left-10 w-64 h-64 bg-primary/5 rounded-full blur-3xl"
            style={{ animation: 'float 6s ease-in-out infinite' }}
          />
          <div
            className="absolute bottom-10 right-10 w-80 h-80 bg-primary-light/10 rounded-full blur-3xl"
            style={{ animation: 'float 8s ease-in-out infinite 1s' }}
          />
          <div
            className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-[#f5f0d1]/30 rounded-full blur-3xl"
            style={{ animation: 'float 7s ease-in-out infinite 0.5s' }}
          />

          <div className="relative container mx-auto px-4 text-center">
            {/* Spinning compass */}
            <motion.div
              className="mx-auto mb-8 w-20 h-20 text-primary/40"
              initial={{ opacity: 0, scale: 0.5 }}
              whileInView={{ opacity: 1, scale: 1 }}
              viewport={{ once: true }}
              transition={{ duration: 0.6, type: 'spring' }}
            >
              <Compass className="w-20 h-20" style={{ animation: 'slowSpin 8s linear infinite' }} />
            </motion.div>

            {/* Typewriter heading */}
            <div className="mb-6 min-h-[48px]">
              <h2 className="text-3xl md:text-4xl font-bold text-neutral-900 font-display">
                {typedHeading}
                {!typingDone && (
                  <span className="inline-block w-[3px] h-8 bg-primary ml-1 animate-pulse" />
                )}
              </h2>
            </div>

            {/* Subtitle fade in */}
            <motion.p
              className="text-lg md:text-xl text-neutral-600 max-w-lg mx-auto mb-8"
              initial={{ opacity: 0, y: 15 }}
              whileInView={{ opacity: typingDone ? 1 : 0, y: typingDone ? 0 : 15 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5 }}
            >
              {t('experiences_coming_soon_desc', { name: displayName })}
            </motion.p>

            {/* Animated dots */}
            <div className="flex justify-center gap-3 mb-10">
              {[0, 1, 2].map((i) => (
                <motion.div
                  key={i}
                  className="w-3 h-3 rounded-full bg-primary"
                  animate={{
                    scale: [1, 1.4, 1],
                    opacity: [0.4, 1, 0.4],
                  }}
                  transition={{
                    duration: 1.5,
                    repeat: Infinity,
                    delay: i * 0.3,
                    ease: 'easeInOut',
                  }}
                />
              ))}
            </div>

            {/* CTA button */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5, delay: 0.3 }}
            >
              <Link
                href={(cmsDestination?.link || `/${locale}#destinations`) as never}
                className="inline-flex items-center gap-2 px-8 py-4 bg-primary text-white font-semibold rounded-full hover:bg-primary/90 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
              >
                <Compass className="w-5 h-5" />
                {t('explore_adventures')}
              </Link>
            </motion.div>
          </div>
        </section>
      )}
    </>
  );
}
