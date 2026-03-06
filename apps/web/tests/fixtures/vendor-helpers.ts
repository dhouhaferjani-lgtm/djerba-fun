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
 */
export async function loginVendorUI(page: Page, email: string, password: string): Promise<void> {
  await page.goto(`${VENDOR_PANEL_URL}/vendor/login`);

  // Wait for Filament login form (Livewire uses data.email for field ID)
  await page.waitForSelector('#data\\.email', { state: 'visible', timeout: 15000 });

  // Fill login form - Filament uses data.* IDs
  await page.fill('#data\\.email', email);
  await page.fill('#data\\.password', password);

  // Click login button
  await page.click('button[type="submit"]');

  // Wait for redirect to dashboard
  await page.waitForURL(/\/vendor/, { timeout: 15000 });

  // Verify we're logged in by checking for navigation
  await expect(
    page.locator('.fi-sidebar, .fi-sidebar-nav, [data-sidebar], nav').first()
  ).toBeVisible({ timeout: 10000 });
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
  const submitButton = page
    .locator(
      'button[type="submit"], ' +
        'button:has-text("Create"), ' +
        'button:has-text("Save"), ' +
        'button:has-text("Update"), ' +
        'button:has-text("Submit")'
    )
    .first();

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
 */
export const seededVendor = {
  email: 'vendor@goadventure.tn',
  password: 'password',
};

/**
 * Seeded admin credentials
 */
export const seededAdmin = {
  email: 'admin@goadventure.tn',
  password: 'password',
};
