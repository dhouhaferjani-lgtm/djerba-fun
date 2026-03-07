/**
 * Vendor Panel E2E Test Helpers
 * Helper functions for testing the Filament vendor panel at /vendor/*
 */

import { Page, APIRequestContext, expect } from '@playwright/test';

// Filament vendor panel is served from Laravel, not Next.js
const VENDOR_PANEL_URL = process.env.LARAVEL_URL || 'http://localhost:8000';
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

export interface VendorUser {
  id: string;
  email: string;
  token: string;
  role: string;
  vendorProfileId?: string;
}

export interface VendorListing {
  id: string;
  slug: string;
  title: string;
  status: string;
  serviceType: string;
}

export interface VendorBooking {
  id: string;
  bookingNumber: string;
  status: string;
  totalAmount: number;
  participants: VendorParticipant[];
}

export interface VendorParticipant {
  id: string;
  name: string;
  voucherCode: string;
  checkedInAt: string | null;
}

export interface VendorExtra {
  id: string;
  name: string;
  pricingType: string;
  basePrice: number;
}

export interface AvailabilityRule {
  id: string;
  listingId: string;
  ruleType: string;
  isActive: boolean;
}

/**
 * Login to vendor panel via UI
 * Uses Playwright's recommended getByLabel selectors for Filament 3 forms
 */
export async function loginVendorUI(page: Page, email: string, password: string): Promise<void> {
  await page.goto(`${VENDOR_PANEL_URL}/vendor/login`);
  await page.waitForLoadState('networkidle');

  // Wait for login form to be visible (Filament 3 uses label-based inputs)
  await page.waitForSelector('text=Sign in', { timeout: 15000 });

  // Fill login credentials using Playwright's label-based selectors
  await page.getByLabel('Email address').fill(email);
  await page.getByLabel('Password').fill(password);

  // Click login button
  await page.getByRole('button', { name: 'Sign in' }).click();

  // Wait for redirect to dashboard (with timeout)
  try {
    await page.waitForURL(/\/vendor(?!\/login)/, { timeout: 15000 });
  } catch {
    // If redirect fails, check if we're still on login page with error
    const hasError = await page.locator('.text-danger, .text-red-600, [role="alert"]').isVisible();
    if (hasError) {
      throw new Error('Vendor login failed - check credentials or vendor user setup');
    }
  }

  // Verify we're logged in by checking for Filament panel elements
  await expect(page.locator('body')).not.toContainText('Sign in', { timeout: 10000 });
}

/**
 * Create a vendor user via API
 */
export async function createVendorUser(
  request: APIRequestContext,
  userData: {
    email: string;
    password: string;
    firstName?: string;
    lastName?: string;
    companyName?: string;
  }
): Promise<VendorUser> {
  const response = await request.post(`${API_BASE_URL}/auth/register`, {
    data: {
      email: userData.email,
      password: userData.password,
      password_confirmation: userData.password,
      role: 'vendor',
      first_name: userData.firstName || 'Test',
      last_name: userData.lastName || 'Vendor',
      display_name:
        userData.companyName || `${userData.firstName || 'Test'} ${userData.lastName || 'Vendor'}`,
    },
  });

  const data = await response.json();
  return {
    id: data.user?.id || data.data?.user?.id,
    email: data.user?.email || data.data?.user?.email,
    token: data.token || data.data?.token,
    role: 'vendor',
    vendorProfileId: data.user?.vendor_profile_id || data.data?.user?.vendor_profile_id,
  };
}

/**
 * Login vendor via API (for setting up test data)
 */
export async function loginVendorAPI(
  request: APIRequestContext,
  email: string,
  password: string
): Promise<VendorUser> {
  const response = await request.post(`${API_BASE_URL}/auth/login`, {
    data: { email, password },
  });

  const data = await response.json();
  return {
    id: data.user?.id || data.data?.user?.id,
    email: data.user?.email || data.data?.user?.email,
    token: data.token || data.data?.token,
    role: data.user?.role || data.data?.user?.role,
    vendorProfileId: data.user?.vendor_profile_id,
  };
}

/**
 * Navigate to vendor panel section
 */
export async function navigateToVendorSection(
  page: Page,
  section:
    | 'listings'
    | 'availability-rules'
    | 'extras'
    | 'bookings'
    | 'reviews'
    | 'email-logs'
    | 'check-in-scanner'
): Promise<void> {
  await page.goto(`${VENDOR_PANEL_URL}/vendor/${section}`);
  await page.waitForLoadState('networkidle');
}

/**
 * Create a listing via vendor panel wizard
 */
export async function createListingViaWizard(
  page: Page,
  listingData: {
    serviceType: 'tour' | 'nautical' | 'accommodation' | 'event';
    titleEn: string;
    titleFr: string;
    summaryEn?: string;
    summaryFr?: string;
    descriptionEn?: string;
    descriptionFr?: string;
    priceTnd?: number;
    priceEur?: number;
  }
): Promise<void> {
  // Navigate to create listing
  await page.goto(`${VENDOR_PANEL_URL}/vendor/listings/create`);
  await page.waitForLoadState('networkidle');

  // Step 1: Basic Info - Service Type
  const serviceTypeSelect = page.locator(
    '[data-field="service_type"] select, select[name="data.service_type"]'
  );
  if (await serviceTypeSelect.isVisible()) {
    await serviceTypeSelect.selectOption(listingData.serviceType);
  }

  // Title fields
  await page.fill(
    'input[name*="title"][name*="en"], [data-field="title.en"] input',
    listingData.titleEn
  );
  await page.fill(
    'input[name*="title"][name*="fr"], [data-field="title.fr"] input',
    listingData.titleFr
  );

  // Summary fields (if visible)
  if (listingData.summaryEn) {
    const summaryEnInput = page.locator(
      'textarea[name*="summary"][name*="en"], [data-field="summary.en"] textarea'
    );
    if (await summaryEnInput.isVisible()) {
      await summaryEnInput.fill(listingData.summaryEn);
    }
  }

  // Click Next to proceed through wizard steps
  const nextButton = page.locator('button:has-text("Next"), button[wire\\:click*="nextStep"]');
  if (await nextButton.isVisible()) {
    await nextButton.click();
    await page.waitForTimeout(500);
  }
}

/**
 * Fill Filament form field
 */
export async function fillFilamentField(
  page: Page,
  fieldName: string,
  value: string | number,
  fieldType: 'text' | 'textarea' | 'select' | 'checkbox' | 'number' = 'text'
): Promise<void> {
  const fieldSelector = `[data-field="${fieldName}"], [name="data.${fieldName}"], [name="${fieldName}"]`;

  switch (fieldType) {
    case 'text':
    case 'number':
      await page.fill(`${fieldSelector} input, input${fieldSelector}`, String(value));
      break;
    case 'textarea':
      await page.fill(`${fieldSelector} textarea, textarea${fieldSelector}`, String(value));
      break;
    case 'select':
      await page.selectOption(`${fieldSelector} select, select${fieldSelector}`, String(value));
      break;
    case 'checkbox':
      const checkbox = page.locator(
        `${fieldSelector} input[type="checkbox"], input[type="checkbox"]${fieldSelector}`
      );
      if (value) {
        await checkbox.check();
      } else {
        await checkbox.uncheck();
      }
      break;
  }
}

