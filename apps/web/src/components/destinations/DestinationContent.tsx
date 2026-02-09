'use client';

import { motion, type Variants } from 'framer-motion';
import { useTranslations } from 'next-intl';
import Link from 'next/link';
import { useState, useEffect, useRef } from 'react';
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

interface CmsDestination {
  id: string;
  name: string;
  description_en: string;
  description_fr: string;
  image: string;
  link?: string;
}

interface DestinationContentProps {
  locale: string;
  slug: string;
  cmsDestination: CmsDestination | null;
  location: Location | null;
  listings: ListingSummary[];
}

// --- Highlights Data ---

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
        'Crystal-clear Mediterranean waters and golden sand beaches stretching for miles along the coast.',
      descFr:
        "Des eaux méditerranéennes cristallines et des plages de sable doré s'étendant sur des kilomètres.",
    },
    {
      icon: Landmark,
      titleEn: 'Culture & Heritage',
      titleFr: 'Culture & Patrimoine',
      descEn:
        'Ancient souks, the historic El Ghriba Synagogue, and centuries of rich island traditions.',
      descFr:
        'Souks ancestraux, la synagogue historique de la Ghriba et des siècles de traditions insulaires.',
    },
    {
      icon: UtensilsCrossed,
      titleEn: 'Island Gastronomy',
      titleFr: 'Gastronomie Insulaire',
      descEn:
        'Fresh seafood, traditional couscous, and unique Djerbian flavors blending Mediterranean and North African cuisine.',
      descFr:
        'Fruits de mer frais, couscous traditionnel et saveurs djerbiennes uniques mêlant cuisines méditerranéenne et nord-africaine.',
    },
  ],
  dhaher: [
    {
      icon: Mountain,
      titleEn: 'Mountain Trekking',
      titleFr: 'Randonnée en Montagne',
      descEn:
        'Trek through dramatic gorges and rugged highland trails with breathtaking panoramic views.',
      descFr:
        'Randonnez à travers des gorges spectaculaires et des sentiers montagneux aux panoramas époustouflants.',
    },
    {
      icon: Users,
      titleEn: 'Berber Heritage',
      titleFr: 'Patrimoine Berbère',
      descEn:
        'Discover ancient hilltop villages and experience the warm hospitality of Berber communities.',
      descFr:
        "Découvrez des villages perchés ancestraux et vivez l'hospitalité chaleureuse des communautés berbères.",
    },
    {
      icon: Eye,
      titleEn: 'Panoramic Views',
      titleFr: 'Vues Panoramiques',
      descEn:
        'Stunning vistas from mountain peaks overlooking vast valleys and traditional stone architecture.',
      descFr:
        "Des vistas spectaculaires depuis les sommets surplombant de vastes vallées et l'architecture traditionnelle en pierre.",
    },
  ],
  desert: [
    {
      icon: Compass,
      titleEn: 'Camel Treks & Dunes',
      titleFr: 'Méharées & Dunes',
      descEn:
        'Cross the towering golden dunes of the Grand Erg Oriental on unforgettable camel expeditions.',
      descFr:
        "Traversez les imposantes dunes dorées du Grand Erg Oriental lors d'expéditions chamelières inoubliables.",
    },
    {
      icon: Moon,
      titleEn: 'Starlit Desert Camps',
      titleFr: 'Camps sous les Étoiles',
      descEn: 'Spend magical nights under a canopy of stars in traditional desert bivouacs.',
      descFr:
        "Passez des nuits magiques sous un tapis d'étoiles dans des bivouacs désertiques traditionnels.",
    },
    {
      icon: TreePalm,
      titleEn: 'Oasis Discovery',
      titleFr: 'Découverte des Oasis',
      descEn: 'Explore lush palm oases and ancient villages where life has thrived for millennia.',
      descFr:
        'Explorez des oasis luxuriantes et des villages anciens où la vie prospère depuis des millénaires.',
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

  const highlights = destinationHighlights[slug] ?? fallbackHighlights;

  const comingSoonText = isFr ? 'Expériences à venir' : 'Experiences Coming Soon';
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

      {/* ===== HIGHLIGHTS SECTION ===== */}
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
            className="grid grid-cols-1 md:grid-cols-3 gap-8"
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
                  <p className="text-neutral-600 leading-relaxed">{isFr ? h.descFr : h.descEn}</p>
                </motion.div>
              );
            })}
          </motion.div>
        </div>
      </section>

      {/* ===== DESCRIPTION SECTION (cream bg) ===== */}
      {displayDescription && (
        <motion.section
          className="py-16 bg-[#f5f0d1]"
          initial={{ opacity: 0 }}
          whileInView={{ opacity: 1 }}
          viewport={{ once: true, margin: '-50px' }}
          transition={{ duration: 0.6 }}
        >
          <div className="container mx-auto px-4 max-w-3xl text-center">
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.5, delay: 0.2 }}
            >
              <h2 className="text-2xl md:text-3xl font-bold mb-8 font-display text-neutral-900">
                {t('description_title')}
              </h2>
              <div className="relative">
                <span className="absolute -top-6 -left-4 text-6xl text-primary/20 font-serif leading-none">
                  &ldquo;
                </span>
                <p className="text-lg md:text-xl text-neutral-700 leading-relaxed italic">
                  {displayDescription}
                </p>
                <span className="absolute -bottom-8 -right-4 text-6xl text-primary/20 font-serif leading-none">
                  &rdquo;
                </span>
              </div>
            </motion.div>
          </div>
        </motion.section>
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
                href={`/${locale}#destinations`}
                className="inline-flex items-center gap-2 px-8 py-4 bg-primary text-white font-semibold rounded-full hover:bg-primary/90 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
              >
                <Map className="w-5 h-5" />
                {t('explore_other')}
              </Link>
            </motion.div>
          </div>
        </section>
      )}
    </>
  );
}
