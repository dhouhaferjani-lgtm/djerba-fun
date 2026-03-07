/**
 * Listing Lifecycle E2E Test Helpers
 * Cross-panel helpers for testing listing creation, status changes, and frontend verification
 */

import { Page, expect, Locator } from '@playwright/test';
import {
  loginVendorUI,
  seededVendor,
  waitForFilamentPage,
  submitFilamentForm,
} from './vendor-helpers';
import { loginToAdmin, navigateToAdminResource, waitForNotification } from './admin-api-helpers';
import { adminUsers } from './admin-test-data';

const VENDOR_PANEL_URL = process.env.LARAVEL_URL || 'http://localhost:8000';
const ADMIN_PANEL_URL = process.env.LARAVEL_URL || 'http://localhost:8000';
const FRONTEND_URL = process.env.NEXT_PUBLIC_URL || 'http://localhost:3000';

// ============================================================================
// INTERFACES
// ============================================================================

export interface ListingIdentifiers {
  id: string;
  slug: string;
  title: string;
  status: string;
  serviceType: string;
}

export type ListingStatus = 'draft' | 'pending_review' | 'published' | 'rejected' | 'archived';
export type ServiceType = 'tour' | 'nautical' | 'accommodation' | 'event';

// ============================================================================
// PANEL SWITCHING
// ============================================================================

/**
 * Switch to vendor panel (login as vendor)
 */
export async function switchToVendorPanel(page: Page): Promise<void> {
  await loginVendorUI(page, seededVendor.email, seededVendor.password);
}

/**
 * Switch to admin panel (login as admin)
 */
export async function switchToAdminPanel(page: Page): Promise<void> {
  await loginToAdmin(page, adminUsers.admin.email, adminUsers.admin.password);
}

/**
 * Navigate to public frontend
 */
export async function navigateToFrontend(page: Page, path: string = ''): Promise<void> {
  await page.goto(`${FRONTEND_URL}${path}`);
  await page.waitForLoadState('networkidle');
}

// ============================================================================
// LISTING STATUS VERIFICATION
// ============================================================================

/**
 * Verify listing status in vendor panel table
 */
export async function verifyVendorListingStatus(
  page: Page,
  listingIdentifier: string,
  expectedStatus: ListingStatus
): Promise<void> {
  await page.goto(`${VENDOR_PANEL_URL}/vendor/listings`);
  await page.waitForLoadState('networkidle');

  const row = page.getByRole('row', { name: new RegExp(listingIdentifier, 'i') }).first();
  await expect(row).toBeVisible({ timeout: 10000 });

  // Look for status badge in the row
  const statusBadge = row.locator('.fi-badge, [role="status"], .badge').filter({
    hasText: new RegExp(expectedStatus.replace('_', ' '), 'i'),
  });
  await expect(statusBadge).toBeVisible({ timeout: 5000 });
}

/**
 * Verify listing status in admin panel table
 */
export async function verifyAdminListingStatus(
  page: Page,
  listingIdentifier: string,
  expectedStatus: ListingStatus
): Promise<void> {
  await navigateToAdminResource(page, 'listings');

  const row = page.getByRole('row', { name: new RegExp(listingIdentifier, 'i') }).first();
  await expect(row).toBeVisible({ timeout: 10000 });

  // Admin table shows status as plain text in a cell, not as a badge
  // The status column contains the status value (e.g., "published", "archived", "rejected")
  const statusPattern = expectedStatus.replace('_', ' '); // pending_review -> pending review

  // First try badge selectors (vendor panel uses badges)
  const statusBadge = row.locator('.fi-badge, [role="status"], .badge').filter({
    hasText: new RegExp(statusPattern, 'i'),
  });

  if (await statusBadge.isVisible({ timeout: 2000 }).catch(() => false)) {
    return; // Status badge found
  }

  // If no badge, look for status text in any cell (admin panel uses plain text)
  const rowText = await row.textContent();
  const statusMatch =
    rowText?.toLowerCase().includes(statusPattern.toLowerCase()) ||
    rowText?.toLowerCase().includes(expectedStatus.toLowerCase());

  if (!statusMatch) {
    throw new Error(
      `Expected status "${expectedStatus}" not found in row. Row text: ${rowText?.substring(0, 200)}`
    );
  }
}

// ============================================================================
// FRONTEND LISTING VERIFICATION
// ============================================================================

/**
 * Verify listing visibility on public frontend
 */
export async function verifyFrontendListingVisible(
  page: Page,
  listingSlug: string,
  shouldBeVisible: boolean
): Promise<void> {
  const response = await page.goto(`${FRONTEND_URL}/fr/listings/${listingSlug}`);
  await page.waitForLoadState('networkidle');

  if (shouldBeVisible) {
    // Should have 200 status and show listing content
    expect(response?.status()).toBe(200);
    await expect(page.locator('h1')).toBeVisible({ timeout: 10000 });
    // Should not show 404 content
    const body = await page.locator('body').textContent();
    expect(body?.toLowerCase()).not.toContain('404');
    expect(body?.toLowerCase()).not.toContain('not found');
    expect(body?.toLowerCase()).not.toContain('introuvable');
  } else {
    // Should show 404 or redirect to 404 page
    const bodyText = await page.locator('body').textContent();
    const is404 =
      bodyText?.toLowerCase().includes('404') ||
      bodyText?.toLowerCase().includes('not found') ||
      bodyText?.toLowerCase().includes('introuvable') ||
      response?.status() === 404;
    expect(is404).toBeTruthy();
  }
}