/**
 * Click Filament table action
 */
export async function clickTableAction(
  page: Page,
  rowIdentifier: string,
  actionName: string
): Promise<void> {
  // Find the row containing the identifier
  const row = page.locator(`table tbody tr:has-text("${rowIdentifier}")`).first();

  // Click actions dropdown if exists
  const actionsButton = row.locator('button[data-dropdown-trigger], button:has-text("Actions")');
  if (await actionsButton.isVisible()) {
    await actionsButton.click();
    await page.waitForTimeout(300);
  }

  // Click the specific action
  await page.click(`[data-action="${actionName}"], button:has-text("${actionName}")`);
}

/**
 * Get Filament table row count
 */
export async function getTableRowCount(page: Page): Promise<number> {
  await page.waitForSelector('table tbody', { timeout: 5000 });
  const rows = page.locator('table tbody tr');
  return await rows.count();
}

/**
 * Check if Filament notification appears
 */
export async function expectNotification(
  page: Page,
  type: 'success' | 'warning' | 'danger' | 'info',
  messageContains?: string
): Promise<void> {
  const notificationSelector = `.fi-notification, [data-notification], .notification`;
  await expect(page.locator(notificationSelector)).toBeVisible({ timeout: 5000 });

  if (messageContains) {
    await expect(page.locator(notificationSelector)).toContainText(messageContains);
  }
}

/**
 * Wait for Filament page to load
 */
export async function waitForFilamentPage(page: Page): Promise<void> {
  await page.waitForLoadState('networkidle');
  // Wait for Filament's Livewire to be ready
  await page
    .waitForFunction(
      () => {
        return typeof (window as any).Livewire !== 'undefined';
      },
      { timeout: 10000 }
    )
    .catch(() => {
      // Livewire might not be available, continue anyway
    });
}

/**
 * Submit Filament form
 */
export async function submitFilamentForm(page: Page): Promise<void> {
  // Filament forms typically have a submit button with type="submit" or specific text
  // Prefer visible buttons and specific button text to avoid dropdown items

  // First try "Save changes" button which is common in edit forms
  const saveChangesButton = page.getByRole('button', { name: 'Save changes' });
  if (await saveChangesButton.isVisible({ timeout: 2000 }).catch(() => false)) {
    await saveChangesButton.click();
    await page.waitForLoadState('networkidle');
    return;
  }

  // Try "Create" button which is common in create forms
  const createButton = page.getByRole('button', { name: 'Create' });
  if (await createButton.isVisible({ timeout: 2000 }).catch(() => false)) {
    await createButton.click();
    await page.waitForLoadState('networkidle');
    return;
  }

  // Try "Save" button
  const saveButton = page.getByRole('button', { name: 'Save' });
  if (await saveButton.isVisible({ timeout: 2000 }).catch(() => false)) {
    await saveButton.click();
    await page.waitForLoadState('networkidle');
    return;
  }

  // Fallback to any visible submit button
  const submitButton = page.locator('button[type="submit"]:visible').first();
  await submitButton.click();
  await page.waitForLoadState('networkidle');
}

/**
 * Get vendor panel URL
 */
export function getVendorPanelUrl(path: string = ''): string {
  return `${VENDOR_PANEL_URL}/vendor${path}`;
}

/**
 * Create test booking for vendor via API
 */
export async function createTestBookingForVendor(
  request: APIRequestContext,
  vendorToken: string,
  listingId: string,
  bookingData?: Partial<{
    status: string;
    totalAmount: number;
    participantCount: number;
  }>
): Promise<VendorBooking> {
  // This would call an internal API or test seeder endpoint
  const response = await request.post(`${API_BASE_URL}/test/create-booking`, {
    headers: {
      Authorization: `Bearer ${vendorToken}`,
    },
    data: {
      listing_id: listingId,
      status: bookingData?.status || 'confirmed',
      total_amount: bookingData?.totalAmount || 150,
      participant_count: bookingData?.participantCount || 2,
    },
  });

  const data = await response.json();
  return data.booking || data.data?.booking;
}

/**
 * Create an availability rule for a listing
 */
export async function createAvailabilityRule(
  page: Page,
  data: {
    listingId: string;
    ruleType: 'weekly' | 'daily' | 'specific_dates' | 'blocked_dates';
    daysOfWeek?: number[];
    startTime?: string;
    endTime?: string;
    startDate?: Date;
    endDate?: Date;
    capacity?: number;
    isActive?: boolean;
  }
): Promise<string> {
  await page.goto(`${VENDOR_PANEL_URL}/vendor/availability-rules/create`);
  await page.waitForLoadState('networkidle');

  // Select listing
  const listingSelect = page.locator('select[name*="listing_id"]').first();
  if (await listingSelect.isVisible()) {
    await listingSelect.selectOption(data.listingId);
  } else {
    await page.click('[data-field="listing_id"] button');
    await page.click(`li[data-value="${data.listingId}"]`);
  }

  // Select rule type
  const ruleTypeSelect = page.locator('select[name*="rule_type"]').first();
  if (await ruleTypeSelect.isVisible()) {
    await ruleTypeSelect.selectOption(data.ruleType);
  }

  // Set days of week for weekly rules
  if (data.daysOfWeek && data.daysOfWeek.length > 0) {
    for (const day of data.daysOfWeek) {
      const dayCheckbox = page.locator(`input[name*="days_of_week"][value="${day}"]`).first();
      if (!(await dayCheckbox.isChecked())) {
        await dayCheckbox.click();
      }
    }
  }

  // Set times
  if (data.startTime) {
    await page.fill('input[name*="start_time"]', data.startTime);
  }
  if (data.endTime) {
    await page.fill('input[name*="end_time"]', data.endTime);
  }

  // Set dates
  if (data.startDate) {
    await page.fill('input[name*="start_date"]', data.startDate.toISOString().slice(0, 10));
  }
  if (data.endDate) {
    await page.fill('input[name*="end_date"]', data.endDate.toISOString().slice(0, 10));
  }

  // Set capacity
  if (data.capacity !== undefined) {
    await page.fill('input[name*="capacity"]', data.capacity.toString());
  }

  await submitFilamentForm(page);

  // Get created rule ID from URL
  await page.waitForURL(/\/availability-rules/);
  const url = page.url();
  const match = url.match(/\/availability-rules\/(\d+)/);
  return match ? match[1] : '';
}

/**
 * Mark a booking as paid
 */
export async function markBookingAsPaid(
  page: Page,
  bookingId: string,
  notes?: string
): Promise<void> {
  await page.goto(`${VENDOR_PANEL_URL}/vendor/bookings/${bookingId}`);
  await page.waitForLoadState('networkidle');

  const markPaidButton = page
    .locator('button:has-text("Mark as Paid"), button:has-text("Confirm Payment")')
    .first();
  await markPaidButton.click();

  const notesInput = page.locator('textarea[name*="notes"]').first();
  if (await notesInput.isVisible({ timeout: 2000 }).catch(() => false)) {
    await notesInput.fill(notes || 'Payment confirmed via test');
  }

  const confirmButton = page.locator('button:has-text("Confirm"), button:has-text("Yes")').first();
  if (await confirmButton.isVisible({ timeout: 2000 }).catch(() => false)) {
    await confirmButton.click();
  }

  await page.waitForTimeout(1000);
}

