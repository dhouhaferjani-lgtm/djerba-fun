import { setRequestLocale } from 'next-intl/server';
import { MainLayout } from '@/components/templates/MainLayout';
import { HeroSection } from '@/components/organisms/HeroSection';
import { FeaturedPackagesSection } from '@/components/organisms/FeaturedPackagesSection';
import { LargeBannerSection } from '@/components/organisms/LargeBannerSection';
import { LatestToursEventsSection } from '@/components/organisms/LatestToursEventsSection';
import { InfoSection } from '@/components/organisms/InfoSection';
import {
  DestinationsSection,
  CustomExperienceSection,
  ExperienceTypesSection,
  BlogSection,
  NewsletterSection,
} from '@/components/home';

export default async function HomePage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);

  return (
    <MainLayout locale={locale}>
      <HeroSection locale={locale} />
      <FeaturedPackagesSection />
      <LargeBannerSection />
      <DestinationsSection />
      <LatestToursEventsSection />
      <CustomExperienceSection />
      <ExperienceTypesSection />
      <InfoSection />
      <BlogSection />
      <NewsletterSection />
    </MainLayout>
  );
}