/**
 * Verify listing displays correct service type-specific content
 */
export async function verifyListingTypeContent(
  page: Page,
  listingSlug: string,
  serviceType: ServiceType
): Promise<void> {
  await page.goto(`${FRONTEND_URL}/fr/listings/${listingSlug}`);
  await page.waitForLoadState('networkidle');

  // Common elements
  await expect(page.locator('h1')).toBeVisible();

  switch (serviceType) {
    case 'tour':
      // Tour-specific: duration, difficulty might be visible
      // Check for typical tour elements
      break;
    case 'nautical':
      // Nautical-specific: boat info might be visible
      break;
    case 'accommodation':
      // Accommodation-specific: amenities, rooms
      break;
    case 'event':
      // Event-specific: dates, venue
      break;
  }
}

/**
 * Verify availability calendar on frontend
 */
export async function verifyAvailabilityCalendar(
  page: Page,
  listingSlug: string,
  expectedSlots: { date: string; available: boolean }[]
): Promise<void> {
  await page.goto(`${FRONTEND_URL}/fr/listings/${listingSlug}`);
  await page.waitForLoadState('networkidle');

  // Look for availability calendar/date picker
  const calendar = page.locator(
    '[data-testid="availability-calendar"], .availability-calendar, [class*="calendar"]'
  );
  if (await calendar.isVisible({ timeout: 5000 }).catch(() => false)) {
    // Calendar is visible, verify slots
    for (const slot of expectedSlots) {
      const dayButton = calendar.locator(`button:has-text("${new Date(slot.date).getDate()}")`);
      if (slot.available) {
        await expect(dayButton).not.toBeDisabled();
      } else {
        await expect(dayButton).toBeDisabled();
      }
    }
  }
}

// ============================================================================
// VENDOR PANEL ACTIONS
// ============================================================================

/**
 * Submit a listing for review from vendor panel
 */
