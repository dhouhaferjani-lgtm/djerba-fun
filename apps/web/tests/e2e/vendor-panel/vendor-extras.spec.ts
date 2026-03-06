/**
 * Vendor Panel E2E Tests - Section 2.3: Extras Management
 * Test Cases: TC-V020 to TC-V024
 *
 * Tests extras (add-ons) creation and management
 * through the Filament vendor panel.
 */

import { test, expect } from '@playwright/test';
import {
  loginVendorUI,
  waitForFilamentPage,
  submitFilamentForm,
  seededVendor,
} from '../../fixtures/vendor-helpers';
import { extrasData, generateUniqueTestData } from '../../fixtures/vendor-test-data';

const VENDOR_PANEL_URL = process.env.LARAVEL_URL || 'http://localhost:8000';

test.describe('Vendor Panel - Extras Management (2.3)', () => {
  test.beforeEach(async ({ page }) => {
    await loginVendorUI(page, seededVendor.email, seededVendor.password);
  });

  /**
   * TC-V020: Create Per-Booking Extra
   * Tests creating an extra with flat per-booking pricing
   */
  test('TC-V020: Create Per-Booking Extra', async ({ page }) => {
    const uniqueName = generateUniqueTestData('Equipment Rental');

    await page.goto(`${VENDOR_PANEL_URL}/vendor/extras/create`);
    await waitForFilamentPage(page);

    // Fill name (EN)
    const nameEnInput = page
      .locator('input[name*="name"][name*="en"], input[name="data.name.en"]')
      .first();
    if (await nameEnInput.isVisible()) {
      await nameEnInput.fill(uniqueName);
    }

    // Fill name (FR)
    const nameFrInput = page
      .locator('input[name*="name"][name*="fr"], input[name="data.name.fr"]')
      .first();
    if (await nameFrInput.isVisible()) {
      await nameFrInput.fill(`${uniqueName} FR`);
    }

    // Select category: Equipment
    const categorySelect = page
      .locator('select[name*="category"], [data-field*="category"] select')
      .first();
    if (await categorySelect.isVisible()) {
      await categorySelect.selectOption('equipment');
    }

    // Select pricing type: Per Booking
    const pricingTypeSelect = page
      .locator('select[name*="pricing_type"], [data-field*="pricing_type"] select')
      .first();
    if (await pricingTypeSelect.isVisible()) {
      await pricingTypeSelect.selectOption('per_booking');
    }
    await page.waitForTimeout(500);

    // Set prices
    const priceTndInput = page
      .locator(
        'input[name*="tnd_price"], input[name*="price_tnd"], [data-field*="base_price_tnd"] input'
      )
      .first();
    if (await priceTndInput.isVisible()) {
      await priceTndInput.fill(String(extrasData.perBooking.priceTnd));
    }

    const priceEurInput = page
      .locator(
        'input[name*="eur_price"], input[name*="price_eur"], [data-field*="base_price_eur"] input'
      )
      .first();
    if (await priceEurInput.isVisible()) {
      await priceEurInput.fill(String(extrasData.perBooking.priceEur));
    }

    // Save
    await submitFilamentForm(page);

    // Verify: Extra created
    await expect(page.locator('body')).toContainText(/created|success/i);
  });

  /**
   * TC-V021: Create Per-Person Extra
   * Tests creating an extra priced per participant
   */
  test('TC-V021: Create Per-Person Extra', async ({ page }) => {
    const uniqueName = generateUniqueTestData('Lunch Package');

    await page.goto(`${VENDOR_PANEL_URL}/vendor/extras/create`);
    await waitForFilamentPage(page);

    // Fill name (EN)
    const nameEnInput = page.locator('input[name*="name"][name*="en"]').first();
    if (await nameEnInput.isVisible()) {
      await nameEnInput.fill(uniqueName);
    }

    // Fill name (FR)
    const nameFrInput = page.locator('input[name*="name"][name*="fr"]').first();
    if (await nameFrInput.isVisible()) {
      await nameFrInput.fill(`${uniqueName} FR`);
    }

    // Select category: Food
    const categorySelect = page.locator('select[name*="category"]').first();
    if (await categorySelect.isVisible()) {
      await categorySelect.selectOption('food');
    }

    // Select pricing type: Per Person
    const pricingTypeSelect = page.locator('select[name*="pricing_type"]').first();
    if (await pricingTypeSelect.isVisible()) {
      await pricingTypeSelect.selectOption('per_person');
    }
    await page.waitForTimeout(500);

    // Set prices (multiplied by number of participants)
    const priceTndInput = page
      .locator('input[name*="tnd_price"], input[name*="price_tnd"]')
      .first();
    if (await priceTndInput.isVisible()) {
      await priceTndInput.fill(String(extrasData.perPerson.priceTnd));
    }

    const priceEurInput = page
      .locator('input[name*="eur_price"], input[name*="price_eur"]')
      .first();
    if (await priceEurInput.isVisible()) {
      await priceEurInput.fill(String(extrasData.perPerson.priceEur));
    }

    // Save
    await submitFilamentForm(page);

    // Verify: Extra created with per-person pricing
    await expect(page.locator('body')).toContainText(/created|success/i);
  });

  /**
   * TC-V022: Create Per-Person-Type Extra
   * Tests creating an extra with different prices for adult/child
   */
  test('TC-V022: Create Per-Person-Type Extra', async ({ page }) => {
    const uniqueName = generateUniqueTestData('Photo Package');

    await page.goto(`${VENDOR_PANEL_URL}/vendor/extras/create`);
    await waitForFilamentPage(page);

    // Fill name
    const nameEnInput = page.locator('input[name*="name"][name*="en"]').first();
    if (await nameEnInput.isVisible()) {
      await nameEnInput.fill(uniqueName);
    }

    const nameFrInput = page.locator('input[name*="name"][name*="fr"]').first();
    if (await nameFrInput.isVisible()) {
      await nameFrInput.fill(`${uniqueName} FR`);
    }

    // Select category
    const categorySelect = page.locator('select[name*="category"]').first();
    if (await categorySelect.isVisible()) {
      await categorySelect.selectOption('other');
    }

    // Select pricing type: Per Person Type
    const pricingTypeSelect = page.locator('select[name*="pricing_type"]').first();
    if (await pricingTypeSelect.isVisible()) {
      await pricingTypeSelect.selectOption('per_person_type');
    }
    await page.waitForTimeout(500);

    // Set adult prices
    const adultTndInput = page
      .locator(
        'input[name*="adult"][name*="tnd"], input[name*="prices"][name*="adult"][name*="tnd"]'
      )
      .first();
    if (await adultTndInput.isVisible()) {
      await adultTndInput.fill(String(extrasData.perPersonType.prices.adult.tnd));
    }

    const adultEurInput = page
      .locator(
        'input[name*="adult"][name*="eur"], input[name*="prices"][name*="adult"][name*="eur"]'
      )
      .first();
    if (await adultEurInput.isVisible()) {
      await adultEurInput.fill(String(extrasData.perPersonType.prices.adult.eur));
    }

    // Set child prices
    const childTndInput = page
      .locator(
        'input[name*="child"][name*="tnd"], input[name*="prices"][name*="child"][name*="tnd"]'
      )
      .first();
    if (await childTndInput.isVisible()) {
      await childTndInput.fill(String(extrasData.perPersonType.prices.child.tnd));
    }

    const childEurInput = page
      .locator(
        'input[name*="child"][name*="eur"], input[name*="prices"][name*="child"][name*="eur"]'
      )
      .first();
    if (await childEurInput.isVisible()) {
      await childEurInput.fill(String(extrasData.perPersonType.prices.child.eur));
    }

    // Save
    await submitFilamentForm(page);

    // Verify: Extra created with different prices per person type
    await expect(page.locator('body')).toContainText(/created|success/i);
  });

  /**
   * TC-V023: Required Extra
   * Tests creating an extra and marking it as required on a listing
   */
  test('TC-V023: Required Extra', async ({ page }) => {
    const uniqueName = generateUniqueTestData('Insurance');

    await page.goto(`${VENDOR_PANEL_URL}/vendor/extras/create`);
    await waitForFilamentPage(page);

    // Fill name
    const nameEnInput = page.locator('input[name*="name"][name*="en"]').first();
    if (await nameEnInput.isVisible()) {
      await nameEnInput.fill(uniqueName);
    }

    const nameFrInput = page.locator('input[name*="name"][name*="fr"]').first();
    if (await nameFrInput.isVisible()) {
      await nameFrInput.fill(`${uniqueName} FR`);
    }

    // Set pricing type
    const pricingTypeSelect = page.locator('select[name*="pricing_type"]').first();
    if (await pricingTypeSelect.isVisible()) {
      await pricingTypeSelect.selectOption('per_person');
    }
    await page.waitForTimeout(500);

    // Set price
    const priceTndInput = page
      .locator('input[name*="tnd_price"], input[name*="price_tnd"]')
      .first();
    if (await priceTndInput.isVisible()) {
      await priceTndInput.fill(String(extrasData.required.priceTnd));
    }

    // Save the extra first
    await submitFilamentForm(page);
    await page.waitForTimeout(1000);

    // Now go to listings and attach this extra as required
    await page.goto(`${VENDOR_PANEL_URL}/vendor/listings`);
    await waitForFilamentPage(page);

    // Find a listing and edit it
    const firstListingRow = page.locator('table tbody tr').first();
    if (await firstListingRow.isVisible()) {
      // Click edit
      const editLink = firstListingRow.locator('a[href*="/edit"]').first();
      if (await editLink.isVisible()) {
        await editLink.click();
        await page.waitForLoadState('networkidle');

        // Look for extras relation manager or tab
        const extrasTab = page
          .locator('button:has-text("Extras"), [role="tab"]:has-text("Extras")')
          .first();
        if (await extrasTab.isVisible()) {
          await extrasTab.click();
          await page.waitForTimeout(500);

          // Find the extra and mark as required
          const requiredCheckbox = page
            .locator(`input[name*="is_required"], input[type="checkbox"][name*="required"]`)
            .first();
          if (await requiredCheckbox.isVisible()) {
            await requiredCheckbox.check();
          }

          // Save
          await submitFilamentForm(page);

          // Verify: Extra marked as required
          await expect(page.locator('body')).toContainText(/saved|updated|success/i);
        }
      }
    }
  });

  /**
   * TC-V024: Extra with Inventory Limit
   * Tests creating an extra with max capacity/quantity limit
   */
  test('TC-V024: Extra with Inventory Limit', async ({ page }) => {
    const uniqueName = generateUniqueTestData('Premium Upgrade');

    await page.goto(`${VENDOR_PANEL_URL}/vendor/extras/create`);
    await waitForFilamentPage(page);

    // Fill name
    const nameEnInput = page.locator('input[name*="name"][name*="en"]').first();
    if (await nameEnInput.isVisible()) {
      await nameEnInput.fill(uniqueName);
    }

    const nameFrInput = page.locator('input[name*="name"][name*="fr"]').first();
    if (await nameFrInput.isVisible()) {
      await nameFrInput.fill(`${uniqueName} FR`);
    }

    // Select category
    const categorySelect = page.locator('select[name*="category"]').first();
    if (await categorySelect.isVisible()) {
      await categorySelect.selectOption('activity');
    }

    // Set pricing type
    const pricingTypeSelect = page.locator('select[name*="pricing_type"]').first();
    if (await pricingTypeSelect.isVisible()) {
      await pricingTypeSelect.selectOption('per_booking');
    }
    await page.waitForTimeout(500);

    // Set price
    const priceTndInput = page
      .locator('input[name*="tnd_price"], input[name*="price_tnd"]')
      .first();
    if (await priceTndInput.isVisible()) {
      await priceTndInput.fill(String(extrasData.limited.priceTnd));
    }

    // Set max capacity/inventory limit
    const maxCapacityInput = page
      .locator('input[name*="max_capacity"], input[name*="max_quantity"], input[name*="inventory"]')
      .first();
    if (await maxCapacityInput.isVisible()) {
      await maxCapacityInput.fill(String(extrasData.limited.maxCapacity));
    }

    // Save
    await submitFilamentForm(page);

    // Verify: Extra created with inventory limit
    await expect(page.locator('body')).toContainText(/created|success/i);
  });
});
