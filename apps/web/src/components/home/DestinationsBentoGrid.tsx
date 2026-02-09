'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';
import Link from 'next/link';
import { useState, useEffect, useRef } from 'react';

// Default hardcoded destinations (fallback when CMS is empty)
const defaultDestinations = [
  {
    id: 'houmet-souk',
    name: 'Houmet Souk',
    description: 'Cultural heart of Djerba',
    descriptionFr: 'Cœur culturel de Djerba',
    image: '/images/destinations/houmet-souk.jpg',
    link: null as string | null,
    blurDataURL:
      'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAKCAYAAAC9vt6cAAAACXBIWXMAAAsTAAALEwEAmpwYAAABEklEQVQokY2RsU7DMBCGP9upEkICA0gsLEhsLIhnYGPgCXgTXoSFN2BhYWRkYGRAYkBCQkJCQuqktnNnBpMoStqe9Ov+s+67/05Uvd1KNBZjDcYYrLWICCLyq4H/NlX9jQMQEay1GGNYXyjQuxxy9fAeO/gkjiOsNQS+TxT4GGNYXyiWAlT1DzDWUq3V6HZ7dLs9arUa1loWF8NSQK1Ww/f9CUCtVgOg0WgQhiHtdptms0kYhtRqNQDq9fq/gGq1ShRFdDod2u02URRRrVYBaDab0wFJkvD+/o6I0Gq1aLVa9Pt9RIQkSQBoNBrTAVEU8fLyQpIkvL6+kiQJz8/PRCGJBAHI4xjyeDwmjuPZgB/yF+d+Af7rJwAAAABJRU5ErkJggg==',
  },
  {
    id: 'guellala',
    name: 'Guellala',
    description: 'Pottery village',
    descriptionFr: 'Village des potiers',
    image: '/images/destinations/guellala.jpg',
    link: null as string | null,
    blurDataURL:
      'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAKCAYAAAC9vt6cAAAACXBIWXMAAAsTAAALEwEAmpwYAAABDklEQVQokY2RMU7DQBBF38yuHUdJQUEBEhUVEjcAiYKOgoqSC3AGrsANOAM34AZcgIqGgoKCAgkJCQkJydne3aFYJ44TJ4w0mv/1Z2Y0ouPzqxhjMcZgjMFay88G/9tU9TcOQEQwxmCMYX2hQO9iyOXje+zoizgKsdYQ+D5h4GOMYXWhWApQ1T/AWEu5UqbT6dDpdihXylhrWVoMSwHlchnf9ycApVIJgFqtRhAENJtNGo0GQRBQKBQA8H2/FFAsFomiiFarRbPZJIoiisUiAI1Go0RAu93m7e0NEaHRaNBoNOj1eogI7XYbAM/zpgPCMOTl5QURod1u0263eX5+JgxDPM+bDvA8j8fHR0SkFPAD09Dh/tKzf38AAAAASUVORK5CYII=',
  },
  {
    id: 'ile-flamants-roses',
    name: "L'île des flamants roses",
    description: 'Flamingo sanctuary',
    descriptionFr: 'Sanctuaire des flamants',
    image: '/images/destinations/ile-flamants-roses.jpg',
    link: null as string | null,
    blurDataURL:
      'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMCwsKCwsNDhIQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT/2wBDAQMEBAUEBQkFBQkUDQsNFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBT/wAARCAAKABADASIAAhEBAxEB/8QAFgABAQEAAAAAAAAAAAAAAAAABwgJ/8QAJBAAAgEDBAICAwAAAAAAAAAAAQIDBAURBgcSIQAIEzFBUWH/xAAVAQEBAAAAAAAAAAAAAAAAAAADBP/EABsRAAICAwEAAAAAAAAAAAAAAAECAAMEESFB/9oADAMBAAIRAxEAPwDU+4b0W+1brbRXyiqruaKoSeNYpfid3Q5XkSCQMj99Dz1i70bg7T+u9Gbj6R01a7ZqN6mspquCrimjqopY+DxSq6xgniyuuGI4n7U9HrnrHQW7VJYKHWFrq7hHaJXltzQ1jUzRO4AdSTG4dCQAN1YAMenw6ftytp9utldLbV6G0zbtOVV5t0lHeK+jqZqir+OSVDI8crOzPwUBDzyOMD9YGdM1iu2X0gRlLyKKbT3P/9k=',
  },
];

// Default blur placeholder for CMS images
const DEFAULT_BLUR =
  'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAFUlEQVR42mN8/+H9fwYiAeOoQvoqABtS0HlJIZvxAAAAAElFTkSuQmCC';

// CMS destination interface (from platform settings)
interface CmsDestination {
  id: string;
  name: string;
  description_en: string;
  description_fr: string;
  image: string;
  link?: string;
}

