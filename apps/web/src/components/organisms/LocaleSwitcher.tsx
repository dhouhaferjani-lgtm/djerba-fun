'use client';

import * as DropdownMenu from '@radix-ui/react-dropdown-menu';
import { useRouter, usePathname } from '@/i18n/navigation';
import { useState, useEffect } from 'react';
import { ChevronDown, Check } from 'lucide-react';

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

  useEffect(() => {
    setMounted(true);
  }, []);

  const switchLocale = (newLocale: string) => {
    router.replace(pathname, { locale: newLocale as 'en' | 'fr' | 'ar' });
  };

  const currentLocale = locales.find((l) => l.code === locale) || locales[0];

  // SSR placeholder to prevent hydration mismatch
  if (!mounted) {
    return (
      <div className="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium text-white">
        <span className="text-lg">🌐</span>
        <span>{locale.toUpperCase()}</span>
      </div>
    );
  }

  return (
    <DropdownMenu.Root>
      <DropdownMenu.Trigger asChild>
        <button
          className="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium text-white hover:bg-white/10 transition-colors outline-none"
          aria-label="Select language"
        >
          <span className="text-lg">{currentLocale.flag}</span>
          <span>{currentLocale.code.toUpperCase()}</span>
          <ChevronDown className="h-4 w-4 opacity-70" />
        </button>
      </DropdownMenu.Trigger>

      <DropdownMenu.Portal>
        <DropdownMenu.Content
          className="min-w-[160px] bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50 animate-in fade-in-0 zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95"
          sideOffset={8}
          align="end"
        >
          {locales.map((loc) => (
            <DropdownMenu.Item
              key={loc.code}
              className="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 cursor-pointer outline-none data-[highlighted]:bg-gray-50"
              onSelect={() => switchLocale(loc.code)}
            >
              <span className="text-lg">{loc.flag}</span>
              <span className="flex-1">{loc.label}</span>
              {locale === loc.code && <Check className="h-4 w-4 text-primary" />}
            </DropdownMenu.Item>
          ))}
        </DropdownMenu.Content>
      </DropdownMenu.Portal>
    </DropdownMenu.Root>
  );
}
