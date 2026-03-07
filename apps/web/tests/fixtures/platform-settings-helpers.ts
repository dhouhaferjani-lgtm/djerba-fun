/**
 * Platform Settings E2E Test Helpers
 *
 * Helper functions for testing the Platform Settings admin page
 * across all 24 tabs with support for:
 * - Tab navigation
 * - Translatable fields (EN/FR)
 * - Media file uploads
 * - Feature toggles
 * - Frontend verification
 */

import { Page, expect, Locator } from '@playwright/test';
import { join } from 'path';

// Base URLs
const ADMIN_URL = process.env.ADMIN_URL || 'http://localhost:8000/admin';
const FRONTEND_URL = process.env.NEXT_PUBLIC_URL || 'http://localhost:3000';
const PLATFORM_SETTINGS_URL = `${ADMIN_URL}/platform-settings`;

// Test image paths
const IMAGES_DIR = join(__dirname, 'images');

export const testImages = {
  logoLight: join(IMAGES_DIR, 'test-logo-light.png'),
  logoDark: join(IMAGES_DIR, 'test-logo-dark.png'),
  hero: join(IMAGES_DIR, 'test-hero.jpg'),
  founder: join(IMAGES_DIR, 'test-founder.jpg'),
  ogImage: join(IMAGES_DIR, 'test-og-image.png'),
  favicon: join(IMAGES_DIR, 'test-favicon.ico'),
  brandPillar: join(IMAGES_DIR, 'test-brand-pillar.png'),
  eventImage: join(IMAGES_DIR, 'test-event-image.png'),
  testimonialPhoto: join(IMAGES_DIR, 'test-testimonial-photo.png'),
  destinationImage: join(IMAGES_DIR, 'test-destination-image.png'),
  partnerLogo: join(IMAGES_DIR, 'test-partner-logo.png'),
};

// Tab names mapping (as they appear in Filament UI)
// IMPORTANT: These must match the actual tab names in PlatformSettingsPage.php
export const tabNames = {
  platformIdentity: 'Platform Identity',
  logoBranding: 'Logo & Branding',
  eventOfYear: 'Event of the Year',
  destinations: 'Destinations',
  testimonials: 'Testimonials',
  experienceCategories: 'Experience Categories',
  blogSection: 'Blog Section',
  featuredPackages: 'Featured Packages',
  customExperience: 'Custom Experience CTA',
  newsletter: 'Newsletter',
  aboutPage: 'About Page',
  seoMetadata: 'SEO & Metadata',
  contact: 'Contact',
  address: 'Address',
  socialMedia: 'Social Media',
  email: 'Email',
  payment: 'Payment',
  booking: 'Booking',
  localization: 'Localization',
  features: 'Features',
  analytics: 'Analytics',
  // Fixed: Backend uses "Legal" not "Legal & Compliance"
  legal: 'Legal',
  // Fixed: Backend uses "Vendors" not "Vendor Settings"
  vendors: 'Vendors',
  // Note: Brand Colors tab does NOT exist in backend
};

/**
 * Navigate to Platform Settings page
 */
export async function navigateToPlatformSettings(page: Page): Promise<void> {
  await page.goto(PLATFORM_SETTINGS_URL);
  await page.waitForLoadState('networkidle');
  // Wait for the platform settings page to load (look for tabs or main content)
  // Filament uses Livewire components, so we look for tab elements instead of forms
  await page.waitForSelector('[role="tablist"], [role="tab"], .fi-tabs, [wire\\:id]', {
    timeout: 15000,
  });
}

/**
 * Ensure required Platform Settings fields are populated before save operations.
 *
 * Platform Settings has many required fields across different tabs.
 * This function populates essential required fields to prevent validation errors
 * when saving changes on individual tabs.
 *
 * Call this in a beforeAll/beforeEach hook or before first save operation.
 */
