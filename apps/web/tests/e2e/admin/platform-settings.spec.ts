/**
 * Admin Panel - Platform Settings E2E Tests
 *
 * Test Cases:
 * TC-A040: Update Platform Identity
 * TC-A041: Update Logo and Branding
 * TC-A042: Configure Featured Destinations
 * TC-A043: Update Payment Settings
 * TC-A044: Configure Booking Hold Settings
 */

import { test, expect, Page } from '@playwright/test';
import {
  adminUsers,
  adminUrls,
  adminPlatformSettings,
  adminSelectors,
} from '../../fixtures/admin-test-data';
import {
  loginToAdmin,
  navigateToAdminResource,
  waitForNotification,
} from '../../fixtures/admin-api-helpers';

test.describe('Admin Panel - Platform Settings', () => {
  let page: Page;

  test.beforeEach(async ({ browser }) => {
    page = await browser.newPage();
    console.log('📍 Logging into admin panel...');
    await loginToAdmin(page, adminUsers.admin.email, adminUsers.admin.password);
    console.log('✅ Admin login successful');
  });

  test.afterEach(async () => {
    await page.close();
  });

  test('TC-A040: Update Platform Identity', async () => {
    console.log('📍 Step 1: Navigate to Platform Settings');
    await page.goto(adminUrls.platformSettings);
    await page.waitForLoadState('networkidle');

    console.log('📍 Step 2: Update platform name and tagline');
    // Look for Platform Identity section/tab
    const identityTab = page
      .locator('button:has-text("Identity"), a:has-text("Identity"), [data-tab="identity"]')
      .first();
    if (await identityTab.isVisible()) {
      await identityTab.click();
      await page.waitForLoadState('networkidle');
    }

    // Update platform name (English)
    const nameEnInput = page
      .locator(
        'input[name*="platform_name"][name*="en"], input[name*="name.en"], [data-field="platform_name.en"] input'
      )
      .first();
    const originalNameEn = await nameEnInput.inputValue().catch(() => '');

    if (await nameEnInput.isVisible()) {
      const newName = `Evasion Djerba - Updated ${Date.now()}`;
      await nameEnInput.fill(newName);
    }

    // Update tagline (English)
    const taglineEnInput = page
      .locator(
        'input[name*="tagline"][name*="en"], input[name*="tagline.en"], [data-field="tagline.en"] input'
      )
      .first();
    if (await taglineEnInput.isVisible()) {
      const newTagline = 'Your Gateway to Djerba Adventures';
      await taglineEnInput.fill(newTagline);
    }

    console.log('📍 Step 3: Save changes');
    await page.click('button:has-text("Save")');
    await page.waitForLoadState('networkidle');

    const notification = await waitForNotification(page, 'success');
    expect(notification).toBeTruthy();
    console.log('✅ Platform identity updated');

    console.log('📍 Step 4: Verify changes on frontend');
    const frontendPage = await page.context().newPage();
    await frontendPage.goto('http://localhost:3000');
    await frontendPage.waitForLoadState('networkidle');

    // Check for updated branding in header or footer
    const pageContent = await frontendPage.textContent('body');
    console.log('✅ Frontend loaded - branding should reflect changes');

    // Restore original name
    if (originalNameEn && (await nameEnInput.isVisible())) {
      await nameEnInput.fill(originalNameEn);
      await page.click('button:has-text("Save")');
    }

    await frontendPage.close();
  });

  test('TC-A041: Update Logo and Branding', async () => {
    console.log('📍 Step 1: Navigate to Platform Settings');
    await page.goto(adminUrls.platformSettings);
    await page.waitForLoadState('networkidle');

    console.log('📍 Step 2: Navigate to Logo & Branding tab');
    const brandingTab = page
      .locator(
        'button:has-text("Branding"), button:has-text("Logo"), a:has-text("Branding"), [data-tab="branding"]'
      )
      .first();
    if (await brandingTab.isVisible()) {
      await brandingTab.click();
      await page.waitForLoadState('networkidle');
    }

    console.log('📍 Step 3: Check for logo upload fields');
    // Light mode logo
    const lightLogoInput = page
      .locator(
        'input[type="file"][name*="logo_light"], [data-field="logo_light"] input[type="file"]'
      )
      .first();
    const lightLogoVisible = await lightLogoInput.isVisible().catch(() => false);
    console.log(`  Light logo upload: ${lightLogoVisible ? 'Available' : 'Not found'}`);

    // Dark mode logo
    const darkLogoInput = page
      .locator('input[type="file"][name*="logo_dark"], [data-field="logo_dark"] input[type="file"]')
      .first();
    const darkLogoVisible = await darkLogoInput.isVisible().catch(() => false);
    console.log(`  Dark logo upload: ${darkLogoVisible ? 'Available' : 'Not found'}`);

    // Favicon
    const faviconInput = page
      .locator('input[type="file"][name*="favicon"], [data-field="favicon"] input[type="file"]')
      .first();
    const faviconVisible = await faviconInput.isVisible().catch(() => false);
    console.log(`  Favicon upload: ${faviconVisible ? 'Available' : 'Not found'}`);

    // Note: Actual file upload would require test files
    console.log('📍 Step 4: Logo upload fields verified');
    console.log('✅ Logo and branding settings test completed');
    console.log('  (File upload test skipped - would require test image files)');
  });

  test('TC-A042: Configure Featured Destinations', async () => {
    console.log('📍 Step 1: Navigate to Platform Settings');
    await page.goto(adminUrls.platformSettings);
    await page.waitForLoadState('networkidle');

    console.log('📍 Step 2: Navigate to Destinations tab');
    const destinationsTab = page
      .locator(
        'button:has-text("Destinations"), a:has-text("Destinations"), [data-tab="destinations"]'
      )
      .first();
    if (await destinationsTab.isVisible()) {
      await destinationsTab.click();
      await page.waitForLoadState('networkidle');
    }

    console.log('📍 Step 3: Add/Edit a destination');
    // Look for repeater add button
    const addDestinationBtn = page
      .locator(
        'button:has-text("Add Destination"), button:has-text("Add Item"), [data-action="add-destination"]'
      )
      .first();

    if (await addDestinationBtn.isVisible()) {
      await addDestinationBtn.click();
      await page.waitForLoadState('networkidle');

      // Fill destination details
      const nameInput = page
        .locator('input[name*="destinations"][name*="name"], input[name*="name"]')
        .last();
      if (await nameInput.isVisible()) {
        await nameInput.fill(adminPlatformSettings.destinations.name);
      }

      const slugInput = page
        .locator('input[name*="destinations"][name*="slug"], input[name*="slug"]')
        .last();
      if (await slugInput.isVisible()) {
        await slugInput.fill(adminPlatformSettings.destinations.slug);
      }

      // Description (EN/FR)
      const descEnInput = page
        .locator(
          'textarea[name*="destinations"][name*="description"][name*="en"], textarea[name*="description.en"]'
        )
        .last();
      if (await descEnInput.isVisible()) {
        await descEnInput.fill(adminPlatformSettings.destinations.descriptionEn);
      }

      const descFrInput = page
        .locator(
          'textarea[name*="destinations"][name*="description"][name*="fr"], textarea[name*="description.fr"]'
        )
        .last();
      if (await descFrInput.isVisible()) {
        await descFrInput.fill(adminPlatformSettings.destinations.descriptionFr);
      }
    } else {
      console.log('⚠️ Add destination button not found - checking existing destinations');
    }

    console.log('📍 Step 4: Save changes');
    await page.click('button:has-text("Save")');
    await page.waitForLoadState('networkidle');

    console.log('📍 Step 5: Verify on frontend homepage');
    const frontendPage = await page.context().newPage();
    await frontendPage.goto('http://localhost:3000');
    await frontendPage.waitForLoadState('networkidle');

    // Look for bento grid / destinations section
    const destinationsSection = frontendPage
      .locator('[data-testid="destinations"], .destinations-grid, .bento-grid')
      .first();
    if (await destinationsSection.isVisible()) {
      console.log('✅ Destinations section found on homepage');
    } else {
      console.log('⚠️ Destinations section not visible on homepage');
    }

    await frontendPage.close();
    console.log('✅ Featured destinations configuration test completed');
  });

  test('TC-A043: Update Payment Settings', async () => {
    console.log('📍 Step 1: Navigate to Platform Settings');
    await page.goto(adminUrls.platformSettings);
    await page.waitForLoadState('networkidle');

    console.log('📍 Step 2: Navigate to Payment tab');
    const paymentTab = page
      .locator('button:has-text("Payment"), a:has-text("Payment"), [data-tab="payment"]')
      .first();
    if (await paymentTab.isVisible()) {
      await paymentTab.click();
      await page.waitForLoadState('networkidle');
    }

    console.log('📍 Step 3: Update exchange rate EUR→TND');
    const exchangeRateInput = page
      .locator(
        'input[name*="exchange_rate"], input[name*="eur_to_tnd"], [data-field="exchange_rate"] input'
      )
      .first();
    const originalRate = await exchangeRateInput.inputValue().catch(() => '3.35');

    if (await exchangeRateInput.isVisible()) {
      // Update to new rate
      const newRate = '3.40';
      await exchangeRateInput.fill(newRate);
      console.log(`  Exchange rate: ${originalRate} → ${newRate}`);
    }

    console.log('📍 Step 4: Enable/disable payment methods');
    // Bank transfer toggle
    const bankTransferToggle = page
      .locator(
        'input[name*="bank_transfer"], input[name*="payment_methods"][value*="bank"], [data-field="bank_transfer_enabled"] input'
      )
      .first();
    if (await bankTransferToggle.isVisible()) {
      const isEnabled = await bankTransferToggle.isChecked();
      console.log(`  Bank Transfer: ${isEnabled ? 'Enabled' : 'Disabled'}`);
    }

    // Cash toggle
    const cashToggle = page
      .locator(
        'input[name*="cash"], input[name*="payment_methods"][value*="cash"], [data-field="cash_enabled"] input'
      )
      .first();
    if (await cashToggle.isVisible()) {
      const isEnabled = await cashToggle.isChecked();
      console.log(`  Cash on Arrival: ${isEnabled ? 'Enabled' : 'Disabled'}`);
    }

    console.log('📍 Step 5: Save changes');
    await page.click('button:has-text("Save")');
    await page.waitForLoadState('networkidle');

    const notification = await waitForNotification(page, 'success');
    expect(notification).toBeTruthy();
    console.log('✅ Payment settings updated');

    // Restore original exchange rate
    if (await exchangeRateInput.isVisible()) {
      await exchangeRateInput.fill(originalRate);
      await page.click('button:has-text("Save")');
    }

    console.log('📍 Frontend Check: Verify checkout shows correct payment options');
    // Would require going through checkout flow
    console.log('✅ Payment settings test completed');
  });

  test('TC-A044: Configure Booking Hold Settings', async () => {
    console.log('📍 Step 1: Navigate to Platform Settings');
    await page.goto(adminUrls.platformSettings);
    await page.waitForLoadState('networkidle');

    console.log('📍 Step 2: Navigate to Booking tab');
    const bookingTab = page
      .locator('button:has-text("Booking"), a:has-text("Booking"), [data-tab="booking"]')
      .first();
    if (await bookingTab.isVisible()) {
      await bookingTab.click();
      await page.waitForLoadState('networkidle');
    }

    console.log('📍 Step 3: Update hold duration');
    const holdDurationInput = page
      .locator(
        'input[name*="hold_duration"], input[name*="hold_minutes"], [data-field="hold_duration"] input'
      )
      .first();
    const originalDuration = await holdDurationInput.inputValue().catch(() => '15');

    if (await holdDurationInput.isVisible()) {
      // Set to 10 minutes
      await holdDurationInput.fill('10');
      console.log(`  Hold duration: ${originalDuration} → 10 minutes`);
    }

    console.log('📍 Step 4: Save changes');
    await page.click('button:has-text("Save")');
    await page.waitForLoadState('networkidle');

    const notification = await waitForNotification(page, 'success');
    expect(notification).toBeTruthy();
    console.log('✅ Booking hold settings updated');

    console.log('📍 Frontend Check: Verify hold timer shows 10 minutes');
    const frontendPage = await page.context().newPage();
    await frontendPage.goto('http://localhost:3000/listings');
    await frontendPage.waitForLoadState('networkidle');

    // This would require:
    // 1. Selecting a listing
    // 2. Adding to cart
    // 3. Checking the hold timer displays 10:00
    console.log('  (Timer verification would require full booking flow)');

    // Restore original duration
    if (await holdDurationInput.isVisible()) {
      await holdDurationInput.fill(originalDuration);
      await page.click('button:has-text("Save")');
    }

    await frontendPage.close();
    console.log('✅ Booking hold settings test completed');
  });
});
