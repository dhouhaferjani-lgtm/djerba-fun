/**
 * Customer-flow regression for per-slot price overrides.
 *
 * Locks the chain that broke in production over rounds 1-4:
 *   1. Filament dropdown could not save the override (round 1, 0d586fa)
 *   2. CartItem read listing.pricing instead of slot.effectivePrices
 *      (round 2, b04fe88)
 *   3. Listing-detail booking-panel grand total computed from listing
 *      defaults instead of slot effectivePrices (round 3, 3a64692)
 *   4. Voyageurs quantity-selector labels showed listing defaults while
 *      the breakdown below them showed the override (round 4, this spec)
 *
 * The spec drives a real customer browser session through every visible
 * surface a slot-override price has to flow through:
 *
 *   slot picker chip → quantity-selector label → breakdown line →
 *   booking-panel total → cart line → cart total → checkout summary
 *
 * Each surface is a separate code path. The PHPUnit suite covers the
 * backend half but cannot catch a frontend reading the wrong field —
 * round 3 was a frontend-only bug that backend tests passed clean while
 * the user saw the wrong price on the live site.
 *
 * Pre-conditions:
 *   • Dev DB seeded; Kroumirie listing with rule 1 carrying a price
 *     override on adult (TND 150 / EUR 48). The dev tinker block in
 *     round-1 setup matches; CI seed should mirror.
 *   • Next dev server reachable at LISTING_BASE_URL (default :3100).
 *   • Laravel API at LARAVEL_API_URL (default :8100). The frontend is
 *     configured at NEXT_PUBLIC_API_URL — only used here as a
 *     readiness probe.
 */

import { test, expect, Page } from '@playwright/test';

const LISTING_BASE_URL = process.env.LISTING_BASE_URL ?? 'http://localhost:3100';
const LARAVEL_API_URL = process.env.LARAVEL_API_URL ?? 'http://localhost:8100';
const LISTING_SLUG = process.env.E2E_LISTING_SLUG ?? 'kroumirie-mountains-summit-trek';
const EXPECTED_OVERRIDE_EUR = Number(process.env.E2E_EXPECTED_OVERRIDE_EUR ?? 48);
const EXPECTED_LISTING_BASE_EUR = Number(process.env.E2E_LISTING_BASE_EUR ?? 38);

/**
 * Hit the Laravel API directly and confirm the seeded data carries an
 * `effectivePrices.EUR.adult === EXPECTED_OVERRIDE_EUR`. If the seed
 * shifted, surface a clear failure here instead of a confusing assertion
 * deep in the UI flow.
 */
async function assertSeededOverridePresent(): Promise<void> {
  const today = new Date();
  const start = today.toISOString().slice(0, 10);
  const end = new Date(today.getTime() + 30 * 86_400_000).toISOString().slice(0, 10);
  const url = `${LARAVEL_API_URL}/api/v1/listings/${LISTING_SLUG}/availability?start_date=${start}&end_date=${end}`;
  const res = await fetch(url, {
    headers: { Accept: 'application/json', 'Accept-Language': 'fr' },
  });
  expect(res.ok, `Availability endpoint must respond 2xx — got ${res.status}`).toBe(true);
  const body = (await res.json()) as {
    data: Array<{ effectivePrices?: { EUR?: Record<string, number> } }>;
  };
  const overriddenSlot = body.data.find(
    (slot) => slot.effectivePrices?.EUR?.adult === EXPECTED_OVERRIDE_EUR
  );
  expect(
    overriddenSlot,
    `No slot in /availability returned effectivePrices.EUR.adult=${EXPECTED_OVERRIDE_EUR}. ` +
      'Re-seed the dev DB or set E2E_EXPECTED_OVERRIDE_EUR / E2E_LISTING_SLUG.'
  ).toBeTruthy();
}

async function dismissCookieBanner(page: Page): Promise<void> {
  const accept = page.getByRole('button', { name: /Tout Accepter|Accept All/ }).first();
  if (await accept.isVisible({ timeout: 2000 }).catch(() => false)) {
    await accept.click();
  }
}

async function pickFirstAvailableDateAndSlot(page: Page): Promise<void> {
  // Open the booking flow.
  await page.locator('[data-testid="book-now-button"]').first().click();

  // First enabled date in the visible month — the dev seed has Saturdays
  // and Sundays bookable; we don't hard-code 2026-05-02 because the seed
  // re-rolls on `make fresh`.
  const enabledDate = page
    .locator('[data-testid^="date-"]:not([disabled])')
    .filter({ has: page.locator(':not(.opacity-40)') })
    .first();
  // Fallback: if the .opacity-40 filter picks nothing (dev styling drift),
  // grab the first non-disabled date button at all.
  const dateButton = (await enabledDate.count())
    ? enabledDate
    : page.locator('[data-testid^="date-"]:not([disabled])').first();
  await dateButton.click();

  // Pick the first available time slot on that date.
  await page.locator('[data-testid="time-slot"]').first().click({ timeout: 10_000 });
}

