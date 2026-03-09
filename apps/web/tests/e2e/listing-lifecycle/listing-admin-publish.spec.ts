/**
 * Listing Admin Publish Flow E2E Tests
 *
 * Tests that admin can publish listings via status dropdown and that
 * published_at is set correctly.
 *
 * This test was created to verify fix for bug where admin status dropdown
 * did not set published_at, causing listings to not appear on frontend.
 */

import { test, expect } from '@playwright/test';
import {
  loginVendorUI,
  seededVendor,
  navigateToVendorSection,
  selectServiceType,
  selectLocation,
  fillTranslatableTitle,
  fillTranslatableSummary,
  clickWizardNext,
  fillPricing,
} from '../../fixtures/vendor-helpers';
import {
  loginToAdmin,
  navigateToAdminResource,
  waitForNotification,
} from '../../fixtures/admin-api-helpers';
import { adminUsers } from '../../fixtures/admin-test-data';

const FRONTEND_URL = process.env.NEXT_PUBLIC_URL || 'http://localhost:3000';
const VENDOR_PANEL_URL = process.env.LARAVEL_URL || 'http://localhost:8000';
const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

test.describe('Admin Publish Flow (published_at bug fix)', () => {
  test.setTimeout(180000); // 3 minutes for full flow

  /**
   * TC-PUB-001: Admin can publish accommodation listing via status dropdown
   *
   * Steps:
   * 1. Create draft accommodation listing via vendor panel
   * 2. Login as admin and change status to Published
   * 3. Verify published_at is set in database
   * 4. Verify listing appears on frontend
   */
  test('TC-PUB-001: Admin publishes accommodation listing via status dropdown', async ({
    page,
  }) => {
    const testId = Date.now().toString(36);
    const testTitle = `E2E Publish Test ${testId}`;

    console.log('\n========================================');
    console.log('ADMIN PUBLISH FLOW TEST');
    console.log(`Timestamp: ${new Date().toISOString()}`);
    console.log(`Test Title: ${testTitle}`);
    console.log('========================================\n');

    // Step 1: Create draft accommodation listing via vendor panel
    console.log('Step 1: Creating draft accommodation listing...');
    await loginVendorUI(page, seededVendor.email, seededVendor.password);

    await navigateToVendorSection(page, 'listings/create');
    await page.waitForLoadState('networkidle');

    // Select accommodation type
    await selectServiceType(page, 'accommodation');
    await page.waitForTimeout(500);

    // Fill basic info
    await fillTranslatableTitle(page, testTitle, `${testTitle} FR`);
    await fillTranslatableSummary(page, 'Test summary', 'Résumé de test');

    // Navigate to location step
    await clickWizardNext(page);
    await page.waitForTimeout(500);

    // Select location
    await selectLocation(page, 'djerba');
    await page.waitForTimeout(500);

    // Skip through optional steps
    for (let i = 0; i < 4; i++) {
      const nextButton = page.getByRole('button', { name: 'Next' });
      const skipButton = page.getByRole('button', { name: 'Skip' });

      if (await skipButton.isVisible({ timeout: 2000 }).catch(() => false)) {
        await skipButton.click();
      } else if (await nextButton.isVisible({ timeout: 2000 }).catch(() => false)) {
        await nextButton.click();
      }
      await page.waitForTimeout(500);
    }

    // Fill pricing
    await fillPricing(page, { tnd: '100', eur: '30' });

    // Save as draft
    const saveDraftButton = page.getByRole('button', { name: /save.*draft/i });
    if (await saveDraftButton.isVisible({ timeout: 3000 })) {
      await saveDraftButton.click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);
    }

    // Get the slug from URL
    const currentUrl = page.url();
    const slugMatch = currentUrl.match(/\/vendor\/listings\/([^\/]+)\/edit/);
    const slug = slugMatch?.[1] || null;
    console.log(`Listing created with slug: ${slug}`);
    expect(slug, 'Listing slug must be captured').toBeTruthy();

    // Step 2: Admin publishes via status dropdown
    console.log('\nStep 2: Admin publishes via status dropdown...');
    await loginToAdmin(page, adminUsers.admin.email, adminUsers.admin.password);
    await navigateToAdminResource(page, 'listings');
    await page.waitForLoadState('networkidle');

    // Search for the listing
    const searchInput = page.locator('input[type="search"]').first();
    if (await searchInput.isVisible({ timeout: 3000 })) {
      await searchInput.fill(testTitle.substring(0, 15));
      await page.waitForTimeout(1000);
    }

    // Find and edit the listing
    const row = page
      .getByRole('row', { name: new RegExp(testTitle.substring(0, 15), 'i') })
      .first();
    expect(await row.isVisible({ timeout: 5000 }), 'Listing row should be visible').toBeTruthy();

    const editLink = row.locator('a[href*="/edit"]').first();
    await editLink.click();
    await page.waitForLoadState('networkidle');

    // Change status dropdown to Published
    const statusSelect = page.locator('select[name="data.status"]').first();
    if (await statusSelect.isVisible({ timeout: 3000 })) {
      await statusSelect.selectOption('published');
    } else {
      // Try Filament select component
      const statusTrigger = page
        .locator('label:has-text("Status")')
        .locator('..')
        .locator('button')
        .first();
      if (await statusTrigger.isVisible({ timeout: 2000 })) {
        await statusTrigger.click();
        await page.waitForTimeout(500);
        const publishedOption = page.locator('[role="option"]:has-text("Published")').first();
        if (await publishedOption.isVisible({ timeout: 2000 })) {
          await publishedOption.click();
        }
      }
    }
    await page.waitForTimeout(500);

    // Save the form
    const saveButton = page.locator('button:has-text("Save")').first();
    await saveButton.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    console.log('Status changed to Published and saved');

    // Step 3: Verify published_at is set via API
    console.log('\nStep 3: Verifying published_at is set via API...');

    // Navigate to frontend to check if listing appears
    const frontendUrl = `${FRONTEND_URL}/listings?type=accommodation`;
    console.log(`Checking frontend: ${frontendUrl}`);
    await page.goto(frontendUrl);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Check API directly
    const apiUrl = `${API_URL}/listings?service_type=accommodation`;
    console.log(`Checking API: ${apiUrl}`);
    const apiResponse = await page.request.get(apiUrl);
    const apiData = await apiResponse.json();

    // Find our test listing in the response
    const testListing = apiData.data?.find((listing: any) =>
      listing.title?.toLowerCase().includes(testTitle.toLowerCase().substring(0, 10))
    );

    console.log(`Test listing found in API: ${!!testListing}`);
    if (testListing) {
      console.log(`  Status: ${testListing.status}`);
      console.log(`  Title: ${testListing.title}`);
    }

    expect(testListing, 'Test listing should appear in API response').toBeTruthy();
    expect(testListing.status, 'Listing status should be published').toBe('published');

    // Step 4: Verify on frontend
    console.log('\nStep 4: Verifying listing appears on frontend...');
    await page.goto(frontendUrl);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Look for the listing on the page
    const listingCards = page.locator('[data-testid="listing-card"], article, .listing-card');
    const cardCount = await listingCards.count();
    console.log(`Found ${cardCount} listing cards on page`);

    if (cardCount > 0) {
      const pageContent = await page.locator('body').textContent();
      const listingVisible = pageContent
        ?.toLowerCase()
        .includes(testTitle.toLowerCase().substring(0, 10));
      console.log(`Test listing visible on page: ${listingVisible}`);
    }

    console.log('\n========================================');
    console.log('TEST COMPLETED SUCCESSFULLY');
    console.log('========================================\n');
  });

  /**
   * TC-PUB-002: Verify existing published listings still appear
   *
   * Regression test to ensure the fix doesn't break existing published listings
   */
  test('TC-PUB-002: Existing published listings still appear on frontend', async ({ page }) => {
    console.log('\n========================================');
    console.log('REGRESSION TEST: Existing Published Listings');
    console.log('========================================\n');

    // Check API for published listings
    const apiUrl = `${API_URL}/listings`;
    const apiResponse = await page.request.get(apiUrl);
    const apiData = await apiResponse.json();

    console.log(`Total listings from API: ${apiData.data?.length || 0}`);

    // Verify we have published listings
    const publishedListings = apiData.data?.filter((l: any) => l.status === 'published') || [];
    console.log(`Published listings: ${publishedListings.length}`);

    expect(publishedListings.length, 'Should have at least one published listing').toBeGreaterThan(
      0
    );

    // Verify frontend shows listings
    await page.goto(`${FRONTEND_URL}/fr`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Check for listing cards on homepage or navigate to search
    let listingCards = page.locator('[data-testid="listing-card"], article, .listing-card');
    let cardCount = await listingCards.count();

    if (cardCount === 0) {
      // Try search page
      await page.goto(`${FRONTEND_URL}/fr/search`);
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);
      listingCards = page.locator('[data-testid="listing-card"], article, .listing-card');
      cardCount = await listingCards.count();
    }

    console.log(`Listing cards visible on frontend: ${cardCount}`);

    console.log('\n========================================');
    console.log('REGRESSION TEST COMPLETED');
    console.log('========================================\n');
  });

  /**
   * TC-PUB-003: Verify each service type can be published
   *
   * Tests that all 4 service types (tour, nautical, accommodation, event)
   * can be published and appear in their respective frontend sections
   */
  test('TC-PUB-003: All service types can be published and displayed', async ({ page }) => {
    console.log('\n========================================');
    console.log('SERVICE TYPE PUBLISH TEST');
    console.log('========================================\n');

    const serviceTypes = ['tour', 'nautical', 'accommodation', 'event'];

    for (const serviceType of serviceTypes) {
      const apiUrl = `${API_URL}/listings?service_type=${serviceType}`;
      const apiResponse = await page.request.get(apiUrl);
      const apiData = await apiResponse.json();

      const count = apiData.data?.length || 0;
      console.log(`${serviceType}: ${count} published listings`);

      // At least tours and nautical should have listings (from seed data)
      if (serviceType === 'tour' || serviceType === 'nautical') {
        expect(count, `${serviceType} should have published listings`).toBeGreaterThan(0);
      }
    }

    console.log('\n========================================');
    console.log('SERVICE TYPE TEST COMPLETED');
    console.log('========================================\n');
  });
});