export async function ensureRequiredFieldsPopulated(page: Page): Promise<void> {
  // Navigate to Platform Identity tab (has most required fields)
  await navigateToTab(page, tabNames.platformIdentity);

  // Filament 3 uses wire:model for inputs. Check if fields are already populated
  // by looking for any visible text input in the Platform Identity section
  const platformIdentityInputs = page.locator(
    '.fi-ta-content input[type="text"]:visible, [role="tabpanel"] input[type="text"]:visible'
  );
  const inputCount = await platformIdentityInputs.count();

  if (inputCount > 0) {
    // Check the first input (should be platform_name.en)
    const firstInput = platformIdentityInputs.first();
    const currentValue = await firstInput.inputValue().catch(() => '');

    if (!currentValue || currentValue.trim() === '') {
      console.log('📝 Populating required Platform Settings fields...');

      // Fill ALL visible text inputs in Platform Identity tab with default values
      // Filament shows EN fields in the "English" nested tab, FR in "French" nested tab
      // We need to fill the visible inputs first, then switch language tabs

      // Fill visible inputs (likely EN)
      const visibleInputs = page.locator('[role="tabpanel"]:visible input[type="text"]:visible');
      const visibleCount = await visibleInputs.count();
      const defaultValues = [
        'Djerba Fun',
        'Experience the island differently',
        'Your adventure awaits',
        'Discover authentic experiences',
      ];

      for (let i = 0; i < Math.min(visibleCount, defaultValues.length); i++) {
        const input = visibleInputs.nth(i);
        const existingVal = await input.inputValue().catch(() => '');
        if (!existingVal || existingVal.trim() === '') {
          await input.fill(defaultValues[i]);
        }
      }

      // Try to find and click French language tab within the Platform Identity section
      const frenchTab = page
        .locator(
          '[role="tab"]:has-text("French"), [role="tab"]:has-text("FR"), button:has-text("French")'
        )
        .first();
      if ((await frenchTab.count()) > 0 && (await frenchTab.isVisible())) {
        await frenchTab.click();
        await page.waitForTimeout(300);

        // Fill visible FR inputs
        const frInputs = page.locator('[role="tabpanel"]:visible input[type="text"]:visible');
        const frCount = await frInputs.count();
        const frDefaultValues = [
          'Djerba Fun',
          "Vivez l'île autrement",
          'Votre aventure vous attend',
          'Découvrez des expériences authentiques',
        ];

        for (let i = 0; i < Math.min(frCount, frDefaultValues.length); i++) {
          const input = frInputs.nth(i);
          const existingVal = await input.inputValue().catch(() => '');
          if (!existingVal || existingVal.trim() === '') {
            await input.fill(frDefaultValues[i]);
          }
        }
      }

      // Navigate to Logo & Branding tab to fill hero text fields
      await navigateToTab(page, tabNames.logoBranding);
      await page.waitForTimeout(500);

      // Fill visible hero inputs
      const heroInputs = page.locator('[role="tabpanel"]:visible input[type="text"]:visible');
      const heroCount = await heroInputs.count();
      const heroDefaultValues = [
        'Experience Djerba',
        'Your adventure starts here',
        'Adventure',
        'Explore',
        'Connect',
      ];

      for (let i = 0; i < Math.min(heroCount, heroDefaultValues.length); i++) {
        const input = heroInputs.nth(i);
        const existingVal = await input.inputValue().catch(() => '');
        if (!existingVal || existingVal.trim() === '') {
          await input.fill(heroDefaultValues[i]);
        }
      }

      // Save the populated fields
      const saveButton = page.getByRole('button', { name: /Save/i });
      if (await saveButton.isVisible()) {
        await saveButton.click();
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(2000);

        // Check for errors after save
        const errorNotification = page.locator('.fi-no-notification.fi-color-danger:visible');
        if ((await errorNotification.count()) > 0) {
          console.log('⚠️ Warning: Could not populate all required fields, some tests may fail');
        } else {
          console.log('✅ Required Platform Settings fields populated');
        }
      }
    } else {
      console.log('✅ Required Platform Settings fields already populated');
    }
  }
}

/**
 * Navigate to a specific tab in Platform Settings
 */
export async function navigateToTab(page: Page, tabName: string): Promise<void> {
  const tab = page.getByRole('tab', { name: new RegExp(tabName, 'i') });
  await expect(tab).toBeVisible({ timeout: 5000 });
  await tab.click();
  await page.waitForLoadState('networkidle');
  // Wait for tab panel content to update
  await page.waitForTimeout(300);
}

/**
 * Click the Save Settings button and wait for save completion
 *
 * Strategy: Filament 3 notifications are brief and may become invisible quickly.
 * Instead of waiting for a visible notification, we:
 * 1. Click save
 * 2. Wait for Livewire/network to settle
 * 3. Verify no error notification is visible
 * 4. Consider save successful
 *
 * @param page - Playwright Page object
 * @param options.throwOnError - If false, log error but don't throw (default: true)
 * @returns true if save succeeded, false if there was an error (when throwOnError=false)
 */
