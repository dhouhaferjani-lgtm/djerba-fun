/**
 * Customer-flow coverage for OPTIONAL group pricing (greedy "circle" packing).
 *
 * Fixture (seeded by TieredPricingE2EFixtureSeeder): a TOUR listing with NORMAL
 * per-person-type pricing (adult 50, child 30 in BOTH currencies) plus optional
 * group prices for sizes 2 -> 90 and 3 -> 130 (both currencies). Amounts are
 * currency-independent, so the displayed number is the same in TND or EUR:
 *
 *   1 -> 50, 2 -> 90, 3 -> 130, 4 -> 130+50=180, 5 -> 130+90=220, 6 -> 130+130=260.
 *
 * The spec drives the real booking panel: a tiered listing keeps the normal
 * PERSON-TYPE selector, and the grand total must follow greedy packing — the
 * largest group price that fits, the remainder cycling back through the group
 * prices, and any leftover charged individually.
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
  // Configured group prices: "Group of 2" -> 90 and "Group of 3" -> 130.
  expect(Number(pricing?.tiers?.[0]?.groupSize)).toBe(2);
  expect(Number(pricing?.tiers?.[0]?.tndTotal)).toBe(90);
  expect(Number(pricing?.tiers?.[1]?.groupSize)).toBe(3);
  expect(Number(pricing?.tiers?.[1]?.tndTotal)).toBe(130);
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

  test('tiered listing keeps person types and follows greedy group packing', async ({ page }) => {
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
    const inc = async (to: string) => {
      await adultIncrement.click();
      await expect(adultCount).toHaveText(to);
    };

    // Default = 1 adult -> individual rate, total 50.
    await expect(adultCount).toHaveText('1');
    await expect(total()).toContainText('50.00', { timeout: 10_000 });

    // 2 -> group-of-2, total 90.
    await inc('2');
    await expect(total()).toContainText('90.00', { timeout: 10_000 });

    // 3 -> group-of-3, total 130.
    await inc('3');
    await expect(total()).toContainText('130.00', { timeout: 10_000 });

    // 4 -> group-of-3 + 1 individual = 180.
    await inc('4');
    await expect(total()).toContainText('180.00', { timeout: 10_000 });

    // 5 -> group-of-3 + group-of-2 = 220 (the "circle").
    await inc('5');
    await expect(total()).toContainText('220.00', { timeout: 10_000 });

    // 6 -> group-of-3 + group-of-3 = 260.
    await inc('6');
    await expect(total()).toContainText('260.00', { timeout: 10_000 });
  });
});
