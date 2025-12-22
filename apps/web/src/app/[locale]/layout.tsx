import type { Metadata } from 'next';
import Script from 'next/script';
import { Inter, Poppins } from 'next/font/google';
import { NextIntlClientProvider } from 'next-intl';
import { getMessages, setRequestLocale } from 'next-intl/server';
import { routing } from '@/i18n/routing';
import { Providers } from '@/lib/providers/Providers';
import CookieConsentBanner from '@/components/consent/CookieConsentBanner';
import { OrganizationJsonLd } from '@/components/seo/JsonLd';
import { WebVitals } from '../web-vitals';
import '../globals.css';

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://goadventure.com';
const GA_TRACKING_ID = process.env.NEXT_PUBLIC_GA_ID;

const inter = Inter({
  variable: '--font-inter',
  subsets: ['latin'],
  display: 'swap',
});

const poppins = Poppins({
  variable: '--font-poppins',
  subsets: ['latin'],
  weight: ['400', '500', '600', '700'],
  display: 'swap',
});

export const metadata: Metadata = {
  title: {
    template: '%s | Go Adventure',
    default: 'Go Adventure - Tourism Marketplace',
  },
  description:
    'Discover and book unique tours, activities, and events. Your trusted marketplace for unforgettable travel experiences.',
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
  authors: [{ name: 'Go Adventure' }],
  creator: 'Go Adventure',
  publisher: 'Go Adventure',
  openGraph: {
    type: 'website',
    locale: 'en_US',
    alternateLocale: 'fr_FR',
    siteName: 'Go Adventure',
    title: 'Go Adventure - Tourism Marketplace',
    description:
      'Discover and book unique tours, activities, and events. Your trusted marketplace for unforgettable travel experiences.',
    images: [
      {
        url: '/og-image.png',
        width: 1200,
        height: 630,
        alt: 'Go Adventure - Tourism Marketplace',
      },
    ],
  },
  twitter: {
    card: 'summary_large_image',
    title: 'Go Adventure - Tourism Marketplace',
    description:
      'Discover and book unique tours, activities, and events. Your trusted marketplace for unforgettable travel experiences.',
    creator: '@goadventure',
    images: ['/og-image.png'],
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
  metadataBase: new URL(process.env.NEXT_PUBLIC_SITE_URL || 'https://goadventure.com'),
  alternates: {
    canonical: '/',
    languages: {
      en: '/en',
      fr: '/fr',
    },
  },
};

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
    <html lang={locale}>
      <head>
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
      </head>
      <body className={`${inter.variable} ${poppins.variable} font-sans antialiased`}>
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
      </body>
    </html>
  );
}
