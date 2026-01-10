'use client';

import Image from 'next/image';
import { shouldUnoptimizeImage } from '@/lib/utils/image';

// Default fallback images when CMS images are not uploaded
const defaultImages = {
  pillar1: 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=800',
  pillar2: 'https://images.unsplash.com/photo-1500835556837-99ac94a94552?w=800',
  pillar3: 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800',
};

interface MarketingMosaicSectionProps {
  brandPillar1Url?: string | null;
  brandPillar2Url?: string | null;
  brandPillar3Url?: string | null;
}

export function MarketingMosaicSection({
  brandPillar1Url,
  brandPillar2Url,
  brandPillar3Url,
}: MarketingMosaicSectionProps) {
  const brandPillars = [
    {
      id: 'sustainable',
      image: brandPillar1Url || defaultImages.pillar1,
      title: 'Sustainable Travel',
      description: 'Eco-conscious adventures that protect our planet',
      bgColor: 'bg-primary/85',
    },
    {
      id: 'authentic',
      image: brandPillar2Url || defaultImages.pillar2,
      title: 'Authentic Experiences',
      description: 'Connect with local cultures and traditions',
      bgColor: 'bg-secondary/85',
    },
    {
      id: 'adventure',
      image: brandPillar3Url || defaultImages.pillar3,
      title: 'Epic Adventures',
      description: 'Unforgettable journeys in breathtaking landscapes',
      bgColor: 'bg-primary/85',
    },
  ];

  return (
    <section className="py-16 bg-[#f5f0d1] overflow-hidden">
      <div className="container mx-auto px-4">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-24 py-12">
          {brandPillars.map((pillar) => (
            <div key={pillar.id} className="group relative" style={{ paddingBottom: '100%' }}>
              {/* Main Image Card - Absolute positioned to maintain square */}
              <div className="absolute inset-0 overflow-hidden rounded-lg">
                <Image
                  src={pillar.image}
                  alt={pillar.title}
                  fill
                  className="object-cover transition-transform duration-500 group-hover:scale-110"
                  unoptimized={shouldUnoptimizeImage(pillar.image)}
                />
              </div>

              {/* Border Decor - Offset Top Left (OUTSIDE) */}
              <div
                className="absolute border-4 border-white rounded-lg pointer-events-none z-10"
                style={{
                  top: '-12px',
                  left: '-12px',
                  width: 'calc(100% + 24px)',
                  height: 'calc(100% + 24px)',
                }}
              />

              {/* Content Box - Offset Bottom Right (OUTSIDE, LARGER) */}
              <div
                className={`absolute ${pillar.bgColor} p-6 md:p-8 rounded-lg transition-all duration-500 group-hover:translate-x-2 group-hover:translate-y-2 z-20`}
                style={{
                  bottom: '-20%',
                  right: '-10%',
                  minWidth: '85%',
                }}
              >
                <h3 className="text-base md:text-lg font-display font-bold text-white uppercase tracking-wide mb-2">
                  {pillar.title}
                </h3>
                <p className="text-xs md:text-sm text-white/90 leading-relaxed">
                  {pillar.description}
                </p>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
