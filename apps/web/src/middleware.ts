import createMiddleware from 'next-intl/middleware';
import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';
import { routing } from './i18n/routing';

// Create the next-intl middleware with locale detection disabled
// This ensures the default URL (/) always shows French content
// Users must explicitly switch languages via the language switcher
const intlMiddleware = createMiddleware({
  ...routing,
  localeDetection: false,
});

export default async function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Redirect removed /ar locale to French equivalent (SEO preservation)
  // Arabic was disabled but /ar/* URLs are indexed by search engines
  if (pathname === '/ar' || pathname.startsWith('/ar/')) {
    const newPath = pathname.replace(/^\/ar\/?/, '/') || '/';
    return NextResponse.redirect(new URL(newPath, request.url), 301);
  }

  // Handle redirects from old URL structure to new location-first structure
  // Old: /{locale}/listings/{slug}
  // New: /{location}/{slug} (fr) or /{locale}/{location}/{slug} (en/ar)

  // Match pattern: /{locale}/listings/{slug}
  const oldListingPattern = /^\/(fr|en)\/listings\/([a-z0-9-]+)$/;
  const match = pathname.match(oldListingPattern);

  if (match) {
    const [, locale, slug] = match;

    // For now, redirect to a generic location "tunisia"
    // In production, this would fetch the listing's actual location
    const newPath = locale === 'fr' ? `/tunisia/${slug}` : `/${locale}/tunisia/${slug}`;

    return NextResponse.redirect(new URL(newPath, request.url), 301);
  }

  // Let next-intl handle locale detection and routing
  return intlMiddleware(request);
}

export const config = {
  matcher: [
    // Match all pathnames except for
    // - /api (API routes)
    // - /_next (Next.js internals)
    // - /static (static files)
    // - .*\\..*$ (files with extensions like .js, .css, etc.)
    '/((?!api|_next|static|.*\\..*$).*)',
  ],
};
