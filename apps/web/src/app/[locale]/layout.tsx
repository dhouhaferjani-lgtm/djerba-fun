import type { Metadata } from 'next';
import Script from 'next/script';
import { NextIntlClientProvider } from 'next-intl';
import { getMessages, setRequestLocale } from 'next-intl/server';
import { routing } from '@/i18n/routing';
import { Providers } from '@/lib/providers/Providers';
import CookieConsentBanner from '@/components/consent/CookieConsentBanner';
import { OrganizationJsonLd } from '@/components/seo/JsonLd';
import { WebVitals } from '../web-vitals';
import { getBrandingUrls } from '@/lib/api/server';
import '../globals.css';

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://goadventure.com';
const GA_TRACKING_ID = process.env.NEXT_PUBLIC_GA_ID;

// Default metadata values (fallback if API fails)
const DEFAULT_TITLE = 'Go Adventure - Tourism Marketplace';
const DEFAULT_DESCRIPTION =
  'Discover and book unique tours, activities, and events. Your trusted marketplace for unforgettable travel experiences.';

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

  const platformName = branding.platformName || 'Go Adventure';
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
      'tourism',
      'tours',
      'activities',
      'events',
      'adventure',
      'travel',
      'marketplace',
      'experiences',
      'outdoor activities',
      'guided tours',
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
      creator: '@goadventure',
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

  return (
    <>
      {/* Google Analytics 4 */}
      {GA_TRACKING_ID && (
        <>
          <Script
            src={`https://www.googletagmanager.com/gtag/js?id=${GA_TRACKING_ID}`}
            strategy="afterInteractive"
          />
          <Script id="google-analytics" strategy="afterInteractive">
            {`
              window.dataLayer = window.dataLayer || [];
              function gtag(){dataLayer.push(arguments);}
              gtag('js', new Date());
              gtag('config', '${GA_TRACKING_ID}', {
                page_path: window.location.pathname,
              });
            `}
          </Script>
        </>
      )}

      {/* Web Vitals Monitoring */}
      <WebVitals />

      {/* Organization Schema for Go Adventure platform */}
      <OrganizationJsonLd
        name="Go Adventure"
        url={SITE_URL}
        logo={`${SITE_URL}/logo.png`}
        description="Discover and book unique tours, activities, and events. Your trusted marketplace for unforgettable travel experiences."
        sameAs={[
          'https://facebook.com/goadventure',
          'https://instagram.com/goadventure',
          'https://twitter.com/goadventure',
        ]}
      />
      <NextIntlClientProvider messages={messages}>
        <Providers>{children}</Providers>
        <CookieConsentBanner />
      </NextIntlClientProvider>
    </>
  );
}
