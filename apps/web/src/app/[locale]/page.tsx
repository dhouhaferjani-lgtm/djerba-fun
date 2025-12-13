import { getTranslations, setRequestLocale } from 'next-intl/server';
import { MainLayout } from '@/components/templates/MainLayout';
import { HeroSection } from '@/components/organisms/HeroSection';
import { ListingGrid } from '@/components/organisms/ListingGrid';
import type { ListingSummary } from '@go-adventure/schemas';

// Mock data for now - will be replaced with API calls
const mockListings: ListingSummary[] = [];

export default async function HomePage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations('home');

  return (
    <MainLayout locale={locale}>
      <HeroSection locale={locale} />

      <section className="container mx-auto px-4 py-12">
        <h2 className="text-3xl font-bold text-neutral-900 mb-8">{t('featured_tours')}</h2>
        <ListingGrid
          listings={mockListings}
          locale={locale}
          emptyMessage="Check back soon for exciting tours and events!"
        />
      </section>
    </MainLayout>
  );
}
