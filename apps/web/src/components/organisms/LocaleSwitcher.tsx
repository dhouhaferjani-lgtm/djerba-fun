'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- next-intl requires typed routes, using any for dynamic paths */
import { usePathname, useRouter } from 'next/navigation';
import { useState, useEffect } from 'react';

interface LocaleSwitcherProps {
  locale: string;
}

const locales = [
  { code: 'en', label: 'English', flag: '🇬🇧' },
  { code: 'fr', label: 'Français', flag: '🇫🇷' },
];

const DEFAULT_LOCALE = 'fr';
const SUPPORTED_LOCALES = ['en', 'fr', 'ar'];

export function LocaleSwitcher({ locale }: LocaleSwitcherProps) {
  const pathname = usePathname();
  const router = useRouter();
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  const switchLocale = (newLocale: string) => {
    // Get the path without the current locale prefix
    let pathWithoutLocale = pathname;

    // Check if current path starts with a locale prefix
    for (const loc of SUPPORTED_LOCALES) {
      if (pathname.startsWith(`/${loc}/`)) {
        pathWithoutLocale = pathname.slice(loc.length + 1); // Remove "/{locale}"
        break;
      } else if (pathname === `/${loc}`) {
        pathWithoutLocale = '/';
        break;
      }
    }

    // Build new path based on target locale
    let newPath: string;
    if (newLocale === DEFAULT_LOCALE) {
      // French is default - no prefix needed
      newPath = pathWithoutLocale || '/';
    } else {
      // Non-default locale - add prefix
      newPath = `/${newLocale}${pathWithoutLocale}`;
    }

    router.push(newPath as any);
  };

  const currentLocale = locales.find((l) => l.code === locale);
  const otherLocale = locales.find((l) => l.code !== locale);

  if (!mounted) {
    return (
      <div className="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium text-white">
        <span className="text-lg">🌐</span>
        <span>{locale.toUpperCase()}</span>
      </div>
    );
  }

  return (
    <button
      onClick={() => otherLocale && switchLocale(otherLocale.code)}
      className="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium text-white hover:bg-white/10 transition-colors"
      aria-label={`Switch to ${otherLocale?.label}`}
    >
      <span className="text-lg">{currentLocale?.flag}</span>
      <span>{currentLocale?.code.toUpperCase()}</span>
    </button>
  );
}
