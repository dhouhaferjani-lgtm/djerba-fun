import { defineRouting } from 'next-intl/routing';

export const routing = defineRouting({
  // French is default (no prefix), English at /en, Arabic at /ar
  locales: ['fr', 'en', 'ar'],
  defaultLocale: 'fr',
  localePrefix: 'as-needed', // Only add prefix for non-default locales
});

export type Locale = (typeof routing.locales)[number];
