import type { Metadata } from 'next';
import { useTranslations } from 'next-intl';
import { getTranslations } from 'next-intl/server';
import Link from 'next/link';
import { Button } from '@go-adventure/ui';
import { Home, Search, Compass } from 'lucide-react';
import { MainLayout } from '@/components/templates/MainLayout';

export async function generateMetadata(): Promise<Metadata> {
  const t = await getTranslations('errors.404');
  return {
    title: t('title'),
    robots: { index: false, follow: false },
  };
}

/**
 * 404 Not Found Page
 * Displayed when a user navigates to a non-existent route.
 * Uses MainLayout for consistent header/footer and i18n support.
 */
export default function NotFound() {
  const locale = 'en'; // Default locale for root-level 404
  const t = useTranslations('errors.404');

  return (
    <MainLayout locale={locale}>
      {/* Hero Section with Primary Gradient */}
      <div className="relative bg-gradient-to-b from-primary/5 via-secondary/5 to-white py-20 md:py-32">
        <div className="container mx-auto px-4">
          <div className="max-w-3xl mx-auto text-center space-y-8">
            {/* 404 with Icon */}
            <div className="relative">
              <h1 className="text-[10rem] md:text-[14rem] font-display font-bold text-primary leading-none opacity-10 select-none">
                404
              </h1>
              <div className="absolute inset-0 flex items-center justify-center">
                <Compass className="h-24 w-24 md:h-32 md:w-32 text-primary animate-pulse" />
              </div>
            </div>

            {/* Message */}
            <div className="space-y-4">
              <h2 className="text-3xl md:text-4xl font-display font-semibold text-heading">
                {t('heading')}
              </h2>
              <p className="text-lg md:text-xl text-body max-w-2xl mx-auto">{t('message')}</p>
            </div>

            {/* Action Buttons */}
            <div className="flex flex-col sm:flex-row gap-4 justify-center items-center pt-6">
              <Link href={`/${locale}`}>
                <Button variant="primary" size="lg">
                  <Home className="h-5 w-5 mr-2" />
                  {t('home_button')}
                </Button>
              </Link>
              <Link href={`/${locale}/listings`}>
                <Button variant="outline" size="lg">
                  <Search className="h-5 w-5 mr-2" />
                  {t('browse_button')}
                </Button>
              </Link>
            </div>
          </div>
        </div>
      </div>
    </MainLayout>
  );
}
