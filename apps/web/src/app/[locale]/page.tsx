import { setRequestLocale } from 'next-intl/server';
import { MainLayout } from '@/components/templates/MainLayout';
import { HeroSection } from '@/components/organisms/HeroSection';
import { FeaturedPackagesSection } from '@/components/organisms/FeaturedPackagesSection';
import { MarketingMosaicSection } from '@/components/home/MarketingMosaicSection';
import { PromoBannerSection } from '@/components/home/PromoBannerSection';
import { CategoriesGridSection } from '@/components/home/CategoriesGridSection';
import { DestinationsBentoGrid } from '@/components/home/DestinationsBentoGrid';
import { CTASectionWithBlobs } from '@/components/home/CTASectionWithBlobs';
import { BlogSection } from '@/components/home';
import { BlockRenderer } from '@/components/cms';
import { getPageByCode } from '@/lib/api/cms';
import { getBrandingUrls, getEventOfYearData } from '@/lib/api/server';

export default async function HomePage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);

  // Fetch branding, event of year data, and CMS content in parallel
  const [branding, eventOfYear, cmsPage] = await Promise.all([
    getBrandingUrls(locale),
    getEventOfYearData(locale),
    getPageByCode({ code: 'HOME', locale }).catch(() => null),
  ]);

  return (
    <MainLayout locale={locale}>
      {/* Always show hardcoded Hero and Marketing Mosaic */}
      <HeroSection locale={locale} heroBannerUrl={branding.heroBanner} />
      <MarketingMosaicSection
        brandPillar1Url={branding.brandPillar1}
        brandPillar2Url={branding.brandPillar2}
        brandPillar3Url={branding.brandPillar3}
      />

      {/* CMS-managed middle sections OR hardcoded fallback */}
      {cmsPage && cmsPage.content_blocks && cmsPage.content_blocks.length > 0 ? (
        <BlockRenderer blocks={cmsPage.content_blocks} />
      ) : (
        <>
          <FeaturedPackagesSection />
          <PromoBannerSection locale={locale} eventOfYear={eventOfYear} />
          <CategoriesGridSection locale={locale} />
          <DestinationsBentoGrid locale={locale} />
          <CTASectionWithBlobs locale={locale} />
        </>
      )}

      {/* Always show Blog section */}
      <BlogSection locale={locale} />
    </MainLayout>
  );
}
