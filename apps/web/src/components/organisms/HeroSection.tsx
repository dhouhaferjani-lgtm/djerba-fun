'use client';

import { useTranslations } from 'next-intl';
import { SearchBar } from '../molecules/SearchBar';
import { useRouter } from 'next/navigation';

interface HeroSectionProps {
  locale: string;
}

export function HeroSection({ locale }: HeroSectionProps) {
  const t = useTranslations('home');
  const router = useRouter();

  const handleSearch = (query: string) => {
    if (query.trim()) {
      router.push(`/${locale}/listings?q=${encodeURIComponent(query)}`);
    }
  };

  return (
    <section className="relative bg-gradient-to-br from-[#0D642E] to-[#8BC34A] text-white">
      <div className="container mx-auto px-4 py-20 md:py-32">
        <div className="max-w-3xl mx-auto text-center">
          <h1 className="text-4xl md:text-6xl font-bold mb-6">{t('hero_title')}</h1>
          <p className="text-xl md:text-2xl mb-8 text-white/90">{t('hero_subtitle')}</p>

          <div className="max-w-2xl mx-auto">
            <SearchBar
              onSearch={handleSearch}
              placeholder={t('search_placeholder')}
              className="bg-white rounded-lg p-2"
            />
          </div>
        </div>
      </div>

      {/* Decorative wave */}
      <div className="absolute bottom-0 left-0 right-0">
        <svg
          viewBox="0 0 1440 120"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
          className="w-full h-auto"
        >
          <path
            d="M0 0L60 10C120 20 240 40 360 46.7C480 53 600 47 720 43.3C840 40 960 40 1080 46.7C1200 53 1320 67 1380 73.3L1440 80V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0V0Z"
            fill="white"
          />
        </svg>
      </div>
    </section>
  );
}
