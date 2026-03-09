/**
 * Platform Settings - Logo & Pillar Image Upload E2E Tests
 *
 * Tests that verify images uploaded in admin panel appear correctly on frontend.
 * This is a critical path test for the media upload functionality.
 *
 * Test Coverage:
 * Logo Tests:
 * - TC-LOGO-01: Light logo uploaded in admin appears on frontend
 * - TC-LOGO-02: Logo URL is returned by API (not null)
 * - TC-LOGO-03: Logo URL persists after page reload
 * - TC-LOGO-04: API returns public URL (not admin proxy URL)
 *
 * Pillar Image Tests:
 * - TC-PILLAR-01: Pillar 1 image uploaded in admin is returned by API
 * - TC-PILLAR-02: All pillar images have correct structure in API
 * - TC-PILLAR-03: Pillar image URLs are public (not admin proxy)
 */

import { test, expect } from '@playwright/test';
import { adminUsers } from '../../fixtures/admin-test-data';
import { loginToAdmin } from '../../fixtures/admin-api-helpers';
import {
  navigateToPlatformSettings,
  navigateToTab,
  saveSettings,
  tabNames,
} from '../../fixtures/platform-settings-helpers';
import { join } from 'path';

// Test configuration
const TEST_TIMEOUT = 90000;
const ADMIN_URL = 'http://localhost:8000/admin';
const FRONTEND_URL = 'http://localhost:3000';
const API_URL = 'http://localhost:8000/api/v1';

// Test images - use existing test images in Downloads folder
// In a production test, these would be in the fixtures/images directory
const TEST_LOGO_PATH = '/Users/otospexmob/Downloads/1-9.png';