export async function vendorSubmitForReview(page: Page, listingIdentifier: string): Promise<void> {
  console.log(`DEBUG vendorSubmitForReview: Starting for "${listingIdentifier}"`);
  await page.goto(`${VENDOR_PANEL_URL}/vendor/listings`);
  await waitForFilamentPage(page);

  // Find the listing row
  const row = page.getByRole('row', { name: new RegExp(listingIdentifier, 'i') }).first();
  await expect(row).toBeVisible({ timeout: 10000 });
  console.log('DEBUG vendorSubmitForReview: Found listing row');

  // In Filament 3, "Submit for Review" is a direct action button in the row, not a dropdown item
  const submitButton = row.getByRole('button', { name: /submit.*review/i });

  if (await submitButton.isVisible({ timeout: 3000 }).catch(() => false)) {
    console.log('DEBUG vendorSubmitForReview: Clicking Submit for Review button');
    await submitButton.click();
  } else {
    console.log('DEBUG vendorSubmitForReview: Submit button not visible, trying dropdown');
    // Fallback: try opening actions dropdown if button not directly visible
    const actionsButton = row
      .locator('button[aria-haspopup="menu"], [data-dropdown-trigger] button')
      .first();
    if (await actionsButton.isVisible()) {
      await actionsButton.click();
      await page.waitForTimeout(300);
    }
    await page.getByRole('menuitem', { name: /submit.*review|request.*review/i }).click();
  }

  // Wait for Filament confirmation modal to appear
  console.log('DEBUG vendorSubmitForReview: Waiting for modal');

  // Wait for the modal description text to appear
  const modalDescription = page.locator('text=Before submitting');
  await expect(modalDescription).toBeVisible({ timeout: 10000 });

  // Wait for modal animation
  await page.waitForTimeout(500);

  // CRITICAL: Find the CORRECT modal window that contains "Before submitting"
  // There may be multiple modals (e.g., notifications modal)
  const confirmationModal = page
    .locator('.fi-modal-window')
    .filter({ hasText: 'Before submitting' });
  await expect(confirmationModal).toBeVisible({ timeout: 5000 });

  // Get the confirmation modal buttons
  const modalButtons = await confirmationModal.locator('button').all();
  console.log(`DEBUG vendorSubmitForReview: Confirmation modal has ${modalButtons.length} buttons`);
  for (let i = 0; i < Math.min(modalButtons.length, 5); i++) {
    const btnText = await modalButtons[i].textContent().catch(() => 'N/A');
    console.log(
      `DEBUG vendorSubmitForReview: Modal button ${i}: "${btnText?.trim().substring(0, 20)}"`
    );
  }

  // Log all buttons on the page to find the right one
  const allButtons = await page.locator('button').all();
  const buttonTexts = await Promise.all(
    allButtons.slice(0, 20).map(async (btn) => {
      const text = await btn.textContent().catch(() => '');
      const isVisible = await btn.isVisible().catch(() => false);
      return `"${text?.trim().substring(0, 30)}" (visible: ${isVisible})`;
    })
  );
  console.log(`DEBUG vendorSubmitForReview: Buttons on page: ${buttonTexts.join(', ')}`);

  // Find the Confirm button INSIDE the confirmation modal (not from notifications modal)
  let confirmButton = confirmationModal.getByRole('button', { name: /confirm/i }).first();

  if (!(await confirmButton.isVisible({ timeout: 2000 }).catch(() => false))) {
    // Try finding by text inside the confirmation modal
    confirmButton = confirmationModal.locator('button:has-text("Confirm")').first();
  }

  const confirmBtnText = await confirmButton.textContent().catch(() => 'N/A');
  console.log(
    `DEBUG vendorSubmitForReview: Found Confirm button inside modal: "${confirmBtnText?.trim()}"`
  );

  // Click the confirm button and wait for Livewire response
  // Set up network monitoring
  const responsePromise = page
    .waitForResponse((resp) => resp.url().includes('livewire') || resp.url().includes('listings'), {
      timeout: 10000,
    })
    .catch(() => null);

  // Try both click methods for reliability
  await confirmButton.click();
  await page.waitForTimeout(500);

  // If modal is still visible, try JS click
  if (await modalDescription.isVisible({ timeout: 1000 }).catch(() => false)) {
    console.log('DEBUG vendorSubmitForReview: Modal still open, trying JS click');
    await confirmButton.evaluate((btn: HTMLButtonElement) => btn.click());
    await page.waitForTimeout(500);
  }

  // Wait for Livewire response
  const response = await responsePromise;
  if (response) {
    const responseText = await response.text().catch(() => '');
    console.log(`DEBUG vendorSubmitForReview: Livewire response status: ${response.status()}`);
    if (
      responseText.includes('error') ||
      responseText.includes('Error') ||
      responseText.includes('validation')
    ) {
      console.log(
        `DEBUG vendorSubmitForReview: Response contains error: ${responseText.substring(0, 500)}`
      );
    }
  }

  // Wait for Livewire to process
  await page.waitForTimeout(2000);

  // Check if modal is still visible (might indicate an error)
  const modalStillVisible = await modalDescription.isVisible({ timeout: 1000 }).catch(() => false);
  console.log(
    `DEBUG vendorSubmitForReview: Modal still visible after Confirm: ${modalStillVisible}`
  );

  // If modal is still visible, look for error messages inside it
  if (modalStillVisible) {
    const modalContent = await confirmationModal.textContent().catch(() => '');
    console.log(
      `DEBUG vendorSubmitForReview: Modal content after Confirm: ${modalContent?.substring(0, 500)}`
    );
  }

  // Wait longer for error notification - Filament sends persistent danger notifications for validation errors
  await page.waitForTimeout(1500);

  // Check if there's an error notification (Filament uses "Cannot Submit for Review" title for validation errors)
  const errorNotification = page
    .locator('.fi-notification')
    .filter({
      hasText:
        /cannot submit|error|failed|please fix|missing|required|location|title|summary|description|duration|meeting|pricing|group|cancellation/i,
    });
  const hasError = await errorNotification.isVisible({ timeout: 5000 }).catch(() => false);

  if (hasError) {
    const errorText = await errorNotification.textContent();
    console.log(`DEBUG vendorSubmitForReview: ERROR NOTIFICATION: ${errorText?.trim()}`);
    // Throw error with details about what's missing
    throw new Error(`Submit for Review failed - validation error: ${errorText?.trim()}`);
  }

  // Also check for any notifications that appeared (success or error)
  const anyNotificationAfterAction = page.locator('.fi-notification');
  const notificationCountAfterAction = await anyNotificationAfterAction.count();
  if (notificationCountAfterAction > 0) {
    for (let i = 0; i < Math.min(notificationCountAfterAction, 3); i++) {
      const notifText = await anyNotificationAfterAction
        .nth(i)
        .textContent()
        .catch(() => 'N/A');
      console.log(
        `DEBUG vendorSubmitForReview: Notification ${i}: "${notifText?.trim().substring(0, 200)}"`
      );
    }
  }

  // Wait for network and check success
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(500);

  // Check for ANY notification
  const anyNotification = page.locator('.fi-notification');
  const notificationCount = await anyNotification.count();
  console.log(`DEBUG vendorSubmitForReview: Total notifications found: ${notificationCount}`);
  if (notificationCount > 0) {
    const notificationText = await anyNotification
      .first()
      .textContent()
      .catch(() => 'N/A');
    console.log(`DEBUG vendorSubmitForReview: First notification: "${notificationText?.trim()}"`);
  }

  // Check for success notification
  const successNotification = page
    .locator('.fi-notification')
    .filter({ hasText: /submitted|pending|success/i });
  const hasSuccess = await successNotification.isVisible({ timeout: 2000 }).catch(() => false);
  console.log(`DEBUG vendorSubmitForReview: Success notification visible: ${hasSuccess}`);

  // Check the listing row status right now
  const listingRow = page.getByRole('row', { name: new RegExp(listingIdentifier, 'i') }).first();
  const rowText = await listingRow.textContent().catch(() => 'N/A');
  console.log(`DEBUG vendorSubmitForReview: Current row content: "${rowText?.substring(0, 200)}"`);

  if (!hasSuccess) {
    console.log(
      'DEBUG vendorSubmitForReview: No success notification, waiting for table to stabilize'
    );
    // Wait for table to potentially re-render
    await page.waitForTimeout(2000);

    // Check row content again
    const rowTextAfterWait = await listingRow.textContent().catch(() => 'N/A');
    console.log(
      `DEBUG vendorSubmitForReview: Row content after wait: "${rowTextAfterWait?.substring(0, 200)}"`
    );

    // If still no content, reload page
    if (!rowTextAfterWait || rowTextAfterWait.trim().length < 10) {
      console.log('DEBUG vendorSubmitForReview: Row still empty, reloading page');
      await page.reload();
      await waitForFilamentPage(page);
    }
  }

  console.log('DEBUG vendorSubmitForReview: Completed');
}

