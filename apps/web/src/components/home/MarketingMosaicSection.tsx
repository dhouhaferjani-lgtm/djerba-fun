'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';
import { useRef, useState, useEffect, useCallback } from 'react';
import { shouldUnoptimizeImage } from '@/lib/utils/image';
import { cn } from '@/lib/utils/cn';

// CSS for single line sliding around the square perimeter
const cornerAnimationStyles = `
  /* Animation 1: Starts from top-left, goes clockwise */
  @keyframes slideLine {
    /* Top edge: slide left to right */
    0% {
      top: 12px; left: 12px;
      width: 40px; height: 2px;
    }
    20% {
      top: 12px; left: calc(100% - 52px);
      width: 40px; height: 2px;
    }
    /* Right edge: slide top to bottom */
    25% {
      top: 12px; left: calc(100% - 14px);
      width: 2px; height: 40px;
    }
    45% {
      top: calc(100% - 52px); left: calc(100% - 14px);
      width: 2px; height: 40px;
    }
    /* Bottom edge: slide right to left */
    50% {
      top: calc(100% - 14px); left: calc(100% - 52px);
      width: 40px; height: 2px;
    }
    70% {
      top: calc(100% - 14px); left: 12px;
      width: 40px; height: 2px;
    }
    /* Left edge: slide bottom to top */
    75% {
      top: calc(100% - 52px); left: 12px;
      width: 2px; height: 40px;
    }
    95% {
      top: 12px; left: 12px;
      width: 2px; height: 40px;
    }
    /* Back to top edge */
    100% {
      top: 12px; left: 12px;
      width: 40px; height: 2px;
    }
  }

  /* Animation 2: Starts from right edge, goes clockwise */
  @keyframes slideLineFromRight {
    /* Right edge: slide top to bottom */
    0% {
      top: 12px; left: calc(100% - 14px);
      width: 2px; height: 40px;
    }
    20% {
      top: calc(100% - 52px); left: calc(100% - 14px);
      width: 2px; height: 40px;
    }
    /* Bottom edge: slide right to left */
    25% {
      top: calc(100% - 14px); left: calc(100% - 52px);
      width: 40px; height: 2px;
    }
    45% {
      top: calc(100% - 14px); left: 12px;
      width: 40px; height: 2px;
    }
    /* Left edge: slide bottom to top */
    50% {
      top: calc(100% - 52px); left: 12px;
      width: 2px; height: 40px;
    }
    70% {
      top: 12px; left: 12px;
      width: 2px; height: 40px;
    }
    /* Top edge: slide left to right */
    75% {
      top: 12px; left: 12px;
      width: 40px; height: 2px;
    }
    95% {
      top: 12px; left: calc(100% - 52px);
      width: 40px; height: 2px;
    }
    /* Back to right edge */
    100% {
      top: 12px; left: calc(100% - 14px);
      width: 2px; height: 40px;
    }
  }

  .snake-line {
    animation: slideLine 6s linear infinite;
  }

  .snake-line-from-right {
    animation: slideLineFromRight 6s linear infinite;
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
      overlayBg: 'rgba(205, 92, 92, 0.92)', // Coral
      textColor: 'white',
      lineColor: '#F5B041', // Gold line
      lineAnimation: 'snake-line',
    },
    {
      id: 'authentic',
      image: brandPillar2Url || defaultImages.pillar2,
      title: brandPillarsData?.pillar2?.title || t('pillar_authentic_title'),
      description: brandPillarsData?.pillar2?.description || t('pillar_authentic_description'),
      overlayBg: 'rgba(180, 83, 53, 0.90)', // Terracotta
      textColor: 'white',
      lineColor: '#FFD700', // Golden line
      lineAnimation: 'snake-line-from-right',
    },
    {
      id: 'adventure',
      image: brandPillar3Url || defaultImages.pillar3,
      title: brandPillarsData?.pillar3?.title || t('pillar_adventure_title'),
      description: brandPillarsData?.pillar3?.description || t('pillar_adventure_description'),
      overlayBg: 'rgba(218, 165, 32, 0.88)', // Golden yellow
      textColor: '#5D3A1A', // Dark brown text for contrast
      lineColor: '#5D3A1A', // Dark brown line
      lineAnimation: 'snake-line',
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
      <style dangerouslySetInnerHTML={{ __html: cornerAnimationStyles }} />
      <div className="container mx-auto px-4">
        <div className="relative">
          {/* Horizontal scroll carousel */}
          <div
            ref={scrollContainerRef}
            className="flex gap-4 md:gap-6 overflow-x-auto snap-x snap-mandatory scrollbar-hide scroll-smooth pb-4"
            data-testid="pillar-carousel"
            role="region"
            aria-roledescription="carousel"
            aria-label="Brand pillars"
          >
            {brandPillars.map((pillar) => (
              <div
                key={pillar.id}
                className="flex-shrink-0 w-[85vw] sm:w-[45vw] md:w-[calc(33.333%-1rem)] snap-start"
                data-testid="pillar-card"
              >
                {/* Square aspect ratio container */}
                <div className="relative w-full" style={{ paddingBottom: '100%' }}>
                  {/* Card container */}
                  <div className="absolute inset-0 overflow-hidden cursor-pointer group">
                    {/* Background Image with Hover Zoom */}
                    <Image
                      src={pillar.image}
                      alt={pillar.title}
                      fill
                      className="object-cover transition-transform duration-300 ease-out group-hover:scale-110"
                      unoptimized={shouldUnoptimizeImage(pillar.image)}
                    />

                    {/* White border segments (stops at colored square edges) */}
                    {/* Top border - full width */}
                    <div
                      className="absolute h-[1px] bg-white"
                      style={{
                        left: '6%',
                        right: '10%',
                        top: '6%',
                      }}
                    />
                    {/* Left border - full height */}
                    <div
                      className="absolute w-[1px] bg-white"
                      style={{
                        left: '6%',
                        top: '6%',
                        bottom: '10%',
                      }}
                    />
                    {/* Right border - stops at colored square top (10%) */}
                    <div
                      className="absolute w-[1px] bg-white"
                      style={{
                        right: '10%',
                        top: '6%',
                        height: '4%',
                      }}
                    />
                    {/* Bottom border - stops at colored square left (10%) */}
                    <div
                      className="absolute h-[1px] bg-white"
                      style={{
                        left: '6%',
                        bottom: '10%',
                        width: '4%',
                      }}
                    />

                    {/* Centered Colored Overlay with Rounded Corners */}
                    <div
                      className="absolute flex flex-col justify-center items-start p-6 text-left rounded-2xl"
                      data-testid="pillar-overlay"
                      style={{
                        backgroundColor: pillar.overlayBg,
                        left: '10%',
                        right: '6%',
                        top: '10%',
                        bottom: '6%',
                      }}
                    >
                      {/* Single line that slides around the square perimeter */}
                      <div
                        className={`absolute ${pillar.lineAnimation}`}
                        style={{ backgroundColor: pillar.lineColor }}
                      />

                      {/* Text Content */}
                      <div>
                        <h3
                          className="text-5xl md:text-6xl font-bold uppercase tracking-wide leading-tight"
                          style={{ color: pillar.textColor }}
                        >
                          {pillar.title}
                        </h3>
                        {/* Separator Line */}
                        <div
                          className="w-10 h-[4px] my-3"
                          style={{ backgroundColor: pillar.lineColor }}
                        />
                        <p
                          className="text-sm md:text-base leading-relaxed uppercase tracking-wide opacity-90"
                          style={{ color: pillar.textColor }}
                        >
                          {pillar.description}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Mobile scroll indicators (dots) */}
          <div className="flex justify-center gap-2 mt-2 md:hidden" role="tablist">
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