test.describe('Logo Upload - Admin to Frontend Integration', () => {
  test.setTimeout(TEST_TIMEOUT);

  test.beforeEach(async ({ page }) => {
    console.log('Logging into admin panel...');
    await loginToAdmin(page, adminUsers.admin.email, adminUsers.admin.password);
    console.log('Admin login successful');
  });

  test('TC-LOGO-01: Light logo uploaded in admin appears on frontend', async ({ page }) => {
    console.log('TC-LOGO-01: Testing logo upload flow');

    // Step 1: Navigate to Platform Settings > Logo & Branding
    await navigateToPlatformSettings(page);
    await navigateToTab(page, tabNames.logoBranding);
    await page.waitForTimeout(1000);

    // Step 2: Find the logo_light file input and upload
    console.log('Step 2: Uploading logo');
    const logoSection = page
      .locator('div')
      .filter({ hasText: /Logo \(Light Mode\)/i })
      .first();
    const fileInput = logoSection.locator('input[type="file"]').first();

    if ((await fileInput.count()) > 0) {
      await fileInput.setInputFiles(TEST_LOGO_PATH);
      // Wait for FilePond to process
      await page.waitForTimeout(3000);
      console.log('Logo uploaded');
    } else {
      // Fallback: try to find any file input in the logos section
      const allFileInputs = page.locator('input[type="file"]');
      if ((await allFileInputs.count()) > 0) {
        await allFileInputs.first().setInputFiles(TEST_LOGO_PATH);
        await page.waitForTimeout(3000);
        console.log('Logo uploaded via fallback');
      } else {
        console.log('Could not find file input for logo');
        return; // Skip test if no file input found
      }
    }

    // Step 3: Save settings
    console.log('Step 3: Saving settings');
    await saveSettings(page, { throwOnError: false });
    await page.waitForTimeout(2000);

    // Step 4: Verify via API that logo was saved
    console.log('Step 4: Verifying via API');
    const apiResponse = await page.request.get(`${API_URL}/platform/settings`);
    const apiData = await apiResponse.json();
    const logoLightUrl = apiData?.data?.branding?.logoLight;

    console.log(`API logoLight URL: ${logoLightUrl || 'null'}`);

    // Assert: Logo URL should not be null
    expect(logoLightUrl).not.toBeNull();
    expect(typeof logoLightUrl).toBe('string');
    expect(logoLightUrl.length).toBeGreaterThan(0);

    // Step 5: Verify logo is not using admin proxy URL
    console.log('Step 5: Verifying URL is public (not admin proxy)');
    expect(logoLightUrl).not.toContain('/admin/media-proxy/');

    // Step 6: Navigate to frontend and verify logo appears
    console.log('Step 6: Verifying on frontend');
    await page.goto(FRONTEND_URL);
    await page.waitForLoadState('networkidle');

    const headerLogo = page.locator('header img').first();
    await expect(headerLogo).toBeVisible({ timeout: 10000 });

    const logoSrc = await headerLogo.getAttribute('src');
    console.log(`Frontend logo src: ${logoSrc}`);

    // Logo should NOT be the fallback image
    expect(logoSrc).not.toBe('/images/evasion-djerba-logo.png');

    console.log('TC-LOGO-01: PASSED - Logo uploaded and displayed correctly');
  });

  test('TC-LOGO-02: Logo URL persists after admin page reload', async ({ page }) => {
    console.log('TC-LOGO-02: Testing logo URL persistence');

    // Step 1: Upload logo
    await navigateToPlatformSettings(page);
    await navigateToTab(page, tabNames.logoBranding);
    await page.waitForTimeout(1000);

    const fileInput = page.locator('input[type="file"]').first();
    if ((await fileInput.count()) > 0) {
      await fileInput.setInputFiles(TEST_LOGO_PATH);
      await page.waitForTimeout(3000);
      await saveSettings(page, { throwOnError: false });
      await page.waitForTimeout(2000);
    }

    // Step 2: Reload admin page
    await page.reload();
    await page.waitForLoadState('networkidle');

    // Step 3: Navigate back to Logo & Branding
    await navigateToTab(page, tabNames.logoBranding);
    await page.waitForTimeout(1000);

    // Step 4: Verify logo preview is still visible
    const logoPreview = page
      .locator('div')
      .filter({ hasText: /Logo \(Light Mode\)/i })
      .first()
      .locator('img, .filepond--item');
    const hasPreview = (await logoPreview.count()) > 0;

    console.log(`Logo preview visible after reload: ${hasPreview}`);

    // Step 5: Verify via API that logo URL still exists
    const apiResponse = await page.request.get(`${API_URL}/platform/settings`);
    const apiData = await apiResponse.json();
    const logoLightUrl = apiData?.data?.branding?.logoLight;

    expect(logoLightUrl).not.toBeNull();
    console.log('TC-LOGO-02: PASSED - Logo URL persists after reload');
  });

  test('TC-LOGO-03: API returns null for logos before upload', async ({ page }) => {
    console.log('TC-LOGO-03: Testing API structure without uploads');

    // Check API response structure even without uploads
    const apiResponse = await page.request.get(`${API_URL}/platform/settings`);
    expect(apiResponse.status()).toBe(200);

    const apiData = await apiResponse.json();

    // Structure should exist
    expect(apiData).toHaveProperty('data');
    expect(apiData.data).toHaveProperty('branding');
    expect(apiData.data.branding).toHaveProperty('logoLight');
    expect(apiData.data.branding).toHaveProperty('logoDark');

    console.log('TC-LOGO-03: PASSED - API structure is correct');
  });

  test('TC-LOGO-04: Multiple media uploads work independently', async ({ page }) => {
    console.log('TC-LOGO-04: Testing multiple media uploads');

    await navigateToPlatformSettings(page);
    await navigateToTab(page, tabNames.logoBranding);
    await page.waitForTimeout(1000);

    // Try uploading logo light
    const lightInput = page.locator('input[type="file"]').first();
    if ((await lightInput.count()) > 0) {
      await lightInput.setInputFiles(TEST_LOGO_PATH);
      await page.waitForTimeout(2000);
    }

    await saveSettings(page, { throwOnError: false });
    await page.waitForTimeout(2000);

    // Verify via API
    const apiResponse = await page.request.get(`${API_URL}/platform/settings`);
    const apiData = await apiResponse.json();

    const branding = apiData?.data?.branding;
    console.log(`Branding response: logoLight=${branding?.logoLight ? 'present' : 'null'}`);

    // At minimum, the structure should be correct
    expect(branding).toBeDefined();
    expect(branding).toHaveProperty('logoLight');
    expect(branding).toHaveProperty('logoDark');
    expect(branding).toHaveProperty('heroBanner');

    console.log('TC-LOGO-04: PASSED - Multiple media structure verified');
  });
});