/**
 * Approve a pending review
 */
export async function approveReview(page: Page, reviewId: string): Promise<void> {
  await page.goto(`${VENDOR_PANEL_URL}/vendor/reviews/${reviewId}`);
  await page.waitForLoadState('networkidle');

  const approveButton = page.locator('button:has-text("Approve")').first();
  await approveButton.click();

  const confirmButton = page.locator('button:has-text("Confirm"), button:has-text("Yes")').first();
  if (await confirmButton.isVisible({ timeout: 2000 }).catch(() => false)) {
    await confirmButton.click();
  }

  await page.waitForTimeout(1000);
}

/**
 * Reject a review with reason
 */
export async function rejectReview(page: Page, reviewId: string, reason: string): Promise<void> {
  await page.goto(`${VENDOR_PANEL_URL}/vendor/reviews/${reviewId}`);
  await page.waitForLoadState('networkidle');

  const rejectButton = page.locator('button:has-text("Reject")').first();
  await rejectButton.click();

  const reasonInput = page.locator('textarea[name*="reason"]').first();
  await reasonInput.fill(reason);

  const confirmButton = page
    .locator('button:has-text("Confirm"), button:has-text("Reject")')
    .first();
  await confirmButton.click();

  await page.waitForTimeout(1000);
}

/**
 * Reply to an approved review
 */
export async function replyToReview(page: Page, reviewId: string, reply: string): Promise<void> {
  await page.goto(`${VENDOR_PANEL_URL}/vendor/reviews/${reviewId}`);
  await page.waitForLoadState('networkidle');

  const replyButton = page.locator('button:has-text("Reply")').first();
  await replyButton.click();

  const replyInput = page.locator('textarea[name*="reply"], textarea[name*="response"]').first();
  await replyInput.fill(reply);

  const saveButton = page.locator('button:has-text("Save"), button:has-text("Submit")').first();
  await saveButton.click();

  await page.waitForTimeout(1000);
}

/**
 * Get vendor's first listing ID
 */
export async function getVendorFirstListingId(page: Page): Promise<string> {
  await navigateToVendorSection(page, 'listings');

  const firstEditLink = page.locator('table tbody tr:first-child a[href*="/edit"]').first();
  const href = await firstEditLink.getAttribute('href');

  if (href) {
    const match = href.match(/\/listings\/(\d+)/);
    return match ? match[1] : '';
  }
  return '';
}

/**
 * Get first pending review ID
 */
export async function getFirstPendingReviewId(page: Page): Promise<string> {
  await page.goto(`${VENDOR_PANEL_URL}/vendor/reviews?tableFilters[status][value]=pending`);
  await page.waitForLoadState('networkidle');

  const firstRow = page.locator('table tbody tr:first-child').first();
  if (!(await firstRow.isVisible({ timeout: 3000 }).catch(() => false))) {
    return '';
  }

  const viewLink = page.locator('table tbody tr:first-child a[href*="/reviews/"]').first();
  const href = await viewLink.getAttribute('href');

  if (href) {
    const match = href.match(/\/reviews\/(\d+)/);
    return match ? match[1] : '';
  }
  return '';
}

/**
 * Seeded vendor credentials (from VendorSeeder)
 * Note: Updated to match djerba-fun branding
 */
export const seededVendor = {
  email: 'vendor@djerba.fun',
  password: 'password',
};

/**
 * Seeded admin credentials
 * Note: Updated to match djerba-fun branding
 */
export const seededAdmin = {
  email: 'admin@djerba.fun',
  password: 'password',
};

// ============================================================
// Filament 3 Form Helpers (using Playwright's recommended selectors)
// ============================================================

/**
 * Select service type in Filament 3 form
 * The form uses a native HTML select element styled as combobox
 * DOM: combobox "Type*" with options "Tour", "Nautical", etc.
 */
export async function selectServiceType(
  page: Page,
  serviceType: 'tour' | 'nautical' | 'accommodation' | 'event'
): Promise<void> {
  // Map service type to display text in dropdown
  const serviceTypeMap: Record<string, string> = {
    tour: 'Tour',
    nautical: 'Nautical',
    accommodation: 'Accommodation (Multi-Day)',
    event: 'Event',
  };

  // The Filament form uses a combobox (native select) labeled "Type*"
  const typeSelect = page.getByRole('combobox', { name: /type/i }).first();

  await typeSelect.waitFor({ state: 'visible', timeout: 10000 });
  await typeSelect.selectOption({ label: serviceTypeMap[serviceType] });
  await page.waitForTimeout(500); // Wait for Livewire to update
}

/**
 * Complete helper to fill Basic Information step (Step 1) of the wizard
 * This is the most critical step that all listing tests need
 */
export async function fillBasicInfoStep(
  page: Page,
  options: {
    serviceType: 'tour' | 'nautical' | 'accommodation' | 'event';
    titleEn: string;
    titleFr?: string;
    summaryEn?: string;
    summaryFr?: string;
    selectLocation?: boolean;
  }
): Promise<void> {
  const {
    serviceType,
    titleEn,
    titleFr = `${titleEn} FR`,
    summaryEn = 'Test summary for E2E testing',
    summaryFr,
    selectLocation: shouldSelectLocation = true,
  } = options;

  // 1. Select service type
  await selectServiceType(page, serviceType);

  // 2. Select location (if required)
  if (shouldSelectLocation) {
    await selectLocation(page, 1);
  }

  // 3. Fill titles
  await fillTranslatableTitle(page, titleEn, titleFr);

  // 4. Fill summaries
  await fillTranslatableSummary(page, summaryEn, summaryFr);
}

/**
 * Navigate through all wizard steps and save as draft
 * Returns true if listing was created successfully
 */
export async function completeWizardAndSaveDraft(page: Page): Promise<boolean> {
  // Click "Save Draft" button which is always visible at the top
  const saveDraftBtn = page.getByRole('button', { name: 'Save Draft' });

  if (await saveDraftBtn.isVisible({ timeout: 3000 })) {
    await saveDraftBtn.click();

    // Wait for save to complete - look for notification or navigation
    try {
      // Wait for success notification
      await page
        .locator('.fi-notification')
        .filter({ hasText: /saved|created|success/i })
        .waitFor({ state: 'visible', timeout: 5000 });
      return true;
    } catch {
      // Check if we navigated to edit page (also indicates success)
      const currentUrl = page.url();
      if (currentUrl.includes('/edit') || currentUrl.includes('/listings/')) {
        return true;
      }
    }
  }

  return false;
}

/**
 * Select location in Filament 3 form
 * The location field is a custom Filament select (searchable combobox)
 * DOM shows: generic "Location" > combobox with "Select an option"
 */
