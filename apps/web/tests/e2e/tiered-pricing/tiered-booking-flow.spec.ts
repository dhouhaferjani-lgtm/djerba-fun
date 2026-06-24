/**
 * Customer-flow coverage for OPTIONAL group-discount pricing.
 *
 * Fixture (seeded by TieredPricingE2EFixtureSeeder): a TOUR listing with NORMAL
 * per-person-type pricing (adult 50, child 30 in BOTH currencies) plus optional
 * group discounts for sizes 2 -> 90 and 5 -> 200 (both currencies). Amounts are
 * currency-independent, so the displayed number is the same in TND or EUR:
 *
 *   1 adult -> 50, 2 -> 90, 3 -> 150, 5 -> 200, 6 -> 300.
 *
 * The spec drives the real booking panel: a tiered listing must render the
 * normal PERSON-TYPE selector (group discounts are an overlay, not a
 * replacement), and the grand total must apply the group discount only for the
 * exact configured sizes (2 and 5) and use normal per-person pricing otherwise.
 *
 * Pre-conditions:
 *   • Dev DB seeded with the fixture:
 *       php artisan db:seed --class=TieredPricingE2EFixtureSeeder
 *   • Next dev server at LISTING_BASE_URL (default :3100), API at :8100.
 */

import { test, expect, Page, Locator } from '@playwright/test';

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
  // First configured group discount is "Group of 2" -> 90.
  expect(Number(pricing?.tiers?.[0]?.groupSize)).toBe(2);
  expect(Number(pricing?.tiers?.[0]?.tndTotal)).toBe(90);
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

test.describe('Customer flow — optional group-discount pricing', () => {
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

  test('tiered listing keeps person types and applies the group discount only for set sizes', async ({
    page,
  }) => {
    await page.goto(`${LISTING_BASE_URL}/listings/${LISTING_SLUG}`);
    await page.waitForLoadState('networkidle');
    await dismissCookieBanner(page);

    await openBookingAndPickSlot(page);

    // Tiered listings keep the normal person-type selector (NOT a single stepper).
    const adultCount = page.locator('[data-testid="person-type-adult-count"]:visible').first();
    await adultCount.waitFor({ state: 'visible', timeout: 10_000 });

    const adultIncrement = page
      .locator('[data-testid="person-type-adult-increment"]:visible')
      .first();
    const total = (): Locator =>
      page.locator('[data-testid="price-breakdown-total"]:visible').first();

    // Default = 1 adult -> normal pricing, total 50.
    await expect(adultCount).toHaveText('1');
    await expect(total()).toContainText('50.00', { timeout: 10_000 });

    // 2 travellers -> group-of-2 discount, total 90 (not 100).
    await adultIncrement.click();
    await expect(adultCount).toHaveText('2');
    await expect(total()).toContainText('90.00', { timeout: 10_000 });

    // 3 travellers -> no size-3 discount, normal 3 x 50 = 150.
    await adultIncrement.click();
    await expect(adultCount).toHaveText('3');
    await expect(total()).toContainText('150.00', { timeout: 10_000 });

    // 5 travellers -> group-of-5 discount, total 200 (not 250).
    await adultIncrement.click();
    await adultIncrement.click();
    await expect(adultCount).toHaveText('5');
    await expect(total()).toContainText('200.00', { timeout: 10_000 });

    // 6 travellers -> groups of 6+ never discounted, normal 6 x 50 = 300.
    await adultIncrement.click();
    await expect(adultCount).toHaveText('6');
    await expect(total()).toContainText('300.00', { timeout: 10_000 });
  });
});