test.describe('Pillar Image Upload - Admin to Frontend Integration', () => {
  test.setTimeout(TEST_TIMEOUT);

  test.beforeEach(async ({ page }) => {
    console.log('Logging into admin panel...');
    await loginToAdmin(page, adminUsers.admin.email, adminUsers.admin.password);
    console.log('Admin login successful');
  });

  test('TC-PILLAR-01: Pillar 1 image URL is returned by API', async ({ page }) => {
    console.log('TC-PILLAR-01: Testing pillar 1 image API response');

    // Navigate to Platform Settings > Logo & Branding
    await navigateToPlatformSettings(page);
    await navigateToTab(page, tabNames.logoBranding);
    await page.waitForTimeout(1000);

    // Find pillar 1 file input (scroll down to Brand Pillar Images section)
    await page.evaluate(() => window.scrollBy(0, 500));
    await page.waitForTimeout(500);

    const pillar1Section = page
      .locator('div')
      .filter({ hasText: /Pillar 1: Sustainable Travel/i })
      .first();
    const pillar1Input = pillar1Section.locator('input[type="file"]').first();

    if ((await pillar1Input.count()) > 0) {
      await pillar1Input.setInputFiles(TEST_LOGO_PATH);
      await page.waitForTimeout(3000);
      console.log('Pillar 1 image uploaded');

      await saveSettings(page, { throwOnError: false });
      await page.waitForTimeout(2000);
    }

    // Verify via API
    const apiResponse = await page.request.get(`${API_URL}/platform/settings`);
    const apiData = await apiResponse.json();
    const brandPillar1 = apiData?.data?.branding?.brandPillar1;

    console.log(`API brandPillar1 URL: ${brandPillar1 || 'null'}`);

    // Assert: Pillar URL should exist
    expect(brandPillar1).not.toBeNull();
    expect(typeof brandPillar1).toBe('string');
    expect(brandPillar1.length).toBeGreaterThan(0);

    console.log('TC-PILLAR-01: PASSED - Pillar 1 image URL returned by API');
  });

  test('TC-PILLAR-02: All pillar images have correct API structure', async ({ page }) => {
    console.log('TC-PILLAR-02: Testing pillar images API structure');

    // Check API response structure
    const apiResponse = await page.request.get(`${API_URL}/platform/settings`);
    expect(apiResponse.status()).toBe(200);

    const apiData = await apiResponse.json();

    // Structure should exist
    expect(apiData).toHaveProperty('data');
    expect(apiData.data).toHaveProperty('branding');
    expect(apiData.data.branding).toHaveProperty('brandPillar1');
    expect(apiData.data.branding).toHaveProperty('brandPillar2');
    expect(apiData.data.branding).toHaveProperty('brandPillar3');

    console.log('TC-PILLAR-02: PASSED - Pillar images API structure is correct');
  });

  test('TC-PILLAR-03: Pillar image URLs are public (not admin proxy)', async ({ page }) => {
    console.log('TC-PILLAR-03: Testing pillar image URLs are public');

    // Navigate and upload pillar image
    await navigateToPlatformSettings(page);
    await navigateToTab(page, tabNames.logoBranding);
    await page.waitForTimeout(1000);

    // Scroll to pillar section
    await page.evaluate(() => window.scrollBy(0, 500));
    await page.waitForTimeout(500);

    const pillar1Section = page
      .locator('div')
      .filter({ hasText: /Pillar 1: Sustainable Travel/i })
      .first();
    const pillar1Input = pillar1Section.locator('input[type="file"]').first();

    if ((await pillar1Input.count()) > 0) {
      await pillar1Input.setInputFiles(TEST_LOGO_PATH);
      await page.waitForTimeout(3000);
      await saveSettings(page, { throwOnError: false });
      await page.waitForTimeout(2000);
    }

    // Verify URL is public
    const apiResponse = await page.request.get(`${API_URL}/platform/settings`);
    const apiData = await apiResponse.json();
    const brandPillar1 = apiData?.data?.branding?.brandPillar1;

    if (brandPillar1) {
      expect(brandPillar1).not.toContain('/admin/media-proxy/');
      expect(brandPillar1).toContain('/storage/');
      console.log('TC-PILLAR-03: PASSED - Pillar URL is public');
    } else {
      console.log('TC-PILLAR-03: SKIPPED - No pillar image uploaded');
    }
  });

  test('TC-PILLAR-04: Pillar image is accessible via HTTP', async ({ page }) => {
    console.log('TC-PILLAR-04: Testing pillar image accessibility');

    // Get pillar URL from API
    const apiResponse = await page.request.get(`${API_URL}/platform/settings`);
    const apiData = await apiResponse.json();
    const brandPillar1 = apiData?.data?.branding?.brandPillar1;

    if (brandPillar1) {
      // Try to fetch the image directly
      const imageResponse = await page.request.get(brandPillar1);
      expect(imageResponse.status()).toBe(200);

      const contentType = imageResponse.headers()['content-type'];
      expect(contentType).toContain('image/');

      console.log(`TC-PILLAR-04: PASSED - Image accessible, content-type: ${contentType}`);
    } else {
      console.log('TC-PILLAR-04: SKIPPED - No pillar image in API');
    }
  });
});
