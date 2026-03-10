'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';
import { useRef, useState, useEffect, useCallback } from 'react';
import { shouldUnoptimizeImage } from '@/lib/utils/image';
import { cn } from '@/lib/utils/cn';

// Animation keyframes for circular cards
const animationStyles = `
  /* Rotating gradient ring */
  @keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }

  /* Fade up entrance animation */
  @keyframes fade-up {
    from {
      opacity: 0;
      transform: translateY(30px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  /* Orbiting particle animation - clockwise */
  @keyframes orbit-cw {
    from {
      transform: rotate(0deg) translateX(var(--orbit-radius)) rotate(0deg);
    }
    to {
      transform: rotate(360deg) translateX(var(--orbit-radius)) rotate(-360deg);
    }
  }

  /* Orbiting particle animation - counter-clockwise */
  @keyframes orbit-ccw {
    from {
      transform: rotate(0deg) translateX(var(--orbit-radius)) rotate(0deg);
    }
    to {
      transform: rotate(-360deg) translateX(var(--orbit-radius)) rotate(360deg);
    }
  }

  .pillar-card {
    animation: fade-up 0.8s ease-out forwards;
    opacity: 0;
  }

  .pillar-card:nth-child(1) { animation-delay: 0.1s; }
  .pillar-card:nth-child(2) { animation-delay: 0.3s; }
  .pillar-card:nth-child(3) { animation-delay: 0.5s; }

  .rotating-ring {
    animation: spin-slow 20s linear infinite;
  }

  .pillar-image-container:hover .rotating-ring {
    animation-duration: 8s;
  }

  /* Orbiting particles - sharp contoured dots */
  .orbit-particle {
    position: absolute;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    top: 50%;
    left: 50%;
    margin-top: -4px;
    margin-left: -4px;
    border: 2px solid var(--particle-color);
  }

  .orbit-particle-cw {
    animation: orbit-cw var(--orbit-duration) linear infinite;
  }

  .orbit-particle-ccw {
    animation: orbit-ccw var(--orbit-duration) linear infinite;
  }
`;

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
      ringColorRgb: '205, 92, 92',
      accentColor: '#F5B041', // Gold accent
    },
    {
      id: 'authentic',
      image: brandPillar2Url || defaultImages.pillar2,
      title: brandPillarsData?.pillar2?.title || t('pillar_authentic_title'),
      description: brandPillarsData?.pillar2?.description || t('pillar_authentic_description'),
      ringColor: '#B45335', // Terracotta
      ringColorRgb: '180, 83, 53',
      accentColor: '#FFD700', // Golden accent
    },
    {
      id: 'adventure',
      image: brandPillar3Url || defaultImages.pillar3,
      title: brandPillarsData?.pillar3?.title || t('pillar_adventure_title'),
      description: brandPillarsData?.pillar3?.description || t('pillar_adventure_description'),
      ringColor: '#DAA520', // Golden yellow
      ringColorRgb: '218, 165, 32',
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
    <section className="pt-16 pb-16 bg-neutral-100">
      <style dangerouslySetInnerHTML={{ __html: animationStyles }} />
      <div className="container mx-auto px-4">
        <div className="relative">
          {/* Horizontal scroll carousel */}
          <div
            ref={scrollContainerRef}
            className="flex gap-6 md:gap-8 overflow-x-auto snap-x snap-mandatory scrollbar-hide scroll-smooth py-8 px-4 -mx-4"
            data-testid="pillar-carousel"
            role="region"
            aria-roledescription="carousel"
            aria-label="Brand pillars"
          >
            {brandPillars.map((pillar) => (
              <div
                key={pillar.id}
                className="pillar-card flex-shrink-0 w-[85vw] sm:w-[45vw] md:w-[calc(33.333%-1.5rem)] snap-start flex flex-col items-center"
                data-testid="pillar-card"
              >
                {/* Circular image container */}
                <div className="pillar-image-container relative group cursor-pointer">
                  {/* Orbiting particles */}
                  <div
                    className="orbit-particle orbit-particle-cw"
                    style={
                      {
                        '--orbit-radius': '140px',
                        '--orbit-duration': '8s',
                        '--particle-color': pillar.ringColor,
                        backgroundColor: pillar.ringColor,
                        animationDelay: '0s',
                      } as React.CSSProperties
                    }
                  />
                  <div
                    className="orbit-particle orbit-particle-cw"
                    style={
                      {
                        '--orbit-radius': '140px',
                        '--orbit-duration': '8s',
                        '--particle-color': pillar.ringColor,
                        backgroundColor: pillar.ringColor,
                        animationDelay: '-4s',
                      } as React.CSSProperties
                    }
                  />
                  <div
                    className="orbit-particle orbit-particle-ccw"
                    style={
                      {
                        '--orbit-radius': '150px',
                        '--orbit-duration': '12s',
                        '--particle-color': pillar.accentColor,
                        backgroundColor: pillar.accentColor,
                        animationDelay: '-3s',
                      } as React.CSSProperties
                    }
                  />
                  <div
                    className="orbit-particle orbit-particle-ccw"
                    style={
                      {
                        '--orbit-radius': '150px',
                        '--orbit-duration': '12s',
                        '--particle-color': pillar.accentColor,
                        backgroundColor: pillar.accentColor,
                        animationDelay: '-9s',
                      } as React.CSSProperties
                    }
                  />

                  {/* Rotating gradient ring */}
                  <div
                    className="rotating-ring absolute -inset-1 rounded-full"
                    style={{
                      background: `conic-gradient(from 0deg, ${pillar.ringColor}, transparent 40%, transparent 60%, ${pillar.ringColor})`,
                    }}
                  />

                  {/* Static ring background */}
                  <div
                    className="absolute -inset-1 rounded-full opacity-30"
                    style={{ backgroundColor: pillar.ringColor }}
                  />

                  {/* Circular image with colored ring */}
                  <div
                    className="relative w-48 h-48 sm:w-56 sm:h-56 md:w-64 md:h-64 rounded-full overflow-hidden ring-4 ring-offset-4 ring-offset-neutral-100 transition-all duration-500 group-hover:scale-105 group-hover:shadow-2xl"
                    style={
                      {
                        '--tw-ring-color': pillar.ringColor,
                        '--ring-color-rgb': pillar.ringColorRgb,
                      } as React.CSSProperties
                    }
                  >
                    <Image
                      src={pillar.image}
                      alt={pillar.title}
                      fill
                      className="object-cover transition-transform duration-700 ease-out group-hover:scale-115"
                      unoptimized={shouldUnoptimizeImage(pillar.image)}
                    />

                    {/* Gradient overlay on hover */}
                    <div
                      className="absolute inset-0 opacity-0 group-hover:opacity-30 transition-opacity duration-500"
                      style={{
                        background: `radial-gradient(circle at center, transparent 30%, ${pillar.ringColor} 100%)`,
                      }}
                    />
                  </div>
                </div>

                {/* Text content below circle */}
                <div className="mt-8 text-center px-4">
                  <h3 className="text-xl md:text-2xl font-bold text-gray-900 uppercase tracking-wide">
                    {pillar.title}
                  </h3>
                  {/* Animated colored separator line */}
                  <div
                    className="w-12 h-1 mx-auto my-3 rounded-full transition-all duration-300 group-hover:w-20"
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
                  'w-2 h-2 rounded-full transition-all duration-300',
                  currentIndex === idx ? 'bg-amber-500 scale-125' : 'bg-neutral-300'
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