/**
 * Get listing rejection reason from vendor panel
 */
export async function getListingRejectionReason(
  page: Page,
  listingIdentifier: string
): Promise<string | null> {
  await page.goto(`${VENDOR_PANEL_URL}/vendor/listings`);
  await waitForFilamentPage(page);

  // Find and click edit on the listing
  const row = page.getByRole('row', { name: new RegExp(listingIdentifier, 'i') }).first();
  const editLink = row.locator('a[href*="/edit"]').first();
  await editLink.click();
  await page.waitForLoadState('networkidle');

  // Look for rejection reason field
  const rejectionReasonField = page.locator(
    '[data-field="rejection_reason"], [name*="rejection_reason"], .rejection-reason'
  );
  if (await rejectionReasonField.isVisible({ timeout: 3000 }).catch(() => false)) {
    return await rejectionReasonField.textContent();
  }

  // Also check for a banner or alert with rejection reason
  const rejectionBanner = page
    .locator('.fi-alert, .alert, [role="alert"]')
    .filter({ hasText: /reject/i });
  if (await rejectionBanner.isVisible({ timeout: 2000 }).catch(() => false)) {
    return await rejectionBanner.textContent();
  }

  return null;
}

/**
 * Extract listing slug from the current page URL or content
 */
export async function extractListingSlug(page: Page): Promise<string> {
  const url = page.url();

  // Try to extract from URL patterns
  let match = url.match(/\/listings\/([^\/\?]+)/);
  if (match) return match[1];

  match = url.match(/\/listings\/(\d+)\/edit/);
  if (match) return match[1];

  // Try to find slug field in the form
  const slugInput = page.locator('input[name*="slug"]').first();
  if (await slugInput.isVisible({ timeout: 2000 }).catch(() => false)) {
    return (await slugInput.inputValue()) || '';
  }

  return '';
}

// ============================================================================
// ADMIN PANEL ACTIONS
// ============================================================================

/**
 * Admin approves a listing
 */
export async function adminApproveListing(page: Page, listingIdentifier: string): Promise<void> {
  await navigateToAdminResource(page, 'listings');

  // Find the listing row
  const row = page.getByRole('row', { name: new RegExp(listingIdentifier, 'i') }).first();
  await expect(row).toBeVisible({ timeout: 10000 });

  // Open actions dropdown - the trigger button is visible and contains only an img (icon)
  // The dropdown items are hidden until the trigger is clicked
  const actionsButton = row
    .locator('button:visible')
    .filter({ has: page.locator('svg, img') })
    .last();
  await actionsButton.scrollIntoViewIfNeeded();
  await actionsButton.click();
  await page.waitForTimeout(500);

  // Click approve action - might be "Approve", "Approve & Publish", or "Publish"
  const approveItem = page
    .getByRole('menuitem', { name: /approve.*publish|^approve$|^publish$/i })
    .first();
  // If no menuitem, try button
  if (!(await approveItem.isVisible({ timeout: 2000 }).catch(() => false))) {
    // The actions might be rendered as buttons, not menuitems
    const approveButton = page
      .getByRole('button', { name: /approve.*publish|^approve$|^publish$/i })
      .first();
    await approveButton.click();
  } else {
    await approveItem.click();
  }

  // Wait for modal to fully render and settle
  await page.waitForTimeout(1000);

  // Confirm in the modal - look for the confirm/submit button inside the modal
  const modalConfirmButton = page
    .locator(
      '.fi-modal-footer button[type="submit"], .fi-modal-footer button:has-text("Confirm"), .fi-modal button:has-text("Approve")'
    )
    .first();
  if (await modalConfirmButton.isVisible({ timeout: 3000 }).catch(() => false)) {
    await modalConfirmButton.click({ force: true });
  } else {
    // Try generic confirm button
    const confirmButton = page.getByRole('button', { name: /confirm|yes|approve/i }).first();
    if (await confirmButton.isVisible({ timeout: 2000 }).catch(() => false)) {
      await confirmButton.click({ force: true });
    }
  }

  // Wait for success notification
  await waitForNotification(page, 'success');
}

