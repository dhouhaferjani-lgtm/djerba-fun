'use client';

import { useTranslations } from 'next-intl';
import { Button } from '@djerba-fun/ui';
import { ChevronDown, Clock, CheckCircle } from 'lucide-react';

export function HeroSection() {
  const t = useTranslations('customTrip.hero');

  const scrollToWizard = () => {
    const element = document.getElementById('wizard-section');
    if (element) {
      element.scrollIntoView({ behavior: 'smooth' });
    }
  };

  return (
    <section className="relative min-h-[60vh] flex items-center justify-center overflow-hidden">
      {/* Background Image with Overlay */}
      <div className="absolute inset-0 z-0">
        <div
          className="absolute inset-0 bg-cover bg-center bg-no-repeat"
          style={{
            backgroundImage: "url('/images/custom-trip-hero.jpg')",
          }}
        />
        <div className="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/60" />
      </div>

      {/* Content */}
      <div className="relative z-10 container mx-auto px-4 text-center">
        <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-4 leading-tight">
          {t('title')}
        </h1>
        <p className="text-xl md:text-2xl text-white/90 mb-8 max-w-2xl mx-auto">{t('subtitle')}</p>

        {/* Trust Badges */}
        <div className="flex flex-wrap justify-center gap-6 mb-8">
          <div className="flex items-center gap-2 text-white/90">
            <CheckCircle className="h-5 w-5 text-primary-light" />
            <span>{t('badge_free_consultation')}</span>
          </div>
          <div className="flex items-center gap-2 text-white/90">
            <Clock className="h-5 w-5 text-primary-light" />
            <span>{t('badge_response_time')}</span>
          </div>
        </div>

        {/* CTA Button */}
        <Button
          variant="primary"
          size="lg"
          onClick={scrollToWizard}
          className="bg-primary hover:bg-primary/90 text-white px-8 py-4 text-lg shadow-xl hover:shadow-2xl transition-all duration-300"
        >
          {t('cta_button')}
          <ChevronDown className="h-5 w-5 ml-2 animate-bounce" />
        </Button>
      </div>
    </section>
  );
}
