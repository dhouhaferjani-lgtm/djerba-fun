import type { Metadata } from 'next';
import Script from 'next/script';
import { NextIntlClientProvider } from 'next-intl';
import { getMessages, setRequestLocale } from 'next-intl/server';
import { routing } from '@/i18n/routing';
import { Providers } from '@/lib/providers/Providers';
import { BrandingProvider } from '@/lib/contexts/BrandingContext';
import CookieConsentBanner from '@/components/consent/CookieConsentBanner';
import { OrganizationJsonLd } from '@/components/seo/JsonLd';
import { WebVitals } from '../web-vitals';
import { getBrandingUrls, getSchemaOrgData } from '@/lib/api/server';
import '../globals.css';
// Leaflet CSS must be imported in layout (not in dynamically imported components)
// to ensure CSS is loaded before map components render
import 'leaflet/dist/leaflet.css';

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://evasiondjerba.com';
// Fallback GA ID from env (CMS value is preferred and fetched in layout)
const FALLBACK_GA_ID = process.env.NEXT_PUBLIC_GA_ID;

// Force dynamic rendering for all routes under this layout
// This prevents build-time API calls and enables server-side rendering
export const dynamic = 'force-dynamic';

// Default metadata values (fallback if API fails)
const DEFAULT_TITLE = "Evasion Djerba - Vivez l'île autrement";
const DEFAULT_DESCRIPTION =
  'Découvrez Djerba avec des excursions uniques, activités nautiques et hébergements authentiques. Votre aventure méditerranéenne commence ici!';

/**
 * Generate dynamic metadata from platform settings.
 * Falls back to defaults if API is unavailable.
 */
export async function generateMetadata({
  params,
}: {
  params: Promise<{ locale: string }>;
}): Promise<Metadata> {
  const { locale } = await params;
  const branding = await getBrandingUrls(locale);

  const platformName = branding.platformName || 'Evasion Djerba';
  const title = branding.tagline ? `${platformName} - ${branding.tagline}` : DEFAULT_TITLE;
  const description = branding.description || DEFAULT_DESCRIPTION;

  // Use dynamic OG image from platform settings, or fall back to static
  const ogImage = branding.ogImage || '/og-image.png';

  return {
    title: {
      template: `%s | ${platformName}`,
      default: title,
    },
    description,
    keywords: [
      'djerba',
      'tunisie',
      'tunisia',
      'excursions',
      'activités nautiques',
      'jet ski',
      'parachute ascensionnel',
      'hébergement',
      'méditerranée',
      'tourisme',
    ],
    authors: [{ name: platformName }],
    creator: platformName,
    publisher: platformName,
    openGraph: {
      type: 'website',
      locale: locale === 'fr' ? 'fr_FR' : 'en_US',
      alternateLocale: locale === 'fr' ? 'en_US' : 'fr_FR',
      siteName: platformName,
      title,
      description,
      images: [
        {
          url: ogImage,
          width: 1200,
          height: 630,
          alt: title,
        },
      ],
    },
    twitter: {
      card: 'summary_large_image',
      title,
      description,
      creator: '@evasiondjerba',
      images: [ogImage],
    },
    robots: {
      index: true,
      follow: true,
      googleBot: {
        index: true,
        follow: true,
        'max-video-preview': -1,
        'max-image-preview': 'large',
        'max-snippet': -1,
      },
    },
    metadataBase: new URL(SITE_URL),
    alternates: {
      canonical: '/',
      languages: {
        en: '/en',
        fr: '/fr',
      },
    },
    // Favicon icons - use proxy routes for consistent delivery
    // These routes dynamically fetch from platform settings API
    icons: {
      icon: '/favicon.ico',
      apple: '/apple-touch-icon.png',
    },
  };
}

export function generateStaticParams() {
  return routing.locales.map((locale) => ({ locale }));
}

export default async function LocaleLayout({
  children,
  params,
}: Readonly<{
  children: React.ReactNode;
  params: Promise<{ locale: string }>;
}>) {
  const { locale } = await params;
  setRequestLocale(locale);
  const messages = await getMessages();

  // Fetch CMS data for analytics and schema
  const branding = await getBrandingUrls(locale);
  const schemaData = await getSchemaOrgData(locale);

  // Use CMS analytics ID with env fallback
  const gaTrackingId = branding.ga4MeasurementId || FALLBACK_GA_ID;

  return (
    <>
      {/* Google Analytics 4 - CMS value with env fallback */}
      {gaTrackingId && (
        <>
          <Script
            src={`https://www.googletagmanager.com/gtag/js?id=${gaTrackingId}`}
            strategy="afterInteractive"
          />
          <Script id="google-analytics" strategy="afterInteractive">
            {`
              window.dataLayer = window.dataLayer || [];
              function gtag(){dataLayer.push(arguments);}
              gtag('js', new Date());
              gtag('config', '${gaTrackingId}', {
                page_path: window.location.pathname,
              });
            `}
          </Script>
        </>
      )}

      {/* Web Vitals Monitoring */}
      <WebVitals />

      {/* Organization Schema - dynamic from CMS API or fallback */}
      {schemaData ? (
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(schemaData) }}
        />
      ) : (
        <OrganizationJsonLd
          name={branding.platformName || 'Evasion Djerba'}
          url={SITE_URL}
          logo={branding.logoLight || `${SITE_URL}/logo.png`}
          description={
            branding.description ||
            'Découvrez Djerba avec des excursions uniques, activités nautiques et hébergements authentiques. Votre aventure méditerranéenne commence ici!'
          }
          sameAs={[
            'https://facebook.com/evasiondjerba',
            'https://instagram.com/evasiondjerba',
            'https://tiktok.com/@evasiondjerba',
          ]}
        />
      )}
      <NextIntlClientProvider messages={messages}>
        <BrandingProvider
          branding={{
            logoLight: branding.logoLight,
            logoDark: branding.logoDark,
            platformName: branding.platformName,
          }}
        >
          <Providers>{children}</Providers>
        </BrandingProvider>
        <CookieConsentBanner />
      </NextIntlClientProvider>
    </>
  );
}