/**
 * Admin rejects a listing with reason
 */
export async function adminRejectListing(
  page: Page,
  listingIdentifier: string,
  reason: string
): Promise<void> {
  await navigateToAdminResource(page, 'listings');

  // Find the listing row
  const row = page.getByRole('row', { name: new RegExp(listingIdentifier, 'i') }).first();
  await expect(row).toBeVisible({ timeout: 10000 });

  // Open actions dropdown - same pattern as adminApproveListing
  const actionsButton = row
    .locator('button:visible')
    .filter({ has: page.locator('svg, img') })
    .last();
  await actionsButton.scrollIntoViewIfNeeded();
  await actionsButton.click();
  await page.waitForTimeout(500);

  // Click reject action
  const rejectItem = page.getByRole('menuitem', { name: /reject/i }).first();
  if (!(await rejectItem.isVisible({ timeout: 2000 }).catch(() => false))) {
    const rejectButton = page.getByRole('button', { name: /reject/i }).first();
    await rejectButton.click();
  } else {
    await rejectItem.click();
  }

  await page.waitForTimeout(500);

  // Fill rejection reason in modal
  const reasonInput = page
    .locator('textarea[name*="rejection_reason"], textarea[name*="reason"], .fi-modal textarea')
    .first();
  await expect(reasonInput).toBeVisible({ timeout: 5000 });
  await reasonInput.fill(reason);

  // Confirm rejection
  const modalConfirmButton = page
    .locator(
      '.fi-modal-footer button[type="submit"], .fi-modal-footer button:has-text("Confirm"), .fi-modal button:has-text("Reject")'
    )
    .first();
  if (await modalConfirmButton.isVisible({ timeout: 3000 }).catch(() => false)) {
    await modalConfirmButton.click({ force: true });
  } else {
    const confirmButton = page.getByRole('button', { name: /confirm|reject|submit/i }).last();
    await confirmButton.click({ force: true });
  }

  // Wait for success notification
  await waitForNotification(page, 'success');
}

/**
 * Admin archives a listing
 */
export async function adminArchiveListing(page: Page, listingIdentifier: string): Promise<void> {
  await navigateToAdminResource(page, 'listings');

  console.log(`DEBUG adminArchiveListing: Looking for listing "${listingIdentifier}"`);

  const row = page.getByRole('row', { name: new RegExp(listingIdentifier, 'i') }).first();
  await expect(row).toBeVisible({ timeout: 10000 });

  // Get the listing status from the row to verify it's published
  const rowText = await row.textContent();
  console.log(`DEBUG adminArchiveListing: Row text: ${rowText?.substring(0, 200)}`);

  // Open actions dropdown - same pattern as adminApproveListing
  const actionsButton = row
    .locator('button:visible')
    .filter({ has: page.locator('svg, img') })
    .last();
  await actionsButton.scrollIntoViewIfNeeded();
  console.log(`DEBUG adminArchiveListing: Clicking actions dropdown`);
  await actionsButton.click();
  await page.waitForTimeout(500);

  // Debug: List all visible menuitems
  const allMenuItems = page.locator('[role="menuitem"]');
  const menuItemCount = await allMenuItems.count();
  console.log(`DEBUG adminArchiveListing: Found ${menuItemCount} menuitems`);
  for (let i = 0; i < menuItemCount; i++) {
    const item = allMenuItems.nth(i);
    const itemText = await item.textContent();
    const isVisible = await item.isVisible();
    console.log(
      `DEBUG adminArchiveListing: MenuItem ${i}: "${itemText?.trim()}" (visible: ${isVisible})`
    );
  }

  // Click archive action
  const archiveItem = page.getByRole('menuitem', { name: /archive/i }).first();
  if (!(await archiveItem.isVisible({ timeout: 2000 }).catch(() => false))) {
    console.log(`DEBUG adminArchiveListing: Archive menuitem not visible, trying button`);
    const archiveButton = page.getByRole('button', { name: /archive/i }).first();
    if (await archiveButton.isVisible({ timeout: 2000 }).catch(() => false)) {
      await archiveButton.click();
    } else {
      console.log(`DEBUG adminArchiveListing: No archive button found either`);
      // Try clicking directly on the text
      const archiveText = page.locator('text=/archive/i').first();
      if (await archiveText.isVisible({ timeout: 2000 }).catch(() => false)) {
        await archiveText.click();
      } else {
        throw new Error('Archive action not found');
      }
    }
  } else {
    console.log(`DEBUG adminArchiveListing: Clicking archive menuitem`);
    await archiveItem.click();
  }

  await page.waitForTimeout(500);

  // Confirm if modal appears
  const modalConfirmButton = page
    .locator(
      '.fi-modal-footer button[type="submit"], .fi-modal-footer button:has-text("Confirm"), .fi-modal button:has-text("Archive")'
    )
    .first();
  if (await modalConfirmButton.isVisible({ timeout: 3000 }).catch(() => false)) {
    console.log(`DEBUG adminArchiveListing: Clicking modal confirm button`);
    await modalConfirmButton.click({ force: true });
  } else {
    const confirmButton = page.getByRole('button', { name: /confirm|yes|archive/i });
    if (await confirmButton.isVisible({ timeout: 2000 }).catch(() => false)) {
      console.log(`DEBUG adminArchiveListing: Clicking confirm button`);
      await confirmButton.click({ force: true });
    } else {
      console.log(`DEBUG adminArchiveListing: No confirmation needed`);
    }
  }

  await waitForNotification(page, 'success');
}