export async function selectLocation(page: Page, locationIndex: number = 1): Promise<void> {
  // Find the Location field container and click its combobox trigger
  const locationContainer = page
    .locator('div')
    .filter({ hasText: /^Location$/ })
    .first();
  const locationTrigger = locationContainer
    .locator('..')
    .locator('button, [role="combobox"]')
    .first();

  if (await locationTrigger.isVisible({ timeout: 3000 })) {
    await locationTrigger.click();
    await page.waitForTimeout(500);

    // Wait for dropdown options to appear
    const options = page.locator('[role="option"]');
    await options.first().waitFor({ state: 'visible', timeout: 3000 });

    // Select by index (skip "Select an option" placeholder)
    const optionCount = await options.count();
    if (optionCount > locationIndex) {
      await options.nth(locationIndex).click();
    } else if (optionCount > 0) {
      await options.first().click();
    }
    await page.waitForTimeout(300);
    return;
  }

  // Fallback: Try direct combobox approach
  const allComboboxes = page.getByRole('combobox');
  const count = await allComboboxes.count();

  // Location is typically the second combobox (after Type)
  if (count >= 2) {
    const locationCombobox = allComboboxes.nth(1);
    await locationCombobox.click();
    await page.waitForTimeout(500);

    const options = page.locator('[role="option"]');
    const optionCount = await options.count();
    if (optionCount > locationIndex) {
      await options.nth(locationIndex).click();
    }
  }
}

/**
 * Fill translatable title fields in Filament 3 form
 * Use getByRole('textbox') to be specific and avoid matching tabpanel
 */
export async function fillTranslatableTitle(
  page: Page,
  titleEn: string,
  titleFr: string
): Promise<void> {
  // English title - use textbox role to avoid matching tabpanel
  const titleEnField = page.getByRole('textbox', { name: 'Title (English)' });
  if (await titleEnField.isVisible({ timeout: 3000 })) {
    await titleEnField.fill(titleEn);
  }

  // French title - use textbox role
  const titleFrField = page.getByRole('textbox', { name: 'Title (French)' });
  if (await titleFrField.isVisible({ timeout: 2000 })) {
    await titleFrField.fill(titleFr);
  }
}

/**
 * Fill translatable summary/textarea fields
 * Use getByRole('textbox') to be specific and avoid matching other elements
 */
export async function fillTranslatableSummary(
  page: Page,
  summaryEn: string,
  summaryFr?: string
): Promise<void> {
  // English summary - use textbox role
  const summaryEnField = page.getByRole('textbox', { name: 'Summary (English)' });
  if (await summaryEnField.isVisible({ timeout: 3000 })) {
    await summaryEnField.fill(summaryEn);
  }

  // French summary (if provided)
  if (summaryFr) {
    const summaryFrField = page.getByRole('textbox', { name: 'Summary (French)' });
    if (await summaryFrField.isVisible({ timeout: 2000 })) {
      await summaryFrField.fill(summaryFr);
    }
  }
}

/**
 * Click next/continue in Filament wizard
 */
export async function clickWizardNext(page: Page): Promise<boolean> {
  const nextButtons = [
    page.getByRole('button', { name: /next/i }),
    page.getByRole('button', { name: /continue/i }),
    page.getByRole('button', { name: /suivant/i }),
    page.locator('button[wire\\:click*="nextStep"]'),
  ];

  for (const button of nextButtons) {
    if (await button.isVisible({ timeout: 1000 }).catch(() => false)) {
      await button.click();
      await page.waitForTimeout(500);
      return true;
    }
  }
  return false;
}

/**
 * Skip optional wizard step
 */
export async function clickWizardSkip(page: Page): Promise<boolean> {
  const skipButtons = [
    page.getByRole('button', { name: /skip/i }),
    page.getByRole('button', { name: /passer/i }),
    page.locator('button:has-text("Skip")'),
  ];

  for (const button of skipButtons) {
    if (await button.isVisible({ timeout: 1000 }).catch(() => false)) {
      await button.click();
      await page.waitForTimeout(500);
      return true;
    }
  }
  return false;
}

/**
 * Fill pricing fields in Filament 3 form
 * DOM shows: spinbutton "Price in Tunisian Dinar*" and spinbutton "Price in Euro*"
 */
export async function fillPricing(page: Page, tndPrice: number, eurPrice: number): Promise<void> {
  // Price in TND - actual label from DOM
  const tndField = page.getByRole('spinbutton', { name: /price in tunisian dinar/i }).first();
  if (await tndField.isVisible({ timeout: 3000 })) {
    await tndField.fill(tndPrice.toString());
  }

  // Price in EUR - actual label from DOM
  const eurField = page.getByRole('spinbutton', { name: /price in euro/i }).first();
  if (await eurField.isVisible({ timeout: 2000 })) {
    await eurField.fill(eurPrice.toString());
  }
}

/**
 * Fill TinyMCE editor content (TinyEditor in Filament)
 * TinyEditor uses iframe-based editing - we need to interact with the iframe body
 * Ported from admin-api-helpers.ts for vendor panel use
 */
export async function fillTinyMCEContent(page: Page, content: string): Promise<void> {
  console.log(`fillTinyMCEContent: Filling content (${content.length} chars)`);

  // Wait for TinyMCE to fully initialize (it takes time to load)
  await page.waitForTimeout(2000);

  // TinyMCE iframe selectors - try multiple patterns
  const iframeSelectors = [
    'iframe.tox-edit-area__iframe',
    'iframe[id*="tiny"]',
    '.tox-tinymce iframe',
  ];

  for (const selector of iframeSelectors) {
    try {
      // Wait for iframe to be available
      const iframe = page.locator(selector).first();
      await iframe.waitFor({ state: 'visible', timeout: 5000 });
      console.log(`TinyMCE iframe found via: ${selector}`);

      // Use frameLocator to access the iframe content
      const editorFrame = page.frameLocator(selector).first();
      const editorBody = editorFrame.locator('body');

      // Wait for body to be editable (TinyMCE sets contenteditable="true")
      await editorBody.waitFor({ state: 'visible', timeout: 5000 });

      // Check if body has contenteditable attribute (indicates TinyMCE is ready)
      const isEditable = await editorBody
        .evaluate((el) => el.contentEditable === 'true')
        .catch(() => false);
      if (!isEditable) {
        console.log(`TinyMCE body not editable yet, waiting...`);
        await page.waitForTimeout(1000);
      }

      // Click to focus the editor
      await editorBody.click();
      await page.waitForTimeout(200);

      // Clear existing content and type new content
      await page.keyboard.press('Control+a');
      await page.waitForTimeout(100);

      const plainContent = content.replace(/<[^>]*>/g, '');
      await page.keyboard.type(plainContent, { delay: 10 });
      console.log(`TinyMCE content filled (${plainContent.length} chars typed)`);

      // CRITICAL: Trigger TinyMCE save to sync content with underlying textarea/Livewire
      // TinyMCE doesn't automatically sync on blur - we need to call save() explicitly
      await page.evaluate(() => {
        // @ts-ignore
        if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
          // @ts-ignore
          tinymce.activeEditor.save();
          // Trigger change event on the underlying textarea
          // @ts-ignore
          const textarea = tinymce.activeEditor.getElement();
          if (textarea) {
            textarea.dispatchEvent(new Event('change', { bubbles: true }));
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
          }
          console.log('TinyMCE save() called and events dispatched');
        }
      });
      await page.waitForTimeout(500);

      // Additional blur trigger for Livewire
      await page.keyboard.press('Tab');
      await page.waitForTimeout(500);

      // Verify content was typed
      const bodyText = await editorBody.textContent().catch(() => '');
      if (bodyText && bodyText.includes(plainContent.substring(0, 20))) {
        console.log('TinyMCE content verified');
        return;
      }

      console.log('TinyMCE content may not have been filled, continuing to next selector');
    } catch (e) {
      console.log(`TinyMCE selector failed: ${selector} - ${e}`);
      continue;
    }
  }

  console.log('TinyMCE iframe approach failed, trying contenteditable fallback');
  // Fallback: try to find a contenteditable element directly (some TinyMCE configs)
  const contentEditable = page
    .locator('.tox-tinymce [contenteditable="true"], [data-mce-bogus] [contenteditable="true"]')
    .first();
  if (await contentEditable.isVisible({ timeout: 3000 }).catch(() => false)) {
    await contentEditable.click();
    await page.waitForTimeout(200);
    await page.keyboard.press('Control+a');
    const plainContent = content.replace(/<[^>]*>/g, '');
    await page.keyboard.type(plainContent, { delay: 10 });
    console.log('Content filled via contenteditable fallback');
    return;
  }

  console.log('WARNING: No content editor found - content not filled');
}

