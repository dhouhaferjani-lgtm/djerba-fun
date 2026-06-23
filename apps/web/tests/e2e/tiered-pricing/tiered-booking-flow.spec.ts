/**
 * Customer-flow coverage for tiered (group / positional) pricing.
 *
 * Fixture (seeded by TieredPricingE2EFixtureSeeder): a TOUR listing with
 * cumulative tier totals T = [100, 180, 180] in BOTH currencies, so the
 * displayed total is currency-independent:
 *
 *   group of 1 -> 100, of 2 -> 180, of 3 -> 180, of 6 -> 360.
 *
 * The spec drives the real booking panel: it must render a single
 * "number of travellers" stepper (NOT person-type selectors) and the
 * grand total must follow the tiered formula as the count changes.
 *
 * Pre-conditions:
 *   • Dev DB seeded with the fixture:
 *       php artisan db:seed --class=TieredPricingE2EFixtureSeeder
 *   • Next dev server at LISTING_BASE_URL (default :3100), API at :8100.
 */

import { test, expect, Page } from '@playwright/test';

const LISTING_BASE_URL = process.env.LISTING_BASE_URL ?? 'http://localhost:3100';
const LARAVEL_API_URL = process.env.LARAVEL_API_URL ?? 'http://localhost:8100';
const LISTING_SLUG = process.env.E2E_TIERED_SLUG ?? 'tiered-group-tour-e2e';

/** Fetch JSON with a few retries to ride out transient API resets under load. */
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

/** Fail fast with a clear message if the tiered fixture isn't seeded. */
async function assertTieredFixturePresent(): Promise<void> {
  const res = await fetchJsonWithRetry(`${LARAVEL_API_URL}/api/v1/listings/${LISTING_SLUG}`);
  expect(res.ok, `Listing endpoint must respond 2xx — got ${res.status}`).toBe(true);
  const pricing = (await res.json())?.data?.pricing;
  expect(
    pricing?.pricingStrategy,
    'Fixture missing. Run: php artisan db:seed --class=TieredPricingE2EFixtureSeeder'
  ).toBe('tiered');
  expect(Number(pricing?.tiers?.[0]?.tndTotal)).toBe(100);
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

  // Let the availability query settle so date buttons enable — avoids racing
  // a cold/recompiling dev server where availability briefly returns nothing.
  await page.waitForLoadState('networkidle').catch(() => {});

  const firstDate = page.locator('[data-testid^="date-"]:not([disabled])').first();
  await firstDate.waitFor({ state: 'visible', timeout: 30_000 });
  await firstDate.click();

  await page.locator('[data-testid="time-slot"]').first().click({ timeout: 15_000 });
}

test.describe('Customer flow — tiered group pricing', () => {
  test.setTimeout(120_000);

  // Desktop + mobile render duplicate booking-flow DOM (sidebar vs sticky drawer);
  // the pricing logic is viewport-independent (same React components) and is
  // asserted here across all three desktop engines (chromium/firefox/webkit).
  test.beforeEach(({ isMobile }) => {
    test.skip(!!isMobile, 'Covered on desktop projects; booking-flow DOM is duplicated on mobile.');
  });

  test.beforeAll(async () => {
    await assertTieredFixturePresent();
  });

  test('booking panel uses a traveller-count stepper and follows the tiered formula', async ({
    page,
  }) => {
    await page.goto(`${LISTING_BASE_URL}/listings/${LISTING_SLUG}`);
    await page.waitForLoadState('networkidle');
    await dismissCookieBanner(page);

    await openBookingAndPickSlot(page);

    // Tiered listings show the single traveller-count stepper, NOT person types.
    await page.waitForSelector('[data-testid="traveler-count"]', {
      state: 'visible',
      timeout: 10_000,
    });
    await expect(
      page.locator('[data-testid="person-type-adult-count"]'),
      'Tiered listings must NOT render the person-type selector'
    ).toHaveCount(0);

    const increment = page.locator('[data-testid="traveler-count-increment"]');

    // Default count = 1 -> total 100.
    await expect(page.locator('[data-testid="traveler-count"]')).toHaveText('1');
    await expect(page.getByText(/100\.00/).first()).toBeVisible({ timeout: 10_000 });

    // 2 travellers -> 180.
    await increment.click();
    await expect(page.locator('[data-testid="traveler-count"]')).toHaveText('2');
    await expect(page.getByText(/180\.00/).first()).toBeVisible({ timeout: 10_000 });

    // 3 travellers -> still 180 (3rd traveller is effectively free).
    await increment.click();
    await expect(page.locator('[data-testid="traveler-count"]')).toHaveText('3');
    await expect(page.getByText(/180\.00/).first()).toBeVisible({ timeout: 10_000 });

    // 6 travellers -> 360 (the pattern repeats).
    await increment.click();
    await increment.click();
    await increment.click();
    await expect(page.locator('[data-testid="traveler-count"]')).toHaveText('6');
    await expect(page.getByText(/360\.00/).first()).toBeVisible({ timeout: 10_000 });
  });
});
