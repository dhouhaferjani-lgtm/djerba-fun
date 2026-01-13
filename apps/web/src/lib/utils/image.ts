/**
 * Image utilities for handling Next.js Image optimization
 *
 * Next.js 16 blocks image optimization for URLs resolving to private IPs
 * (localhost, 127.0.0.1, etc.) for security reasons. These utilities help
 * detect and handle such URLs properly.
 */

/**
 * Check if a URL points to a localhost/private IP that Next.js Image
 * optimization will block. When true, images should use unoptimized={true}.
 *
 * @param url - The image URL to check
 * @returns true if the URL is from a localhost/private host
 */
export function isPrivateUrl(url: string | null | undefined): boolean {
  if (!url) return false;

  try {
    const parsed = new URL(url);
    const hostname = parsed.hostname.toLowerCase();

    // Check for localhost variations
    if (
      hostname === 'localhost' ||
      hostname === '127.0.0.1' ||
      hostname === '::1' ||
      hostname.endsWith('.localhost') ||
      hostname.endsWith('.local')
    ) {
      return true;
    }

    // Check for private IP ranges
    // 10.x.x.x, 172.16-31.x.x, 192.168.x.x
    const ipParts = hostname.split('.');
    if (ipParts.length === 4 && ipParts.every((p) => /^\d+$/.test(p))) {
      const [a, b] = ipParts.map(Number);
      if (a === 10) return true;
      if (a === 172 && b >= 16 && b <= 31) return true;
      if (a === 192 && b === 168) return true;
      if (a === 127) return true;
    }

    return false;
  } catch {
    // Invalid URL - assume not private
    return false;
  }
}

/**
 * Determine if Next.js Image should use unoptimized mode for a URL.
 * This is needed for localhost URLs in development that would otherwise
 * be blocked by Next.js security features.
 *
 * @param url - The image URL to check
 * @returns true if the image should be unoptimized
 */
export function shouldUnoptimizeImage(url: string | null | undefined): boolean {
  return isPrivateUrl(url);
}

/**
 * Get the Laravel backend base URL (without /api/v1 suffix)
 */
function getStorageBaseUrl(): string {
  const apiUrl = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';
  // Remove /api/v1 suffix to get base URL
  return apiUrl.replace(/\/api\/v1\/?$/, '');
}

/**
 * Normalize a media URL to be usable with Next.js Image component.
 * - Full URLs (http/https) are returned as-is
 * - Relative paths without leading slash get full Laravel storage URL
 * - Paths with leading slash get full Laravel storage URL
 *
 * @param url - The media URL to normalize
 * @returns A properly formatted URL for Next.js Image
 */
export function normalizeMediaUrl(url: string | null | undefined): string {
  if (!url) return '';

  // Already a full URL
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url;
  }

  const baseUrl = getStorageBaseUrl();

  // Path with leading slash - prepend base URL
  if (url.startsWith('/')) {
    return `${baseUrl}${url}`;
  }

  // Relative path from Laravel storage - prepend full storage URL
  return `${baseUrl}/storage/${url}`;
}
