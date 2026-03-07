/**
 * Filament Admin Panel automation helpers for E2E tests
 */

import { Page, expect } from '@playwright/test';

const ADMIN_URL = process.env.ADMIN_URL || 'http://localhost:8000/admin';
const ADMIN_EMAIL = 'admin@goadventure.tn';
const ADMIN_PASSWORD = 'password';

export interface ListingData {
  title_en: string;
  title_fr?: string;
  summary_en: string;
  summary_fr?: string;
  serviceType: 'tour' | 'nautical' | 'accommodation' | 'event';
  vendorId?: string;
  locationId?: string;
  tndPrice: number;
  eurPrice: number;
  isFeatured?: boolean;
}

export interface CouponData {
  code: string;
  name: string;
  discountType: 'percentage' | 'fixed_amount';
  discountValue: number;
  validFrom?: Date;
  validUntil?: Date;
  usageLimit?: number;
  minimumOrder?: number;
  listingIds?: string[];
  isActive?: boolean;
}

/**
 * Login to Filament Admin Panel
 */
export async function loginToAdmin(page: Page): Promise<void> {
  await page.goto(`${ADMIN_URL}/login`);

  // Wait for Filament login form (Livewire uses data.email for field ID)
  await page.waitForSelector('#data\\.email', { state: 'visible', timeout: 15000 });

  // Fill credentials using Filament's Livewire field IDs
  await page.fill('#data\\.email', ADMIN_EMAIL);
  await page.fill('#data\\.password', ADMIN_PASSWORD);

  // Submit and wait for dashboard
  await page.click('button[type="submit"]');
  await page.waitForURL(`${ADMIN_URL}/**`, { timeout: 15000 });

  // Verify we're logged in by checking for Filament panel body class
  // The body.fi-body.fi-panel-admin class only appears on authenticated admin panel pages
  await expect(page.locator('body.fi-body.fi-panel-admin')).toBeVisible({ timeout: 10000 });
  // Also verify main content area loaded (confirms page finished loading)
  await expect(page.locator('.fi-main, main, [class*="main"]').first()).toBeVisible({
    timeout: 10000,
  });
}

/**
 * Navigate to a Filament resource page
 */
export async function navigateToResource(page: Page, resource: string): Promise<void> {
  await page.goto(`${ADMIN_URL}/${resource}`);
  await page.waitForLoadState('networkidle');
}

/**
 * Navigate to a listing in admin panel
 */
export async function navigateToListing(page: Page, listingId: string): Promise<void> {
  await page.goto(`${ADMIN_URL}/listings/${listingId}/edit`);
  await page.waitForLoadState('networkidle');
}

/**
 * Publish a listing by changing its status
 */
export async function publishListing(page: Page, listingId: string): Promise<void> {
  await navigateToListing(page, listingId);

  // Wait for form to load
  await page.waitForLoadState('networkidle');

  // Find status field using Filament 3 patterns - try multiple selectors
  const statusSelect = page.getByRole('combobox', { name: /status/i }).first();

  if (await statusSelect.isVisible({ timeout: 5000 }).catch(() => false)) {
    await statusSelect.selectOption({ label: 'Published' });
  } else {
    // Fallback: try native select with various selectors
    const nativeSelect = page
      .locator('select')
      .filter({ hasText: /published|draft|archived/i })
      .first();
    if (await nativeSelect.isVisible({ timeout: 3000 }).catch(() => false)) {
      await nativeSelect.selectOption({ label: 'Published' });
    }
  }

  // Save changes
  await saveForm(page);
}

/**
 * Archive a listing
 */
export async function archiveListing(page: Page, listingId: string): Promise<void> {
  await navigateToListing(page, listingId);

  // Wait for form to load
  await page.waitForLoadState('networkidle');

  // Find status field using Filament 3 patterns
  const statusSelect = page.getByRole('combobox', { name: /status/i }).first();

  if (await statusSelect.isVisible({ timeout: 5000 }).catch(() => false)) {
    await statusSelect.selectOption({ label: 'Archived' });
  } else {
    // Fallback: try native select
    const nativeSelect = page
      .locator('select')
      .filter({ hasText: /published|draft|archived/i })
      .first();
    if (await nativeSelect.isVisible({ timeout: 3000 }).catch(() => false)) {
      await nativeSelect.selectOption({ label: 'Archived' });
    }
  }

  await saveForm(page);
}

/**
 * Toggle featured status of a listing
 */
