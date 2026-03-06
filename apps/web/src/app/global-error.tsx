'use client';

import { useEffect } from 'react';

/**
 * Global Error Boundary
 *
 * Catches errors in the root layout. This is a last-resort error boundary.
 * Uses inline CSS variables to ensure colors are consistent with design tokens
 * even if globals.css fails to load.
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
      <head>
        <title>Erreur Critique - Evasion Djerba</title>
        <meta name="robots" content="noindex, nofollow" />
        <style
          dangerouslySetInnerHTML={{
            __html: `
              :root {
                --primary: #1B2A4E;
                --gray-50: #f9fafb;
                --gray-100: #f3f4f6;
                --gray-200: #e5e7eb;
                --gray-400: #9ca3af;
                --gray-500: #6b7280;
                --gray-900: #111827;
              }
            `,
          }}
        />
      </head>
      <body>
        <div
          style={{
            minHeight: '100vh',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: '1rem',
            backgroundColor: 'var(--gray-50)',
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
                color: 'var(--gray-900)',
                marginBottom: '1rem',
              }}
            >
              Something Went Wrong
            </h1>
            <p
              style={{
                fontSize: '1.125rem',
                color: 'var(--gray-500)',
                marginBottom: '2rem',
              }}
            >
              We encountered an unexpected error. Please try again.
            </p>

            {error.digest && (
              <p
                style={{
                  fontSize: '0.875rem',
                  color: 'var(--gray-400)',
                  fontFamily: 'monospace',
                  backgroundColor: 'var(--gray-100)',
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
                  backgroundColor: 'var(--primary)',
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
                  color: 'var(--primary)',
                  padding: '0.75rem 1.5rem',
                  borderRadius: '0.375rem',
                  border: '1px solid var(--primary)',
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
                borderTop: '1px solid var(--gray-200)',
              }}
            >
              <p style={{ fontSize: '0.875rem', color: 'var(--gray-500)' }}>
                Si ce problème persiste, veuillez contacter{' '}
                <a
                  href="mailto:support@evasiondjerba.com"
                  style={{
                    color: 'var(--primary)',
                    textDecoration: 'underline',
                  }}
                >
                  support@evasiondjerba.com
                </a>
              </p>
            </div>
          </div>
        </div>
      </body>
    </html>
  );
}
