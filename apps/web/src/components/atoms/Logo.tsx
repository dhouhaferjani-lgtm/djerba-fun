'use client';

import Link from 'next/link';
import Image from 'next/image';
import { useLocale } from 'next-intl';
import { usePlatformSettings } from '@/lib/api/hooks';

interface LogoProps {
  variant?: 'light' | 'dark';
  className?: string;
  showText?: boolean;
}

// Next.js 16+ blocks images from private IPs (localhost) for security.
// In production, S3/CloudFront URLs work normally with optimization.
// In development (localhost), we skip optimization.
function isLocalhostUrl(url: string): boolean {
  try {
    const parsed = new URL(url);
    return parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1';
  } catch {
    return false;
  }
}

export function Logo({ variant = 'light', className = '', showText = false }: LogoProps) {
  const locale = useLocale();
  const { data: settings, isLoading } = usePlatformSettings(locale);

  const logoUrl = variant === 'dark' ? settings?.branding?.logoDark : settings?.branding?.logoLight;

  const platformName = settings?.platform?.name || 'Go Adventure';

  // Skip Next.js image optimization for localhost URLs (dev only)
  const skipOptimization = logoUrl ? isLocalhostUrl(logoUrl) : false;

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
          unoptimized={skipOptimization}
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
