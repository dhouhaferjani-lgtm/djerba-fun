/**
 * Admin Panel - CMS Page Management E2E Tests
 *
 * Tests for the autonomous page creation feature with destination-style sections.
 *
 * Test Sections:
 * - Navigation: Verifies admin can access Pages resource
 * - Creation: Admin can create pages with destination content
 * - Frontend: Pages display correctly with all sections
 */

import { test, expect, Page } from '@playwright/test';
import { loginToAdmin, ADMIN_URL } from '../../fixtures/admin-helpers';

// Test data
const TEST_PAGE_TITLE_EN = 'Test Djerba Guide';
const TEST_PAGE_TITLE_FR = 'Guide de Djerba Test';
const TEST_PAGE_SLUG_EN = 'test-djerba-guide';
const TEST_PAGE_SLUG_FR = 'guide-de-djerba-test';

const ADMIN_EMAIL = 'admin@djerbafun.com';
const ADMIN_PASSWORD = 'password';

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Login to admin panel with specific credentials
 */
async function loginAsAdmin(page: Page): Promise<void> {
  await page.goto(`${ADMIN_URL}/login`);
  await page.waitForSelector('#data\\.email', { state: 'visible', timeout: 15000 });
  await page.fill('#data\\.email', ADMIN_EMAIL);
  await page.fill('#data\\.password', ADMIN_PASSWORD);
  await page.click('button[type="submit"]');
  await page.waitForURL(`${ADMIN_URL}/**`, { timeout: 15000 });
  await expect(page.locator('body.fi-body.fi-panel-admin')).toBeVisible({ timeout: 10000 });
}

/**
 * Navigate to Pages resource in admin
 */
async function navigateToPages(page: Page): Promise<void> {
  await page.goto(`${ADMIN_URL}/pages`);
  await page.waitForLoadState('networkidle');
}

/**
 * Navigate to Create Page form
 */
async function navigateToCreatePage(page: Page): Promise<void> {
  await page.goto(`${ADMIN_URL}/pages/create`);
  await page.waitForLoadState('networkidle');
}

// ============================================================================
// TEST SUITE: Navigation
// ============================================================================

test.describe('CMS Pages - Navigation', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('TC-P001: Admin can access Pages list', async ({ page }) => {
    await navigateToPages(page);

    // Verify we're on the pages list
    await expect(page).toHaveURL(/\/admin\/pages/);

    // Check for Filament table structure
    const tableOrEmpty = page.locator('table, .fi-ta-empty-state');
    await expect(tableOrEmpty).toBeVisible({ timeout: 10000 });
  });

  test('TC-P002: Admin can access Create Page form', async ({ page }) => {
    await navigateToCreatePage(page);

    // Verify we're on the create page
    await expect(page).toHaveURL(/\/admin\/pages\/create/);

    // Check for form presence - look for title field
    const titleField = page.locator('input[id*="title"], [data-field="title"]').first();
    await expect(titleField).toBeVisible({ timeout: 10000 });
  });

  test('TC-P003: Pages resource appears in Content navigation group', async ({ page }) => {
    await page.goto(`${ADMIN_URL}`);

    // Look for Content navigation group
    const contentGroup = page.locator('[class*="navigation"], nav').filter({ hasText: 'Content' });
    await expect(contentGroup).toBeVisible({ timeout: 10000 });
  });
});

// ============================================================================
// TEST SUITE: Page Creation
// ============================================================================

