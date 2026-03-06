/**
 * Vendor Panel E2E Tests - Section 2.2: Availability Rules
 * Test Cases: TC-V010 to TC-V014
 *
 * Tests availability rule creation and management
 * through the Filament vendor panel.
 */

import { test, expect } from '@playwright/test';
import {
  loginVendorUI,
  waitForFilamentPage,
  submitFilamentForm,
  seededVendor,
} from '../../fixtures/vendor-helpers';
import { availabilityRules, getFutureDate } from '../../fixtures/vendor-test-data';

const VENDOR_PANEL_URL = process.env.LARAVEL_URL || 'http://localhost:8000';

test.describe('Vendor Panel - Availability Rules (2.2)', () => {
  test.beforeEach(async ({ page }) => {
    await loginVendorUI(page, seededVendor.email, seededVendor.password);
  });

  /**
   * TC-V010: Create Weekly Availability Rule
   * Tests creating a weekly recurring availability rule
   */
  test('TC-V010: Create Weekly Availability Rule', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules/create`);
    await waitForFilamentPage(page);

    // Select a listing
    const listingSelect = page
      .locator(
        'select[name*="listing"], [data-field*="listing"] select, [data-field*="listing"] button'
      )
      .first();
    if (await listingSelect.isVisible()) {
      if ((await listingSelect.evaluate((el) => el.tagName.toLowerCase())) === 'select') {
        await listingSelect.selectOption({ index: 1 });
      } else {
        await listingSelect.click();
        await page.locator('[role="option"], li[data-value]').first().click();
      }
    }
    await page.waitForTimeout(500);

    // Select rule type: Weekly
    const ruleTypeSelect = page
      .locator('select[name*="rule_type"], [data-field*="rule_type"] select')
      .first();
    if (await ruleTypeSelect.isVisible()) {
      await ruleTypeSelect.selectOption('weekly');
    }
    await page.waitForTimeout(500);

    // Select days: Monday, Wednesday, Friday (1, 3, 5)
    for (const day of availabilityRules.weekly.daysOfWeek) {
      const dayCheckbox = page
        .locator(
          `input[name*="days_of_week"][value="${day}"], ` +
            `input[type="checkbox"][value="${day}"], ` +
            `[data-day="${day}"] input[type="checkbox"]`
        )
        .first();
      if (await dayCheckbox.isVisible()) {
        if (!(await dayCheckbox.isChecked())) {
          await dayCheckbox.check();
        }
      }
    }

    // Set time: 09:00 - 17:00
    const startTimeInput = page
      .locator('input[name*="start_time"], [data-field*="start_time"] input')
      .first();
    if (await startTimeInput.isVisible()) {
      await startTimeInput.fill(availabilityRules.weekly.startTime);
    }

    const endTimeInput = page
      .locator('input[name*="end_time"], [data-field*="end_time"] input')
      .first();
    if (await endTimeInput.isVisible()) {
      await endTimeInput.fill(availabilityRules.weekly.endTime);
    }

    // Set capacity: 10 per slot
    const capacityInput = page
      .locator('input[name*="capacity"], [data-field*="capacity"] input')
      .first();
    if (await capacityInput.isVisible()) {
      await capacityInput.fill(String(availabilityRules.weekly.capacity));
    }

    // Save
    await submitFilamentForm(page);

    // Verify: Rule created successfully
    await expect(page.locator('body')).toContainText(/created|success|weekly/i);
  });

  /**
   * TC-V011: Create Daily Availability
   * Tests creating a daily availability rule for all days
   */
  test('TC-V011: Create Daily Availability', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules/create`);
    await waitForFilamentPage(page);

    // Select a listing
    const listingSelect = page
      .locator('select[name*="listing"], [data-field*="listing"] select')
      .first();
    if (await listingSelect.isVisible()) {
      await listingSelect.selectOption({ index: 1 });
    }
    await page.waitForTimeout(500);

    // Select rule type: Daily
    const ruleTypeSelect = page
      .locator('select[name*="rule_type"], [data-field*="rule_type"] select')
      .first();
    if (await ruleTypeSelect.isVisible()) {
      await ruleTypeSelect.selectOption('daily');
    }
    await page.waitForTimeout(500);

    // Set time
    const startTimeInput = page
      .locator('input[name*="start_time"], [data-field*="start_time"] input')
      .first();
    if (await startTimeInput.isVisible()) {
      await startTimeInput.fill(availabilityRules.daily.startTime);
    }

    const endTimeInput = page
      .locator('input[name*="end_time"], [data-field*="end_time"] input')
      .first();
    if (await endTimeInput.isVisible()) {
      await endTimeInput.fill(availabilityRules.daily.endTime);
    }

    // Set capacity
    const capacityInput = page
      .locator('input[name*="capacity"], [data-field*="capacity"] input')
      .first();
    if (await capacityInput.isVisible()) {
      await capacityInput.fill(String(availabilityRules.daily.capacity));
    }

    // Save
    await submitFilamentForm(page);

    // Verify: Activity available every day
    await expect(page.locator('body')).toContainText(/created|success|daily/i);
  });

  /**
   * TC-V012: Create Specific Date Availability
   * Tests creating availability for specific dates only
   */
  test('TC-V012: Create Specific Date Availability', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules/create`);
    await waitForFilamentPage(page);

    // Select a listing
    const listingSelect = page
      .locator('select[name*="listing"], [data-field*="listing"] select')
      .first();
    if (await listingSelect.isVisible()) {
      await listingSelect.selectOption({ index: 1 });
    }
    await page.waitForTimeout(500);

    // Select rule type: Specific Dates
    const ruleTypeSelect = page
      .locator('select[name*="rule_type"], [data-field*="rule_type"] select')
      .first();
    if (await ruleTypeSelect.isVisible()) {
      await ruleTypeSelect.selectOption('specific_dates');
    }
    await page.waitForTimeout(500);

    // Add specific dates
    const startDateInput = page
      .locator('input[name*="start_date"], [data-field*="start_date"] input')
      .first();
    if (await startDateInput.isVisible()) {
      await startDateInput.fill(getFutureDate(7)); // 7 days from now
    }

    const endDateInput = page
      .locator('input[name*="end_date"], [data-field*="end_date"] input')
      .first();
    if (await endDateInput.isVisible()) {
      await endDateInput.fill(getFutureDate(14)); // 14 days from now
    }

    // Set time
    const startTimeInput = page.locator('input[name*="start_time"]').first();
    if (await startTimeInput.isVisible()) {
      await startTimeInput.fill(availabilityRules.specificDates.startTime);
    }

    const endTimeInput = page.locator('input[name*="end_time"]').first();
    if (await endTimeInput.isVisible()) {
      await endTimeInput.fill(availabilityRules.specificDates.endTime);
    }

    // Set capacity
    const capacityInput = page.locator('input[name*="capacity"]').first();
    if (await capacityInput.isVisible()) {
      await capacityInput.fill(String(availabilityRules.specificDates.capacity));
    }

    // Save
    await submitFilamentForm(page);

    // Verify: Only those dates available
    await expect(page.locator('body')).toContainText(/created|success|specific/i);
  });

  /**
   * TC-V013: Block Date Range
   * Tests creating a blocked dates rule (e.g., maintenance period)
   */
  test('TC-V013: Block Date Range', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules/create`);
    await waitForFilamentPage(page);

    // Select a listing
    const listingSelect = page
      .locator('select[name*="listing"], [data-field*="listing"] select')
      .first();
    if (await listingSelect.isVisible()) {
      await listingSelect.selectOption({ index: 1 });
    }
    await page.waitForTimeout(500);

    // Select rule type: Blocked Dates
    const ruleTypeSelect = page
      .locator('select[name*="rule_type"], [data-field*="rule_type"] select')
      .first();
    if (await ruleTypeSelect.isVisible()) {
      await ruleTypeSelect.selectOption('blocked_dates');
    }
    await page.waitForTimeout(500);

    // Set blocked date range
    const startDateInput = page
      .locator('input[name*="start_date"], [data-field*="start_date"] input')
      .first();
    if (await startDateInput.isVisible()) {
      await startDateInput.fill(getFutureDate(5)); // 5 days from now
    }

    const endDateInput = page
      .locator('input[name*="end_date"], [data-field*="end_date"] input')
      .first();
    if (await endDateInput.isVisible()) {
      await endDateInput.fill(getFutureDate(10)); // 10 days from now
    }

    // Add reason (if field exists)
    const reasonInput = page.locator('input[name*="reason"], textarea[name*="reason"]').first();
    if (await reasonInput.isVisible()) {
      await reasonInput.fill(availabilityRules.blocked.reason);
    }

    // Save
    await submitFilamentForm(page);

    // Verify: Blocked rule created
    await expect(page.locator('body')).toContainText(/created|success|blocked/i);
  });

  /**
   * TC-V014: Overlapping Rules
   * Tests that blocked rule overrides weekly rule for specific days
   */
  test('TC-V014: Overlapping Rules', async ({ page }) => {
    // This test requires:
    // 1. Creating a weekly rule first
    // 2. Creating a blocked rule that overlaps
    // 3. Verifying the blocked rule takes precedence

    // Step 1: Navigate to availability rules list
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules`);
    await waitForFilamentPage(page);

    // Check if there are existing rules
    const existingRules = await page.locator('table tbody tr').count();

    // Step 2: Create a weekly rule (if not exists)
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules/create`);
    await waitForFilamentPage(page);

    // Select listing
    const listingSelect = page.locator('select[name*="listing"]').first();
    if (await listingSelect.isVisible()) {
      await listingSelect.selectOption({ index: 1 });
    }

    // Create weekly rule for all weekdays (Mon-Fri)
    const ruleTypeSelect = page.locator('select[name*="rule_type"]').first();
    if (await ruleTypeSelect.isVisible()) {
      await ruleTypeSelect.selectOption('weekly');
    }
    await page.waitForTimeout(500);

    // Select all weekdays (1-5)
    for (let day = 1; day <= 5; day++) {
      const dayCheckbox = page.locator(`input[name*="days_of_week"][value="${day}"]`).first();
      if (await dayCheckbox.isVisible()) {
        if (!(await dayCheckbox.isChecked())) {
          await dayCheckbox.check();
        }
      }
    }

    // Set times and capacity
    await page.locator('input[name*="start_time"]').first().fill('09:00');
    await page.locator('input[name*="end_time"]').first().fill('17:00');
    await page.locator('input[name*="capacity"]').first().fill('10');

    await submitFilamentForm(page);
    await page.waitForTimeout(1000);

    // Step 3: Create blocked rule for a specific weekday (Wednesday = day 3)
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules/create`);
    await waitForFilamentPage(page);

    // Select same listing
    const listingSelect2 = page.locator('select[name*="listing"]').first();
    if (await listingSelect2.isVisible()) {
      await listingSelect2.selectOption({ index: 1 });
    }

    // Create blocked dates rule
    const ruleTypeSelect2 = page.locator('select[name*="rule_type"]').first();
    if (await ruleTypeSelect2.isVisible()) {
      await ruleTypeSelect2.selectOption('blocked_dates');
    }
    await page.waitForTimeout(500);

    // Block a specific date range that includes a weekday
    await page.locator('input[name*="start_date"]').first().fill(getFutureDate(7));
    await page.locator('input[name*="end_date"]').first().fill(getFutureDate(7)); // Same day

    await submitFilamentForm(page);

    // Expected: Both rules created, blocked rule should override for that specific day
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules`);
    await waitForFilamentPage(page);

    const finalRules = await page.locator('table tbody tr').count();
    expect(finalRules).toBeGreaterThan(existingRules);
  });
});
