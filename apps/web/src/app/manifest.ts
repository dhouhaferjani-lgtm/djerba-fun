import type { MetadataRoute } from 'next';

/**
 * PWA Manifest
 *
 * Defines how the application should behave when installed as a PWA.
 */
export default function manifest(): MetadataRoute.Manifest {
  return {
    name: 'Go Adventure - Tourism Marketplace',
    short_name: 'Go Adventure',
    description:
      'Discover and book unique tours, activities, and events. Your trusted marketplace for unforgettable travel experiences.',
    start_url: '/',
    display: 'standalone',
    background_color: '#ffffff',
    theme_color: '#0D642E',
    orientation: 'portrait-primary',
    scope: '/',
    lang: 'en',
    categories: ['travel', 'tourism', 'lifestyle'],
    icons: [
      {
        src: '/icon-192.png',
        sizes: '192x192',
        type: 'image/png',
        purpose: 'maskable',
      },
      {
        src: '/icon-512.png',
        sizes: '512x512',
        type: 'image/png',
        purpose: 'maskable',
      },
      {
        src: '/icon-384.png',
        sizes: '384x384',
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
