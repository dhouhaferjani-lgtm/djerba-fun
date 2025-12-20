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

export function LocaleSwitcher({ locale }: LocaleSwitcherProps) {
  const pathname = usePathname();
  const router = useRouter();
  const [mounted, setMounted] = useState(false);

  // Prevent hydration mismatch by only rendering after client mount
  useEffect(() => {
    setMounted(true);
  }, []);

  const switchLocale = (newLocale: string) => {
    const segments = pathname.split('/');
    segments[1] = newLocale;
    router.push(segments.join('/') as any);
  };

  const currentLocale = locales.find((l) => l.code === locale);
  const otherLocale = locales.find((l) => l.code !== locale);

  // Show placeholder during SSR to prevent hydration mismatch
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
