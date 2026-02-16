/**
 * Performance Optimization: Optimized font loading
 *
 * Root layout provides HTML structure with optimized font loading.
 * Uses next/font for automatic font optimization with:
 * - display: 'swap' to prevent invisible text (FOIT)
 * - Preload fonts for better LCP
 * - Subset optimization for smaller file sizes
 *
 * Benefits:
 * - Better Largest Contentful Paint (LCP)
 * - Prevents layout shift from font loading
 * - Automatic font subsetting and optimization
 */

import { Inter, Poppins, Playfair_Display } from 'next/font/google';
import { getLocale } from 'next-intl/server';

// Inter font for body text - optimized with Latin subset
const inter = Inter({
  variable: '--font-inter',
  subsets: ['latin'],
  display: 'swap', // Show fallback font immediately, swap when loaded
  preload: true, // Preload for faster initial render
  adjustFontFallback: true, // Adjust size to match fallback font
});

// Poppins font for headings - specific weights only
const poppins = Poppins({
  variable: '--font-poppins',
  subsets: ['latin'],
  weight: ['400', '500', '600', '700'], // Only weights we use
  display: 'swap',
  preload: true,
  adjustFontFallback: true,
});

// Playfair Display font for hero headlines - elegant serif italic
const playfair = Playfair_Display({
  variable: '--font-playfair',
  subsets: ['latin'],
  weight: ['400', '500', '600', '700'],
  style: ['normal', 'italic'],
  display: 'swap',
  preload: true,
});

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  const locale = await getLocale();
  return (
    <html lang={locale} suppressHydrationWarning>
      <head>
        {/* Preconnect to external domains for faster resource loading */}
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
      </head>
      <body
        className={`${inter.variable} ${poppins.variable} ${playfair.variable} font-sans antialiased`}
        suppressHydrationWarning
      >
        {children}
      </body>
    </html>
  );
}
