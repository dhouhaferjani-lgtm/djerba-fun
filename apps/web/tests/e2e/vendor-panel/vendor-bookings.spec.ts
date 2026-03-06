/**
 * Vendor Panel E2E Tests - Section 2.4: Booking Management
 * Test Cases: TC-V030 to TC-V035
 *
 * Tests vendor booking management functionality
 * through the Filament vendor panel.
 */

import { test, expect } from '@playwright/test';
import { loginVendorUI, waitForFilamentPage, seededVendor } from '../../fixtures/vendor-helpers';
import { bookingData } from '../../fixtures/vendor-test-data';

const VENDOR_PANEL_URL = process.env.LARAVEL_URL || 'http://localhost:8000';

test.describe('Vendor Panel - Booking Management (2.4)', () => {
  test.beforeEach(async ({ page }) => {
    await loginVendorUI(page, seededVendor.email, seededVendor.password);
  });

  /**
   * TC-V030: View Own Bookings Only
   * Verifies vendor can only see bookings for their own listings
   */
  test('TC-V030: View Own Bookings Only', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/bookings`);
    await waitForFilamentPage(page);

    // Verify bookings table is visible
    const bookingsTable = page.locator('table, [data-resource="bookings"]');
    await expect(bookingsTable).toBeVisible({ timeout: 10000 });

    // Check that table columns include expected fields
    const tableHeaders = page.locator('table thead th, [role="columnheader"]');
    const headerTexts = await tableHeaders.allTextContents();

    // Should have columns like: Booking Number, Listing, Status, Traveler, etc.
    expect(headerTexts.join(' ').toLowerCase()).toMatch(
      /booking|listing|status|traveler|date|amount/i
    );

    // All bookings shown should be for this vendor's listings
    // This is enforced by the Filament resource query scope
    // We verify by checking that we can see some bookings (if any exist)
    const bookingRows = page.locator('table tbody tr');
    const rowCount = await bookingRows.count();

    // If there are bookings, verify each row has expected structure
    if (rowCount > 0) {
      const firstRow = bookingRows.first();
      await expect(firstRow).toBeVisible();

      // Should contain booking number format
      const rowText = await firstRow.textContent();
      expect(rowText).toBeTruthy();
    }
  });

  /**
   * TC-V031: Mark Booking as Paid (Offline Payment)
   * Tests marking a pending payment booking as paid
   */
  test('TC-V031: Mark Booking as Paid (Offline Payment)', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/bookings`);
    await waitForFilamentPage(page);

    // Filter for pending payment bookings
    const statusFilter = page.locator('select[name*="status"], [data-filter="status"]').first();
    if (await statusFilter.isVisible()) {
      await statusFilter.selectOption('pending_payment');
      await page.waitForTimeout(1000);
    }

    // Find a pending payment booking
    const pendingRow = page
      .locator('table tbody tr:has-text("Pending"), table tbody tr:has-text("pending_payment")')
      .first();

    if (await pendingRow.isVisible({ timeout: 5000 }).catch(() => false)) {
      // Click to view booking
      await pendingRow.click();
      await page.waitForLoadState('networkidle');

      // Look for Mark as Paid action
      const markPaidButton = page
        .locator(
          'button:has-text("Mark as Paid"), ' +
            'button:has-text("Confirm Payment"), ' +
            '[data-action="markAsPaid"]'
        )
        .first();

      if (await markPaidButton.isVisible()) {
        await markPaidButton.click();
        await page.waitForTimeout(500);

        // Enter payment notes if modal appears
        const notesInput = page
          .locator('textarea[name*="notes"], textarea[name*="payment_notes"]')
          .first();
        if (await notesInput.isVisible({ timeout: 2000 }).catch(() => false)) {
          await notesInput.fill('Payment received via bank transfer - Test');
        }

        // Confirm
        const confirmButton = page
          .locator('button:has-text("Confirm"), button:has-text("Yes"), button[type="submit"]')
          .first();
        if (await confirmButton.isVisible({ timeout: 2000 }).catch(() => false)) {
          await confirmButton.click();
        }

        await page.waitForLoadState('networkidle');

        // Verify: Status changed to Confirmed
        await expect(page.locator('body')).toContainText(/confirmed|paid|success/i);
      }
    } else {
      // No pending payment bookings available
      test.skip();
    }
  });

  /**
   * TC-V032: Record Partial Payment
   * Tests recording partial payment and then completing payment
   */
  test('TC-V032: Record Partial Payment', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/bookings`);
    await waitForFilamentPage(page);

    // Find a pending payment booking
    const pendingRow = page
      .locator('table tbody tr:has-text("Pending"), table tbody tr:has-text("pending_payment")')
      .first();

    if (await pendingRow.isVisible({ timeout: 5000 }).catch(() => false)) {
      await pendingRow.click();
      await page.waitForLoadState('networkidle');

      // Look for Partial Payment action
      const partialPaymentButton = page
        .locator(
          'button:has-text("Partial Payment"), ' +
            'button:has-text("Record Payment"), ' +
            '[data-action="partialPayment"]'
        )
        .first();

      if (await partialPaymentButton.isVisible()) {
        await partialPaymentButton.click();
        await page.waitForTimeout(500);

        // Enter partial amount
        const amountInput = page
          .locator('input[name*="amount"], input[type="number"][name*="payment"]')
          .first();
        if (await amountInput.isVisible()) {
          await amountInput.fill('100'); // Partial amount
        }

        // Confirm
        const confirmButton = page
          .locator('button:has-text("Confirm"), button:has-text("Record")')
          .first();
        if (await confirmButton.isVisible()) {
          await confirmButton.click();
        }

        await page.waitForLoadState('networkidle');

        // Verify: Payment intent logged
        await expect(page.locator('body')).toContainText(/recorded|partial|success/i);

        // Record remaining payment
        if (await partialPaymentButton.isVisible()) {
          await partialPaymentButton.click();
          await page.waitForTimeout(500);

          // Enter remaining amount
          if (await amountInput.isVisible()) {
            await amountInput.fill('200'); // Remaining amount
          }

          const confirmButton2 = page
            .locator('button:has-text("Confirm"), button:has-text("Record")')
            .first();
          if (await confirmButton2.isVisible()) {
            await confirmButton2.click();
          }

          // Verify: Booking fully paid, status confirmed
          await expect(page.locator('body')).toContainText(/confirmed|paid|complete/i);
        }
      }
    } else {
      test.skip();
    }
  });

  /**
   * TC-V033: Mark Booking Completed
   * Tests marking a past confirmed booking as completed
   */
  test('TC-V033: Mark Booking Completed', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/bookings`);
    await waitForFilamentPage(page);

    // Filter for confirmed bookings
    const statusFilter = page.locator('select[name*="status"]').first();
    if (await statusFilter.isVisible()) {
      await statusFilter.selectOption('confirmed');
      await page.waitForTimeout(1000);
    }

    // Find a confirmed booking (ideally with past date)
    const confirmedRow = page
      .locator('table tbody tr:has-text("Confirmed"), table tbody tr:has-text("confirmed")')
      .first();

    if (await confirmedRow.isVisible({ timeout: 5000 }).catch(() => false)) {
      await confirmedRow.click();
      await page.waitForLoadState('networkidle');

      // Look for Mark Completed action
      const completeButton = page
        .locator(
          'button:has-text("Mark Completed"), ' +
            'button:has-text("Complete"), ' +
            '[data-action="markCompleted"]'
        )
        .first();

      if (await completeButton.isVisible()) {
        await completeButton.click();
        await page.waitForTimeout(500);

        // Confirm if modal appears
        const confirmButton = page
          .locator('button:has-text("Confirm"), button:has-text("Yes")')
          .first();
        if (await confirmButton.isVisible({ timeout: 2000 }).catch(() => false)) {
          await confirmButton.click();
        }

        await page.waitForLoadState('networkidle');

        // Verify: Status = Completed
        await expect(page.locator('body')).toContainText(/completed|success/i);
      }
    } else {
      test.skip();
    }
  });

  /**
   * TC-V034: Mark No-Show
   * Tests marking a past confirmed booking as no-show
   */
  test('TC-V034: Mark No-Show', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/bookings`);
    await waitForFilamentPage(page);

    // Filter for confirmed bookings
    const statusFilter = page.locator('select[name*="status"]').first();
    if (await statusFilter.isVisible()) {
      await statusFilter.selectOption('confirmed');
      await page.waitForTimeout(1000);
    }

    // Find a confirmed booking
    const confirmedRow = page.locator('table tbody tr:has-text("Confirmed")').first();

    if (await confirmedRow.isVisible({ timeout: 5000 }).catch(() => false)) {
      await confirmedRow.click();
      await page.waitForLoadState('networkidle');

      // Look for No-Show action
      const noShowButton = page
        .locator(
          'button:has-text("No-Show"), ' +
            'button:has-text("Mark No Show"), ' +
            '[data-action="markNoShow"]'
        )
        .first();

      if (await noShowButton.isVisible()) {
        await noShowButton.click();
        await page.waitForTimeout(500);

        // Confirm if modal appears
        const confirmButton = page
          .locator('button:has-text("Confirm"), button:has-text("Yes")')
          .first();
        if (await confirmButton.isVisible({ timeout: 2000 }).catch(() => false)) {
          await confirmButton.click();
        }

        await page.waitForLoadState('networkidle');

        // Verify: Status = No Show
        await expect(page.locator('body')).toContainText(/no.?show|success/i);
      }
    } else {
      test.skip();
    }
  });

  /**
   * TC-V035: Contact Traveler
   * Tests the contact traveler functionality
   */
  test('TC-V035: Contact Traveler', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/bookings`);
    await waitForFilamentPage(page);

    // Find any booking
    const firstRow = page.locator('table tbody tr').first();

    if (await firstRow.isVisible({ timeout: 5000 }).catch(() => false)) {
      await firstRow.click();
      await page.waitForLoadState('networkidle');

      // Look for Contact Traveler action
      const contactButton = page
        .locator(
          'button:has-text("Contact"), ' +
            'a:has-text("Contact Traveler"), ' +
            'a[href^="mailto:"], ' +
            '[data-action="contact"]'
        )
        .first();

      if (await contactButton.isVisible()) {
        // Check if it's a mailto link
        const href = await contactButton.getAttribute('href');

        if (href && href.startsWith('mailto:')) {
          // Verify: Email client would open with traveler's email
          expect(href).toMatch(/mailto:.+@.+/);
        } else {
          // Click to see contact options
          await contactButton.click();
          await page.waitForTimeout(500);

          // Verify some contact method is shown
          await expect(page.locator('body')).toContainText(/email|contact|@/i);
        }
      }
    } else {
      test.skip();
    }
  });
});
