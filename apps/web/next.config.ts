import type { NextConfig } from 'next';
import createNextIntlPlugin from 'next-intl/plugin';

const withNextIntl = createNextIntlPlugin('./src/i18n/request.ts');

/**
 * Performance-optimized Next.js configuration
 *
 * Key optimizations:
 * - AVIF/WebP image formats for smaller file sizes
 * - Optimized image sizing breakpoints
 * - Compression enabled
 * - Bundle analyzer for development
 * - React compiler and strict mode
 * - Optimized font loading
 */

const nextConfig: NextConfig = {
  output: 'standalone',

  // Image optimization configuration
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
    // Use modern formats: AVIF first (best compression), WebP fallback
    formats: ['image/avif', 'image/webp'],
    // Optimized device sizes for responsive images
    deviceSizes: [640, 750, 828, 1080, 1200, 1920, 2048, 3840],
    imageSizes: [16, 32, 48, 64, 96, 128, 256, 384],
    // Minimize layout shift with proper sizing
    minimumCacheTTL: 60,
    // Lazy load images by default
    unoptimized: false,
  },

  // Typed routes for better DX and smaller bundles
  typedRoutes: true,

  // Enable compression (gzip/brotli)
  compress: true,

  // Production optimizations
  productionBrowserSourceMaps: false,

  // Security and performance headers
  poweredByHeader: false,

  // Enable React strict mode for better error detection
  reactStrictMode: true,

  // Compiler optimizations
  compiler: {
    // Remove console logs in production
    removeConsole: process.env.NODE_ENV === 'production' ? { exclude: ['error', 'warn'] } : false,
  },

  // Experimental features for better performance
  experimental: {
    // Optimize package imports to reduce bundle size
    optimizePackageImports: [
      'lucide-react',
      'date-fns',
      '@go-adventure/ui',
      'react-hook-form',
      'framer-motion',
    ],
  },

  // Turbopack for faster builds (Next.js 16+)
  turbopack: {},
};

// Bundle analyzer - enabled with ANALYZE=true environment variable
const withBundleAnalyzer =
  process.env.ANALYZE === 'true'
    ? require('@next/bundle-analyzer')({
        enabled: true,
        openAnalyzer: true,
      })
    : (config: NextConfig) => config;

export default withBundleAnalyzer(withNextIntl(nextConfig));