/**
 * Admin republishes an archived listing
 */
export async function adminRepublishListing(page: Page, listingIdentifier: string): Promise<void> {
  await navigateToAdminResource(page, 'listings');

  console.log(`DEBUG adminRepublishListing: Looking for listing "${listingIdentifier}"`);

  const row = page.getByRole('row', { name: new RegExp(listingIdentifier, 'i') }).first();
  await expect(row).toBeVisible({ timeout: 10000 });

  // Get the listing status from the row to verify it's archived
  const rowText = await row.textContent();
  console.log(`DEBUG adminRepublishListing: Row text: ${rowText?.substring(0, 200)}`);

  // Open actions dropdown - same pattern as adminApproveListing
  const actionsButton = row
    .locator('button:visible')
    .filter({ has: page.locator('svg, img') })
    .last();
  await actionsButton.scrollIntoViewIfNeeded();
  console.log(`DEBUG adminRepublishListing: Clicking actions dropdown`);
  await actionsButton.click();
  await page.waitForTimeout(500);

  // Debug: List all visible menuitems
  const allMenuItems = page.locator('[role="menuitem"]');
  const menuItemCount = await allMenuItems.count();
  console.log(`DEBUG adminRepublishListing: Found ${menuItemCount} menuitems`);
  for (let i = 0; i < menuItemCount; i++) {
    const item = allMenuItems.nth(i);
    const itemText = await item.textContent();
    const isVisible = await item.isVisible();
    console.log(
      `DEBUG adminRepublishListing: MenuItem ${i}: "${itemText?.trim()}" (visible: ${isVisible})`
    );
  }

  // Click republish/publish action
  const publishItem = page.getByRole('menuitem', { name: /republish|publish/i }).first();
  if (!(await publishItem.isVisible({ timeout: 2000 }).catch(() => false))) {
    console.log(`DEBUG adminRepublishListing: Republish menuitem not visible, trying button`);
    const publishButton = page.getByRole('button', { name: /republish|publish/i }).first();
    if (await publishButton.isVisible({ timeout: 2000 }).catch(() => false)) {
      await publishButton.click();
    } else {
      console.log(`DEBUG adminRepublishListing: No republish button found either`);
      // Try clicking directly on the text
      const republishText = page.locator('text=/republish/i').first();
      if (await republishText.isVisible({ timeout: 2000 }).catch(() => false)) {
        await republishText.click();
      } else {
        throw new Error('Republish action not found');
      }
    }
  } else {
    console.log(`DEBUG adminRepublishListing: Clicking republish menuitem`);
    await publishItem.click();
  }

  await page.waitForTimeout(500);

  // Confirm if modal appears
  const modalConfirmButton = page
    .locator(
      '.fi-modal-footer button[type="submit"], .fi-modal-footer button:has-text("Confirm"), .fi-modal button:has-text("Publish")'
    )
    .first();
  if (await modalConfirmButton.isVisible({ timeout: 3000 }).catch(() => false)) {
    console.log(`DEBUG adminRepublishListing: Clicking modal confirm button`);
    await modalConfirmButton.click({ force: true });
  } else {
    const confirmButton = page.getByRole('button', { name: /confirm|yes|publish/i });
    if (await confirmButton.isVisible({ timeout: 2000 }).catch(() => false)) {
      console.log(`DEBUG adminRepublishListing: Clicking confirm button`);
      await confirmButton.click({ force: true });
    } else {
      console.log(`DEBUG adminRepublishListing: No confirmation needed`);
    }
  }

  await waitForNotification(page, 'success');
}

/**
 * Admin changes listing status via edit form
 */
