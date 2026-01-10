'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';
import { Sparkles } from 'lucide-react';
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
          <h1 className="text-5xl md:text-7xl lg:text-8xl font-bold text-white mb-6 leading-tight">
            {t('hero_title_line1')}
            <br />
            <span className="text-secondary">{t('hero_title_line2')}</span>
          </h1>
          <p className="text-xl md:text-2xl text-neutral-200 mb-10 max-w-2xl mx-auto">
            {t('hero_subtitle')}
          </p>

          {/* Search Form - Floating with Glassmorphism */}
          <div className="max-w-5xl mx-auto mb-6">
            <HeroSearchForm locale={locale} />
          </div>

          {/* AI Tip Pill */}
          <div className="inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm px-4 py-2 rounded-full text-white text-sm">
            <Sparkles className="h-4 w-4" />
            <span>Travel Tip: Best time to visit the Sahara is October to April</span>
          </div>
        </div>
      </div>
    </section>
  );
}
