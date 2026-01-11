'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';
import { HeroSearchForm } from '../molecules/HeroSearchForm';
import { shouldUnoptimizeImage } from '@/lib/utils/image';

// Default hero image (Unsplash) - used when no custom banner is set
const DEFAULT_HERO_IMAGE = 'https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=1920';

interface HeroSectionProps {
  locale: string;
  heroBannerUrl?: string | null;
}

export function HeroSection({ locale, heroBannerUrl }: HeroSectionProps) {
  const t = useTranslations('home');
  const backgroundImage = heroBannerUrl || DEFAULT_HERO_IMAGE;

  return (
    <section className="relative h-[85vh] flex items-center justify-center overflow-hidden">
      {/* Background Image */}
      <div className="absolute inset-0 z-0">
        <Image
          src={backgroundImage}
          alt="Hero Banner"
          fill
          className="object-cover"
          priority
          unoptimized={shouldUnoptimizeImage(backgroundImage)}
        />
        {/* Dark Green Gradient Overlay - keeps photo visible but ensures text readability */}
        <div
          className="absolute inset-0"
          style={{
            background:
              'linear-gradient(to bottom, rgba(13, 100, 46, 0.7) 0%, rgba(13, 100, 46, 0.5) 50%, rgba(13, 100, 46, 0.6) 100%)',
          }}
        />
      </div>

      {/* Content */}
      <div className="container mx-auto px-4 relative z-10 text-center">
        <div className="max-w-7xl mx-auto">
          {/* Hero Headline - Elegant serif style with text shadow */}
          <h1
            className="font-normal text-white mb-3 md:mb-4 leading-[1.1] tracking-normal"
            style={{
              fontFamily: 'var(--font-serif)',
              fontSize: 'clamp(32px, 6vw, 150px)',
              textShadow: '0 2px 4px rgba(0, 0, 0, 0.3)',
            }}
          >
            <span className="block whitespace-nowrap">{t('hero_title_line1')}</span>
            <span className="block text-[#8BC34A] -mt-[0.15em]">{t('hero_title_line2')}</span>
          </h1>
          <p className="text-base sm:text-lg md:text-xl lg:text-2xl font-light text-white/90 mb-8 max-w-2xl mx-auto leading-tight">
            {t('hero_subtitle')}
          </p>

          {/* Search Form - Floating with Glassmorphism */}
          <div className="max-w-5xl mx-auto mb-6">
            <HeroSearchForm locale={locale} />
          </div>

          {/* Travel Tip Banner - Transparent with white border */}
          <div className="w-full max-w-3xl mx-auto bg-white/10 backdrop-blur-sm border border-white px-6 py-3 rounded-lg">
            <p className="text-white text-sm">
              <span className="text-[#8BC34A] font-semibold">Travel Tip:</span>{' '}
              <span>{t('hero_travel_tip_content')}</span>
            </p>
          </div>
        </div>
      </div>
    </section>
  );
}
