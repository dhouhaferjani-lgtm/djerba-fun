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
  getTestimonialsSectionData,
  getExperienceCategoriesData,
  getBlogSectionData,
  getFeaturedPackagesSectionData,
  getCustomExperienceData,
  getNewsletterData,
  getHomepageSections,
  type HomepageSection,
} from '@/lib/api/server';

export default async function HomePage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);

  // Fetch all data in parallel including section order
  const [
    branding,
    eventOfYear,
    heroData,
    brandPillarsData,
    cmsPage,
    featuredDestinations,
    testimonials,
    testimonialsSectionData,
    experienceCategoriesData,
    blogSectionData,
    featuredPackagesData,
    customExperienceData,
    newsletterData,
    homepageSections,
  ] = await Promise.all([
    getBrandingUrls(locale),
    getEventOfYearData(locale),
    getHeroData(locale),
    getBrandPillarsData(locale),
    getPageByCode({ code: 'HOME', locale }).catch(() => null),
    getFeaturedDestinations(locale),
    getTestimonials(locale),
    getTestimonialsSectionData(locale),
    getExperienceCategoriesData(locale),
    getBlogSectionData(locale),
    getFeaturedPackagesSectionData(locale),
    getCustomExperienceData(locale),
    getNewsletterData(locale),
    getHomepageSections(locale),
  ]);

  // Fetch featured listings with the limit from CMS (must be separate due to dependency)
  const featuredListings = await getFeaturedListings(featuredPackagesData.limit);

  // Check if CMS blocks are available (overrides some sections)
  const hasCmsBlocks = cmsPage && cmsPage.contentBlocks && cmsPage.contentBlocks.length > 0;

  // Filter to enabled sections and sort by order
  const enabledSections = homepageSections.filter((s: HomepageSection) => s.enabled);

  /**
   * Render a section by its ID.
   * Returns the appropriate component with props.
   */
  const renderSection = (sectionId: string) => {
    switch (sectionId) {
      case 'hero':
        return (
          <HeroSection
            key="hero"
            locale={locale}
            heroBannerUrl={branding.heroBanner}
            heroBannerIsVideo={branding.heroBannerIsVideo}
            heroBannerThumbnail={branding.heroBannerThumbnail}
            heroData={heroData}
          />
        );
      case 'marketing_mosaic':
        return (
          <MarketingMosaicSection
            key="marketing_mosaic"
            brandPillar1Url={branding.brandPillar1}
            brandPillar2Url={branding.brandPillar2}
            brandPillar3Url={branding.brandPillar3}
            brandPillarsData={brandPillarsData}
          />
        );
      case 'featured_packages':
        // Only render if CMS enabled (individual section control)
        if (!featuredPackagesData.enabled) return null;
        return (
          <FeaturedPackagesSection
            key="featured_packages"
            listings={featuredListings}
            locale={locale}
            cmsData={featuredPackagesData}
          />
        );
      case 'promo_banner':
        // Skip if CMS blocks are used (they may contain custom promo content)
        if (hasCmsBlocks) return null;
        return <PromoBannerSection key="promo_banner" locale={locale} eventOfYear={eventOfYear} />;
      case 'experience_categories':
        // Skip if CMS blocks are used, or if disabled in CMS settings
        if (hasCmsBlocks || !experienceCategoriesData.enabled) return null;
        return (
          <ExperienceCategoriesSection
            key="experience_categories"
            cmsData={experienceCategoriesData}
          />
        );
      case 'testimonials':
        // Skip if CMS blocks are used
        if (hasCmsBlocks) return null;
        return (
          <TestimonialsSection
            key="testimonials"
            testimonials={testimonials}
            locale={locale}
            cmsData={testimonialsSectionData}
          />
        );
      case 'destinations':
        // Skip if CMS blocks are used
        if (hasCmsBlocks) return null;
        return (
          <DestinationsBentoGrid
            key="destinations"
            locale={locale}
            cmsDestinations={featuredDestinations}
          />
        );
      case 'cta':
        // Skip if CMS blocks are used, or if disabled in CMS settings
        if (hasCmsBlocks || !customExperienceData.enabled) return null;
        return <CTASectionWithBlobs key="cta" locale={locale} cmsData={customExperienceData} />;
      case 'blog':
        // Only render if CMS enabled
        if (!blogSectionData.enabled) return null;
        return <BlogSection key="blog" locale={locale} cmsData={blogSectionData} />;
      case 'newsletter':
        // Only render if CMS enabled
        if (!newsletterData.enabled) return null;
        return <NewsletterSection key="newsletter" cmsData={newsletterData} />;
      default:
        return null;
    }
  };

  return (
    <MainLayout locale={locale}>
      {/* Render sections dynamically based on admin-configured order */}
      {enabledSections.map((section: HomepageSection) => renderSection(section.id))}

      {/* CMS blocks are rendered after all sections if available */}
      {hasCmsBlocks && <BlockRenderer blocks={cmsPage.contentBlocks} />}
    </MainLayout>
  );
}