export async function setListingFeatured(
  page: Page,
  listingId: string,
  featured: boolean
): Promise<void> {
  await navigateToListing(page, listingId);

  // Find featured toggle (Filament toggle component)
  const featuredToggle = page.locator(
    'input[name*="is_featured"], [wire\\:model*="is_featured"], [data-field="is_featured"] input'
  );

  const isChecked = await featuredToggle.isChecked();

  if (isChecked !== featured) {
    await featuredToggle.click();
  }

  await saveForm(page);
}

/**
 * Update listing price
 */
export async function updateListingPrice(
  page: Page,
  listingId: string,
  tndPrice: number,
  eurPrice?: number
): Promise<void> {
  await navigateToListing(page, listingId);

  // Find TND price field
  const tndPriceInput = page
    .locator(
      'input[name*="tnd_price"], [wire\\:model*="tnd_price"], [data-field="tnd_price"] input'
    )
    .first();

  await tndPriceInput.fill(tndPrice.toString());

  if (eurPrice !== undefined) {
    const eurPriceInput = page
      .locator(
        'input[name*="eur_price"], [wire\\:model*="eur_price"], [data-field="eur_price"] input'
      )
      .first();
    await eurPriceInput.fill(eurPrice.toString());
  }

  await saveForm(page);
}

/**
 * Update listing translation
 */
export async function updateListingTranslation(
  page: Page,
  listingId: string,
  locale: 'en' | 'fr',
  field: 'title' | 'summary' | 'description',
  value: string
): Promise<void> {
  await navigateToListing(page, listingId);

  // Switch to locale tab if needed
  const localeTab = page.locator(
    `button:has-text("${locale.toUpperCase()}"), [data-locale="${locale}"]`
  );
  if (await localeTab.isVisible()) {
    await localeTab.click();
    await page.waitForTimeout(500);
  }

  // Find and fill the field
  const fieldInput = page
    .locator(
      `[name*="${field}"][name*="${locale}"], [data-field="${field}"] textarea, [data-field="${field}"] input`
    )
    .first();

  if (field === 'description') {
    // Description might be a rich text editor
    const richEditor = page.locator('[data-field="description"] [contenteditable="true"]').first();
    if (await richEditor.isVisible()) {
      await richEditor.fill(value);
    } else {
      await fieldInput.fill(value);
    }
  } else {
    await fieldInput.fill(value);
  }

  await saveForm(page);
}

/**
 * Create a new coupon in admin panel
 */
export async function createCoupon(page: Page, data: CouponData): Promise<string> {
  await page.goto(`${ADMIN_URL}/coupons/create`);
  await page.waitForLoadState('networkidle');

  // Fill basic info
  await page.fill('input[name*="code"]', data.code);
  await page.fill('input[name*="name"]', data.name);

  // Set discount type
  const discountTypeSelect = page.locator('select[name*="discount_type"]').first();
  if (await discountTypeSelect.isVisible()) {
    await discountTypeSelect.selectOption(data.discountType);
  } else {
    await page.click('[data-field="discount_type"] button');
    await page.click(
      `li:has-text("${data.discountType === 'percentage' ? 'Percentage' : 'Fixed Amount'}")`
    );
  }

  // Set discount value
  await page.fill('input[name*="discount_value"]', data.discountValue.toString());

  // Set validity dates
  if (data.validFrom) {
    await page.fill('input[name*="valid_from"]', data.validFrom.toISOString().slice(0, 16));
  }
  if (data.validUntil) {
    await page.fill('input[name*="valid_until"]', data.validUntil.toISOString().slice(0, 16));
  }

  // Set usage limit if provided
  if (data.usageLimit !== undefined) {
    await page.fill('input[name*="usage_limit"]', data.usageLimit.toString());
  }

  // Set minimum order if provided
  if (data.minimumOrder !== undefined) {
    await page.fill('input[name*="minimum_order"]', data.minimumOrder.toString());
  }

  // Set listing restrictions if provided
  if (data.listingIds && data.listingIds.length > 0) {
    const listingIdsInput = page.locator('input[name*="listing_ids"]').first();
    await listingIdsInput.fill(data.listingIds.join(','));
  }

  // Set active status
  if (data.isActive === false) {
    const activeToggle = page.locator('input[name*="is_active"]').first();
    if (await activeToggle.isChecked()) {
      await activeToggle.click();
    }
  }

  // Save
  await saveForm(page);

  // Get the created coupon ID from URL
  await page.waitForURL(`${ADMIN_URL}/coupons/**`);
  const url = page.url();
  const match = url.match(/\/coupons\/(\d+)/);
  return match ? match[1] : '';
}

