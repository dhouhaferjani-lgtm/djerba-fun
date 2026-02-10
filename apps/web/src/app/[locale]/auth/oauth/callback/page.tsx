'use client';

import { useEffect } from 'react';
import { useSearchParams } from 'next/navigation';
import { Loader2 } from 'lucide-react';

/**
 * OAuth callback page — opened inside a popup by SocialLoginButtons.
 * 1. Reads token from URL query params
 * 2. Stores token in localStorage
 * 3. Posts message to parent window
 * 4. Closes popup
 */
export default function OAuthCallbackPage() {
  const searchParams = useSearchParams();

  useEffect(() => {
    const token = searchParams.get('token');
    const error = searchParams.get('error');

    if (token) {
      // Store auth token
      localStorage.setItem('auth_token', token);

      // Notify parent window
      if (window.opener) {
        window.opener.postMessage({ type: 'oauth_success' }, window.location.origin);
        window.close();
      } else {
        // If not in a popup (e.g., redirect fallback), go to home
        window.location.href = '/';
      }
    } else if (error) {
      // Close popup on error
      if (window.opener) {
        window.close();
      } else {
        window.location.href = '/auth/login';
      }
    }
  }, [searchParams]);

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="text-center">
        <Loader2 className="w-8 h-8 text-primary animate-spin mx-auto mb-4" />
        <p className="text-gray-600">Completing sign in...</p>
      </div>
    </div>
  );
}