export async function saveSettings(
  page: Page,
  options: { throwOnError?: boolean } = {}
): Promise<boolean> {
  const { throwOnError = true } = options;

  const saveButton = page.getByRole('button', { name: /Save/i });
  await expect(saveButton).toBeVisible();
  await saveButton.click();

  // Wait for Livewire to process the save
  await page.waitForLoadState('networkidle');

  // Give the notification time to appear/settle
  await page.waitForTimeout(1000);

  // Check for error notifications - if any error is visible, handle it
  const errorNotification = page.locator(
    '.fi-no-notification.fi-color-danger:visible, ' +
      '.fi-no-notification.fi-status-danger:visible, ' +
      '[role="alert"]:visible'
  );
  const hasError = (await errorNotification.count()) > 0;

  if (hasError) {
    const errorText = await errorNotification.first().textContent();
    const cleanErrorText = errorText?.replace(/\s+/g, ' ').trim() || 'Unknown error';

    if (throwOnError) {
      throw new Error(`Save failed with error: ${cleanErrorText}`);
    } else {
      console.log(`⚠️ Save failed (non-fatal): ${cleanErrorText}`);
      return false;
    }
  }

  // If no error, consider save successful
  return true;
}

/**
 * Wait for Filament 3 notification (optional - for specific notification checks)
 *
 * Note: Filament 3 notifications may become invisible quickly due to CSS transitions.
 * For save operations, prefer using saveSettings() which uses a more robust approach.
 *
 * Filament 3 notification structure:
 * - Container: .fi-no (with role="status")
 * - Notification: .fi-no-notification
 * - Success: .fi-color-success or .fi-status-success
 * - Error: .fi-color-danger or .fi-status-danger
 */
export async function waitForNotification(
  page: Page,
  type: 'success' | 'error' = 'success',
  timeout: number = 10000
): Promise<void> {
  // Filament 3 uses these specific classes for notifications
  const colorClass = type === 'success' ? 'fi-color-success' : 'fi-color-danger';
  const statusClass = type === 'success' ? 'fi-status-success' : 'fi-status-danger';

  // For success: wait briefly then check for errors (success notifications are brief)
  // For error: actively wait for the error notification
  if (type === 'success') {
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);

    // Verify no error notification
    const errorCount = await page.locator('.fi-no-notification.fi-color-danger:visible').count();
    if (errorCount > 0) {
      throw new Error('Expected success but found error notification');
    }
  } else {
    // Wait for error notification to appear
    const selector = `.fi-no-notification.${colorClass}, .fi-no-notification.${statusClass}`;
    await page.waitForSelector(selector, { state: 'visible', timeout });
  }
}

/**
 * Fill a simple text input field
 */
export async function fillTextField(page: Page, fieldName: string, value: string): Promise<void> {
  const input = page.locator(
    `input[name="${fieldName}"], input[wire\\:model*="${fieldName}"], textarea[name="${fieldName}"]`
  );
  await input.clear();
  await input.fill(value);
}

/**
 * Fill a translatable text field (EN and FR versions)
 */
export async function fillTranslatableField(
  page: Page,
  fieldBaseName: string,
  valueEn: string,
  valueFr: string
): Promise<void> {
  // Fill English field
  const enInput = page.locator(
    `input[name*="${fieldBaseName}"][name*="en"], textarea[name*="${fieldBaseName}"][name*="en"], ` +
      `[wire\\:model*="${fieldBaseName}"][wire\\:model*="en"]`
  );
  if ((await enInput.count()) > 0) {
    await enInput.first().clear();
    await enInput.first().fill(valueEn);
  }

  // Fill French field
  const frInput = page.locator(
    `input[name*="${fieldBaseName}"][name*="fr"], textarea[name*="${fieldBaseName}"][name*="fr"], ` +
      `[wire\\:model*="${fieldBaseName}"][wire\\:model*="fr"]`
  );
  if ((await frInput.count()) > 0) {
    await frInput.first().clear();
    await frInput.first().fill(valueFr);
  }
}

/**
 * Fill a translatable field using label-based selection (more reliable for Filament)
 */
export async function fillTranslatableFieldByLabel(
  page: Page,
  labelEn: string,
  valueEn: string,
  labelFr: string,
  valueFr: string
): Promise<void> {
  // Fill EN field by label
  const enField = page.getByLabel(labelEn, { exact: false });
  if ((await enField.count()) > 0) {
    await enField.first().clear();
    await enField.first().fill(valueEn);
  }

  // Fill FR field by label
  const frField = page.getByLabel(labelFr, { exact: false });
  if ((await frField.count()) > 0) {
    await frField.first().clear();
    await frField.first().fill(valueFr);
  }
}

