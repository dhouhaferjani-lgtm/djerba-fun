import type { NextConfig } from 'next';
import createNextIntlPlugin from 'next-intl/plugin';

const withNextIntl = createNextIntlPlugin('./src/i18n/request.ts');

const nextConfig: NextConfig = {
  output: 'standalone',
  images: {
    remotePatterns: [
      {
        protocol: 'http',
        hostname: 'localhost',
        port: '9000',
        pathname: '/go-adventure/**',
      },
      {
        protocol: 'https',
        hostname: '*.goadventure.com',
      },
    ],
  },
  experimental: {
    typedRoutes: true,
  },
};

export default withNextIntl(nextConfig);
