'use client';

import { useEffect } from 'react';
import { Button } from '@djerba-fun/ui';
import { AlertTriangle, Home, RefreshCw } from 'lucide-react';

/**
 * Locale-Level Error Boundary
 *
 * Catches errors within locale pages and displays a user-friendly error page.
 * This error boundary exists at the [locale] level to properly catch errors
 * that occur in locale-specific pages while still having access to the layout.
 */
export default function LocaleError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    console.error('Locale error:', error);
  }, [error]);

  return (
    <div className="min-h-screen bg-gradient-to-b from-error-light to-white flex items-center justify-center px-4">
      <div className="max-w-2xl w-full text-center space-y-8">
        {/* Error Illustration */}
        <div className="flex justify-center">
          <div className="bg-error-light rounded-full p-6">
            <AlertTriangle className="h-16 w-16 text-error-dark" />
          </div>
        </div>

        {/* Error Message */}
        <div className="space-y-4">
          <h1 className="text-4xl font-bold text-neutral-900">Something Went Wrong</h1>
          <p className="text-lg text-neutral-600">
            We encountered an unexpected error. Don&apos;t worry, we&apos;re on it!
          </p>
          {error.digest && (
            <p className="text-sm text-neutral-500 font-mono bg-neutral-100 px-4 py-2 rounded inline-block">
              Error ID: {error.digest}
            </p>
          )}
        </div>

        {/* Action Buttons */}
        <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
          <Button variant="primary" size="lg" onClick={reset}>
            <RefreshCw className="h-5 w-5 mr-2" />
            Try Again
          </Button>
          <Button variant="outline" size="lg" onClick={() => (window.location.href = '/')}>
            <Home className="h-5 w-5 mr-2" />
            Back to Home
          </Button>
        </div>

        {/* Help Section */}
        <div className="pt-8 border-t border-neutral-200">
          <h3 className="text-lg font-semibold text-neutral-900 mb-4">Need Help?</h3>
          <p className="text-sm text-neutral-600">
            If this problem persists, please{' '}
            <a
              href="mailto:support@djerbafun.com"
              className="text-primary hover:underline font-medium"
            >
              contact our support team
            </a>
            .
          </p>
        </div>
      </div>
    </div>
  );
}