/**
 * Toggle a feature/boolean field
 */
export async function toggleFeature(
  page: Page,
  featureName: string,
  enabled: boolean
): Promise<void> {
  // Try multiple selectors for Filament toggle
  const toggle = page
    .locator(`[name*="${featureName}"]`)
    .or(page.getByLabel(new RegExp(featureName, 'i')))
    .or(page.locator(`input[type="checkbox"][wire\\:model*="${featureName}"]`));

  const firstToggle = toggle.first();
  const isChecked = await firstToggle.isChecked().catch(() => false);

  if (isChecked !== enabled) {
    await firstToggle.click();
    await page.waitForTimeout(200); // Wait for Livewire update
  }
}

/**
 * Toggle a feature using its label (more reliable)
 */
export async function toggleFeatureByLabel(
  page: Page,
  label: string,
  enabled: boolean
): Promise<void> {
  // Find the toggle by its label
  const labelElement = page.locator(`label:has-text("${label}")`);
  const toggleContainer = labelElement
    .locator('..')
    .locator('[role="switch"], input[type="checkbox"]');

  if ((await toggleContainer.count()) > 0) {
    const isChecked = await toggleContainer
      .first()
      .isChecked()
      .catch(() => false);
    if (isChecked !== enabled) {
      await toggleContainer.first().click();
      await page.waitForTimeout(200);
    }
  } else {
    // Fallback: click on the label's parent if it contains a toggle
    const checkbox = labelElement.locator('~ input[type="checkbox"], ~ div input[type="checkbox"]');
    if ((await checkbox.count()) > 0) {
      const isChecked = await checkbox.first().isChecked();
      if (isChecked !== enabled) {
        await checkbox.first().click();
        await page.waitForTimeout(200);
      }
    }
  }
}

/**
 * Upload a file to a file input field
 */
export async function uploadFile(page: Page, fieldName: string, filePath: string): Promise<void> {
  // Filament uses various patterns for file upload
  const fileInput = page.locator(
    `input[type="file"][name*="${fieldName}"], ` +
      `input[type="file"][wire\\:model*="${fieldName}"], ` +
      `[data-field="${fieldName}"] input[type="file"]`
  );

  if ((await fileInput.count()) > 0) {
    await fileInput.first().setInputFiles(filePath);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000); // Wait for upload processing
  }
}

/**
 * Upload a file using label-based selection
 */
export async function uploadFileByLabel(
  page: Page,
  label: string,
  filePath: string
): Promise<void> {
  // Find upload area by label
  const uploadSection = page.locator(`*:has-text("${label}")`).first();
  const fileInput = uploadSection.locator('input[type="file"]').first();

  if ((await fileInput.count()) > 0) {
    await fileInput.setInputFiles(filePath);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
  }
}

/**
 * Set a numeric field value
 */
export async function setNumericField(page: Page, fieldName: string, value: number): Promise<void> {
  const input = page.locator(
    `input[name="${fieldName}"], input[type="number"][wire\\:model*="${fieldName}"]`
  );
  await input.clear();
  await input.fill(value.toString());
}

/**
 * Select an option from a select/dropdown field
 */
export async function selectOption(page: Page, fieldName: string, value: string): Promise<void> {
  const select = page.locator(`select[name="${fieldName}"], select[wire\\:model*="${fieldName}"]`);
  if ((await select.count()) > 0) {
    await select.selectOption(value);
  }
}

/**
 * Set a URL field value with validation
 */
export async function setUrlField(page: Page, fieldName: string, url: string): Promise<void> {
  const input = page.locator(
    `input[name="${fieldName}"], input[type="url"][wire\\:model*="${fieldName}"]`
  );
  await input.clear();
  await input.fill(url);
}

/**
 * Add an item to a JSON repeater field (like business_hours, commitments, etc.)
 */
export async function addRepeaterItem(
  page: Page,
  repeaterName: string,
  itemData: Record<string, string>
): Promise<void> {
  // Find and click the "Add" button for the repeater
  const addButton = page
    .locator(
      `[wire\\:click*="${repeaterName}.add"], ` +
        `button:has-text("Add ${repeaterName}"), ` +
        `button:has-text("Add Item")`
    )
    .first();

  if (await addButton.isVisible()) {
    await addButton.click();
    await page.waitForTimeout(500);

    // Fill the item fields
    for (const [field, value] of Object.entries(itemData)) {
      const input = page.locator(`input[name*="${repeaterName}"][name*="${field}"]`).last();
      if ((await input.count()) > 0) {
        await input.fill(value);
      }
    }
  }
}

