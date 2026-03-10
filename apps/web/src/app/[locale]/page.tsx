import { setRequestLocale } from 'next-intl/server';
import { MainLayout } from '@/components/templates/MainLayout';

// Force dynamic rendering to ensure locale-specific content is always fresh
export const dynamic = 'force-dynamic';
export const revalidate = 0;
import { HeroSection } from '@/components/organisms/HeroSection';
import { FeaturedPackagesSection } from '@/components/organisms/FeaturedPackagesSection';
import { MarketingMosaicSection } from '@/components/home/MarketingMosaicSection';
import { PromoBannerSection } from '@/components/home/PromoBannerSection';
import { DestinationsBentoGrid } from '@/components/home/DestinationsBentoGrid';
import { CTASectionWithBlobs } from '@/components/home/CTASectionWithBlobs';
import {
  BlogSection,
  ExperienceCategoriesSection,
  TestimonialsSection,
  NewsletterSection,
} from '@/components/home';
import { BlockRenderer } from '@/components/cms';
import { getPageByCode } from '@/lib/api/cms';
import {
  getBrandingUrls,
  getEventOfYearData,
  getFeaturedListings,
  getHeroData,
  getBrandPillarsData,
  getFeaturedDestinations,
  getTestimonials,
  getExperienceCategoriesData,
  getBlogSectionData,
  getFeaturedPackagesSectionData,
  getCustomExperienceData,
  getNewsletterData,
} from '@/lib/api/server';

export default async function HomePage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);

  // Fetch CMS section data and other content in parallel
  const [
    branding,
    eventOfYear,
    heroData,
    brandPillarsData,
    cmsPage,
    featuredDestinations,
    testimonials,
    experienceCategoriesData,
    blogSectionData,
    featuredPackagesData,
    customExperienceData,
    newsletterData,
  ] = await Promise.all([
    getBrandingUrls(locale),
    getEventOfYearData(locale),
    getHeroData(locale),
    getBrandPillarsData(locale),
    getPageByCode({ code: 'HOME', locale }).catch(() => null),
    getFeaturedDestinations(locale),
    getTestimonials(locale),
    getExperienceCategoriesData(locale),
    getBlogSectionData(locale),
    getFeaturedPackagesSectionData(locale),
    getCustomExperienceData(locale),
    getNewsletterData(locale),
  ]);

  // Fetch featured listings with the limit from CMS (must be separate due to dependency)
  const featuredListings = await getFeaturedListings(featuredPackagesData.limit);

  return (
    <MainLayout locale={locale}>
      {/* Always show Hero and Marketing Mosaic - CMS text with translation fallbacks */}
      <HeroSection
        locale={locale}
        heroBannerUrl={branding.heroBanner}
        heroBannerIsVideo={branding.heroBannerIsVideo}
        heroBannerThumbnail={branding.heroBannerThumbnail}
        heroData={heroData}
      />
      <MarketingMosaicSection
        brandPillar1Url={branding.brandPillar1}
        brandPillar2Url={branding.brandPillar2}
        brandPillar3Url={branding.brandPillar3}
        brandPillarsData={brandPillarsData}
      />

      {/* Featured Listings - À venir */}
      {featuredPackagesData.enabled && (
        <FeaturedPackagesSection
          listings={featuredListings}
          locale={locale}
          cmsData={featuredPackagesData}
        />
      )}

      {/* CMS-managed middle sections OR hardcoded fallback */}
      {cmsPage && cmsPage.content_blocks && cmsPage.content_blocks.length > 0 ? (
        <BlockRenderer blocks={cmsPage.content_blocks} />
      ) : (
        <>
          <PromoBannerSection locale={locale} eventOfYear={eventOfYear} />
          {experienceCategoriesData.enabled && (
            <ExperienceCategoriesSection cmsData={experienceCategoriesData} />
          )}
          <TestimonialsSection testimonials={testimonials} locale={locale} />
          <DestinationsBentoGrid locale={locale} cmsDestinations={featuredDestinations} />
          {customExperienceData.enabled && (
            <CTASectionWithBlobs locale={locale} cmsData={customExperienceData} />
          )}
        </>
      )}

      {/* Always show Blog section if enabled */}
      {blogSectionData.enabled && <BlogSection locale={locale} cmsData={blogSectionData} />}

      {/* Newsletter section - CMS controlled */}
      {newsletterData.enabled && <NewsletterSection cmsData={newsletterData} />}
    </MainLayout>
  );
}
