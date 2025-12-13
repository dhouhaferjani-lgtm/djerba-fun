'use client';

/* eslint-disable @typescript-eslint/no-explicit-any -- next-intl requires typed routes, using any for dynamic paths */
import { usePathname, useRouter } from 'next/navigation';
import { Globe } from 'lucide-react';

interface LocaleSwitcherProps {
  locale: string;
}

const locales = [
  { code: 'en', label: 'English' },
  { code: 'fr', label: 'Français' },
];

export function LocaleSwitcher({ locale }: LocaleSwitcherProps) {
  const pathname = usePathname();
  const router = useRouter();

  const switchLocale = (newLocale: string) => {
    const segments = pathname.split('/');
    segments[1] = newLocale;
    router.push(segments.join('/') as any);
  };

  const currentLocale = locales.find((l) => l.code === locale);
  const otherLocale = locales.find((l) => l.code !== locale);

  return (
    <button
      onClick={() => otherLocale && switchLocale(otherLocale.code)}
      className="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium text-neutral-700 hover:bg-neutral-100 transition-colors"
      aria-label={`Switch to ${otherLocale?.label}`}
    >
      <Globe className="h-4 w-4" />
      <span>{currentLocale?.label}</span>
    </button>
  );
}