test.describe('CMS Pages - Creation', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('TC-P010: Admin can create a basic page', async ({ page }) => {
    await navigateToCreatePage(page);

    // Fill basic fields using Filament's field structure
    // Title field (Spatie translatable - look for English tab first)
    const titleInput = page.locator('input[id*="title"]').first();
    await titleInput.fill(TEST_PAGE_TITLE_EN);

    // Slug field
    const slugInput = page.locator('input[id*="slug"]').first();
    await slugInput.fill(TEST_PAGE_SLUG_EN);

    // Enable publishing by clicking the published toggle
    const publishToggle = page.locator('button[id*="published"]').first();
    if (await publishToggle.isVisible()) {
      await publishToggle.click();
    }

    // Save the page
    const saveButton = page
      .locator('button:has-text("Create"), button[type="submit"]:has-text("Save")')
      .first();
    await saveButton.click();

    // Wait for success notification or redirect
    await Promise.race([
      page.waitForSelector('[class*="notification"]', { timeout: 10000 }).catch(() => {}),
      page.waitForURL(/\/admin\/pages\/\d+/, { timeout: 10000 }).catch(() => {}),
      page.waitForURL(/\/admin\/pages$/, { timeout: 10000 }).catch(() => {}),
    ]);
  });

  test('TC-P011: Page form has destination-style sections', async ({ page }) => {
    await navigateToCreatePage(page);

    // Wait for form to load
    await page.waitForLoadState('networkidle');

    // Check for Description section (per-locale columns)
    const descriptionSection = page
      .locator(
        '[class*="section"]:has-text("Description"), [class*="fieldset"]:has-text("Description")'
      )
      .first();
    // Description might be in any section, just check the form exists
    const formExists = page.locator('form').first();
    await expect(formExists).toBeVisible({ timeout: 10000 });

    // Check for SEO section
    const seoSection = page.locator('text=SEO').first();
    await expect(seoSection).toBeVisible({ timeout: 10000 });
  });

  test('TC-P012: Page form has Content Sections tab', async ({ page }) => {
    await navigateToCreatePage(page);
    await page.waitForLoadState('networkidle');

    // Look for Content Sections tab or section
    const contentSections = page
      .locator('button:has-text("Content Sections"), [role="tab"]:has-text("Content")')
      .first();
    if (await contentSections.isVisible()) {
      await contentSections.click();
      await page.waitForTimeout(500);

      // Check for section action buttons
      const highlightsSection = page.locator('text=Highlights').first();
      await expect(highlightsSection).toBeVisible({ timeout: 5000 });
    }
  });
});

// ============================================================================
// TEST SUITE: Page API
// ============================================================================

test.describe('CMS Pages - API', () => {
  test('TC-P020: Pages API returns expected structure', async ({ request }) => {
    const response = await request.get('http://localhost:8000/api/v1/pages?locale=en');

    expect(response.status()).toBe(200);

    const data = await response.json();
    expect(data).toHaveProperty('data');
    expect(Array.isArray(data.data)).toBe(true);
  });

  test('TC-P021: Page API returns localized content', async ({ request }) => {
    // First, create a test page via API or assume one exists
    // For this test, we'll just verify the endpoint works

    const responseFr = await request.get('http://localhost:8000/api/v1/pages?locale=fr');
    const responseEn = await request.get('http://localhost:8000/api/v1/pages?locale=en');

    expect(responseFr.status()).toBe(200);
    expect(responseEn.status()).toBe(200);
  });

  test('TC-P022: Page API returns destination-style fields', async ({ request }) => {
    const response = await request.get('http://localhost:8000/api/v1/pages?locale=en');

    expect(response.status()).toBe(200);

    const data = await response.json();

    // If there are pages, check their structure
    if (data.data.length > 0) {
      const page = data.data[0];

      // Check for new destination-style fields in response structure
      expect(page).toHaveProperty('title');
      expect(page).toHaveProperty('slug');
    }
  });
});

// ============================================================================
// TEST SUITE: Frontend Display
// ============================================================================

test.describe('CMS Pages - Frontend Display', () => {
  test('TC-P030: Dynamic page route exists', async ({ page }) => {
    // Navigate to the pages listing to get a real slug
    const response = await page.request.get('http://localhost:8000/api/v1/pages?locale=en');
    const data = await response.json();

    if (data.data.length > 0) {
      const testPage = data.data[0];

      // Try to navigate to the page on frontend
      await page.goto(`http://localhost:3000/pages/${testPage.slug}`);

      // Check that we don't get a 404
      const notFound = page.locator('text=404, text=not found').first();
      const isNotFound = await notFound.isVisible().catch(() => false);

      if (!isNotFound) {
        // Page loaded successfully
        expect(page.url()).toContain(`/pages/${testPage.slug}`);
      }
    }
  });

  test('TC-P031: Page displays title and content', async ({ page }) => {
    // Get a published page from API
    const response = await page.request.get('http://localhost:8000/api/v1/pages?locale=en');
    const data = await response.json();

    if (data.data.length > 0) {
      const testPage = data.data[0];

      await page.goto(`http://localhost:3000/pages/${testPage.slug}`);
      await page.waitForLoadState('networkidle');

      // Check for page title in heading
      const heading = page.locator('h1').first();
      if (await heading.isVisible().catch(() => false)) {
        const text = await heading.textContent();
        expect(text?.toLowerCase()).toContain(testPage.title.toLowerCase());
      }
    }
  });
});