/**
 * Deactivate a coupon
 */
export async function deactivateCoupon(page: Page, couponId: string): Promise<void> {
  await page.goto(`${ADMIN_URL}/coupons/${couponId}/edit`);
  await page.waitForLoadState('networkidle');

  // Find and uncheck active toggle
  const activeToggle = page
    .locator('input[name*="is_active"], [wire\\:model*="is_active"]')
    .first();

  if (await activeToggle.isChecked()) {
    await activeToggle.click();
  }

  await saveForm(page);
}

/**
 * Cancel a booking from admin panel
 */
export async function cancelBooking(page: Page, bookingId: string, reason?: string): Promise<void> {
  await page.goto(`${ADMIN_URL}/bookings/${bookingId}`);
  await page.waitForLoadState('networkidle');

  // Click cancel action button
  const cancelButton = page.locator('button:has-text("Cancel"), [wire\\:click*="cancel"]').first();
  await cancelButton.click();

  // If modal appears with reason field
  const reasonInput = page.locator('textarea[name*="reason"], input[name*="reason"]').first();
  if (await reasonInput.isVisible({ timeout: 2000 }).catch(() => false)) {
    await reasonInput.fill(reason || 'Cancelled by admin for testing');
  }

  // Confirm cancellation
  const confirmButton = page.locator('button:has-text("Confirm"), button:has-text("Yes")').first();
  await confirmButton.click();

  // Wait for action to complete
  await page.waitForTimeout(1000);
}

/**
 * Navigate to Platform Settings page
 */
export async function navigateToPlatformSettings(page: Page): Promise<void> {
  await page.goto(`${ADMIN_URL}/platform-settings`);
  await page.waitForLoadState('networkidle');
}

/**
 * Update a platform setting
 */
export async function updatePlatformSetting(
  page: Page,
  tab: string,
  fieldName: string,
  value: string | number | boolean
): Promise<void> {
  await navigateToPlatformSettings(page);

  // Click on the tab
  const tabButton = page
    .locator(`button:has-text("${tab}"), [data-tab="${tab.toLowerCase().replace(/\s+/g, '-')}"]`)
    .first();
  await tabButton.click();
  await page.waitForTimeout(500);

  // Find the field
  const fieldSelector = `input[name*="${fieldName}"], select[name*="${fieldName}"], textarea[name*="${fieldName}"], [data-field="${fieldName}"] input`;
  const field = page.locator(fieldSelector).first();

  if (typeof value === 'boolean') {
    const isChecked = await field.isChecked();
    if (isChecked !== value) {
      await field.click();
    }
  } else {
    await field.fill(value.toString());
  }

  // Save settings
  await page.click('button:has-text("Save")');
  await page.waitForTimeout(1000);
}

/**
 * Get a listing ID (slug) from admin listings table
 * Note: Filament uses slugs as record keys, not numeric IDs
 */
export async function getFirstListingId(page: Page): Promise<string> {
  await navigateToResource(page, 'listings');

  // Wait for table to load
  await page.waitForSelector('table tbody tr', { timeout: 10000 });

  // Get first row's edit link - Filament uses slugs in URLs
  const firstEditLink = page.locator('table tbody tr:first-child a[href*="/edit"]').first();
  const href = await firstEditLink.getAttribute('href');

  if (href) {
    // Match slug pattern: /listings/{slug}/edit
    const match = href.match(/\/listings\/([^\/]+)\/edit/);
    return match ? match[1] : '';
  }
  return '';
}

/**
 * Get listing slug from admin panel
 * Note: Since Filament uses slugs as record keys, the listingId IS the slug
 */
export async function getListingSlug(page: Page, listingId: string): Promise<string> {
  // In Filament, the record key is the slug, so listingId is already the slug
  return listingId;
}

/**
 * Save Filament form
 */
async function saveForm(page: Page): Promise<void> {
  // Click save button
  const saveButton = page
    .locator(
      'button:has-text("Save"), button:has-text("Update"), button[type="submit"]:has-text("Save")'
    )
    .first();

  await saveButton.click();

  // Wait for save to complete (notification or page reload)
  await Promise.race([
    page
      .waitForSelector('[class*="notification"], [class*="toast"]', { timeout: 5000 })
      .catch(() => {}),
    page.waitForLoadState('networkidle').catch(() => {}),
  ]);

  await page.waitForTimeout(500);
}

export { ADMIN_URL, ADMIN_EMAIL, ADMIN_PASSWORD };
