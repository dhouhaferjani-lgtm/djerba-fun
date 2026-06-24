/**
 * Regression guard: introducing tiered pricing must NOT change the classic
 * flat per-person-type booking flow.
 *
 * Fixture (TieredPricingE2EFixtureSeeder): a flat TOUR listing with
 * adult=50, child=30 in both currencies. The booking panel must render the
 * person-type selector (NOT the tiered traveller-count stepper) and price
 * linearly: 2 adults -> 100, 3 adults -> 150.
 */

import { test, expect, Page } from '@playwright/test';

const LISTING_BASE_URL = process.env.LISTING_BASE_URL ?? 'http://localhost:3100';
const LARAVEL_API_URL = process.env.LARAVEL_API_URL ?? 'http://localhost:8100';
const LISTING_SLUG = process.env.E2E_FLAT_SLUG ?? 'flat-pricing-tour-e2e';

async function fetchJsonWithRetry(url: string, attempts = 5): Promise<Response> {
  let lastErr: unknown;
  for (let i = 0; i < attempts; i++) {
    try {
      const res = await fetch(url, { headers: { Accept: 'application/json' } });
      if (res.ok) return res;
      lastErr = new Error(`HTTP ${res.status}`);
    } catch (e) {
      lastErr = e;
    }
    await new Promise((r) => setTimeout(r, 2000));
  }
  throw lastErr;
}

async function assertFlatFixturePresent(): Promise<void> {
  const res = await fetchJsonWithRetry(`${LARAVEL_API_URL}/api/v1/listings/${LISTING_SLUG}`);
  expect(res.ok, `Listing endpoint must respond 2xx — got ${res.status}`).toBe(true);
  const pricing = (await res.json())?.data?.pricing;
  expect(
    pricing?.pricingStrategy,
    'Flat fixture must report strategy "flat". Run: php artisan db:seed --class=TieredPricingE2EFixtureSeeder'
  ).toBe('flat');
  expect(Array.isArray(pricing?.personTypes)).toBe(true);
}

async function dismissCookieBanner(page: Page): Promise<void> {
  const accept = page.getByRole('button', { name: /Tout Accepter|Accept All/ }).first();
  if (await accept.isVisible({ timeout: 2000 }).catch(() => false)) {
    await accept.click();
  }
}

async function openBookingAndPickSlot(page: Page): Promise<void> {
  // Two book-now-buttons exist (desktop sidebar + mobile sticky bar); only one
  // is visible per viewport — click the visible one so the flow works on mobile too.
  await page.locator('[data-testid="book-now-button"]:visible').first().click();
  await page.waitForLoadState('networkidle').catch(() => {});
  const firstDate = page.locator('[data-testid^="date-"]:not([disabled])').first();
  await firstDate.waitFor({ state: 'visible', timeout: 30_000 });
  await firstDate.click();
  await page.locator('[data-testid="time-slot"]').first().click({ timeout: 15_000 });
}

test.describe('Regression — flat per-person-type pricing is unchanged', () => {
  test.setTimeout(120_000);

  // Booking-flow DOM is duplicated on mobile (sidebar vs sticky drawer); the
  // flat pricing path is viewport-independent and asserted on the desktop engines.
  test.beforeEach(({ isMobile }) => {
    test.skip(!!isMobile, 'Covered on desktop projects; booking-flow DOM is duplicated on mobile.');
  });

  test.beforeAll(async () => {
    await assertFlatFixturePresent();
  });

  test('flat listing keeps person-type selectors and linear totals', async ({ page }) => {
    await page.goto(`${LISTING_BASE_URL}/listings/${LISTING_SLUG}`);
    await page.waitForLoadState('networkidle');
    await dismissCookieBanner(page);

    await openBookingAndPickSlot(page);

    // Every listing (flat or tiered) shows the person-type selector. The old
    // single traveller-count stepper was removed entirely — guard it stays gone.
    await page.waitForSelector('[data-testid="person-type-adult-count"]', {
      state: 'visible',
      timeout: 10_000,
    });
    await expect(
      page.locator('[data-testid="traveler-count"]'),
      'The legacy traveller-count stepper must not render'
    ).toHaveCount(0);

    const adultInc = page.locator('[data-testid="person-type-adult-increment"]');

    // Default 1 adult -> 50.
    await expect(page.getByText(/50\.00/).first()).toBeVisible({ timeout: 10_000 });

    // 2 adults -> 100 (linear).
    await adultInc.click();
    await expect(page.locator('[data-testid="person-type-adult-count"]')).toHaveText('2');
    await expect(page.getByText(/100\.00/).first()).toBeVisible({ timeout: 10_000 });

    // 3 adults -> 150 (linear, NOT a tiered repeat).
    await adultInc.click();
    await expect(page.locator('[data-testid="person-type-adult-count"]')).toHaveText('3');
    await expect(page.getByText(/150\.00/).first()).toBeVisible({ timeout: 10_000 });
  });
});