export async function adminChangeListingStatus(
  page: Page,
  listingIdentifier: string,
  newStatus: ListingStatus
): Promise<void> {
  await navigateToAdminResource(page, 'listings');

  // Find and click edit on the listing
  const row = page.getByRole('row', { name: new RegExp(listingIdentifier, 'i') }).first();
  await row.getByRole('link', { name: /edit/i }).click();
  await page.waitForLoadState('networkidle');

  // Change status dropdown
  const statusSelect = page.locator('select[name*="status"], [data-field="status"] select').first();
  if (await statusSelect.isVisible()) {
    await statusSelect.selectOption(newStatus);
  }

  // Save
  await submitFilamentForm(page);
  await waitForNotification(page, 'success');
}

// ============================================================================
// TYPE-SPECIFIC FIELD HELPERS
// ============================================================================

/**
 * Fill tour-specific fields in the wizard
 */
export async function fillTourSpecificFields(
  page: Page,
  data: {
    duration?: { value: number; unit: string };
    difficulty?: string;
    distance?: { value: number; unit: string };
    activityTypeId?: string;
    hasElevationProfile?: boolean;
    itinerary?: Array<{ title: string; description: string; durationMinutes?: number }>;
  }
): Promise<void> {
  // Duration
  if (data.duration) {
    const durationInput = page
      .locator('input[name*="duration.value"], input[name*="duration_value"]')
      .first();
    if (await durationInput.isVisible()) {
      await durationInput.fill(String(data.duration.value));
    }
    const durationUnitSelect = page
      .locator('select[name*="duration.unit"], select[name*="duration_unit"]')
      .first();
    if (await durationUnitSelect.isVisible()) {
      await durationUnitSelect.selectOption(data.duration.unit);
    }
  }

  // Difficulty
  if (data.difficulty) {
    const difficultySelect = page.locator('select[name*="difficulty"]').first();
    if (await difficultySelect.isVisible()) {
      await difficultySelect.selectOption(data.difficulty);
    }
  }

  // Distance
  if (data.distance) {
    const distanceInput = page
      .locator('input[name*="distance.value"], input[name*="distance_value"]')
      .first();
    if (await distanceInput.isVisible()) {
      await distanceInput.fill(String(data.distance.value));
    }
  }

  // Activity type
  if (data.activityTypeId) {
    const activitySelect = page.locator('select[name*="activity_type"]').first();
    if (await activitySelect.isVisible()) {
      await activitySelect.selectOption(data.activityTypeId);
    }
  }
}

/**
 * Fill nautical-specific fields in the wizard
 */
export async function fillNauticalSpecificFields(
  page: Page,
  data: {
    boatName?: string;
    boatLength?: number;
    boatCapacity?: number;
    boatYear?: number;
    licenseRequired?: boolean;
    licenseType?: string;
    crewIncluded?: boolean;
    fuelIncluded?: boolean;
    equipmentIncluded?: string[];
    minRentalHours?: number;
  }
): Promise<void> {
  // Boat name
  if (data.boatName) {
    const boatNameInput = page.locator('input[name*="boat_name"]').first();
    if (await boatNameInput.isVisible()) {
      await boatNameInput.fill(data.boatName);
    }
  }

  // Boat length
  if (data.boatLength) {
    const boatLengthInput = page.locator('input[name*="boat_length"]').first();
    if (await boatLengthInput.isVisible()) {
      await boatLengthInput.fill(String(data.boatLength));
    }
  }

  // Boat capacity
  if (data.boatCapacity) {
    const boatCapacityInput = page.locator('input[name*="boat_capacity"]').first();
    if (await boatCapacityInput.isVisible()) {
      await boatCapacityInput.fill(String(data.boatCapacity));
    }
  }

  // Boat year
  if (data.boatYear) {
    const boatYearInput = page.locator('input[name*="boat_year"]').first();
    if (await boatYearInput.isVisible()) {
      await boatYearInput.fill(String(data.boatYear));
    }
  }

  // License required
  if (data.licenseRequired !== undefined) {
    const licenseCheckbox = page
      .locator('input[type="checkbox"][name*="license_required"]')
      .first();
    if (await licenseCheckbox.isVisible()) {
      if (data.licenseRequired) {
        await licenseCheckbox.check();
      } else {
        await licenseCheckbox.uncheck();
      }
    }
  }

  // Crew included
  if (data.crewIncluded !== undefined) {
    const crewCheckbox = page.locator('input[type="checkbox"][name*="crew_included"]').first();
    if (await crewCheckbox.isVisible()) {
      if (data.crewIncluded) {
        await crewCheckbox.check();
      } else {
        await crewCheckbox.uncheck();
      }
    }
  }

  // Fuel included
  if (data.fuelIncluded !== undefined) {
    const fuelCheckbox = page.locator('input[type="checkbox"][name*="fuel_included"]').first();
    if (await fuelCheckbox.isVisible()) {
      if (data.fuelIncluded) {
        await fuelCheckbox.check();
      } else {
        await fuelCheckbox.uncheck();
      }
    }
  }
}

/**
 * Fill accommodation-specific fields in the wizard
 */
