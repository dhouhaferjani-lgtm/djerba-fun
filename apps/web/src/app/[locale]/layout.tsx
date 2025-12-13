import type { Metadata } from 'next';
import { Inter, Poppins } from 'next/font/google';
import { NextIntlClientProvider } from 'next-intl';
import { getMessages, setRequestLocale } from 'next-intl/server';
import { routing } from '@/i18n/routing';
import { Providers } from '@/lib/providers/Providers';
import '../globals.css';

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
      <body className={`${inter.variable} ${poppins.variable} font-sans antialiased`}>
        <NextIntlClientProvider messages={messages}>
          <Providers>{children}</Providers>
        </NextIntlClientProvider>
      </body>
    </html>
  );
}
