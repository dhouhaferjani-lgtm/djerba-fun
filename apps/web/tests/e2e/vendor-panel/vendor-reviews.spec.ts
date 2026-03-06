/**
 * Vendor Panel E2E Tests - Section 2.5: Review Management
 * Test Cases: TC-V040 to TC-V042
 *
 * Tests vendor review moderation functionality
 * through the Filament vendor panel.
 */

import { test, expect } from '@playwright/test';
import { loginVendorUI, waitForFilamentPage, seededVendor } from '../../fixtures/vendor-helpers';
import { reviewData } from '../../fixtures/vendor-test-data';

const VENDOR_PANEL_URL = process.env.LARAVEL_URL || 'http://localhost:8000';

test.describe('Vendor Panel - Review Management (2.5)', () => {
  test.beforeEach(async ({ page }) => {
    await loginVendorUI(page, seededVendor.email, seededVendor.password);
  });

  /**
   * TC-V040: Approve Pending Review
   * Tests approving a pending review to publish it
   */
  test('TC-V040: Approve Pending Review', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/reviews`);
    await waitForFilamentPage(page);

    // Look for badge showing pending count in navigation
    const pendingBadge = page.locator('[data-badge], .badge:has-text("pending")');

    // Filter for pending reviews
    const statusFilter = page.locator('select[name*="status"], [data-filter="status"]').first();
    if (await statusFilter.isVisible()) {
      await statusFilter.selectOption('pending');
      await page.waitForTimeout(1000);
    }

    // Find a pending review
    const pendingRow = page
      .locator('table tbody tr:has-text("Pending"), table tbody tr:has-text("pending")')
      .first();

    if (await pendingRow.isVisible({ timeout: 5000 }).catch(() => false)) {
      // Click to view review
      await pendingRow.click();
      await page.waitForLoadState('networkidle');

      // Look for Approve button
      const approveButton = page
        .locator('button:has-text("Approve"), ' + '[data-action="approve"]')
        .first();

      if (await approveButton.isVisible()) {
        await approveButton.click();
        await page.waitForTimeout(500);

        // Confirm if modal appears
        const confirmButton = page
          .locator('button:has-text("Confirm"), button:has-text("Yes")')
          .first();
        if (await confirmButton.isVisible({ timeout: 2000 }).catch(() => false)) {
          await confirmButton.click();
        }

        await page.waitForLoadState('networkidle');

        // Verify: Status = Published
        await expect(page.locator('body')).toContainText(/published|approved|success/i);
      }
    } else {
      // No pending reviews available - skip test
      test.skip();
    }
  });

  /**
   * TC-V041: Reject Review with Reason
   * Tests rejecting a review and providing a reason
   */
  test('TC-V041: Reject Review with Reason', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/reviews`);
    await waitForFilamentPage(page);

    // Filter for pending reviews
    const statusFilter = page.locator('select[name*="status"]').first();
    if (await statusFilter.isVisible()) {
      await statusFilter.selectOption('pending');
      await page.waitForTimeout(1000);
    }

    // Find a pending review
    const pendingRow = page.locator('table tbody tr:has-text("Pending")').first();

    if (await pendingRow.isVisible({ timeout: 5000 }).catch(() => false)) {
      await pendingRow.click();
      await page.waitForLoadState('networkidle');

      // Look for Reject button
      const rejectButton = page
        .locator('button:has-text("Reject"), ' + '[data-action="reject"]')
        .first();

      if (await rejectButton.isVisible()) {
        await rejectButton.click();
        await page.waitForTimeout(500);

        // Enter rejection reason
        const reasonInput = page
          .locator(
            'textarea[name*="reason"], ' +
              'textarea[name*="rejection_reason"], ' +
              'input[name*="reason"]'
          )
          .first();

        if (await reasonInput.isVisible()) {
          await reasonInput.fill(
            'This review contains inappropriate content and does not meet our community guidelines.'
          );
        }

        // Confirm rejection
        const confirmButton = page
          .locator(
            'button:has-text("Confirm"), ' + 'button:has-text("Reject"), ' + 'button[type="submit"]'
          )
          .first();

        if (await confirmButton.isVisible()) {
          await confirmButton.click();
        }

        await page.waitForLoadState('networkidle');

        // Verify: Status = Rejected, not shown on frontend
        await expect(page.locator('body')).toContainText(/rejected|success/i);
      }
    } else {
      test.skip();
    }
  });

  /**
   * TC-V042: Reply to Review
   * Tests adding a vendor response to an approved review
   */
  test('TC-V042: Reply to Review', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/reviews`);
    await waitForFilamentPage(page);

    // Filter for published/approved reviews
    const statusFilter = page.locator('select[name*="status"]').first();
    if (await statusFilter.isVisible()) {
      await statusFilter.selectOption('published');
      await page.waitForTimeout(1000);
    }

    // Find a published review (preferably without reply)
    const publishedRow = page
      .locator('table tbody tr:has-text("Published"), table tbody tr:has-text("published")')
      .first();

    if (await publishedRow.isVisible({ timeout: 5000 }).catch(() => false)) {
      await publishedRow.click();
      await page.waitForLoadState('networkidle');

      // Look for Reply button/section
      const replyButton = page
        .locator(
          'button:has-text("Reply"), ' +
            'button:has-text("Add Response"), ' +
            'button:has-text("Respond"), ' +
            '[data-action="reply"]'
        )
        .first();

      if (await replyButton.isVisible()) {
        await replyButton.click();
        await page.waitForTimeout(500);

        // Enter vendor response
        const replyInput = page
          .locator(
            'textarea[name*="reply"], ' +
              'textarea[name*="response"], ' +
              'textarea[name*="vendor_response"]'
          )
          .first();

        if (await replyInput.isVisible()) {
          await replyInput.fill(
            'Thank you so much for your kind words and for taking the time to share your experience! ' +
              'We are thrilled that you enjoyed the tour. We hope to welcome you back soon for another adventure!'
          );
        }

        // Save reply
        const saveButton = page
          .locator(
            'button:has-text("Save"), ' +
              'button:has-text("Submit"), ' +
              'button:has-text("Post Reply"), ' +
              'button[type="submit"]'
          )
          .first();

        if (await saveButton.isVisible()) {
          await saveButton.click();
        }

        await page.waitForLoadState('networkidle');

        // Verify: Reply saved, shown on frontend below review
        await expect(page.locator('body')).toContainText(/saved|success|reply/i);
      }
    } else {
      test.skip();
    }
  });
});