// Grid positions - defines the layout
// Position 0: Big featured (left side, spans 2 rows)
// Position 1: Top-right small (1x1)
// Position 2: Bottom-right small (1x1)
const gridPositions = [
  { gridArea: '1 / 1 / 3 / 3', isBig: true },
  { gridArea: '1 / 3 / 2 / 4', isBig: false },
  { gridArea: '2 / 3 / 3 / 4', isBig: false },
];

const INITIAL_DELAY = 4000; // 4 seconds before first rotation
const ROTATION_INTERVAL = 3000; // 3 seconds between rotations

interface DestinationsBentoGridProps {
  locale: string;
  cmsDestinations?: CmsDestination[];
}

export function DestinationsBentoGrid({ locale, cmsDestinations }: DestinationsBentoGridProps) {
  const t = useTranslations('home');

  // Transform CMS data or use hardcoded defaults
  const destinations =
    cmsDestinations && cmsDestinations.length > 0
      ? cmsDestinations.map((d) => ({
          id: d.id,
          name: d.name,
          description: d.description_en,
          descriptionFr: d.description_fr,
          image: d.image,
          blurDataURL: DEFAULT_BLUR,
          link: d.link || null,
        }))
      : defaultDestinations;
  const [rotationOffset, setRotationOffset] = useState(0);
  const [isAnimating, setIsAnimating] = useState(false);
  const isPausedRef = useRef(false);
  const intervalRef = useRef<NodeJS.Timeout | null>(null);
  const [isMounted, setIsMounted] = useState(false);

  // Set up auto-rotation on mount
  useEffect(() => {
    setIsMounted(true);

    // Define rotate inside useEffect to avoid stale closure
    const rotate = () => {
      if (isPausedRef.current) return;

      setIsAnimating(true);
      setTimeout(() => {
        setRotationOffset((prev) => (prev + 1) % destinations.length);
        setTimeout(() => setIsAnimating(false), 100);
      }, 400);
    };

    // Initial delay before starting rotation
    const initialTimeout = setTimeout(() => {
      // First rotation after initial delay
      rotate();
      // Then continue rotating every ROTATION_INTERVAL
      intervalRef.current = setInterval(rotate, ROTATION_INTERVAL);
    }, INITIAL_DELAY);

    return () => {
      clearTimeout(initialTimeout);
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
      }
    };
  }, []); // Empty deps - only run once on mount

  // Handle pause/resume
  const handleMouseEnter = () => {
    isPausedRef.current = true;
  };

  const handleMouseLeave = () => {
    isPausedRef.current = false;
  };

  // Get destination index for each position (circular rotation)
  const getDestinationAtPosition = (positionIndex: number) => {
    return (positionIndex + rotationOffset) % destinations.length;
  };

  // Manual navigation to a specific destination as featured
  const goToDestination = (destIndex: number) => {
    // Clear and reset interval to avoid quick succession
    if (intervalRef.current) {
      clearInterval(intervalRef.current);
    }

    setIsAnimating(true);
    setTimeout(() => {
      setRotationOffset(destIndex);
      setTimeout(() => {
        setIsAnimating(false);
        // Restart interval after manual navigation
        intervalRef.current = setInterval(() => {
          if (isPausedRef.current) return;
          setIsAnimating(true);
          setTimeout(() => {
            setRotationOffset((prev) => (prev + 1) % destinations.length);
            setTimeout(() => setIsAnimating(false), 100);
          }, 400);
        }, ROTATION_INTERVAL);
      }, 100);
    }, 400);
  };

  // Don't render animated content until mounted (avoid hydration mismatch)
  if (!isMounted) {
    return (
      <section className="py-20 bg-[#f5f0d1]">
        <div className="container mx-auto px-4">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl font-display font-bold text-neutral-900 mb-4">
              {t('destinations_title')}
            </h2>
            <p className="text-lg text-neutral-600 max-w-2xl mx-auto">
              {t('destinations_subtitle')}
            </p>
          </div>
          <div className="hidden md:grid grid-cols-3 grid-rows-2 gap-4 h-[500px]">
            {gridPositions.map((position, posIndex) => {
              const destination = destinations[posIndex];
              return (
                <div
                  key={`pos-${posIndex}`}
                  className="relative overflow-hidden rounded-2xl shadow-lg bg-neutral-200 animate-pulse"
                  style={{ gridArea: position.gridArea }}
                />
              );
            })}
          </div>
        </div>
      </section>
    );
  }

  return (
    <section className="py-20 bg-[#f5f0d1]">
      <div className="container mx-auto px-4">
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-display font-bold text-neutral-900 mb-4">
            {t('destinations_title')}
          </h2>
          <p className="text-lg text-neutral-600 max-w-2xl mx-auto">{t('destinations_subtitle')}</p>
        </div>

        {/* Desktop: Animated Bento Grid */}
        <div
          className="hidden md:grid grid-cols-3 grid-rows-2 gap-4 h-[500px]"
          onMouseEnter={handleMouseEnter}
          onMouseLeave={handleMouseLeave}
        >
          {gridPositions.map((position, posIndex) => {
            const destIndex = getDestinationAtPosition(posIndex);
            const destination = destinations[destIndex];

            return (
              <Link
                key={`pos-${posIndex}`}
                href={
                  (destination.link || `/${locale}/listings?location=${destination.id}`) as never
                }
                className="group relative overflow-hidden rounded-2xl shadow-lg"
                style={{
                  gridArea: position.gridArea,
                }}
              >
                {/* Animated container for smooth image swap */}
                <div
                  className={`absolute inset-0 transition-all duration-500 ease-in-out ${
                    isAnimating ? 'opacity-0 scale-105 blur-sm' : 'opacity-100 scale-100 blur-0'
                  }`}
                >
                  <Image
                    src={destination.image}
                    alt={destination.name}
                    fill
                    placeholder="blur"
                    blurDataURL={destination.blurDataURL}
                    className="object-cover transition-transform duration-700 ease-out group-hover:scale-110"
                    sizes={
                      position.isBig
                        ? '(max-width: 768px) 100vw, 66vw'
                        : '(max-width: 768px) 100vw, 33vw'
                    }
                    priority={position.isBig}
                  />
                  {/* Gradient Overlay */}
                  <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent" />
                </div>

                {/* Featured badge for big position */}
                {position.isBig && (
                  <div className="absolute top-4 right-4 z-10">
                    <div className="flex items-center gap-2 bg-white/20 backdrop-blur-md rounded-full px-4 py-2 border border-white/30">
                      <div className="w-2 h-2 rounded-full bg-primary-light animate-pulse" />
                      <span className="text-xs text-white font-semibold uppercase tracking-wider">
                        Featured
                      </span>
                    </div>
                  </div>
                )}

                {/* Text content - animated with position */}
                <div
                  className={`absolute bottom-0 left-0 right-0 p-6 transition-all duration-500 ease-in-out ${
                    isAnimating ? 'opacity-0 translate-y-6' : 'opacity-100 translate-y-0'
                  }`}
                >
                  <h3
                    className={`font-display font-bold text-white mb-1 transition-all duration-300 ${
                      position.isBig ? 'text-4xl lg:text-5xl' : 'text-xl lg:text-2xl'
                    }`}
                  >
                    {destination.name}
                  </h3>
                  {position.isBig && (
                    <p className="text-white/90 text-lg mt-2 font-light">
                      {locale === 'fr' ? destination.descriptionFr : destination.description}
                    </p>
                  )}
                </div>

                {/* Hover border effect */}
                <div className="absolute inset-0 rounded-2xl ring-0 ring-inset ring-white/0 transition-all duration-300 group-hover:ring-4 group-hover:ring-white/40" />
              </Link>
            );
          })}
        </div>

        {/* Rotation indicators / navigation dots */}
        <div className="hidden md:flex justify-center items-center mt-8 gap-3">
          {destinations.map((dest, index) => {
            const isActive = rotationOffset === index;
            return (
              <button
                key={dest.id}
                onClick={() => goToDestination(index)}
                className={`relative h-3 rounded-full transition-all duration-500 ease-out ${
                  isActive ? 'w-10 bg-primary' : 'w-3 bg-neutral-400/60 hover:bg-neutral-500'
                }`}
                aria-label={`Show ${dest.name} as featured`}
              >
                {isActive && (
                  <span className="absolute inset-0 rounded-full bg-primary animate-ping opacity-20" />
                )}
              </button>
            );
          })}
        </div>

        {/* Mobile: Vertical stack with swipe hint */}
        <div className="md:hidden flex flex-col gap-4">
          {destinations.map((destination, index) => (
            <Link
              key={destination.id}
              href={(destination.link || `/${locale}/listings?location=${destination.id}`) as never}
              className={`group relative overflow-hidden rounded-2xl shadow-md ${
                index === 0 ? 'h-56' : 'h-40'
              }`}
            >
              <Image
                src={destination.image}
                alt={destination.name}
                fill
                placeholder="blur"
                blurDataURL={destination.blurDataURL}
                className="object-cover transition-transform duration-500 group-hover:scale-110"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />
              <div className="absolute bottom-0 left-0 p-5">
                <h3 className="text-2xl font-display font-bold text-white">{destination.name}</h3>
                {index === 0 && (
                  <p className="text-white/80 text-sm mt-1">
                    {locale === 'fr' ? destination.descriptionFr : destination.description}
                  </p>
                )}
              </div>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
}
