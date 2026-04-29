/**
 * Per-Time-Slot Price Override E2E
 *
 * Locks the user-visible end of the per-slot pricing feature: when a vendor
 * configures the same listing at different durations from the same anchor
 * (e.g. 09:00 → 1-hour vs 09:00 → 3-hour with their own price overrides),
 * the customer's slot picker shows DIFFERENT prices side-by-side.
 *
 * The test data is configured against the seeded `djerba-island-discovery-tour`
 * listing — the same listing the manual smoke uses (see plan file).
 *   - 09:00 → 10:00 (1-hour) at listing pricing: 50 TND / €15 adult
 *   - 09:00 → 12:00 (3-hour) at slot override:   120 TND / €40 adult, child falls back
 */

import { test, expect } from '@playwright/test';

const TOUR_LISTING_PATH = '/en/djerba/djerba-island-discovery-tour';
const TOUR_LISTING_FALLBACK = '/en/listings/djerba-island-discovery-tour';

// Slot-price override is viewport-agnostic — the component (TimeSlotPicker)
// renders the same chip on desktop and mobile. Driving the mobile bottom-sheet
// through Playwright requires animation-aware plumbing that the existing
// guest-checkout.spec.ts works around with `.catch(() => {})`. Keeping this
// spec focused on Desktop Chrome until the mobile booking-flow timing is
// stabilised (separate ticket — applies to ALL booking E2E specs).
//
// Force serial execution: parallel workers contending on the single shared
// dev API state (one demo listing, one rule) intermittently race the
// `book-now-button` click against in-flight React Query refetches. Serial
// removes the contention and keeps these tests deterministic on any
// developer's machine.
test.describe.configure({ mode: 'serial' });

