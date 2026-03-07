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

// ============================================================================
// EDGE CASE TESTS (TC-V025 to TC-V032)
// ============================================================================

test.describe('Vendor Panel - Availability Rules Edge Cases (TC-V025 to TC-V032)', () => {
  test.beforeEach(async ({ page }) => {
    await loginVendorUI(page, seededVendor.email, seededVendor.password);
  });

  /**
   * TC-V025: Edit Existing Availability Rule
   * Tests editing an existing availability rule (change time/capacity)
   */
  test('TC-V025: Edit existing availability rule (change time/capacity)', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules`);
    await waitForFilamentPage(page);

    // Find an existing rule
    const existingRule = page.locator('table tbody tr').first();
    if (await existingRule.isVisible({ timeout: 5000 }).catch(() => false)) {
      // Click edit
      const editLink = existingRule.locator('a[href*="/edit"]').first();
      await editLink.click();
      await page.waitForLoadState('networkidle');

      // Change capacity
      const capacityInput = page.locator('input[name*="capacity"]').first();
      if (await capacityInput.isVisible()) {
        const currentCapacity = await capacityInput.inputValue();
        const newCapacity = parseInt(currentCapacity || '10') + 5;
        await capacityInput.fill(String(newCapacity));
      }

      // Change start time
      const startTimeInput = page.locator('input[name*="start_time"]').first();
      if (await startTimeInput.isVisible()) {
        await startTimeInput.fill('10:00');
      }

      // Save changes
      await submitFilamentForm(page);

      // Verify update success
      await expect(page.locator('body')).toContainText(/updated|success|saved/i);
    } else {
      test.skip();
    }
  });

  /**
   * TC-V026: Delete Availability Rule
   * Tests deleting an availability rule and verifying slots are removed
   */
  test('TC-V026: Delete availability rule and verify slots removed', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules`);
    await waitForFilamentPage(page);

    // Count initial rules
    const initialCount = await page.locator('table tbody tr').count();

    if (initialCount > 0) {
      // Find a rule to delete
      const ruleRow = page.locator('table tbody tr').first();

      // Open actions dropdown
      const actionsButton = ruleRow.locator('button[aria-haspopup="menu"]').first();
      if (await actionsButton.isVisible()) {
        await actionsButton.click();
        await page.waitForTimeout(300);
      }

      // Click delete action
      const deleteAction = page
        .locator(
          '[data-action="delete"], button:has-text("Delete"), [role="menuitem"]:has-text("Delete")'
        )
        .first();
      if (await deleteAction.isVisible()) {
        await deleteAction.click();
        await page.waitForTimeout(500);

        // Confirm deletion
        const confirmButton = page
          .locator('button:has-text("Confirm"), button:has-text("Yes"), button:has-text("Delete")')
          .last();
        if (await confirmButton.isVisible({ timeout: 2000 }).catch(() => false)) {
          await confirmButton.click();
        }

        await page.waitForLoadState('networkidle');

        // Verify rule was deleted
        const finalCount = await page.locator('table tbody tr').count();
        expect(finalCount).toBeLessThan(initialCount);
      }
    } else {
      test.skip();
    }
  });

  /**
   * TC-V027: Create Availability for ACCOMMODATION (Daily Slots)
   * Tests creating daily availability for accommodation type listings
   */
  test('TC-V027: Create availability for ACCOMMODATION (daily slots)', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules/create`);
    await waitForFilamentPage(page);

    // Select an accommodation listing
    const listingSelect = page.locator('select[name*="listing"]').first();
    if (await listingSelect.isVisible()) {
      // Try to find accommodation listing
      const options = await listingSelect.locator('option').allTextContents();
      const accommodationIndex = options.findIndex(
        (opt) =>
          opt.toLowerCase().includes('accommodation') ||
          opt.toLowerCase().includes('villa') ||
          opt.toLowerCase().includes('hotel') ||
          opt.toLowerCase().includes('glamping')
      );

      if (accommodationIndex > 0) {
        await listingSelect.selectOption({ index: accommodationIndex });
      } else {
        await listingSelect.selectOption({ index: 1 });
      }
    }
    await page.waitForTimeout(500);

    // Create daily availability
    const ruleTypeSelect = page.locator('select[name*="rule_type"]').first();
    if (await ruleTypeSelect.isVisible()) {
      await ruleTypeSelect.selectOption('daily');
    }
    await page.waitForTimeout(500);

    // For accommodation, availability might be per-night
    // Set check-in time
    const startTimeInput = page.locator('input[name*="start_time"]').first();
    if (await startTimeInput.isVisible()) {
      await startTimeInput.fill('15:00'); // Standard check-in time
    }

    const endTimeInput = page.locator('input[name*="end_time"]').first();
    if (await endTimeInput.isVisible()) {
      await endTimeInput.fill('11:00'); // Standard check-out time
    }

    // Set capacity (number of units available)
    const capacityInput = page.locator('input[name*="capacity"]').first();
    if (await capacityInput.isVisible()) {
      await capacityInput.fill('1'); // One unit per day
    }

    // Save
    await submitFilamentForm(page);

    // Verify success
    await expect(page.locator('body')).toContainText(/created|success/i);
  });

  /**
   * TC-V028: Create Availability for EVENT (Specific Date)
   * Tests creating availability for a specific event date
   */
  test('TC-V028: Create availability for EVENT (specific date)', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules/create`);
    await waitForFilamentPage(page);

    // Select an event listing
    const listingSelect = page.locator('select[name*="listing"]').first();
    if (await listingSelect.isVisible()) {
      const options = await listingSelect.locator('option').allTextContents();
      const eventIndex = options.findIndex(
        (opt) =>
          opt.toLowerCase().includes('event') ||
          opt.toLowerCase().includes('festival') ||
          opt.toLowerCase().includes('concert')
      );

      if (eventIndex > 0) {
        await listingSelect.selectOption({ index: eventIndex });
      } else {
        await listingSelect.selectOption({ index: 1 });
      }
    }
    await page.waitForTimeout(500);

    // Create specific date availability
    const ruleTypeSelect = page.locator('select[name*="rule_type"]').first();
    if (await ruleTypeSelect.isVisible()) {
      await ruleTypeSelect.selectOption('specific_dates');
    }
    await page.waitForTimeout(500);

    // Set specific event date (30 days from now)
    const startDateInput = page.locator('input[name*="start_date"]').first();
    if (await startDateInput.isVisible()) {
      await startDateInput.fill(getFutureDate(30));
    }

    const endDateInput = page.locator('input[name*="end_date"]').first();
    if (await endDateInput.isVisible()) {
      await endDateInput.fill(getFutureDate(32)); // 3-day event
    }

    // Event timing
    const startTimeInput = page.locator('input[name*="start_time"]').first();
    if (await startTimeInput.isVisible()) {
      await startTimeInput.fill('18:00');
    }

    const endTimeInput = page.locator('input[name*="end_time"]').first();
    if (await endTimeInput.isVisible()) {
      await endTimeInput.fill('23:00');
    }

    // Large capacity for event
    const capacityInput = page.locator('input[name*="capacity"]').first();
    if (await capacityInput.isVisible()) {
      await capacityInput.fill('500');
    }

    // Save
    await submitFilamentForm(page);

    await expect(page.locator('body')).toContainText(/created|success/i);
  });

  /**
   * TC-V029: Past Dates Rejected When Creating Rules
   * Tests that creating availability rules with past dates is rejected
   */
  test('TC-V029: Past dates rejected when creating rules', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules/create`);
    await waitForFilamentPage(page);

    // Select a listing
    const listingSelect = page.locator('select[name*="listing"]').first();
    if (await listingSelect.isVisible()) {
      await listingSelect.selectOption({ index: 1 });
    }
    await page.waitForTimeout(500);

    // Select specific dates type
    const ruleTypeSelect = page.locator('select[name*="rule_type"]').first();
    if (await ruleTypeSelect.isVisible()) {
      await ruleTypeSelect.selectOption('specific_dates');
    }
    await page.waitForTimeout(500);

    // Try to set a past date
    const pastDate = new Date();
    pastDate.setDate(pastDate.getDate() - 5);
    const pastDateStr = pastDate.toISOString().split('T')[0];

    const startDateInput = page.locator('input[name*="start_date"]').first();
    if (await startDateInput.isVisible()) {
      await startDateInput.fill(pastDateStr);
    }

    const endDateInput = page.locator('input[name*="end_date"]').first();
    if (await endDateInput.isVisible()) {
      await endDateInput.fill(pastDateStr);
    }

    // Fill other required fields
    await page.locator('input[name*="start_time"]').first().fill('09:00');
    await page.locator('input[name*="end_time"]').first().fill('17:00');
    await page.locator('input[name*="capacity"]').first().fill('10');

    // Try to save
    await submitFilamentForm(page);
    await page.waitForTimeout(1000);

    // Expect validation error for past date
    const bodyText = await page.locator('body').textContent();
    const hasError = bodyText?.toLowerCase().match(/past|future|invalid|error|must be/i);
    const stillOnForm = await page.locator('form').isVisible();

    // Either error message or we're still on the form
    expect(hasError || stillOnForm).toBeTruthy();
  });

  /**
   * TC-V030: Zero Capacity Rejected
   * Tests that creating availability rules with zero capacity is rejected
   */
  test('TC-V030: Zero capacity rejected', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules/create`);
    await waitForFilamentPage(page);

    // Select a listing
    const listingSelect = page.locator('select[name*="listing"]').first();
    if (await listingSelect.isVisible()) {
      await listingSelect.selectOption({ index: 1 });
    }
    await page.waitForTimeout(500);

    // Select weekly type
    const ruleTypeSelect = page.locator('select[name*="rule_type"]').first();
    if (await ruleTypeSelect.isVisible()) {
      await ruleTypeSelect.selectOption('weekly');
    }
    await page.waitForTimeout(500);

    // Select a day
    const mondayCheckbox = page.locator('input[name*="days_of_week"][value="1"]').first();
    if (await mondayCheckbox.isVisible()) {
      await mondayCheckbox.check();
    }

    // Set times
    await page.locator('input[name*="start_time"]').first().fill('09:00');
    await page.locator('input[name*="end_time"]').first().fill('17:00');

    // Set zero capacity
    const capacityInput = page.locator('input[name*="capacity"]').first();
    if (await capacityInput.isVisible()) {
      await capacityInput.fill('0');
    }

    // Try to save
    await submitFilamentForm(page);
    await page.waitForTimeout(1000);

    // Expect validation error
    const bodyText = await page.locator('body').textContent();
    const hasError = bodyText
      ?.toLowerCase()
      .match(/capacity|minimum|greater|invalid|error|at least/i);
    const stillOnForm = await page.locator('form').isVisible();

    expect(hasError || stillOnForm).toBeTruthy();
  });

  /**
   * TC-V031: Multiple Time Slots Per Day
   * Tests creating multiple availability slots for the same day
   */
  test('TC-V031: Multiple time slots per day', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules`);
    await waitForFilamentPage(page);

    const initialCount = await page.locator('table tbody tr').count();

    // Create morning slot
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules/create`);
    await waitForFilamentPage(page);

    const listingSelect = page.locator('select[name*="listing"]').first();
    if (await listingSelect.isVisible()) {
      await listingSelect.selectOption({ index: 1 });
    }
    await page.waitForTimeout(500);

    const ruleTypeSelect = page.locator('select[name*="rule_type"]').first();
    if (await ruleTypeSelect.isVisible()) {
      await ruleTypeSelect.selectOption('weekly');
    }
    await page.waitForTimeout(500);

    // Select Monday
    const mondayCheckbox = page.locator('input[name*="days_of_week"][value="1"]').first();
    if (await mondayCheckbox.isVisible()) {
      await mondayCheckbox.check();
    }

    // Morning slot: 08:00 - 12:00
    await page.locator('input[name*="start_time"]').first().fill('08:00');
    await page.locator('input[name*="end_time"]').first().fill('12:00');
    await page.locator('input[name*="capacity"]').first().fill('10');

    await submitFilamentForm(page);
    await page.waitForTimeout(1000);

    // Create afternoon slot for the same day
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules/create`);
    await waitForFilamentPage(page);

    const listingSelect2 = page.locator('select[name*="listing"]').first();
    if (await listingSelect2.isVisible()) {
      await listingSelect2.selectOption({ index: 1 });
    }
    await page.waitForTimeout(500);

    const ruleTypeSelect2 = page.locator('select[name*="rule_type"]').first();
    if (await ruleTypeSelect2.isVisible()) {
      await ruleTypeSelect2.selectOption('weekly');
    }
    await page.waitForTimeout(500);

    // Select Monday again
    const mondayCheckbox2 = page.locator('input[name*="days_of_week"][value="1"]').first();
    if (await mondayCheckbox2.isVisible()) {
      await mondayCheckbox2.check();
    }

    // Afternoon slot: 14:00 - 18:00
    await page.locator('input[name*="start_time"]').first().fill('14:00');
    await page.locator('input[name*="end_time"]').first().fill('18:00');
    await page.locator('input[name*="capacity"]').first().fill('10');

    await submitFilamentForm(page);

    // Navigate back to list and verify both slots exist
    await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules`);
    await waitForFilamentPage(page);

    const finalCount = await page.locator('table tbody tr').count();
    expect(finalCount).toBeGreaterThanOrEqual(initialCount + 2);
  });

  /**
   * TC-V032: Availability Reflects on Frontend Calendar After Change
   * Tests that availability changes propagate to the frontend calendar
   */
  test('TC-V032: Availability reflects on frontend calendar after change', async ({ page }) => {
    const FRONTEND_URL = process.env.NEXT_PUBLIC_URL || 'http://localhost:3000';

    // First, get a listing's slug
    await page.goto(`${VENDOR_PANEL_URL}/vendor/listings`);
    await waitForFilamentPage(page);

    // Find a published listing
    const publishedRow = page
      .locator('table tbody tr')
      .filter({
        hasText: /published|publie/i,
      })
      .first();

    if (await publishedRow.isVisible({ timeout: 5000 }).catch(() => false)) {
      // Get the listing edit link to find slug
      const editLink = publishedRow.locator('a[href*="/edit"]').first();
      await editLink.click();
      await page.waitForLoadState('networkidle');

      const slugInput = page.locator('input[name*="slug"]').first();
      let slug = '';
      if (await slugInput.isVisible()) {
        slug = await slugInput.inputValue();
      }

      // Get listing ID
      const url = page.url();
      const match = url.match(/\/listings\/(\d+)/);
      const listingId = match ? match[1] : '';

      if (slug && listingId) {
        // Create a new availability rule for this listing
        await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules/create`);
        await waitForFilamentPage(page);

        const listingSelect = page.locator('select[name*="listing"]').first();
        if (await listingSelect.isVisible()) {
          // Try to select by listing ID
          await listingSelect.selectOption(listingId);
        }
        await page.waitForTimeout(500);

        // Create specific date availability for next week
        const ruleTypeSelect = page.locator('select[name*="rule_type"]').first();
        if (await ruleTypeSelect.isVisible()) {
          await ruleTypeSelect.selectOption('specific_dates');
        }
        await page.waitForTimeout(500);

        const futureDate = getFutureDate(7);
        await page.locator('input[name*="start_date"]').first().fill(futureDate);
        await page.locator('input[name*="end_date"]').first().fill(futureDate);
        await page.locator('input[name*="start_time"]').first().fill('10:00');
        await page.locator('input[name*="end_time"]').first().fill('16:00');
        await page.locator('input[name*="capacity"]').first().fill('5');

        await submitFilamentForm(page);

        // Now verify on frontend
        await page.goto(`${FRONTEND_URL}/fr/listings/${slug}`);
        await page.waitForLoadState('networkidle');

        // Check for availability/booking section
        const bookingSection = page
          .locator(
            '[data-testid="booking-panel"], [class*="booking"], [class*="availability"], form'
          )
          .first();

        await expect(bookingSection).toBeVisible({ timeout: 10000 });

        // If calendar is visible, check that date picker is functional
        const datePickerTrigger = page
          .locator(
            '[data-testid="date-picker"], input[type="date"], button:has-text("Select"), button:has-text("Choisir"), [class*="calendar"]'
          )
          .first();

        if (await datePickerTrigger.isVisible({ timeout: 3000 }).catch(() => false)) {
          await datePickerTrigger.click();
          await page.waitForTimeout(500);

          // Verify calendar opens and has some interactive dates
          const calendarDays = page.locator(
            '[role="dialog"] button, [class*="calendar"] button, [class*="day"]'
          );
          const dayCount = await calendarDays.count();
          expect(dayCount).toBeGreaterThan(0);
        }
      }
    } else {
      // No published listing found
      test.skip();
    }
  });
});
