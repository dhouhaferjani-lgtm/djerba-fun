import type { Metadata } from 'next';
import Image from 'next/image';
import { getTranslations, setRequestLocale } from 'next-intl/server';
import { MainLayout } from '@/components/templates/MainLayout';
import { getAboutPageData } from '@/lib/api/server';

export async function generateMetadata({
  params,
}: {
  params: Promise<{ locale: string }>;
}): Promise<Metadata> {
  const { locale } = await params;
  const t = await getTranslations({ locale, namespace: 'about' });
  return {
    title: t('page_title'),
    description: t('meta_description'),
  };
}

// Commitment icons as SVG components (cream/white line art style)
const SustainableIcon = () => (
  <svg
    className="w-16 h-16"
    viewBox="0 0 64 64"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.5"
  >
    <circle cx="32" cy="32" r="28" />
    <path d="M32 4C20 14 14 24 14 32c0 10 8 18 18 18s18-8 18-18c0-8-6-18-18-28z" />
    <path d="M32 14c-6 6-10 12-10 18 0 6 4 10 10 10s10-4 10-10c0-6-4-12-10-18z" />
    <path d="M28 36c0-2 2-4 4-4s4 2 4 4" />
  </svg>
);

const ActiveIcon = () => (
  <svg
    className="w-16 h-16"
    viewBox="0 0 64 64"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.5"
  >
    <circle cx="32" cy="20" r="6" />
    <circle cx="22" cy="44" r="10" />
    <circle cx="44" cy="44" r="10" />
    <path d="M32 26v8l-6 10" />
    <path d="M32 34l10 10" />
    <path d="M18 38l8-4" />
    <path d="M48 38l-8-4" />
  </svg>
);

const ImmersionIcon = () => (
  <svg
    className="w-16 h-16"
    viewBox="0 0 64 64"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.5"
  >
    <path d="M12 32c6-4 12-6 20-6s14 2 20 6" />
    <path d="M20 26c4-2 8-4 12-4s8 2 12 4" />
    <path d="M16 44l8-8 8 8" />
    <path d="M32 44l8-8 8 8" />
    <circle cx="32" cy="22" r="4" />
  </svg>
);

const PassionIcon = () => (
  <svg
    className="w-16 h-16"
    viewBox="0 0 64 64"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.5"
  >
    <path d="M32 56c-12-8-20-16-20-26 0-8 6-14 14-14 4 0 6 2 6 2s2-2 6-2c8 0 14 6 14 14 0 10-8 18-20 26z" />
    <circle cx="32" cy="34" r="8" />
    <circle cx="32" cy="34" r="4" />
  </svg>
);

// Quality icon (star)
const QualityIcon = () => (
  <svg
    className="w-16 h-16"
    viewBox="0 0 64 64"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.5"
  >
    <path d="M32 8l8 16 16 4-12 12 2 18-14-8-14 8 2-18-12-12 16-4z" />
  </svg>
);

// Safety icon (shield)
const SafetyIcon = () => (
  <svg
    className="w-16 h-16"
    viewBox="0 0 64 64"
    fill="none"
    stroke="currentColor"
    strokeWidth="1.5"
  >
    <path d="M32 4L8 14v18c0 14 10 24 24 28 14-4 24-14 24-28V14L32 4z" />
    <path d="M24 32l6 6 10-12" />
  </svg>
);

// Helper function to get icon component by name
function getCommitmentIcon(iconName: string) {
  const icons: Record<string, () => React.JSX.Element> = {
    sustainable: SustainableIcon,
    active: ActiveIcon,
    immersion: ImmersionIcon,
    passion: PassionIcon,
    quality: QualityIcon,
    safety: SafetyIcon,
  };
  return icons[iconName] || PassionIcon;
}

