import createMiddleware from 'next-intl/middleware';
import { routing } from './i18n/routing';

export default createMiddleware(routing);

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
