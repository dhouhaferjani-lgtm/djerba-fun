import type { MetadataRoute } from 'next';
import { colors } from '@djerba-fun/ui';

/**
 * PWA Manifest
 *
 * Defines how the application should behave when installed as a PWA.
 * Colors are imported from the design system for consistency.
 */
export default function manifest(): MetadataRoute.Manifest {
  return {
    name: "Evasion Djerba - Vivez l'île autrement",
    short_name: 'Evasion Djerba',
    description:
      'Découvrez Djerba avec des excursions uniques, activités nautiques et hébergements authentiques. Votre aventure méditerranéenne commence ici!',
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
