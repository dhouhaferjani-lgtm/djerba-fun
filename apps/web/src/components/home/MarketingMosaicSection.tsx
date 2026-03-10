'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';
import { useRef, useState, useEffect, useCallback } from 'react';
import { shouldUnoptimizeImage } from '@/lib/utils/image';
import { cn } from '@/lib/utils/cn';

// Default fallback images when CMS images are not uploaded
const defaultImages = {
  pillar1: 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=800',
  pillar2: 'https://images.unsplash.com/photo-1500835556837-99ac94a94552?w=800',
  pillar3: 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800',
};

interface BrandPillarText {
  title: string | null;
  description: string | null;
}

interface BrandPillarsData {
  pillar1: BrandPillarText;
  pillar2: BrandPillarText;
  pillar3: BrandPillarText;
}

interface MarketingMosaicSectionProps {
  brandPillar1Url?: string | null;
  brandPillar2Url?: string | null;
  brandPillar3Url?: string | null;
  brandPillarsData?: BrandPillarsData;
}

export function MarketingMosaicSection({
  brandPillar1Url,
  brandPillar2Url,
  brandPillar3Url,
  brandPillarsData,
}: MarketingMosaicSectionProps) {
  const t = useTranslations('home');
  const scrollContainerRef = useRef<HTMLDivElement>(null);
  const [currentIndex, setCurrentIndex] = useState(0);

  // Use CMS values with translation fallbacks - Warm sunset tones
  const brandPillars = [
    {
      id: 'sustainable',
      image: brandPillar1Url || defaultImages.pillar1,
      title: brandPillarsData?.pillar1?.title || t('pillar_sustainable_title'),
      description: brandPillarsData?.pillar1?.description || t('pillar_sustainable_description'),
      ringColor: '#CD5C5C', // Coral
      accentColor: '#F5B041', // Gold accent
    },
    {
      id: 'authentic',
      image: brandPillar2Url || defaultImages.pillar2,
      title: brandPillarsData?.pillar2?.title || t('pillar_authentic_title'),
      description: brandPillarsData?.pillar2?.description || t('pillar_authentic_description'),
      ringColor: '#B45335', // Terracotta
      accentColor: '#FFD700', // Golden accent
    },
    {
      id: 'adventure',
      image: brandPillar3Url || defaultImages.pillar3,
      title: brandPillarsData?.pillar3?.title || t('pillar_adventure_title'),
      description: brandPillarsData?.pillar3?.description || t('pillar_adventure_description'),
      ringColor: '#DAA520', // Golden yellow
      accentColor: '#5D3A1A', // Dark brown accent
    },
  ];

  // Track current visible card for mobile indicators
  useEffect(() => {
    const container = scrollContainerRef.current;
    if (!container) return;

    const handleScroll = () => {
      const scrollLeft = container.scrollLeft;
      const cardWidth = container.scrollWidth / brandPillars.length;
      const index = Math.round(scrollLeft / cardWidth);
      setCurrentIndex(Math.min(index, brandPillars.length - 1));
    };

    container.addEventListener('scroll', handleScroll, { passive: true });
    return () => container.removeEventListener('scroll', handleScroll);
  }, [brandPillars.length]);

  // Scroll to specific card
  const scrollToIndex = useCallback(
    (index: number) => {
      const container = scrollContainerRef.current;
      if (!container) return;
      const cardWidth = container.scrollWidth / brandPillars.length;
      container.scrollTo({ left: cardWidth * index, behavior: 'smooth' });
    },
    [brandPillars.length]
  );

  return (
    <section className="py-16 bg-neutral-100">
      <div className="container mx-auto px-4">
        <div className="relative">
          {/* Horizontal scroll carousel */}
          <div
            ref={scrollContainerRef}
            className="flex gap-6 md:gap-8 overflow-x-auto snap-x snap-mandatory scrollbar-hide scroll-smooth pb-4"
            data-testid="pillar-carousel"
            role="region"
            aria-roledescription="carousel"
            aria-label="Brand pillars"
          >
            {brandPillars.map((pillar) => (
              <div
                key={pillar.id}
                className="flex-shrink-0 w-[85vw] sm:w-[45vw] md:w-[calc(33.333%-1.5rem)] snap-start flex flex-col items-center"
                data-testid="pillar-card"
              >
                {/* Circular image container */}
                <div className="relative group cursor-pointer">
                  {/* Outer glow effect on hover */}
                  <div
                    className="absolute -inset-2 rounded-full opacity-0 group-hover:opacity-40 transition-opacity duration-300 blur-xl"
                    style={{ backgroundColor: pillar.ringColor }}
                  />

                  {/* Circular image with colored ring */}
                  <div
                    className="relative w-48 h-48 sm:w-56 sm:h-56 md:w-64 md:h-64 rounded-full overflow-hidden ring-4 ring-offset-4 ring-offset-neutral-100 transition-transform duration-300 group-hover:scale-105"
                    style={{ '--tw-ring-color': pillar.ringColor } as React.CSSProperties}
                  >
                    <Image
                      src={pillar.image}
                      alt={pillar.title}
                      fill
                      className="object-cover transition-transform duration-500 ease-out group-hover:scale-110"
                      unoptimized={shouldUnoptimizeImage(pillar.image)}
                    />

                    {/* Subtle overlay on hover */}
                    <div
                      className="absolute inset-0 opacity-0 group-hover:opacity-20 transition-opacity duration-300"
                      style={{ backgroundColor: pillar.ringColor }}
                    />
                  </div>
                </div>

                {/* Text content below circle */}
                <div className="mt-6 text-center px-4">
                  <h3 className="text-xl md:text-2xl font-bold text-gray-900 uppercase tracking-wide">
                    {pillar.title}
                  </h3>
                  {/* Colored separator line */}
                  <div
                    className="w-12 h-1 mx-auto my-3 rounded-full"
                    style={{ backgroundColor: pillar.ringColor }}
                  />
                  <p className="text-sm md:text-base text-gray-600 leading-relaxed max-w-xs mx-auto">
                    {pillar.description}
                  </p>
                </div>
              </div>
            ))}
          </div>

          {/* Mobile scroll indicators (dots) */}
          <div className="flex justify-center gap-2 mt-4 md:hidden" role="tablist">
            {brandPillars.map((pillar, idx) => (
              <button
                key={pillar.id}
                className={cn(
                  'w-2 h-2 rounded-full transition-colors',
                  currentIndex === idx ? 'bg-amber-500' : 'bg-neutral-300'
                )}
                onClick={() => scrollToIndex(idx)}
                aria-label={`Go to ${pillar.title}`}
                aria-selected={currentIndex === idx}
                role="tab"
              />
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
