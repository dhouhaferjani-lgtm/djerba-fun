/**
 * Vendor Panel E2E Tests - Section 2.7: Email Logs
 * Test Cases: TC-V060 to TC-V061
 *
 * Tests email log viewing and resend functionality
 * through the Filament vendor panel.
 */

import { test, expect } from '@playwright/test';
import { loginVendorUI, waitForFilamentPage, seededVendor } from '../../fixtures/vendor-helpers';

const VENDOR_PANEL_URL = process.env.LARAVEL_URL || 'http://localhost:8000';

test.describe('Vendor Panel - Email Logs (2.7)', () => {
  test.beforeEach(async ({ page }) => {
    await loginVendorUI(page, seededVendor.email, seededVendor.password);
  });

  /**
   * TC-V060: View Email Delivery Status
   * Tests viewing email logs and their delivery status
   */
  test('TC-V060: View Email Delivery Status', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/email-logs`);
    await waitForFilamentPage(page);

    // Verify email logs table is visible
    const emailLogsTable = page.locator('table, [data-resource="email-logs"]');
    await expect(emailLogsTable).toBeVisible({ timeout: 10000 });

    // Check that table has expected columns
    const tableHeaders = page.locator('table thead th, [role="columnheader"]');
    const headerTexts = await tableHeaders.allTextContents();
    const headersJoined = headerTexts.join(' ').toLowerCase();

    // Should have columns for: Recipient, Type, Status, Sent at, etc.
    expect(headersJoined).toMatch(/recipient|email|type|status|sent|date/i);

    // Check for rows (if any emails exist)
    const emailRows = page.locator('table tbody tr');
    const rowCount = await emailRows.count();

    if (rowCount > 0) {
      // Verify status column shows delivery status
      const firstRow = emailRows.first();
      const rowText = await firstRow.textContent();

      // Should contain status like: sent, delivered, opened, failed
      expect(rowText?.toLowerCase()).toMatch(/sent|delivered|opened|failed|pending|queued/i);

      // Click on a row to view details
      await firstRow.click();
      await page.waitForLoadState('networkidle');

      // Verify detail view shows email information
      await expect(page.locator('body')).toContainText(/recipient|email|status|content|subject/i);
    } else {
      // No email logs yet - verify empty state message
      await expect(page.locator('body')).toContainText(/no.*email|empty|no.*records/i);
    }
  });

  /**
   * TC-V061: Resend Failed Email
   * Tests resending a failed email
   */
  test('TC-V061: Resend Failed Email', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/email-logs`);
    await waitForFilamentPage(page);

    // Filter for failed emails
    const statusFilter = page.locator('select[name*="status"], [data-filter="status"]').first();
    if (await statusFilter.isVisible()) {
      await statusFilter.selectOption('failed');
      await page.waitForTimeout(1000);
    }

    // Look for failed email row
    const failedRow = page
      .locator('table tbody tr:has-text("Failed"), table tbody tr:has-text("failed")')
      .first();

    if (await failedRow.isVisible({ timeout: 5000 }).catch(() => false)) {
      // Click on the row to view details
      await failedRow.click();
      await page.waitForLoadState('networkidle');

      // Look for Resend button
      const resendButton = page
        .locator(
          'button:has-text("Resend"), ' +
            'button:has-text("Retry"), ' +
            'button:has-text("Send Again"), ' +
            '[data-action="resend"]'
        )
        .first();

      if (await resendButton.isVisible()) {
        await resendButton.click();
        await page.waitForTimeout(500);

        // Confirm if modal appears
        const confirmButton = page
          .locator('button:has-text("Confirm"), button:has-text("Yes")')
          .first();
        if (await confirmButton.isVisible({ timeout: 2000 }).catch(() => false)) {
          await confirmButton.click();
        }

        await page.waitForLoadState('networkidle');

        // Verify: Email queued for retry
        await expect(page.locator('body')).toContainText(/queued|resent|retry|success/i);
      } else {
        // Resend might be in table actions
        await page.goto(`${VENDOR_PANEL_URL}/vendor/email-logs`);
        await waitForFilamentPage(page);

        // Filter again
        if (await statusFilter.isVisible()) {
          await statusFilter.selectOption('failed');
          await page.waitForTimeout(1000);
        }

        // Try table action
        const failedRow2 = page.locator('table tbody tr:has-text("Failed")').first();
        if (await failedRow2.isVisible()) {
          // Look for actions dropdown
          const actionsButton = failedRow2.locator(
            'button[data-dropdown-trigger], button:has-text("Actions")'
          );
          if (await actionsButton.isVisible()) {
            await actionsButton.click();
            await page.waitForTimeout(300);

            const resendAction = page
              .locator('[data-action="resend"], button:has-text("Resend")')
              .first();
            if (await resendAction.isVisible()) {
              await resendAction.click();
              await page.waitForTimeout(500);

              // Confirm
              const confirmButton = page
                .locator('button:has-text("Confirm"), button:has-text("Yes")')
                .first();
              if (await confirmButton.isVisible({ timeout: 2000 }).catch(() => false)) {
                await confirmButton.click();
              }

              // Verify: Email queued for retry
              await expect(page.locator('body')).toContainText(/queued|resent|success/i);
            }
          }
        }
      }
    } else {
      // No failed emails available - skip test
      test.skip();
    }
  });
});
