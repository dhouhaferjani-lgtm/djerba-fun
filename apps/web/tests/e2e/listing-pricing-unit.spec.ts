import { test, expect } from '@playwright/test';

/**
 * BDD scenarios for the listing pricing-unit-label feature.
 * Spec: docs/specs/2026-05-06-listing-pricing-unit-label.md (§8)
 *
 * Assumes the seeder has run (`make fresh`) so the jetski listing carries
 * pricing.unit_label = { fr: "par jetski", en: "per jetski" }.
 */

const JETSKI_LOCATION = 'djerba';
const JETSKI_SLUG = 'jetski-15-30-min';

const TOUR_LOCATION = 'ain-draham';
const TOUR_SLUG = 'kroumirie-mountains-summit-trek';

test.describe('Listing pricing unit label', () => {
  test('jetski listing headline shows "par jetski" in French', async ({ page }) => {
    await page.goto(`/${JETSKI_LOCATION}/${JETSKI_SLUG}`);
    await page.waitForLoadState('networkidle');

    const priceUnit = page.locator('[data-testid="price-unit-label"]').first();
    await expect(priceUnit).toBeVisible();
    await expect(priceUnit).toHaveText(/par jetski/i);
    await expect(priceUnit).not.toHaveText(/par personne/i);
  });

  test('jetski listing headline shows "per jetski" in English', async ({ page }) => {
    await page.goto(`/en/${JETSKI_LOCATION}/${JETSKI_SLUG}`);
    await page.waitForLoadState('networkidle');

    const priceUnit = page.locator('[data-testid="price-unit-label"]').first();
    await expect(priceUnit).toBeVisible();
    await expect(priceUnit).toHaveText(/per jetski/i);
    await expect(priceUnit).not.toHaveText(/per person/i);
  });

  test('non-machine listing still shows "par personne"', async ({ page }) => {
    await page.goto(`/${TOUR_LOCATION}/${TOUR_SLUG}`);
    await page.waitForLoadState('networkidle');

    const priceUnit = page.locator('[data-testid="price-unit-label"]').first();
    await expect(priceUnit).toBeVisible();
    await expect(priceUnit).toHaveText(/par personne/i);
  });

  test('jetski listing detail does not surface console errors', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (err) => errors.push(err.message));

    await page.goto(`/${JETSKI_LOCATION}/${JETSKI_SLUG}`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);

    expect(errors).toEqual([]);
  });
});
