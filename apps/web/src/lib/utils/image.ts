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
