import type { Metadata } from 'next';
import Image from 'next/image';
import { getTranslations, setRequestLocale } from 'next-intl/server';
import { Link } from '@/i18n/navigation';

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

export default async function AboutPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  setRequestLocale(locale);
  const t = await getTranslations('about');

  const commitments = [
    {
      title: t('sustainable'),
      description: t('sustainable_desc'),
      image: '/images/about/commitments/sustainable.jpg',
      icon: (
        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={1.5}
            d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
      ),
    },
    {
      title: t('active_lifestyle'),
      description: t('active_lifestyle_desc'),
      image: '/images/about/commitments/active-lifestyle.jpg',
      icon: (
        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={1.5}
            d="M13 10V3L4 14h7v7l9-11h-7z"
          />
        </svg>
      ),
    },
    {
      title: t('local_immersion'),
      description: t('local_immersion_desc'),
      image: '/images/about/commitments/local-immersion.jpg',
      icon: (
        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={1.5}
            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
          />
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={1.5}
            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
          />
        </svg>
      ),
    },
    {
      title: t('expertise'),
      description: t('expertise_desc'),
      image: '/images/about/commitments/passion.jpg',
      icon: (
        <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={1.5}
            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
          />
        </svg>
      ),
    },
  ];

  const initiatives = [
    { image: '/images/about/initiatives/workshop.png', alt: 'Educational Workshop' },
    { image: '/images/about/initiatives/sports.png', alt: 'Sports Activities' },
    { image: '/images/about/initiatives/heritage.png', alt: 'Heritage & Craft' },
  ];

  const partners = [
    '/images/about/partners/partner-1.png',
    '/images/about/partners/partner-2.png',
    '/images/about/partners/partner-3.png',
    '/images/about/partners/partner-4.png',
    '/images/about/partners/partner-5.png',
    '/images/about/partners/partner-6.png',
    '/images/about/partners/partner-7.png',
  ];

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section with Background Image */}
      <section className="relative min-h-[400px] flex items-center justify-center">
        <Image
          src="/images/about/hero-banner.png"
          alt="About Go Adventure"
          fill
          className="object-cover"
          priority
        />
        <div className="absolute inset-0 bg-primary/80" />
        <div className="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 text-center text-white py-20">
          <p className="text-primary-light text-lg mb-4 italic">{t('tagline')}</p>
          <h1 className="text-4xl md:text-5xl font-bold mb-6">{t('hero_title')}</h1>
          <p className="text-xl text-white/90 max-w-3xl mx-auto">{t('hero_subtitle')}</p>
        </div>
      </section>

      {/* Our Story Section */}
      <section className="py-16 bg-white">
        <div className="max-w-4xl mx-auto px-4 sm:px-6">
          <h2 className="text-3xl font-bold text-gray-900 mb-8 text-center">{t('our_story')}</h2>
          <div className="prose prose-lg max-w-none text-gray-700">
            <p className="text-lg leading-relaxed mb-8">{t('founder_story')}</p>
            <blockquote className="border-l-4 border-primary pl-6 italic text-gray-600 my-8">
              "{t('founder_quote')}"
            </blockquote>
          </div>
          <div className="mt-8 text-center">
            <p className="text-gray-900 font-semibold">— {t('founder_name')}</p>
          </div>
        </div>
      </section>

      {/* Commitments Section with Images */}
      <section className="py-16 bg-gray-50">
        <div className="max-w-6xl mx-auto px-4 sm:px-6">
          <h2 className="text-3xl font-bold text-gray-900 mb-12 text-center">{t('commitments')}</h2>
          <div className="grid md:grid-cols-2 gap-8">
            {commitments.map((commitment, index) => (
              <div
                key={index}
                className="relative overflow-hidden rounded-lg shadow-sm hover:shadow-lg transition-shadow group"
              >
                <div className="relative h-48">
                  <Image
                    src={commitment.image}
                    alt={commitment.title}
                    fill
                    className="object-cover group-hover:scale-105 transition-transform duration-300"
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent" />
                  <div className="absolute bottom-4 left-4 text-white">{commitment.icon}</div>
                </div>
                <div className="bg-white p-6">
                  <h3 className="text-xl font-semibold text-gray-900 mb-3">{commitment.title}</h3>
                  <p className="text-gray-600">{commitment.description}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Impact Section */}
      <section className="py-16 bg-primary text-white">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 text-center">
          <h2 className="text-3xl font-bold mb-6">{t('impact')}</h2>
          <p className="text-xl text-white/90 leading-relaxed">{t('impact_desc')}</p>
        </div>
      </section>

      {/* Team Section */}
      <section className="py-16 bg-white">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 text-center">
          <h2 className="text-3xl font-bold text-gray-900 mb-6">{t('team')}</h2>
          <p className="text-lg text-gray-600 leading-relaxed">{t('team_desc')}</p>
        </div>
      </section>

      {/* Initiatives Section with Images */}
      <section className="py-16 bg-gray-50">
        <div className="max-w-6xl mx-auto px-4 sm:px-6">
          <h2 className="text-3xl font-bold text-gray-900 mb-6 text-center">{t('initiatives')}</h2>
          <p className="text-lg text-gray-600 leading-relaxed text-center mb-12 max-w-3xl mx-auto">
            {t('initiatives_desc')}
          </p>
          <div className="grid md:grid-cols-3 gap-6">
            {initiatives.map((initiative, index) => (
              <div
                key={index}
                className="relative h-64 rounded-lg overflow-hidden shadow-md hover:shadow-xl transition-shadow"
              >
                <Image
                  src={initiative.image}
                  alt={initiative.alt}
                  fill
                  className="object-cover hover:scale-105 transition-transform duration-300"
                />
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Partners Section */}
      <section className="py-16 bg-white">
        <div className="max-w-6xl mx-auto px-4 sm:px-6">
          <h2 className="text-3xl font-bold text-gray-900 mb-12 text-center">{t('partners')}</h2>
          <div className="flex flex-wrap justify-center items-center gap-8 md:gap-12">
            {partners.map((partner, index) => (
              <div
                key={index}
                className="relative w-24 h-24 md:w-32 md:h-32 grayscale hover:grayscale-0 transition-all duration-300"
              >
                <Image src={partner} alt={`Partner ${index + 1}`} fill className="object-contain" />
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Back to Home */}
      <section className="py-8 bg-gray-50 border-t">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 text-center">
          <Link href="/" className="text-primary hover:underline inline-flex items-center gap-2">
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M15 19l-7-7 7-7"
              />
            </svg>
            {locale === 'fr' ? "Retour à l'accueil" : 'Back to Home'}
          </Link>
        </div>
      </section>
    </div>
  );
}