/**
 * Fill translatable description field (TinyEditor rich text)
 * Filament uses TinyEditor which renders as iframe or contenteditable div
 */
export async function fillTranslatableDescription(
  page: Page,
  descriptionEn: string,
  descriptionFr?: string
): Promise<void> {
  // English description - TinyEditor creates an iframe with contenteditable body
  // First try to find the TinyEditor iframe for English
  const tinyEnFrame = page.frameLocator('iframe[id*="description"][id*="en"]').first();
  const tinyEnBody = tinyEnFrame.locator('body');

  if (await tinyEnBody.isVisible({ timeout: 3000 }).catch(() => false)) {
    await tinyEnBody.fill(descriptionEn);
  } else {
    // Fallback: Try contenteditable div (some TinyEditor configs use this)
    const editableEn = page.locator('[data-field*="description"] [contenteditable="true"]').first();
    if (await editableEn.isVisible({ timeout: 2000 }).catch(() => false)) {
      await editableEn.click();
      await editableEn.fill(descriptionEn);
    } else {
      // Last fallback: textarea
      const textareaEn = page.locator('textarea[name*="description"][name*="en"]').first();
      if (await textareaEn.isVisible({ timeout: 2000 }).catch(() => false)) {
        await textareaEn.fill(descriptionEn);
      }
    }
  }

  if (descriptionFr) {
    // French description
    const tinyFrFrame = page.frameLocator('iframe[id*="description"][id*="fr"]').first();
    const tinyFrBody = tinyFrFrame.locator('body');

    if (await tinyFrBody.isVisible({ timeout: 2000 }).catch(() => false)) {
      await tinyFrBody.fill(descriptionFr);
    } else {
      const editableFr = page
        .locator('[data-field*="description"] [contenteditable="true"]')
        .nth(1);
      if (await editableFr.isVisible({ timeout: 2000 }).catch(() => false)) {
        await editableFr.click();
        await editableFr.fill(descriptionFr);
      } else {
        const textareaFr = page.locator('textarea[name*="description"][name*="fr"]').first();
        if (await textareaFr.isVisible({ timeout: 2000 }).catch(() => false)) {
          await textareaFr.fill(descriptionFr);
        }
      }
    }
  }
}

/**
 * Fill tour duration fields
 */
export async function fillTourDuration(
  page: Page,
  durationValue: number,
  durationUnit: 'hours' | 'days' = 'hours'
): Promise<void> {
  // Duration value - spinbutton or number input
  const durationInput = page.getByRole('spinbutton', { name: /duration.*value|duration/i }).first();
  if (await durationInput.isVisible({ timeout: 3000 }).catch(() => false)) {
    await durationInput.fill(durationValue.toString());
  } else {
    // Fallback to name attribute
    const fallbackInput = page
      .locator('input[name*="duration_value"], input[name*="duration"][type="number"]')
      .first();
    if (await fallbackInput.isVisible({ timeout: 2000 }).catch(() => false)) {
      await fallbackInput.fill(durationValue.toString());
    }
  }

  // Duration unit - select/combobox
  const unitSelect = page.getByRole('combobox', { name: /duration.*unit|unit/i }).first();
  if (await unitSelect.isVisible({ timeout: 2000 }).catch(() => false)) {
    const unitLabel = durationUnit === 'hours' ? 'Hours' : 'Days';
    await unitSelect.selectOption({ label: unitLabel });
  }
}

/**
 * Fill meeting point address field
 * Field name: meeting_point.address (from Filament ListingResource)
 */
export async function fillMeetingPoint(page: Page, address: string): Promise<void> {
  console.log('DEBUG fillMeetingPoint: Looking for meeting point field');
  // Meeting point address - try various selectors
  // Filament uses data[meeting_point][address] naming convention
  const meetingPointField = page
    .locator(
      'input[name*="meeting_point"][name*="address"], ' +
        'input[name="data[meeting_point][address]"], ' +
        'input[id*="meeting_point"][id*="address"]'
    )
    .first();

  if (await meetingPointField.isVisible({ timeout: 3000 }).catch(() => false)) {
    await meetingPointField.fill(address);
    console.log('DEBUG fillMeetingPoint: Filled address via input field');
  } else {
    console.log('DEBUG fillMeetingPoint: Input field not found, trying label');
    // Try by label text
    const labeledField = page.getByLabel(/meeting point address|address/i).first();
    if (await labeledField.isVisible({ timeout: 2000 }).catch(() => false)) {
      await labeledField.fill(address);
    } else {
      // Last resort: find any visible address input in the meeting point section
      const sectionField = page
        .locator('.fi-fo-field-wrp:has-text("Meeting Point") input[type="text"]')
        .first();
      if (await sectionField.isVisible({ timeout: 2000 }).catch(() => false)) {
        await sectionField.fill(address);
      }
    }
  }
}

/**
 * Select cancellation policy from dropdown
 * Field name: cancellation_policy.type (from Filament ListingResource)
 */
export async function selectCancellationPolicy(
  page: Page,
  policy: 'flexible' | 'moderate' | 'strict' = 'flexible'
): Promise<void> {
  console.log('DEBUG selectCancellationPolicy: Looking for policy dropdown');
  const policyLabels: Record<string, string> = {
    flexible: 'Flexible',
    moderate: 'Moderate',
    strict: 'Strict',
  };

  // Try native select first
  const nativeSelect = page
    .locator(
      'select[name*="cancellation_policy"][name*="type"], ' +
        'select[name="data[cancellation_policy][type]"], ' +
        'select[id*="cancellation_policy"][id*="type"]'
    )
    .first();

  if (await nativeSelect.isVisible({ timeout: 3000 }).catch(() => false)) {
    await nativeSelect.selectOption(policy);
    console.log('DEBUG selectCancellationPolicy: Selected via native select');
  } else {
    console.log('DEBUG selectCancellationPolicy: Native select not found, trying Filament select');
    // Try Filament's custom select (uses combobox role)
    const filamentSelect = page
      .getByRole('combobox', { name: /cancellation.*policy|policy.*type/i })
      .first();
    if (await filamentSelect.isVisible({ timeout: 2000 }).catch(() => false)) {
      await filamentSelect.click();
      await page.waitForTimeout(200);
      // Click the option in dropdown
      const option = page.getByRole('option', { name: policyLabels[policy] });
      if (await option.isVisible({ timeout: 2000 }).catch(() => false)) {
        await option.click();
      }
    } else {
      // Try by label
      const labeledSelect = page.getByLabel(/cancellation policy type|policy type/i).first();
      if (await labeledSelect.isVisible({ timeout: 2000 }).catch(() => false)) {
        await labeledSelect.selectOption(policy);
      }
    }
  }
}

