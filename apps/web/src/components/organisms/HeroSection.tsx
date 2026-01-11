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
        {/* Gradient Overlay */}
        <div className="absolute inset-0 bg-gradient-to-t from-primary via-transparent to-transparent opacity-90" />
        {/* Texture Layer */}
        <div className="absolute inset-0 bg-primary/30 mix-blend-multiply" />
      </div>

      {/* Content */}
      <div className="container mx-auto px-4 relative z-10 text-center">
        <div className="max-w-7xl mx-auto">
          {/* Hero Headline - MASSIVE, dominating the hero section */}
          <h1
            className="text-[52px] sm:text-[72px] md:text-[96px] lg:text-[130px] xl:text-[160px] 2xl:text-[180px] font-bold text-white mb-6 leading-none tracking-tight"
            style={{ fontFamily: 'var(--font-serif)' }}
          >
            <span className="italic">{t('hero_title_line1')}</span>
            <br />
            <span className="italic text-[#8BC34A]">{t('hero_title_line2')}</span>
          </h1>
          <p className="text-2xl md:text-3xl lg:text-[32px] font-light text-white/90 mb-10 max-w-3xl mx-auto leading-relaxed">
            {t('hero_subtitle')}
          </p>

          {/* Search Form - Floating with Glassmorphism */}
          <div className="max-w-5xl mx-auto mb-6">
            <HeroSearchForm locale={locale} />
          </div>

          {/* Travel Tip Banner */}
          <div className="w-full max-w-3xl mx-auto bg-primary/80 backdrop-blur-sm px-6 py-3 rounded-lg">
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
