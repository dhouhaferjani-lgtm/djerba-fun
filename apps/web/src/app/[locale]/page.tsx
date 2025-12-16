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

export default async function HomePage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);

  // Try to fetch CMS content for middle sections
  let cmsPage = null;
  try {
    cmsPage = await getPageByCode({ code: 'HOME', locale });
  } catch (error) {
    // CMS page doesn't exist yet, will use hardcoded sections
    console.log('No HOME page in CMS, using hardcoded sections');
  }

  return (
    <MainLayout locale={locale}>
      {/* Always show hardcoded Hero and Marketing Mosaic */}
      <HeroSection locale={locale} />
      <MarketingMosaicSection />

      {/* CMS-managed middle sections OR hardcoded fallback */}
      {cmsPage && cmsPage.content_blocks && cmsPage.content_blocks.length > 0 ? (
        <BlockRenderer blocks={cmsPage.content_blocks} />
      ) : (
        <>
          <FeaturedPackagesSection />
          <PromoBannerSection locale={locale} />
          <CategoriesGridSection locale={locale} />
          <DestinationsBentoGrid locale={locale} />
          <CTASectionWithBlobs locale={locale} />
        </>
      )}

      {/* Always show Blog section */}
      <BlogSection />
    </MainLayout>
  );
}
