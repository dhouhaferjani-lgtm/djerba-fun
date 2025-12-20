import type { NextConfig } from 'next';
import createNextIntlPlugin from 'next-intl/plugin';

const withNextIntl = createNextIntlPlugin('./src/i18n/request.ts');

const nextConfig: NextConfig = {
  output: 'standalone',
  images: {
    // Allow localhost/private IPs for local development with MinIO
    dangerouslyAllowSVG: true,
    remotePatterns: [
      {
        protocol: 'http',
        hostname: 'localhost',
        port: '9000',
        pathname: '/go-adventure/**',
        search: '',
      },
      {
        protocol: 'http',
        hostname: 'localhost',
        port: '9002',
        pathname: '/go-adventure/**',
        search: '',
      },
      {
        protocol: 'http',
        hostname: '127.0.0.1',
        port: '9002',
        pathname: '/go-adventure/**',
        search: '',
      },
      {
        protocol: 'https',
        hostname: '*.goadventure.com',
      },
      {
        protocol: 'https',
        hostname: '*.minio.local',
      },
      {
        protocol: 'https',
        hostname: '*.amazonaws.com',
      },
      {
        protocol: 'https',
        hostname: 's3.amazonaws.com',
      },
      {
        protocol: 'https',
        hostname: 'images.unsplash.com',
      },
    ],
    formats: ['image/avif', 'image/webp'],
    deviceSizes: [640, 750, 828, 1080, 1200, 1920, 2048, 3840],
    imageSizes: [16, 32, 48, 64, 96, 128, 256, 384],
  },
  typedRoutes: true,
  // Enable compression
  compress: true,
  // Optimize production builds
  productionBrowserSourceMaps: false,
  // Performance optimizations
  poweredByHeader: false,
};

// Bundle analyzer (enabled with ANALYZE=true)
// Note: Bundle analyzer import is done dynamically to avoid issues
export default withNextIntl(nextConfig);
