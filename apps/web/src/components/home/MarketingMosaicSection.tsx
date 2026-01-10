'use client';

import Image from 'next/image';
import { shouldUnoptimizeImage } from '@/lib/utils/image';

// CSS for single line sliding around the square perimeter
const cornerAnimationStyles = `
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

  .snake-line {
    animation: slideLine 6s linear infinite;
  }
`;

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
      overlayBg: 'rgba(13, 100, 46, 0.9)', // Dark green
      textColor: 'white',
      lineColor: '#8BC34A', // Lime green line
    },
    {
      id: 'authentic',
      image: brandPillar2Url || defaultImages.pillar2,
      title: 'Authentic Experiences',
      description: 'Connect with local cultures and traditions',
      overlayBg: 'rgba(139, 195, 74, 0.9)', // Lime green
      textColor: '#0D642E', // Dark green text
      lineColor: '#0D642E', // Dark green line
    },
    {
      id: 'adventure',
      image: brandPillar3Url || defaultImages.pillar3,
      title: 'Epic Adventures',
      description: 'Unforgettable journeys in breathtaking landscapes',
      overlayBg: 'rgba(13, 100, 46, 0.9)', // Dark green
      textColor: 'white',
      lineColor: '#8BC34A', // Lime green line
    },
  ];

  return (
    <section className="py-16 bg-[#f5f0d1]">
      <style dangerouslySetInnerHTML={{ __html: cornerAnimationStyles }} />
      <div className="container mx-auto px-4">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {brandPillars.map((pillar) => (
            <div
              key={pillar.id}
              className="relative w-full"
              style={{ paddingBottom: '100%' }} // SQUARE aspect ratio
            >
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

                {/* White border segments (stops at green square edges) */}
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
                {/* Right border - stops at green square top (10%) */}
                <div
                  className="absolute w-[1px] bg-white"
                  style={{
                    right: '10%',
                    top: '6%',
                    height: '4%', // from 6% to 10%
                  }}
                />
                {/* Bottom border - stops at green square left (10%) */}
                <div
                  className="absolute h-[1px] bg-white"
                  style={{
                    left: '6%',
                    bottom: '10%',
                    width: '4%', // from 6% to 10%
                  }}
                />

                {/* Centered Square Colored Overlay */}
                <div
                  className="absolute flex flex-col justify-center items-start p-6 text-left"
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
                    className="absolute snake-line"
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
          ))}
        </div>
      </div>
    </section>
  );
}