/**
 * Create a complete tour listing with ALL required fields filled for review submission
 * This helper fills the entire wizard form properly including:
 * - Basic info (type, location, title, summary, description)
 * - Meeting point address
 * - Pricing (TND + EUR)
 * - Cancellation policy
 */
export async function createCompleteTourListing(page: Page, title: string): Promise<string> {
  await page.goto(`${VENDOR_PANEL_URL}/vendor/listings/create`);
  await waitForFilamentPage(page);

  // Step 1: Basic Information
  console.log('DEBUG: Step 1 - Basic Information');
  await selectServiceType(page, 'tour');
  await selectLocation(page, 1);
  await fillTranslatableTitle(page, title, `${title} FR`);
  await fillTranslatableSummary(page, 'Test tour summary for E2E testing.');

  // Fill description (TinyEditor) - REQUIRED FOR REVIEW
  // Wait for TinyEditor to load (it may take a moment after page load)
  await page.waitForTimeout(1500);
  await fillTinyMCEContent(
    page,
    `Complete tour description for ${title}. This exciting tour includes expert local guides, comfortable transportation, and unforgettable experiences exploring the beautiful island of Djerba.`
  );
  console.log('DEBUG: Filled description');

  // CRITICAL: Wait for Livewire to process the TinyMCE content before navigating
  await page.waitForTimeout(1000);
  await page.waitForLoadState('networkidle');

  // Helper to log current step indicator
  const logCurrentStep = async () => {
    const activeStep = page.locator(
      '.fi-wizard-step.active, [data-active-step], [aria-current="step"]'
    );
    const stepText = await activeStep.textContent().catch(() => 'unknown');
    const allSteps = await page
      .locator('.fi-wizard-step, .wizard-step')
      .allTextContents()
      .catch(() => []);
    console.log(`DEBUG: Current step: "${stepText?.trim()}" | All steps: ${allSteps.join(' | ')}`);
  };

  await logCurrentStep();

  // Step 1 -> Step 2 (Media) - Skip
  console.log('DEBUG: Navigating to Step 2 - Media');
  let nextBtn = page.getByRole('button', { name: 'Next' });
  if (await nextBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
    await nextBtn.click();
    await page.waitForTimeout(1500);
    await page.waitForLoadState('networkidle');
    await logCurrentStep();
  }

  // Step 2 -> Step 3 (Details) - Skip
  console.log('DEBUG: Navigating to Step 3 - Details');
  nextBtn = page.getByRole('button', { name: 'Next' });
  if (await nextBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
    await nextBtn.click();
    await page.waitForTimeout(1000);
    await page.waitForLoadState('networkidle');
    await logCurrentStep();
  }

  // Step 3 -> Step 4 (Tour Service) - Fill meeting point
  console.log('DEBUG: Navigating to Step 4 - Tour Service');
  nextBtn = page.getByRole('button', { name: 'Next' });
  if (await nextBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
    await nextBtn.click();
    await page.waitForTimeout(1000);
    await page.waitForLoadState('networkidle');
    await logCurrentStep();
  }

  // Fill duration - REQUIRED FOR TOURS
  const durationValueInput = page
    .locator('input[name*="duration"][name*="value"], input[id*="duration"][id*="value"]')
    .first();
  if (await durationValueInput.isVisible({ timeout: 3000 }).catch(() => false)) {
    await durationValueInput.fill('4');
    console.log('DEBUG: Filled duration value');
  } else {
    // Try via label
    const durationByLabel = page.getByRole('spinbutton', { name: /duration value/i }).first();
    if (await durationByLabel.isVisible({ timeout: 2000 }).catch(() => false)) {
      await durationByLabel.fill('4');
      console.log('DEBUG: Filled duration value via label');
    }
  }

  // Select duration unit (hours)
  const durationUnitSelect = page.locator('select[name*="duration"][name*="unit"]').first();
  if (await durationUnitSelect.isVisible({ timeout: 2000 }).catch(() => false)) {
    await durationUnitSelect.selectOption('hours');
    console.log('DEBUG: Selected duration unit');
  }

  await page.waitForTimeout(500);

  // Fill meeting point address - REQUIRED FOR REVIEW
  await fillMeetingPoint(page, 'Main Square, Houmt Souk, Djerba, Tunisia');
  console.log('DEBUG: Filled meeting point');

  // CRITICAL: Wait for Livewire to process the input before navigating
  await page.waitForTimeout(500);
  await page.waitForLoadState('networkidle');

  // Step 4 -> Step 5 (Route) - Skip
  console.log('DEBUG: Step 5 - Route (skipping)');
  nextBtn = page.getByRole('button', { name: 'Next' });
  if (await nextBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
    await nextBtn.click();
    await page.waitForTimeout(1000);
    await page.waitForLoadState('networkidle');
  }

  // Step 5 -> Step 6 (Pricing) - Fill pricing and cancellation policy
  console.log('DEBUG: Step 6 - Pricing');
  nextBtn = page.getByRole('button', { name: 'Next' });
  if (await nextBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
    await nextBtn.click();
    await page.waitForTimeout(500);
  }

  // Fill max_group_size - REQUIRED FOR REVIEW
  const maxGroupSizeInput = page
    .locator('input[name*="max_group_size"], input[id*="max_group_size"]')
    .first();
  if (await maxGroupSizeInput.isVisible({ timeout: 3000 }).catch(() => false)) {
    await maxGroupSizeInput.fill('20');
    console.log('DEBUG: Filled max_group_size');
  } else {
    // Try via label
    const maxGroupByLabel = page.getByRole('spinbutton', { name: /max.*group.*size/i }).first();
    if (await maxGroupByLabel.isVisible({ timeout: 2000 }).catch(() => false)) {
      await maxGroupByLabel.fill('20');
      console.log('DEBUG: Filled max_group_size via label');
    }
  }

  await page.waitForTimeout(500);

  // Fill person_types repeater - REQUIRED FOR REVIEW
  // Filament may create default empty entries. We need to fill ALL empty person_types inputs.
  let personTypesInputs = await page.locator('input[name*="person_types"]').count();
  console.log(`DEBUG: Found ${personTypesInputs} person_types inputs initially`);

  // If no person type inputs visible, add one
  if (personTypesInputs === 0) {
    // Scroll down to make sure Add Person Type button is visible
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2));
    await page.waitForTimeout(500);

    const addPersonTypeBtn = page.getByRole('button', { name: /add person type/i });
    if (await addPersonTypeBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
      // Use evaluate to click the button directly (more reliable)
      await addPersonTypeBtn.scrollIntoViewIfNeeded();
      await page.waitForTimeout(200);
      await addPersonTypeBtn.click({ force: true });
      await page.waitForTimeout(2000);
      console.log('DEBUG: Clicked Add Person Type (no existing entry)');

      // Wait for Livewire to add the repeater item
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);
    } else {
      console.log('DEBUG: Add Person Type button NOT visible!');
      // Log all buttons to see what's on page
      const allBtns = await page.locator('button').allTextContents();
      console.log(
        `DEBUG: All buttons: ${allBtns.filter((b) => b.includes('Person') || b.includes('Add')).join(', ')}`
      );
    }
  }

  // Wait for inputs to be ready
  await page.waitForTimeout(1000);

  // Log what person_types inputs exist now (check ALL inputs in DOM, not just visible)
  const personTypesAfter = await page.locator('input[name*="person_types"]').evaluateAll((els) =>
    els.map((el) => ({
      name: el.getAttribute('name'),
      visible: (el as HTMLElement).offsetParent !== null,
      value: (el as HTMLInputElement).value,
    }))
  );
  console.log(`DEBUG: Person types inputs after wait (total in DOM): ${personTypesAfter.length}`);
  if (personTypesAfter.length > 0) {
    console.log(
      `DEBUG: Person types field names: ${personTypesAfter.map((i) => i.name?.split('.').slice(-2).join('.')).join(', ')}`
    );
  }

  // Also check what sections exist on the page
  const sections = await page
    .locator('.fi-section, .fi-fo-section')
    .evaluateAll((els) =>
      els.map((el) => el.querySelector('[class*="heading"], h3, h4')?.textContent?.trim())
    );
  console.log(`DEBUG: Sections on page: ${sections.filter(Boolean).join(', ')}`);

  // Check if we're on the Pricing step - look for pricing section header
  const pricingHeader = page
    .locator('[class*="heading"], h2, h3, h4')
    .filter({ hasText: /pricing|price|person type/i });
  const pricingVisible = await pricingHeader
    .first()
    .isVisible({ timeout: 1000 })
    .catch(() => false);
  console.log(`DEBUG: Pricing header visible: ${pricingVisible}`);

  // Log ALL inputs on the page to debug
  const allInputsOnPage = await page.locator('input').evaluateAll((els) =>
    els.slice(-30).map((el) => ({
      name: el.getAttribute('name'),
      type: el.getAttribute('type'),
      id: el.getAttribute('id'),
    }))
  );
  console.log(
    `DEBUG: Last 30 inputs on page: ${allInputsOnPage
      .filter((i) => i.name?.includes('pricing') || i.name?.includes('person'))
      .map((i) => i.name)
      .join(', ')}`
  );

  // CRITICAL: Filament repeater items start COLLAPSED - we need to EXPAND them to see inputs!
  // Find the Person Type Pricing section specifically
  const personTypePricingSection = page
    .locator('.fi-section')
    .filter({ hasText: /person type pricing/i })
    .first();

  // Find Expand all button WITHIN this specific section
  let expandAllBtn = personTypePricingSection.getByRole('button', { name: /expand all/i });
  if (await expandAllBtn.isVisible({ timeout: 1000 }).catch(() => false)) {
    await expandAllBtn.click();
    await page.waitForTimeout(1500);
    console.log('DEBUG: Clicked Expand all in Person Type Pricing section');
  } else {
    // Fallback: try clicking individual Expand buttons in the section
    const expandBtns = personTypePricingSection.getByRole('button', { name: /^expand$/i });
    const expandCount = await expandBtns.count();
    console.log(`DEBUG: No Expand all found, clicking ${expandCount} individual Expand buttons`);
    for (let i = 0; i < Math.min(expandCount, 3); i++) {
      await expandBtns
        .nth(i)
        .click()
        .catch(() => {});
      await page.waitForTimeout(500);
    }
  }

  // Check for inputs using different selectors - Filament might use 'data.' prefix
  let personTypesInputsAfterExpand = await page.locator('input[name*="person_types"]').count();
  if (personTypesInputsAfterExpand === 0) {
    personTypesInputsAfterExpand = await page.locator('input[name*="pricing"]').count();
  }
  console.log(
    `DEBUG: Person types/pricing inputs after expanding: ${personTypesInputsAfterExpand}`
  );

  // Log any inputs in the Person Type Pricing section
  const inputsInSection = await personTypePricingSection
    .locator('input')
    .evaluateAll((els) => els.map((el) => el.getAttribute('name')));
  console.log(
    `DEBUG: All inputs in Person Type section: ${inputsInSection.filter(Boolean).join(', ').substring(0, 500)}`
  );

  // DEBUG: Get ALL form inputs on the entire page
  const allPageInputs = await page.locator('input:not([type="hidden"])').evaluateAll((els) =>
    els.slice(-50).map((el) => ({
      name: el.getAttribute('name')?.substring(0, 80),
      type: el.getAttribute('type'),
      placeholder: el.getAttribute('placeholder'),
    }))
  );
  const pricingRelatedInputs = allPageInputs.filter(
    (i) =>
      i.name?.includes('pricing') ||
      i.name?.includes('person') ||
      i.name?.includes('price') ||
      i.placeholder?.toLowerCase().includes('adult') ||
      i.placeholder?.toLowerCase().includes('price')
  );
  console.log(`DEBUG: Pricing-related inputs on page: ${JSON.stringify(pricingRelatedInputs)}`);

  // Fill inputs using PLACEHOLDERS since names don't contain "person_types"
  // Fill ALL key inputs (placeholder="adult")
  const keyInputs = page.locator('input[placeholder="adult"]');
  const keyCount = await keyInputs.count();
  console.log(`DEBUG: Found ${keyCount} key inputs (placeholder=adult)`);
  for (let i = 0; i < keyCount; i++) {
    const keyInput = keyInputs.nth(i);
    if (await keyInput.isVisible({ timeout: 500 }).catch(() => false)) {
      const currentValue = await keyInput.inputValue();
      if (!currentValue) {
        await keyInput.fill('adult');
        console.log(`DEBUG: Filled key #${i}`);
      }
    }
  }

  // Fill ALL label.en inputs (placeholder="Adult")
  const labelEnInputs = page.locator('input[placeholder="Adult"]');
  const labelEnCount = await labelEnInputs.count();
  console.log(`DEBUG: Found ${labelEnCount} label.en inputs (placeholder=Adult)`);
  for (let i = 0; i < labelEnCount; i++) {
    const labelEnInput = labelEnInputs.nth(i);
    if (await labelEnInput.isVisible({ timeout: 500 }).catch(() => false)) {
      const currentValue = await labelEnInput.inputValue();
      if (!currentValue) {
        await labelEnInput.fill('Adult');
        console.log(`DEBUG: Filled label.en #${i}`);
      }
    }
  }

  // Fill ALL label.fr inputs (placeholder="Adulte")
  const labelFrInputs = page.locator('input[placeholder="Adulte"]');
  const labelFrCount = await labelFrInputs.count();
  console.log(`DEBUG: Found ${labelFrCount} label.fr inputs (placeholder=Adulte)`);
  for (let i = 0; i < labelFrCount; i++) {
    const labelFrInput = labelFrInputs.nth(i);
    if (await labelFrInput.isVisible({ timeout: 500 }).catch(() => false)) {
      const currentValue = await labelFrInput.inputValue();
      if (!currentValue) {
        await labelFrInput.fill('Adulte');
        console.log(`DEBUG: Filled label.fr #${i}`);
      }
    }
  }

  // Fill price inputs using labels - look for spinbuttons with "Price in Tunisian Dinar" and "Price in Euro"
  const tndPriceInputs = page.getByRole('spinbutton', { name: /price in tunisian dinar/i });
  const tndPriceCount = await tndPriceInputs.count();
  console.log(`DEBUG: Found ${tndPriceCount} TND price inputs`);
  for (let i = 0; i < tndPriceCount; i++) {
    const tndPriceInput = tndPriceInputs.nth(i);
    if (await tndPriceInput.isVisible({ timeout: 500 }).catch(() => false)) {
      const currentValue = await tndPriceInput.inputValue();
      if (!currentValue) {
        await tndPriceInput.fill('150');
        console.log(`DEBUG: Filled tnd_price #${i}`);
      }
    }
  }

  const eurPriceInputs = page.getByRole('spinbutton', { name: /price in euro/i });
  const eurPriceCount = await eurPriceInputs.count();
  console.log(`DEBUG: Found ${eurPriceCount} EUR price inputs`);
  for (let i = 0; i < eurPriceCount; i++) {
    const eurPriceInput = eurPriceInputs.nth(i);
    if (await eurPriceInput.isVisible({ timeout: 500 }).catch(() => false)) {
      const currentValue = await eurPriceInput.inputValue();
      if (!currentValue) {
        await eurPriceInput.fill('45');
        console.log(`DEBUG: Filled eur_price #${i}`);
      }
    }
  }

  console.log('DEBUG: Filled all person type entries');

  // CRITICAL: Wait for Livewire to process pricing before continuing
  await page.waitForTimeout(500);

  // Fill cancellation policy - REQUIRED FOR REVIEW
  await selectCancellationPolicy(page, 'flexible');
  console.log('DEBUG: Filled cancellation policy');

  // CRITICAL: Wait for Livewire to process cancellation policy
  await page.waitForTimeout(500);
  await page.waitForLoadState('networkidle');

  // Log all available buttons on this step
  const buttonsOnLastStep = await page.locator('button').allTextContents();
  console.log(
    `DEBUG: Buttons on last step: ${buttonsOnLastStep
      .filter((b) => b.trim())
      .map((b) => `"${b.trim().substring(0, 20)}"`)
      .join(', ')}`
  );

  // Try to find a "Create" or "Save" button first (which saves all wizard data)
  let saveBtn = page.getByRole('button', { name: /^Create$/i });
  if (!(await saveBtn.isVisible({ timeout: 1000 }).catch(() => false))) {
    saveBtn = page.getByRole('button', { name: /^Save$/i });
  }
  if (!(await saveBtn.isVisible({ timeout: 1000 }).catch(() => false))) {
    saveBtn = page.getByRole('button', { name: 'Save Draft' });
  }
  const saveBtnText = await saveBtn.textContent().catch(() => 'N/A');
  console.log(`DEBUG: Using save button: "${saveBtnText?.trim()}"`);

  await saveBtn.click();
  console.log('DEBUG: Clicked save button');

  // Wait for save to complete
  await page.waitForTimeout(2000);
  await page.waitForLoadState('networkidle');

  // Check for validation errors
  const validationErrors = page.locator(
    '.fi-fo-field-wrp-error-message, [data-validation-error], .text-danger-600'
  );
  const hasValidationErrors = (await validationErrors.count()) > 0;
  if (hasValidationErrors) {
    const errors = await validationErrors.allTextContents();
    console.log(`DEBUG: Validation errors found: ${errors.join(', ')}`);
  }

  // Check for any error notifications
  const errorNotif = page
    .locator('.fi-notification')
    .filter({ hasText: /error|failed|required|invalid/i });
  if (await errorNotif.isVisible({ timeout: 2000 }).catch(() => false)) {
    const errorText = await errorNotif.textContent();
    console.log(`DEBUG: Error notification: ${errorText?.trim()}`);
  }

  // Wait for success notification (listing saved)
  const successNotif = page
    .locator('.fi-notification')
    .filter({ hasText: /created|saved|success/i });
  const hasSuccess = await successNotif.isVisible({ timeout: 5000 }).catch(() => false);
  console.log(`DEBUG: Success notification visible: ${hasSuccess}`);

  // Check if we're still on the create page (validation failure)
  let url = page.url();
  if (url.includes('/create')) {
    console.log(`DEBUG: Still on create page after save - checking for errors`);
    // Log all visible form field errors
    const allErrors = await page.locator('[class*="error"], .text-danger').allTextContents();
    console.log(`DEBUG: All errors on page: ${allErrors.filter((e) => e.trim()).join(', ')}`);

    // Try scrolling and clicking save again
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(500);
    await saveBtn.click();
    await page.waitForTimeout(3000);
    await page.waitForLoadState('networkidle');
    url = page.url();
  }

  // Get the URL slug - check if we've been redirected
  const slugMatch = url.match(/\/listings\/([^\/\?\s]+)/);
  let slug: string;
  if (slugMatch && slugMatch[1] !== 'create') {
    slug = slugMatch[1];
  } else {
    // If still on create, navigate to listings list and find the new one
    console.log(`DEBUG: Navigating to listings to find the created one`);
    await page.goto(`${VENDOR_PANEL_URL}/vendor/listings`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Find the listing by title
    const listingRow = page.getByRole('row', { name: new RegExp(title, 'i') }).first();
    if (await listingRow.isVisible({ timeout: 5000 }).catch(() => false)) {
      // Get the slug from the row's link
      const listingLink = listingRow.locator('a[href*="/listings/"]').first();
      const href = await listingLink.getAttribute('href');
      const hrefMatch = href?.match(/\/listings\/([^\/\?\s]+)/);
      slug = hrefMatch ? hrefMatch[1] : title.toLowerCase().replace(/\s+/g, '-');
    } else {
      console.log(`DEBUG: Could not find listing row for title: ${title}`);
      slug = title.toLowerCase().replace(/\s+/g, '-');
    }
  }

  console.log(`DEBUG: Listing created with slug: ${slug}`);

  return slug;
}

