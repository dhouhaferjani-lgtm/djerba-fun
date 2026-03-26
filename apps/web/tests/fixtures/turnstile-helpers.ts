/**
 * Turnstile helper functions for E2E tests
 *
 * This module provides utilities for mocking Cloudflare Turnstile in Playwright tests.
 * When Turnstile is enabled in production, E2E tests need to bypass the challenge
 * without actually solving a real captcha.
 *
 * Two approaches are supported:
 * 1. Mock the Turnstile widget on the frontend (client-side)
 * 2. Use Cloudflare's test keys (server-side auto-pass)
 *
 * Recommended: Use Cloudflare test keys in .env.testing:
 *   TURNSTILE_SITE_KEY=1x00000000000000000000AA (always passes)
 *   TURNSTILE_SECRET_KEY=1x0000000000000000000000000000000AA (always passes)
 */

import { Page } from '@playwright/test';

// Test token that will be sent to the backend
const E2E_TEST_TOKEN = 'E2E_TEST_TURNSTILE_TOKEN';

/**
 * Mock Turnstile widget for E2E tests.
 *
 * This function injects a mock Turnstile implementation that:
 * - Auto-triggers the callback with a test token after a short delay
 * - Does not render any visible UI
 * - Works with the existing TurnstileWidget React component
 *
 * Call this BEFORE navigating to a page that contains a Turnstile widget.
 *
 * @example
 * ```ts
 * test('user can submit contact form', async ({ page }) => {
 *   await mockTurnstile(page);
 *   await page.goto('/contact');
 *   // Fill form and submit - Turnstile will auto-pass
 * });
 * ```
 */
export async function mockTurnstile(page: Page): Promise<void> {
  await page.addInitScript(() => {
    // Define mock Turnstile before any scripts load
    Object.defineProperty(window, 'turnstile', {
      value: {
        render: (
          container: string | HTMLElement,
          options: {
            sitekey: string;
            callback: (token: string) => void;
            'error-callback'?: (error: string) => void;
            'expired-callback'?: () => void;
          }
        ) => {
          // Auto-trigger callback with test token after a short delay
          // This simulates successful Turnstile verification
          setTimeout(() => {
            options.callback('E2E_TEST_TURNSTILE_TOKEN');
          }, 100);

          return 'mock-widget-id';
        },
        reset: (_widgetId: string) => {
          // No-op for reset
        },
        remove: (_widgetId: string) => {
          // No-op for remove
        },
        isExpired: (_widgetId: string) => {
          return false;
        },
      },
      writable: false,
      configurable: false,
    });
  });
}

/**
 * Mock Turnstile with a specific callback behavior.
 *
 * Use this for testing error scenarios.
 *
 * @example
 * ```ts
 * test('handles Turnstile error gracefully', async ({ page }) => {
 *   await mockTurnstileWithError(page);
 *   await page.goto('/contact');
 *   // Expect error handling UI
 * });
 * ```
 */
export async function mockTurnstileWithError(page: Page): Promise<void> {
  await page.addInitScript(() => {
    Object.defineProperty(window, 'turnstile', {
      value: {
        render: (
          _container: string | HTMLElement,
          options: {
            sitekey: string;
            callback: (token: string) => void;
            'error-callback'?: (error: string) => void;
            'expired-callback'?: () => void;
          }
        ) => {
          // Trigger error callback after a short delay
          setTimeout(() => {
            if (options['error-callback']) {
              options['error-callback']('challenge-error');
            }
          }, 100);

          return 'mock-widget-id';
        },
        reset: () => {},
        remove: () => {},
        isExpired: () => false,
      },
      writable: false,
      configurable: false,
    });
  });
}

/**
 * Mock Turnstile with an expired token scenario.
 *
 * Use this for testing token expiration handling.
 *
 * @example
 * ```ts
 * test('handles Turnstile expiration', async ({ page }) => {
 *   await mockTurnstileWithExpiration(page, 5000);
 *   await page.goto('/contact');
 *   // Token expires after 5 seconds
 * });
 * ```
 */
export async function mockTurnstileWithExpiration(
  page: Page,
  expirationMs: number = 5000
): Promise<void> {
  await page.addInitScript(
    ([expireMs]) => {
      Object.defineProperty(window, 'turnstile', {
        value: {
          render: (
            _container: string | HTMLElement,
            options: {
              sitekey: string;
              callback: (token: string) => void;
              'error-callback'?: (error: string) => void;
              'expired-callback'?: () => void;
            }
          ) => {
            // First, trigger success
            setTimeout(() => {
              options.callback('E2E_TEST_TURNSTILE_TOKEN');
            }, 100);

            // Then trigger expiration
            setTimeout(() => {
              if (options['expired-callback']) {
                options['expired-callback']();
              }
            }, expireMs);

            return 'mock-widget-id';
          },
          reset: () => {},
          remove: () => {},
          isExpired: () => false,
        },
        writable: false,
        configurable: false,
      });
    },
    [expirationMs]
  );
}

/**
 * Wait for Turnstile to be ready (useful if not mocking).
 *
 * In environments where real Turnstile is used (e.g., staging with test keys),
 * this function waits for the widget to appear and complete verification.
 */
export async function waitForTurnstileVerification(
  page: Page,
  timeout: number = 10000
): Promise<void> {
  // Wait for Turnstile iframe or success state
  await page.waitForFunction(
    () => {
      // Check if our mock has been called (token set)
      const turnstileInput = document.querySelector('input[name="cf-turnstile-response"]');
      if (turnstileInput && (turnstileInput as HTMLInputElement).value) {
        return true;
      }

      // Check for Turnstile iframe with success
      const iframe = document.querySelector('iframe[src*="challenges.cloudflare.com"]');
      if (iframe) {
        // Turnstile renders an iframe when active
        return true;
      }

      return false;
    },
    { timeout }
  );
}

/**
 * Get the test token used by mock Turnstile.
 *
 * Use this constant if you need to verify the token in assertions.
 */
export const TEST_TURNSTILE_TOKEN = E2E_TEST_TOKEN;