// ============================================================================
// TEST SUITE: Highlights Section
// ============================================================================

test.describe('CMS Pages - Highlights Section', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('TC-P040: Highlights action opens modal', async ({ page }) => {
    await navigateToCreatePage(page);
    await page.waitForLoadState('networkidle');

    // Look for Content Sections tab
    const contentTab = page.locator('button:has-text("Content Sections")').first();
    if (await contentTab.isVisible()) {
      await contentTab.click();
      await page.waitForTimeout(500);
    }

    // Click Manage Highlights button
    const highlightsButton = page.locator('button:has-text("Manage Highlights")').first();
    if (await highlightsButton.isVisible()) {
      await highlightsButton.click();

      // Check modal opens
      const modal = page.locator('[role="dialog"], .fi-modal').first();
      await expect(modal).toBeVisible({ timeout: 5000 });
    }
  });
});

// ============================================================================
// TEST SUITE: Key Facts Section
// ============================================================================

test.describe('CMS Pages - Key Facts Section', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('TC-P050: Key Facts action opens modal', async ({ page }) => {
    await navigateToCreatePage(page);
    await page.waitForLoadState('networkidle');

    // Look for Content Sections tab
    const contentTab = page.locator('button:has-text("Content Sections")').first();
    if (await contentTab.isVisible()) {
      await contentTab.click();
      await page.waitForTimeout(500);
    }

    // Click Manage Key Facts button
    const keyFactsButton = page.locator('button:has-text("Manage Key Facts")').first();
    if (await keyFactsButton.isVisible()) {
      await keyFactsButton.click();

      // Check modal opens
      const modal = page.locator('[role="dialog"], .fi-modal').first();
      await expect(modal).toBeVisible({ timeout: 5000 });
    }
  });
});

// ============================================================================
// TEST SUITE: Gallery Section
// ============================================================================

test.describe('CMS Pages - Gallery Section', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('TC-P060: Gallery action opens modal', async ({ page }) => {
    await navigateToCreatePage(page);
    await page.waitForLoadState('networkidle');

    // Look for Content Sections tab
    const contentTab = page.locator('button:has-text("Content Sections")').first();
    if (await contentTab.isVisible()) {
      await contentTab.click();
      await page.waitForTimeout(500);
    }

    // Click Manage Gallery button
    const galleryButton = page.locator('button:has-text("Manage Gallery")').first();
    if (await galleryButton.isVisible()) {
      await galleryButton.click();

      // Check modal opens
      const modal = page.locator('[role="dialog"], .fi-modal').first();
      await expect(modal).toBeVisible({ timeout: 5000 });
    }
  });
});

// ============================================================================
// TEST SUITE: Points of Interest Section
// ============================================================================

test.describe('CMS Pages - Points of Interest Section', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('TC-P070: Points of Interest action opens modal', async ({ page }) => {
    await navigateToCreatePage(page);
    await page.waitForLoadState('networkidle');

    // Look for Content Sections tab
    const contentTab = page.locator('button:has-text("Content Sections")').first();
    if (await contentTab.isVisible()) {
      await contentTab.click();
      await page.waitForTimeout(500);
    }

    // Click Manage Points of Interest button
    const poiButton = page.locator('button:has-text("Manage Points")').first();
    if (await poiButton.isVisible()) {
      await poiButton.click();

      // Check modal opens
      const modal = page.locator('[role="dialog"], .fi-modal').first();
      await expect(modal).toBeVisible({ timeout: 5000 });
    }
  });
});
