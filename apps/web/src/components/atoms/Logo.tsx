'use client';

import Link from 'next/link';
import Image from 'next/image';
import { useLocale } from 'next-intl';
import { usePlatformSettings } from '@/lib/api/hooks';
import { shouldUnoptimizeImage } from '@/lib/utils/image';

interface LogoProps {
  variant?: 'light' | 'dark';
  className?: string;
  showText?: boolean;
}

export function Logo({ variant = 'light', className = '', showText = false }: LogoProps) {
  const locale = useLocale();
  const { data: settings, isLoading } = usePlatformSettings(locale);

  const logoUrl = variant === 'dark' ? settings?.branding?.logoDark : settings?.branding?.logoLight;

  const platformName = settings?.platform?.name || 'Go Adventure';

  return (
    <Link href={`/${locale}`} className={`flex items-center gap-2 ${className}`}>
      {logoUrl ? (
        <Image
          src={logoUrl}
          alt={platformName}
          width={150}
          height={40}
          className="h-10 w-auto object-contain"
          priority
          unoptimized={shouldUnoptimizeImage(logoUrl)}
        />
      ) : (
        <span
          className={`text-2xl font-bold ${variant === 'dark' ? 'text-gray-900' : 'text-white'}`}
        >
          {isLoading ? '' : platformName}
        </span>
      )}
      {showText && logoUrl && (
        <span
          className={`text-xl font-semibold ${variant === 'dark' ? 'text-gray-900' : 'text-white'}`}
        >
          {platformName}
        </span>
      )}
    </Link>
  );
}