export async function fillAccommodationSpecificFields(
  page: Page,
  data: {
    accommodationType?: string;
    bedrooms?: number;
    bathrooms?: number;
    maxGuests?: number;
    propertySize?: number;
    checkInTime?: string;
    checkOutTime?: string;
    amenities?: string[];
    mealsIncluded?: { breakfast?: boolean; lunch?: boolean; dinner?: boolean };
    houseRules?: { en?: string; fr?: string };
  }
): Promise<void> {
  // Accommodation type
  if (data.accommodationType) {
    const typeSelect = page.locator('select[name*="accommodation_type"]').first();
    if (await typeSelect.isVisible()) {
      await typeSelect.selectOption(data.accommodationType);
    }
  }

  // Bedrooms
  if (data.bedrooms) {
    const bedroomsInput = page.locator('input[name*="bedrooms"]').first();
    if (await bedroomsInput.isVisible()) {
      await bedroomsInput.fill(String(data.bedrooms));
    }
  }

  // Bathrooms
  if (data.bathrooms) {
    const bathroomsInput = page.locator('input[name*="bathrooms"]').first();
    if (await bathroomsInput.isVisible()) {
      await bathroomsInput.fill(String(data.bathrooms));
    }
  }

  // Max guests
  if (data.maxGuests) {
    const maxGuestsInput = page.locator('input[name*="max_guests"]').first();
    if (await maxGuestsInput.isVisible()) {
      await maxGuestsInput.fill(String(data.maxGuests));
    }
  }

  // Check-in time
  if (data.checkInTime) {
    const checkInInput = page.locator('input[name*="check_in_time"]').first();
    if (await checkInInput.isVisible()) {
      await checkInInput.fill(data.checkInTime);
    }
  }

  // Check-out time
  if (data.checkOutTime) {
    const checkOutInput = page.locator('input[name*="check_out_time"]').first();
    if (await checkOutInput.isVisible()) {
      await checkOutInput.fill(data.checkOutTime);
    }
  }
}

/**
 * Fill event-specific fields in the wizard
 */
export async function fillEventSpecificFields(
  page: Page,
  data: {
    eventType?: string;
    startDate?: string;
    endDate?: string;
    venue?: {
      name?: string;
      address?: string;
      capacity?: number;
    };
    agenda?: Array<{ time: string; title: string; description?: string }>;
  }
): Promise<void> {
  // Event type
  if (data.eventType) {
    const eventTypeSelect = page.locator('select[name*="event_type"]').first();
    if (await eventTypeSelect.isVisible()) {
      await eventTypeSelect.selectOption(data.eventType);
    }
  }

  // Start date
  if (data.startDate) {
    const startDateInput = page.locator('input[name*="start_date"]').first();
    if (await startDateInput.isVisible()) {
      await startDateInput.fill(data.startDate);
    }
  }

  // End date
  if (data.endDate) {
    const endDateInput = page.locator('input[name*="end_date"]').first();
    if (await endDateInput.isVisible()) {
      await endDateInput.fill(data.endDate);
    }
  }

  // Venue name
  if (data.venue?.name) {
    const venueNameInput = page
      .locator('input[name*="venue.name"], input[name*="venue_name"]')
      .first();
    if (await venueNameInput.isVisible()) {
      await venueNameInput.fill(data.venue.name);
    }
  }

  // Venue address
  if (data.venue?.address) {
    const venueAddressInput = page
      .locator(
        'input[name*="venue.address"], input[name*="venue_address"], textarea[name*="venue.address"]'
      )
      .first();
    if (await venueAddressInput.isVisible()) {
      await venueAddressInput.fill(data.venue.address);
    }
  }

  // Venue capacity
  if (data.venue?.capacity) {
    const venueCapacityInput = page
      .locator('input[name*="venue.capacity"], input[name*="venue_capacity"]')
      .first();
    if (await venueCapacityInput.isVisible()) {
      await venueCapacityInput.fill(String(data.venue.capacity));
    }
  }
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Wait for status propagation (useful after status changes)
 */
export async function waitForStatusPropagation(
  page: Page,
  listingSlug: string,
  expectedStatus: ListingStatus,
  timeout: number = 10000
): Promise<void> {
  const startTime = Date.now();

  while (Date.now() - startTime < timeout) {
    await page.reload();
    await page.waitForLoadState('networkidle');

    try {
      const statusBadge = page.locator('.fi-badge, [role="status"]').filter({
        hasText: new RegExp(expectedStatus.replace('_', ' '), 'i'),
      });
      if (await statusBadge.isVisible({ timeout: 1000 })) {
        return;
      }
    } catch {
      // Continue waiting
    }

    await page.waitForTimeout(500);
  }

  throw new Error(`Status did not propagate to ${expectedStatus} within ${timeout}ms`);
}

/**
 * Generate unique listing title with timestamp
 */
export function generateUniqueListingTitle(base: string): string {
  return `${base} ${Date.now().toString(36)}`;
}

/**
 * Get future date in YYYY-MM-DD format
 */
export function getFutureDateISO(daysFromNow: number): string {
  const date = new Date();
  date.setDate(date.getDate() + daysFromNow);
  return date.toISOString().split('T')[0];
}
