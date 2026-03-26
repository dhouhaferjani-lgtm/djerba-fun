'use client';

import Link from 'next/link';
import Image from 'next/image';
import { useLocale } from 'next-intl';
import { usePlatformSettings } from '@/lib/api/hooks';
import { useBranding } from '@/lib/contexts/BrandingContext';
import { shouldUnoptimizeImage } from '@/lib/utils/image';

interface LogoProps {
  variant?: 'light' | 'dark';
  className?: string;
  showText?: boolean;
}

export function Logo({ variant = 'light', className = '', showText = false }: LogoProps) {
  const locale = useLocale();

  // Try server-provided branding context first (prevents logo flash)
  const brandingContext = useBranding();

  // Fallback to client-side fetch if context not available
  const { data: settings, isLoading } = usePlatformSettings(locale);

  const defaultLogo = '/images/evasion-djerba-logo.png';

  // Priority: context (server) -> settings (client cache) -> fallback
  const logoUrl =
    variant === 'dark'
      ? (brandingContext?.logoDark ?? settings?.branding?.logoDark ?? defaultLogo)
      : (brandingContext?.logoLight ?? settings?.branding?.logoLight ?? defaultLogo);

  const platformName =
    brandingContext?.platformName ?? settings?.platform?.name ?? 'Evasion Djerba';

  return (
    <Link href={`/${locale}`} className={`flex items-center gap-2 ${className}`}>
      {logoUrl ? (
        <Image
          src={logoUrl}
          alt={platformName}
          width={180}
          height={50}
          className="h-12 w-auto object-contain"
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