export default async function AboutPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations('about');

  // Fetch CMS data for the about page
  const cmsData = await getAboutPageData(locale);

  // Use CMS commitments if available, otherwise use default from translations
  const commitments =
    cmsData.commitments.length > 0
      ? cmsData.commitments.map((c) => ({
          title: c.title,
          description: c.description,
          Icon: getCommitmentIcon(c.icon),
        }))
      : [
          {
            title: t('sustainable'),
            description: t('sustainable_desc'),
            Icon: SustainableIcon,
          },
          {
            title: t('active_lifestyle'),
            description: t('active_lifestyle_desc'),
            Icon: ActiveIcon,
          },
          {
            title: t('local_immersion'),
            description: t('local_immersion_desc'),
            Icon: ImmersionIcon,
          },
          {
            title: t('expertise'),
            description: t('expertise_desc'),
            Icon: PassionIcon,
          },
        ];

  // Use CMS initiatives if available, otherwise use defaults
  const initiatives =
    cmsData.initiatives.length > 0
      ? cmsData.initiatives
      : [
          { image: '/images/about/initiatives/workshop.png', alt: 'Educational Workshop' },
          { image: '/images/about/initiatives/sports.png', alt: 'Sports Activities' },
          { image: '/images/about/initiatives/heritage.png', alt: 'Heritage & Craft' },
        ];

  // Use CMS partners if available, otherwise use defaults
  const partners =
    cmsData.partners.length > 0
      ? cmsData.partners
      : [
          { name: 'Partner 1', logo: '/images/about/partners/partner-1.png' },
          { name: 'Partner 2', logo: '/images/about/partners/partner-2.png' },
          { name: 'Partner 3', logo: '/images/about/partners/partner-3.png' },
          { name: 'Partner 4', logo: '/images/about/partners/partner-4.png' },
          { name: 'Partner 5', logo: '/images/about/partners/partner-5.png' },
          { name: 'Partner 6', logo: '/images/about/partners/partner-6.png' },
          { name: 'Partner 7', logo: '/images/about/partners/partner-7.png' },
        ];

  return (
    <MainLayout locale={locale}>
      {/* 1. Hero Section */}
      <section className="relative min-h-[500px] flex items-center justify-center">
        <Image
          src={cmsData.hero.image || '/images/about/hero-banner.jpg'}
          alt={cmsData.hero.title || t('hero_title')}
          fill
          className="object-cover"
          priority
        />
        <div className="absolute inset-0 bg-primary/70" />
        <div className="relative z-10 max-w-4xl mx-auto px-4 text-center text-white py-24">
          <p className="text-primary-light text-lg mb-4 italic">
            {cmsData.hero.tagline || t('tagline')}
          </p>
          <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold mb-6">
            {cmsData.hero.title || t('hero_title')}
          </h1>
          <p className="text-xl text-white/90 max-w-3xl mx-auto leading-relaxed">
            {cmsData.hero.subtitle || t('hero_subtitle')}
          </p>
        </div>
      </section>

      {/* 2. L'Aventurier Section - Cream background with green text */}
      <section className="bg-neutral-100 py-16">
        <div className="max-w-6xl mx-auto px-4 sm:px-6">
          <h2 className="text-primary text-2xl md:text-3xl font-bold text-center mb-12 uppercase tracking-wide">
            {t('our_story')}
          </h2>
          <div className="grid md:grid-cols-2 gap-12 items-center">
            <div>
              <h3 className="text-4xl md:text-5xl font-bold text-gray-900 leading-tight">
                {cmsData.story.heading || t('laventurier_heading')}
              </h3>
            </div>
            <div className="text-gray-700 space-y-6">
              <p className="text-lg">{cmsData.story.intro || t('laventurier_intro')}</p>
              <p className="text-lg">{cmsData.story.text1 || t('laventurier_desc1')}</p>
              <p className="text-lg">{cmsData.story.text2 || t('laventurier_desc2')}</p>
            </div>
          </div>
        </div>
      </section>

      {/* 3. Founder Section */}
      <section className="py-16 bg-white">
        <div className="max-w-6xl mx-auto px-4 sm:px-6">
          <div className="grid md:grid-cols-2 gap-12 items-center">
            {/* Founder Photo with curved frame */}
            <div className="relative">
              <div className="relative w-full max-w-md mx-auto aspect-square overflow-hidden rounded-[0_50%_50%_0] bg-gray-200">
                <Image
                  src={cmsData.founder.photo || '/images/about/founder-seif.jpg'}
                  alt={cmsData.founder.name || 'Seif Ben Helel'}
                  fill
                  className="object-cover"
                />
              </div>
            </div>
            {/* Bio Text */}
            <div>
              <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-6 uppercase">
                {t('founder_title')}
              </h2>
              <div className="prose prose-lg text-gray-700 space-y-4">
                <p>{cmsData.founder.story || t('founder_story')}</p>
              </div>
              <blockquote className="mt-8 bg-[#fde68a] p-6 rounded-lg border-l-4 border-primary">
                <p className="text-gray-800 italic text-lg">
                  "{cmsData.founder.quote || t('founder_quote')}"
                </p>
              </blockquote>
            </div>
          </div>
        </div>
      </section>

      {/* 4. Nos Engagements Section */}
      <section className="py-16 bg-primary">
        <div className="max-w-6xl mx-auto px-4 sm:px-6">
          <h2 className="text-3xl md:text-4xl font-bold text-[#fef3c7] text-center mb-12 uppercase">
            {t('commitments')}
          </h2>
          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            {commitments.map((commitment, index) => (
              <div key={index} className="text-center">
                <div className="text-[#fef3c7] mb-4 flex justify-center">
                  <commitment.Icon />
                </div>
                <h3 className="text-lg font-bold text-white mb-3 uppercase">{commitment.title}</h3>
                <p className="text-white/80 text-sm leading-relaxed">{commitment.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 5. Team + Initiatives Side-by-Side */}
      <section className="py-16 bg-gray-100">
        <div className="max-w-6xl mx-auto px-4 sm:px-6">
          <div className="grid md:grid-cols-2 gap-6">
            {/* Team Box - Cream */}
            <div className="bg-neutral-100 p-8 rounded-lg">
              <h3 className="text-2xl font-bold text-gray-900 mb-4 uppercase">
                {cmsData.team.title || t('team')}
              </h3>
              <p className="text-gray-700 leading-relaxed">
                {cmsData.team.description || t('team_desc')}
              </p>
            </div>
            {/* Initiatives Box - Lime */}
            <div className="bg-[#4ade9a] p-8 rounded-lg">
              <h3 className="text-2xl font-bold text-gray-900 mb-4 uppercase">
                {t('initiatives')}
              </h3>
              <p className="text-gray-800 mb-4 leading-relaxed">{t('initiatives_desc')}</p>
              <ul className="space-y-2 text-gray-800">
                <li className="flex items-start">
                  <span className="mr-2">•</span>
                  <span>{t('initiatives_bullet1')}</span>
                </li>
                <li className="flex items-start">
                  <span className="mr-2">•</span>
                  <span>{t('initiatives_bullet2')}</span>
                </li>
                <li className="flex items-start">
                  <span className="mr-2">•</span>
                  <span>{t('initiatives_bullet3')}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </section>

      {/* 6. 1% Impact Banner */}
      <section className="py-8 bg-primary">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 text-center">
          <p className="text-xl md:text-2xl text-white italic">
            {cmsData.impactText || t('impact_desc')}
          </p>
        </div>
      </section>

      {/* 7. Initiative Images - Large, Full Width, Black & White */}
      {initiatives.length > 0 && (
        <section className="bg-white">
          <div className="grid grid-cols-1 md:grid-cols-3">
            {initiatives.map((initiative, index) => (
              <div key={index} className="relative h-80 md:h-[400px] overflow-hidden">
                <Image
                  src={initiative.image}
                  alt={initiative.alt}
                  fill
                  className="object-cover grayscale hover:grayscale-0 transition-all duration-500"
                />
              </div>
            ))}
          </div>
        </section>
      )}

      {/* 8. Partner Logos - All on one line */}
      <section className="py-16 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6">
          <h2 className="text-2xl md:text-3xl font-bold text-primary text-center mb-12 uppercase">
            {t('partners')}
          </h2>
          <div className="flex justify-center items-center gap-4 md:gap-8 flex-nowrap overflow-x-auto pb-4">
            {partners.map((partner, index) => (
              <div
                key={index}
                className="relative w-16 h-16 md:w-24 md:h-24 flex-shrink-0 hover:scale-110 transition-transform duration-300"
              >
                <Image
                  src={partner.logo}
                  alt={partner.name || `Partner ${index + 1}`}
                  fill
                  className="object-contain"
                />
              </div>
            ))}
          </div>
        </div>
      </section>
    </MainLayout>
  );
}