// ============================================================================
// FRONTEND VERIFICATION HELPERS
// ============================================================================

/**
 * Navigate to frontend page
 */
export async function navigateToFrontend(
  page: Page,
  path: string,
  locale: 'en' | 'fr' = 'en'
): Promise<void> {
  const url = locale === 'fr' ? `${FRONTEND_URL}${path}` : `${FRONTEND_URL}/en${path}`;
  await page.goto(url);
  await page.waitForLoadState('networkidle');
}

/**
 * Navigate to homepage
 */
export async function navigateToHomepage(page: Page, locale: 'en' | 'fr' = 'en'): Promise<void> {
  await navigateToFrontend(page, '', locale);
}

/**
 * Navigate to about page
 */
export async function navigateToAboutPage(page: Page, locale: 'en' | 'fr' = 'en'): Promise<void> {
  await navigateToFrontend(page, '/about', locale);
}

/**
 * Verify text content is present on the page
 */
export async function verifyTextOnPage(page: Page, expectedText: string): Promise<void> {
  await expect(page.locator('body')).toContainText(expectedText, { timeout: 10000 });
}

/**
 * Verify text content is NOT present on the page
 */
export async function verifyTextNotOnPage(page: Page, unexpectedText: string): Promise<void> {
  await expect(page.locator('body')).not.toContainText(unexpectedText, { timeout: 5000 });
}

/**
 * Verify a section is visible on the page
 */
export async function verifySectionVisible(
  page: Page,
  sectionIdentifier: string
): Promise<boolean> {
  const section = page.locator(
    `section:has-text("${sectionIdentifier}"), ` +
      `[data-testid*="${sectionIdentifier.toLowerCase().replace(/\s+/g, '-')}"], ` +
      `[class*="${sectionIdentifier.toLowerCase().replace(/\s+/g, '-')}"]`
  );
  return await section.isVisible({ timeout: 5000 }).catch(() => false);
}

/**
 * Verify a section is NOT visible on the page
 */
export async function verifySectionHidden(page: Page, sectionIdentifier: string): Promise<boolean> {
  const section = page.locator(
    `section:has-text("${sectionIdentifier}"), ` +
      `[data-testid*="${sectionIdentifier.toLowerCase().replace(/\s+/g, '-')}"]`
  );
  const isVisible = await section.isVisible({ timeout: 2000 }).catch(() => false);
  return !isVisible;
}

/**
 * Verify a meta tag has expected content
 */
export async function verifyMetaTag(
  page: Page,
  metaName: string,
  expectedContent: string
): Promise<void> {
  const metaTag = page.locator(`meta[name="${metaName}"], meta[property="${metaName}"]`);
  await expect(metaTag).toHaveAttribute('content', expectedContent, { timeout: 5000 });
}

/**
 * Verify Open Graph meta tag
 */
export async function verifyOgTag(
  page: Page,
  property: string,
  expectedContent: string
): Promise<void> {
  const ogTag = page.locator(`meta[property="og:${property}"]`);
  await expect(ogTag).toHaveAttribute('content', expectedContent, { timeout: 5000 });
}

/**
 * Verify favicon link is present
 */
export async function verifyFaviconPresent(page: Page): Promise<void> {
  const favicon = page.locator('link[rel="icon"], link[rel="shortcut icon"]');
  await expect(favicon).toBeVisible();
}

/**
 * Verify logo image is present in header
 */
export async function verifyLogoInHeader(page: Page): Promise<boolean> {
  const logo = page.locator('header img, nav img, [data-testid="logo"] img');
  return await logo.isVisible({ timeout: 5000 }).catch(() => false);
}

/**
 * Get footer contact info
 */
export async function getFooterContactInfo(page: Page): Promise<{
  email?: string;
  phone?: string;
  address?: string;
}> {
  const footer = page.locator('footer');
  const footerText = (await footer.textContent()) || '';

  return {
    email: footerText.match(/[\w.-]+@[\w.-]+\.\w+/)?.[0],
    phone: footerText.match(/\+?\d[\d\s-]{8,}/)?.[0],
    address: undefined, // Would need more specific parsing
  };
}

/**
 * Get footer social links
 */
