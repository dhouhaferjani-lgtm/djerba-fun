'use client';

import { useTranslations } from 'next-intl';
import Image from 'next/image';
import { HeroSearchForm } from '../molecules/HeroSearchForm';

interface HeroSectionProps {
  locale: string;
}

export function HeroSection({ locale }: HeroSectionProps) {
  const t = useTranslations('home');

  return (
    <section className="relative min-h-[85vh] flex items-center overflow-hidden bg-primary">
      {/* Background Image */}
      <div className="absolute inset-0 z-0">
        <Image
          src="https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=1920"
          alt="Eco-friendly adventure"
          fill
          className="object-cover opacity-30"
          priority
        />
        <div className="absolute inset-0 bg-gradient-to-r from-primary-dark via-primary/50 to-transparent" />
      </div>

      <div className="container mx-auto px-4 relative z-10 py-16">
        <div className="max-w-4xl">
          <h1 className="text-4xl md:text-6xl lg:text-7xl font-extrabold text-white mb-6 leading-tight drop-shadow-md">
            {t('hero_title')}
          </h1>
          <p className="text-xl md:text-2xl text-white/90 mb-10 max-w-2xl drop-shadow-sm">
            {t('hero_subtitle')}
          </p>

          {/* Search Form */}
          <HeroSearchForm locale={locale} />

          {/* Quick stats */}
          <div className="mt-12 flex flex-wrap gap-8 text-white">
            <div className="text-center">
              <p className="text-4xl font-bold text-accent">500+</p>
              <p className="text-white/80 text-lg">Experiences</p>
            </div>
            <div className="text-center">
              <p className="text-4xl font-bold text-accent">10k+</p>
              <p className="text-white/80 text-lg">Happy Travelers</p>
            </div>
            <div className="text-center">
              <p className="text-4xl font-bold text-accent">4.9</p>
              <p className="text-white/80 text-lg">Average Rating</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
