import { NextResponse } from 'next/server';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

/**
 * Dynamic apple-touch-icon route handler.
 * Fetches the apple touch icon from platform settings API and proxies it.
 * Falls back to a minimal transparent PNG if API fails.
 */
export async function GET() {
  try {
    // Fetch platform settings to get the dynamic apple touch icon URL
    const response = await fetch(`${API_URL}/platform/settings`, {
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      cache: 'no-store',
    });

    if (!response.ok) {
      console.error('Platform settings API returned:', response.status);
      return serveFallback();
    }

    const settings = await response.json();
    const iconUrl = settings?.data?.branding?.appleTouchIcon;

    if (!iconUrl) {
      return serveFallback();
    }

    // Fetch the actual icon from MinIO/S3
    const iconResponse = await fetch(iconUrl, {
      cache: 'no-store',
    });

    if (!iconResponse.ok) {
      console.error('Failed to fetch apple-touch-icon from:', iconUrl, iconResponse.status);
      return serveFallback();
    }

    const contentType = iconResponse.headers.get('content-type') || 'image/png';
    const iconBuffer = await iconResponse.arrayBuffer();

    return new NextResponse(iconBuffer, {
      headers: {
        'Content-Type': contentType,
        'Cache-Control': 'public, max-age=3600, stale-while-revalidate=86400',
      },
    });
  } catch (error) {
    console.error('Error fetching dynamic apple-touch-icon:', error);
    return serveFallback();
  }
}

/**
 * Serve fallback - minimal 1x1 transparent PNG
 */
function serveFallback() {
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