test.describe('Per-time-slot price override', () => {
  test.skip(
    ({ browserName, isMobile }) => browserName !== 'chromium' || isMobile,
    'Slot-pricing correctness is browser-agnostic; this spec runs on Desktop Chrome only.'
  );

  test.beforeEach(async ({ page }) => {
    // Load listing detail; some seeds use the location-prefixed URL, others
    // the legacy /listings/ path — try both.
    const response = await page.goto(TOUR_LISTING_PATH);
    if (response?.status() === 404) {
      await page.goto(TOUR_LISTING_FALLBACK);
    }
    await page.waitForLoadState('networkidle');

    // The booking calendar is hidden until the customer clicks "Check
    // Availability" (FixedBookingPanel pattern, mirrored from guest-checkout.spec.ts).
    // The page renders two book-now triggers — desktop sticky panel + mobile
    // bottom sheet. Pick whichever is currently visible for this viewport,
    // scroll it into view, and click. Force-click as a fallback for mobile
    // bottom-sheet animations that obscure the button briefly.
    const allBookNow = page.locator('[data-testid="book-now-button"]');
    const visibleBookNow = allBookNow
      .filter({ has: page.locator(':visible') })
      .or(allBookNow.locator('visible=true'))
      .first();
    const bookNowButton =
      (await allBookNow.count()) > 1
        ? allBookNow.locator('visible=true').first()
        : allBookNow.first();
    await bookNowButton.scrollIntoViewIfNeeded();
    await bookNowButton.click({ timeout: 15_000, force: true });

    await page.waitForSelector('[data-testid="booking-date-selector"]', {
      timeout: 15_000,
      state: 'visible',
    });
  });

  /**
   * GIVEN: a listing with two slots at the same start_time (09:00) — one
   *        ending at 10:00 (no override) and one ending at 12:00 (override).
   * WHEN:  the customer picks any future date with both slots available.
   * THEN:  the slot picker renders TWO time chips, each with a distinct
   *        per-slot price exposed via `data-slot-price`.
   *
   * If a regression collapses both slots into a single price chip (or worse,
   * silently drops one of the slots due to the unique-constraint shape),
   * this test fails.
   */
  test('slot picker exposes distinct prices when slots have different overrides', async ({
    page,
  }) => {
    // Pick the first selectable date. The seed configures the override on
    // every weekday going forward, so any clickable day cell works.
    const firstAvailableDay = page.locator('[data-testid^="date-"]:not([disabled])').first();
    await expect(firstAvailableDay).toBeVisible({ timeout: 15_000 });
    await firstAvailableDay.click();
    await page.waitForTimeout(800); // let the slot list render

    // Two slots must be rendered — the schema-level guard.
    const slots = page.locator('[data-testid="time-slot"]');
    await expect(slots).toHaveCount(2, { timeout: 10_000 });

    // Pull the per-slot price exposed via data-slot-price (set in TimeSlotPicker
    // when prices diverge — see `pricesAreUniform()` in the component).
    const firstPrice = await slots.nth(0).getAttribute('data-slot-price');
    const secondPrice = await slots.nth(1).getAttribute('data-slot-price');

    expect(firstPrice, 'first slot must expose data-slot-price').toBeTruthy();
    expect(secondPrice, 'second slot must expose data-slot-price').toBeTruthy();
    expect(firstPrice, 'two slots must have different prices').not.toEqual(secondPrice);

    // The override is strictly higher than listing pricing (50 TND / €15 vs
    // 120 TND / €40 adult). Whichever currency the geo-pricing layer
    // resolved to, the larger price must be ~2.5–3× the smaller.
    const lo = Math.min(Number(firstPrice), Number(secondPrice));
    const hi = Math.max(Number(firstPrice), Number(secondPrice));
    expect(hi / lo).toBeGreaterThan(2);
  });

  /**
   * Visual sanity check: at least one per-slot price chip is actually
   * rendered to the customer (not just on a data-attribute). Locks the
   * label so a future style refactor that hides it via display:none fails.
   */
  test('per-slot price chip is visible to the customer when prices diverge', async ({ page }) => {
    const firstAvailableDay = page.locator('[data-testid^="date-"]:not([disabled])').first();
    await expect(firstAvailableDay).toBeVisible({ timeout: 15_000 });
    await firstAvailableDay.click();
    await page.waitForTimeout(800);

    const slots = page.locator('[data-testid="time-slot"]');
    await expect(slots).toHaveCount(2, { timeout: 10_000 });

    const priceChip = page.locator('[data-testid="slot-price"]').first();
    await expect(priceChip).toBeVisible();
    await expect(priceChip).toContainText(/[\d,]+/);
  });

  /**
   * GIVEN: the rule for the demo listing has `show_duration=true`.
   * WHEN:  the customer opens the slot picker.
   * THEN:  each slot exposes `data-slot-duration` and renders a visible
   *        compact label (e.g. "1h", "3h") next to its time range.
   *
   * Locks iteration-3's user-visible end. If the API stops surfacing the
   * field, the toggle is dropped from the resource, or the component
   * stops respecting it, this test fails.
   */
  test('slot picker renders compact duration label when rule has show_duration on', async ({
    page,
  }) => {
    const firstAvailableDay = page.locator('[data-testid^="date-"]:not([disabled])').first();
    await expect(firstAvailableDay).toBeVisible({ timeout: 15_000 });
    await firstAvailableDay.click();
    await page.waitForTimeout(800);

    const slots = page.locator('[data-testid="time-slot"]');
    await expect(slots).toHaveCount(2, { timeout: 10_000 });

    // Each slot must expose data-slot-duration with a positive integer (minutes).
    const firstDuration = await slots.nth(0).getAttribute('data-slot-duration');
    const secondDuration = await slots.nth(1).getAttribute('data-slot-duration');
    expect(Number(firstDuration)).toBeGreaterThan(0);
    expect(Number(secondDuration)).toBeGreaterThan(0);

    // Two slots, two different durations (1h vs 3h) — locks the per-slot semantic.
    expect(firstDuration).not.toEqual(secondDuration);

    // The visible duration chip is rendered (not just the data-attribute).
    const durationChips = page.locator('[data-testid="slot-duration"]');
    await expect(durationChips.first()).toBeVisible();
    await expect(durationChips.first()).toContainText(/\d+\s*h|\d+\s*min/);
  });

  /**
   * A11y: the slot button's accessible name must include the verbose
   * duration ("1 hour" / "3 hours") when show_duration is on, so screen
   * readers announce the duration cleanly instead of "one h".
   */
  test('slot button accessible name includes verbose duration when show_duration is on', async ({
    page,
  }) => {
    const firstAvailableDay = page.locator('[data-testid^="date-"]:not([disabled])').first();
    await expect(firstAvailableDay).toBeVisible({ timeout: 15_000 });
    await firstAvailableDay.click();
    await page.waitForTimeout(800);

    const slots = page.locator('[data-testid="time-slot"]');
    await expect(slots).toHaveCount(2, { timeout: 10_000 });

    // The accessible name must contain a verbose duration phrase.
    const firstAriaLabel = await slots.nth(0).getAttribute('aria-label');
    const secondAriaLabel = await slots.nth(1).getAttribute('aria-label');
    expect(firstAriaLabel).toMatch(/\d+\s+(hour|hours|minute|minutes)/i);
    expect(secondAriaLabel).toMatch(/\d+\s+(hour|hours|minute|minutes)/i);
  });
});