test.describe('Customer flow — per-slot price override threads through every surface', () => {
  test.setTimeout(120_000);

  test.beforeAll(async () => {
    await assertSeededOverridePresent();
  });

  test('listing detail booking panel shows override at every surface, including selector labels', async ({
    page,
  }) => {
    await page.goto(`${LISTING_BASE_URL}/listings/${LISTING_SLUG}`);
    await page.waitForLoadState('networkidle');
    await dismissCookieBanner(page);

    // The "À partir de" header is intentionally listing-level (€38) — it's
    // the marketing/discovery price; the search grid stays consistent with
    // it. We don't assert on the header — only on the slot-specific surfaces.
    await pickFirstAvailableDateAndSlot(page);

    // Wait for the booking panel to render the Voyageurs section. The
    // person-type-adult-count testid is the most stable hook into the
    // selector block.
    await page.waitForSelector('[data-testid="person-type-adult-count"]', {
      state: 'visible',
      timeout: 10_000,
    });

    // Pull the body text once the slot is selected and assert the three
    // distinct surfaces all carry the override price.
    const overrideStr = `EUR ${EXPECTED_OVERRIDE_EUR.toFixed(2)}`;
    const baseStr = `EUR ${EXPECTED_LISTING_BASE_EUR.toFixed(2)}`;
    // Voyageurs label renders the currency symbol and the amount in
    // separate inline blocks, so the visible-innerText output is
    // "€\n48.00" (or "€48.00" / "€ 48.00" depending on Tailwind whitespace).
    // Accept any whitespace between them.
    const euroAmountRegex = new RegExp(
      `€\\s*${EXPECTED_OVERRIDE_EUR.toFixed(2).replace('.', '\\.')}`
    );
    const baseEuroAmountRegex = new RegExp(
      `€\\s*${EXPECTED_LISTING_BASE_EUR.toFixed(2).replace('.', '\\.')}`
    );

    // Surface 2 (breakdown line) — round 2 fix.
    const breakdownLine = page.getByText(`@ ${overrideStr}`).first();
    await expect(breakdownLine).toBeVisible({ timeout: 10_000 });

    // Snapshot the booking panel's text after the breakdown line renders;
    // surfaces 1 and 3 are easier to assert on the resulting plaintext than
    // via brittle DOM ancestor queries.
    const panelText = await page.evaluate(() => document.body.innerText);

    // Surface 1: Voyageurs Adulte selector label — round 4 fix.
    // Pre-fix the selector showed €38 (listing default) while the breakdown
    // below showed €48 (slot override). Now both must show €48.
    const adultBlockText = await page.evaluate(() => {
      const count = document.querySelector('[data-testid="person-type-adult-count"]');
      if (!count) return '';
      const block = count.closest('[class*="border"], [class*="rounded"], [class*="bg-"]');
      return block?.textContent?.trim() ?? '';
    });
    expect(
      adultBlockText,
      'Voyageurs Adulte selector must include the slot override price'
    ).toMatch(euroAmountRegex);

    // Surface 3: booking-panel grand total — round 3 fix.
    expect(panelText, 'Booking-panel grand total must show override').toMatch(
      new RegExp(`Total\\s*${overrideStr.replace('.', '\\.')}\\b`)
    );

    // Negative assertion: if the listing default ever leaks into the
    // breakdown's Total line, we want the test to scream — that's the
    // exact bug shape the user reported originally.
    expect(panelText).not.toMatch(new RegExp(`Total\\s*${baseStr.replace('.', '\\.')}\\b`));

    // The override-aware adult selector must NOT also show the listing
    // default (€38). Catches a future regression where the price is
    // rendered twice — e.g. an unguarded "old/new" label addition.
    expect(adultBlockText).not.toMatch(baseEuroAmountRegex);
  });

  test('cart and checkout pages preserve the slot override', async ({ page }) => {
    await page.goto(`${LISTING_BASE_URL}/listings/${LISTING_SLUG}`);
    await page.waitForLoadState('networkidle');
    await dismissCookieBanner(page);
    await pickFirstAvailableDateAndSlot(page);

    // Click Add-to-Cart and wait for the cart to actually accept the item
    // before navigating away. The cart count badge in the header is the
    // simplest signal — without this wait, navigation races the POST and
    // the cart page renders empty.
    await page.getByRole('button', { name: /Ajouter au Panier/i }).click();
    await expect(page.locator('a[href*="/cart"]').first()).toContainText(/\b1\b/, {
      timeout: 10_000,
    });

    // Cart page.
    await page.goto(`${LISTING_BASE_URL}/fr/cart`);
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('text=Trek au Sommet des Monts Kroumirie', { timeout: 10_000 });
    const cartBody = await page.evaluate(() => document.body.innerText);
    const overrideStr = `EUR ${EXPECTED_OVERRIDE_EUR.toFixed(2)}`;
    const baseStr = `EUR ${EXPECTED_LISTING_BASE_EUR.toFixed(2)}`;
    expect(cartBody).toContain(overrideStr);
    // No listing-default leak in any cart line.
    expect(cartBody).not.toContain(baseStr);

    // Checkout — Récapitulatif de la Commande grand total.
    await page.getByRole('button', { name: /Passer à la Caisse|Checkout/i }).click();
    await page.waitForURL(/\/cart\/checkout/, { timeout: 15_000 });
    await page.waitForLoadState('networkidle');
    await page.waitForSelector('text=Récapitulatif', { timeout: 10_000 });

    const checkoutBody = await page.evaluate(() => document.body.innerText);
    expect(checkoutBody).toContain(overrideStr);
    expect(checkoutBody).not.toMatch(new RegExp(`Total\\s*${baseStr.replace('.', '\\.')}\\b`));
  });
});
