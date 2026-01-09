import type { MetadataRoute } from 'next';
import { colors } from '@go-adventure/ui';

/**
 * PWA Manifest
 *
 * Defines how the application should behave when installed as a PWA.
 * Colors are imported from the design system for consistency.
 */
export default function manifest(): MetadataRoute.Manifest {
  return {
    name: 'Go Adventure - Tourism Marketplace',
    short_name: 'Go Adventure',
    description:
      'Discover and book unique tours, activities, and events. Your trusted marketplace for unforgettable travel experiences.',
    start_url: '/',
    display: 'standalone',
    background_color: colors.neutral.white,
    theme_color: colors.primary.DEFAULT,
    orientation: 'portrait-primary',
    scope: '/',
    lang: 'en',
    categories: ['travel', 'tourism', 'lifestyle'],
    icons: [
      {
        src: '/android-chrome-192x192.png',
        sizes: '192x192',
        type: 'image/png',
        purpose: 'maskable',
      },
      {
        src: '/android-chrome-512x512.png',
        sizes: '512x512',
        type: 'image/png',
        purpose: 'maskable',
      },
      {
        src: '/apple-touch-icon.png',
        sizes: '180x180',
        type: 'image/png',
        purpose: 'any',
      },
    ],
    screenshots: [
      {
        src: '/screenshot-1.png',
        sizes: '1280x720',
        type: 'image/png',
        form_factor: 'wide',
      },
      {
        src: '/screenshot-2.png',
        sizes: '750x1334',
        type: 'image/png',
        form_factor: 'narrow',
      },
    ],
  };
}
