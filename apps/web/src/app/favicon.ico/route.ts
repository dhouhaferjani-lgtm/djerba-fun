import { NextResponse } from 'next/server';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

/**
 * Dynamic favicon route handler.
 * Fetches the favicon from platform settings API and proxies it.
 * Falls back to a minimal transparent PNG if API fails.
 */
export async function GET() {
  try {
    // Fetch platform settings to get the dynamic favicon URL
    const response = await fetch(`${API_URL}/platform/settings`, {
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      cache: 'no-store',
    });

    if (!response.ok) {
      console.error('Platform settings API returned:', response.status);
      return serveFallbackFavicon();
    }

    const settings = await response.json();
    const faviconUrl = settings?.data?.branding?.favicon;

    if (!faviconUrl) {
      return serveFallbackFavicon();
    }

    // Fetch the actual favicon from MinIO/S3
    const faviconResponse = await fetch(faviconUrl, {
      cache: 'no-store',
    });

    if (!faviconResponse.ok) {
      console.error('Failed to fetch favicon from:', faviconUrl, faviconResponse.status);
      return serveFallbackFavicon();
    }

    const contentType = faviconResponse.headers.get('content-type') || 'image/x-icon';
    const faviconBuffer = await faviconResponse.arrayBuffer();

    return new NextResponse(faviconBuffer, {
      headers: {
        'Content-Type': contentType,
        'Cache-Control': 'public, max-age=3600, stale-while-revalidate=86400',
      },
    });
  } catch (error) {
    console.error('Error fetching dynamic favicon:', error);
    return serveFallbackFavicon();
  }
}

/**
 * Serve fallback - minimal 1x1 transparent PNG
 */
function serveFallbackFavicon() {
  const minimalPng = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
    'base64'
  );
  return new NextResponse(minimalPng, {
    headers: {
      'Content-Type': 'image/png',
      'Cache-Control': 'public, max-age=3600',
    },
  });
}