/**
 * Submit listing for review (vendor action) - Legacy function kept for compatibility
 */
export async function vendorSubmitForReview(page: Page, listingTitle: string): Promise<void> {
  // Find the listing row
  const row = page.locator('table tbody tr').filter({ hasText: listingTitle }).first();

  // Click actions menu
  const actionsBtn = row
    .locator('button')
    .filter({ hasText: /actions/i })
    .first();
  if (await actionsBtn.isVisible()) {
    await actionsBtn.click();
    await page.waitForTimeout(300);
  }

  // Click "Submit for Review"
  const submitAction = page
    .locator('[role="menuitem"], button')
    .filter({ hasText: /submit.*review/i })
    .first();
  if (await submitAction.isVisible({ timeout: 2000 })) {
    await submitAction.click();
    await page.waitForTimeout(500);
  }

  // Confirm if modal appears
  const confirmBtn = page.getByRole('button', { name: /confirm|yes|submit/i }).first();
  if (await confirmBtn.isVisible({ timeout: 2000 }).catch(() => false)) {
    await confirmBtn.click();
  }

  await page.waitForLoadState('networkidle');
}

/**
 * Verify listing status in vendor panel table
 */
export async function verifyVendorListingStatus(
  page: Page,
  listingTitle: string,
  expectedStatus: string
): Promise<void> {
  const row = page.locator('table tbody tr').filter({ hasText: listingTitle }).first();

  // Status badges in Filament use .fi-badge class
  const statusBadge = row.locator('.fi-badge, [class*="badge"]').first();

  await expect(statusBadge).toContainText(expectedStatus.replace('_', ' '), {
    ignoreCase: true,
    timeout: 5000,
  });
}
