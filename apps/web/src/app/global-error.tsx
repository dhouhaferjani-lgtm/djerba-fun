'use client';

import { useEffect } from 'react';

/**
 * Global Error Boundary
 *
 * Catches errors in the root layout. This is a last-resort error boundary.
 * Uses minimal inline styles in case CSS fails to load.
 */
export default function GlobalError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    // Log error to error reporting service
    console.error('Global error:', error);
  }, [error]);

  return (
    <html lang="en">
      <body>
        <div
          style={{
            minHeight: '100vh',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: '1rem',
            backgroundColor: '#f9fafb',
          }}
        >
          <div
            style={{
              maxWidth: '42rem',
              width: '100%',
              textAlign: 'center',
              backgroundColor: 'white',
              padding: '3rem',
              borderRadius: '0.5rem',
              boxShadow: '0 1px 3px 0 rgb(0 0 0 / 0.1)',
            }}
          >
            <h1
              style={{
                fontSize: '2.25rem',
                fontWeight: 'bold',
                color: '#111827',
                marginBottom: '1rem',
              }}
            >
              Something Went Wrong
            </h1>
            <p
              style={{
                fontSize: '1.125rem',
                color: '#6b7280',
                marginBottom: '2rem',
              }}
            >
              We encountered an unexpected error. Please try again.
            </p>

            {error.digest && (
              <p
                style={{
                  fontSize: '0.875rem',
                  color: '#9ca3af',
                  fontFamily: 'monospace',
                  backgroundColor: '#f3f4f6',
                  padding: '0.5rem 1rem',
                  borderRadius: '0.25rem',
                  display: 'inline-block',
                  marginBottom: '2rem',
                }}
              >
                Error ID: {error.digest}
              </p>
            )}

            <div
              style={{
                display: 'flex',
                gap: '1rem',
                justifyContent: 'center',
                flexWrap: 'wrap',
              }}
            >
              <button
                onClick={reset}
                style={{
                  backgroundColor: '#0D642E',
                  color: 'white',
                  padding: '0.75rem 1.5rem',
                  borderRadius: '0.375rem',
                  border: 'none',
                  fontSize: '1rem',
                  fontWeight: '500',
                  cursor: 'pointer',
                }}
              >
                Try Again
              </button>
              <button
                onClick={() => (window.location.href = '/')}
                style={{
                  backgroundColor: 'white',
                  color: '#0D642E',
                  padding: '0.75rem 1.5rem',
                  borderRadius: '0.375rem',
                  border: '1px solid #0D642E',
                  fontSize: '1rem',
                  fontWeight: '500',
                  cursor: 'pointer',
                }}
              >
                Back to Home
              </button>
            </div>

            <div
              style={{
                marginTop: '2rem',
                paddingTop: '2rem',
                borderTop: '1px solid #e5e7eb',
              }}
            >
              <p style={{ fontSize: '0.875rem', color: '#6b7280' }}>
                If this problem persists, please contact{' '}
                <a
                  href="mailto:support@goadventure.com"
                  style={{
                    color: '#0D642E',
                    textDecoration: 'underline',
                  }}
                >
                  support@goadventure.com
                </a>
              </p>
            </div>
          </div>
        </div>
      </body>
    </html>
  );
}