export async function getFooterSocialLinks(page: Page): Promise<string[]> {
  const socialLinks = page.locator(
    'footer a[href*="facebook"], footer a[href*="instagram"], footer a[href*="twitter"], footer a[href*="linkedin"], footer a[href*="youtube"], footer a[href*="tiktok"]'
  );
  const links: string[] = [];
  const count = await socialLinks.count();

  for (let i = 0; i < count; i++) {
    const href = await socialLinks.nth(i).getAttribute('href');
    if (href) links.push(href);
  }

  return links;
}

/**
 * Verify newsletter section is visible on homepage
 */
export async function verifyNewsletterSection(page: Page): Promise<boolean> {
  const newsletterSection = page.locator('section').filter({
    has: page.locator('input[type="email"]'),
  });
  return await newsletterSection.isVisible({ timeout: 5000 }).catch(() => false);
}

/**
 * Verify blog section is visible on homepage
 */
export async function verifyBlogSection(page: Page): Promise<boolean> {
  const blogSection = page.locator(
    'section:has-text("Blog"), section:has-text("Latest"), section:has-text("Articles")'
  );
  return await blogSection.isVisible({ timeout: 5000 }).catch(() => false);
}

/**
 * Count items in a section (e.g., blog posts, packages)
 */
export async function countSectionItems(
  page: Page,
  sectionSelector: string,
  itemSelector: string
): Promise<number> {
  const section = page.locator(sectionSelector);
  const items = section.locator(itemSelector);
  return await items.count();
}

/**
 * Verify GA4 script is injected
 */
export async function verifyGa4Injected(page: Page, measurementId: string): Promise<boolean> {
  const gaScript = page.locator(`script[src*="gtag"][src*="${measurementId}"]`);
  const isPresent = (await gaScript.count()) > 0;

  if (!isPresent) {
    // Check inline script
    const inlineScript = await page.locator('script').allTextContents();
    return inlineScript.some((s) => s.includes(measurementId));
  }

  return isPresent;
}

// ============================================================================
// FIELD VALUE GETTERS (for verification after save)
// ============================================================================

/**
 * Get current value of a text field
 */
export async function getFieldValue(page: Page, fieldName: string): Promise<string> {
  const input = page.locator(
    `input[name="${fieldName}"], input[wire\\:model*="${fieldName}"], textarea[name="${fieldName}"]`
  );
  return await input.inputValue();
}

/**
 * Get current value of a toggle/checkbox
 */
export async function getToggleValue(page: Page, fieldName: string): Promise<boolean> {
  const toggle = page.locator(
    `input[type="checkbox"][name*="${fieldName}"], input[type="checkbox"][wire\\:model*="${fieldName}"]`
  );
  return await toggle.isChecked();
}

/**
 * Verify field has expected value
 */
export async function verifyFieldValue(
  page: Page,
  fieldName: string,
  expectedValue: string
): Promise<void> {
  const input = page.locator(
    `input[name="${fieldName}"], input[wire\\:model*="${fieldName}"], textarea[name="${fieldName}"]`
  );
  await expect(input).toHaveValue(expectedValue);
}

// ============================================================================
// UTILITY HELPERS
// ============================================================================

/**
 * Reload page and wait for form to load
 */
export async function reloadAndWaitForForm(page: Page): Promise<void> {
  await page.reload();
  await page.waitForLoadState('networkidle');
  await page.waitForSelector('form', { timeout: 10000 });
}

/**
 * Generate unique test value to avoid conflicts
 */
export function generateTestValue(prefix: string): string {
  return `${prefix}-${Date.now()}`;
}

/**
 * Wait for Livewire/Alpine to update
 */
export async function waitForLivewireUpdate(page: Page): Promise<void> {
  await page.waitForTimeout(500);
  await page.waitForLoadState('networkidle');
}

/**
 * Scroll to element if needed
 */
export async function scrollToElement(page: Page, selector: string): Promise<void> {
  const element = page.locator(selector).first();
  await element.scrollIntoViewIfNeeded();
}

/**
 * Check if a tab is currently active
 */
export async function isTabActive(page: Page, tabName: string): Promise<boolean> {
  const tab = page.getByRole('tab', { name: new RegExp(tabName, 'i') });
  const ariaSelected = await tab.getAttribute('aria-selected');
  return ariaSelected === 'true';
}

/**
 * Get all visible tabs
 */
export async function getVisibleTabs(page: Page): Promise<string[]> {
  const tabs = page.getByRole('tab');
  const count = await tabs.count();
  const tabNames: string[] = [];

  for (let i = 0; i < count; i++) {
    const name = await tabs.nth(i).textContent();
    if (name) tabNames.push(name.trim());
  }

  return tabNames;
}
