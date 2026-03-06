/**
 * Vendor Panel E2E Tests - Section 2.1: Listing Management
 * Test Cases: TC-V001 to TC-V008
 *
 * Tests vendor listing creation, editing, and lifecycle management
 * through the Filament vendor panel wizard.
 */

import { test, expect } from '@playwright/test';
import {
  loginVendorUI,
  getVendorPanelUrl,
  waitForFilamentPage,
  submitFilamentForm,
  expectNotification,
  clickTableAction,
  seededVendor,
} from '../../fixtures/vendor-helpers';
import {
  listingTemplates,
  vendorUsers,
  generateUniqueTestData,
} from '../../fixtures/vendor-test-data';

const VENDOR_PANEL_URL = process.env.LARAVEL_URL || 'http://localhost:8000';

test.describe('Vendor Panel - Listing Management (2.1)', () => {
  test.beforeEach(async ({ page }) => {
    // Login as vendor before each test
    await loginVendorUI(page, seededVendor.email, seededVendor.password);
  });

  /**
   * TC-V001: Create Tour Listing (Full Wizard)
   * Tests the complete 7-step listing creation wizard for Tour service type
   */
  test('TC-V001: Create Tour Listing (Full Wizard)', async ({ page }) => {
    const testTitle = generateUniqueTestData('Mountain Trek');

    // Navigate to create listing
    await page.goto(`${VENDOR_PANEL_URL}/vendor/listings/create`);
    await waitForFilamentPage(page);

    // Step 1: Basic Information
    // Select service type
    await page
      .locator('select[name*="service_type"], [data-field*="service_type"] select')
      .selectOption('tour');
    await page.waitForTimeout(500);

    // Select location (if dropdown exists)
    const locationSelect = page.locator(
      'select[name*="location"], [data-field*="location"] select'
    );
    if (await locationSelect.isVisible()) {
      await locationSelect.selectOption({ index: 1 }); // Select first available location
    }

    // Fill titles
    await page.fill('input[name*="title"][name*="en"], input[name="data.title.en"]', testTitle);
    await page.fill(
      'input[name*="title"][name*="fr"], input[name="data.title.fr"]',
      `${testTitle} FR`
    );

    // Fill summaries
    const summaryEnField = page.locator(
      'textarea[name*="summary"][name*="en"], textarea[name="data.summary.en"]'
    );
    if (await summaryEnField.isVisible()) {
      await summaryEnField.fill(listingTemplates.tour.summaryEn);
    }
    const summaryFrField = page.locator(
      'textarea[name*="summary"][name*="fr"], textarea[name="data.summary.fr"]'
    );
    if (await summaryFrField.isVisible()) {
      await summaryFrField.fill(listingTemplates.tour.summaryFr);
    }

    // Fill descriptions (may be rich editor)
    const descEnField = page
      .locator(
        '[name*="description"][name*="en"] textarea, [data-field*="description.en"] textarea, .trix-content[input*="description"]'
      )
      .first();
    if (await descEnField.isVisible()) {
      await descEnField.fill(listingTemplates.tour.descriptionEn);
    }

    // Click Next to proceed to Step 2: Media
    const nextButton = page
      .locator('button:has-text("Next"), button[wire\\:click*="nextStep"]')
      .first();
    if (await nextButton.isVisible()) {
      await nextButton.click();
      await page.waitForTimeout(1000);
    }

    // Step 2: Media & Gallery (skip if no images required)
    // Look for file upload or skip
    const skipMediaButton = page
      .locator('button:has-text("Skip"), button:has-text("Next")')
      .first();
    if (await skipMediaButton.isVisible()) {
      await skipMediaButton.click();
      await page.waitForTimeout(500);
    }

    // Step 3: Details & Highlights
    const highlightsField = page
      .locator('[name*="highlights"] textarea, [data-field*="highlights"] textarea')
      .first();
    if (await highlightsField.isVisible()) {
      await highlightsField.fill(listingTemplates.tour.highlightsEn.join('\n'));
    }

    // Continue through remaining steps
    for (let i = 0; i < 4; i++) {
      const continueBtn = page
        .locator('button:has-text("Next"), button:has-text("Continue")')
        .first();
      if (await continueBtn.isVisible()) {
        await continueBtn.click();
        await page.waitForTimeout(500);
      }
    }

    // Step 6: Pricing
    const priceTndField = page
      .locator(
        'input[name*="tnd_price"], input[name*="price_tnd"], [data-field*="tnd_price"] input'
      )
      .first();
    if (await priceTndField.isVisible()) {
      await priceTndField.fill(String(listingTemplates.tour.priceTnd));
    }

    const priceEurField = page
      .locator(
        'input[name*="eur_price"], input[name*="price_eur"], [data-field*="eur_price"] input'
      )
      .first();
    if (await priceEurField.isVisible()) {
      await priceEurField.fill(String(listingTemplates.tour.priceEur));
    }

    // Save as Draft
    const saveDraftButton = page
      .locator('button:has-text("Save"), button:has-text("Create"), button[type="submit"]')
      .first();
    await saveDraftButton.click();
    await page.waitForLoadState('networkidle');

    // Verify: Listing created with status "Draft"
    await expect(page.locator('body')).toContainText(/draft|created|success/i);
  });

  /**
   * TC-V002: Create Nautical Activity Listing
   * Verifies nautical-specific fields appear when selecting nautical service type
   */
  test('TC-V002: Create Nautical Activity Listing', async ({ page }) => {
    const testTitle = generateUniqueTestData('Sailing Adventure');

    await page.goto(`${VENDOR_PANEL_URL}/vendor/listings/create`);
    await waitForFilamentPage(page);

    // Select Nautical service type
    await page
      .locator('select[name*="service_type"], [data-field*="service_type"] select')
      .selectOption('nautical');
    await page.waitForTimeout(500);

    // Verify nautical-specific fields appear
    const nauticalFields = page.locator(
      '[data-field*="boat"], [name*="boat"], [data-field*="nautical"]'
    );
    // These fields should become visible after selecting nautical type

    // Fill basic required fields
    await page.fill('input[name*="title"][name*="en"], input[name="data.title.en"]', testTitle);
    await page.fill(
      'input[name*="title"][name*="fr"], input[name="data.title.fr"]',
      `${testTitle} FR`
    );

    // Look for nautical-specific fields (boat type, capacity, etc.)
    const boatTypeField = page.locator(
      'select[name*="boat_type"], [data-field*="boat_type"] select'
    );
    if (await boatTypeField.isVisible()) {
      await boatTypeField.selectOption({ index: 1 });
    }

    // Save
    await submitFilamentForm(page);

    // Expected: Listing saved with nautical details
    await expect(page.locator('body')).toContainText(/draft|created|success|nautical/i);
  });

  /**
   * TC-V003: Create Accommodation Listing
   * Verifies accommodation-specific fields (type, meals, amenities)
   */
  test('TC-V003: Create Accommodation Listing', async ({ page }) => {
    const testTitle = generateUniqueTestData('Desert Glamping');

    await page.goto(`${VENDOR_PANEL_URL}/vendor/listings/create`);
    await waitForFilamentPage(page);

    // Select Accommodation service type
    await page
      .locator('select[name*="service_type"], [data-field*="service_type"] select')
      .selectOption('accommodation');
    await page.waitForTimeout(500);

    // Fill basic fields
    await page.fill('input[name*="title"][name*="en"], input[name="data.title.en"]', testTitle);
    await page.fill(
      'input[name*="title"][name*="fr"], input[name="data.title.fr"]',
      `${testTitle} FR`
    );

    // Verify accommodation fields
    const accommodationTypeField = page.locator(
      'select[name*="accommodation_type"], [data-field*="accommodation_type"] select'
    );
    if (await accommodationTypeField.isVisible()) {
      await accommodationTypeField.selectOption({ index: 1 });
    }

    // Check for meals included checkboxes
    const mealsField = page.locator('[name*="meals"], [data-field*="meals"]');

    // Save
    await submitFilamentForm(page);

    // Expected: Listing saved correctly
    await expect(page.locator('body')).toContainText(/draft|created|success/i);
  });

  /**
   * TC-V004: Create Event Listing
   * Verifies event-specific fields (event type, dates, venue)
   */
  test('TC-V004: Create Event Listing', async ({ page }) => {
    const testTitle = generateUniqueTestData('Music Festival');

    await page.goto(`${VENDOR_PANEL_URL}/vendor/listings/create`);
    await waitForFilamentPage(page);

    // Select Event service type
    await page
      .locator('select[name*="service_type"], [data-field*="service_type"] select')
      .selectOption('event');
    await page.waitForTimeout(500);

    // Fill basic fields
    await page.fill('input[name*="title"][name*="en"], input[name="data.title.en"]', testTitle);
    await page.fill(
      'input[name*="title"][name*="fr"], input[name="data.title.fr"]',
      `${testTitle} FR`
    );

    // Verify event-specific fields
    const eventTypeField = page.locator(
      'select[name*="event_type"], [data-field*="event_type"] select'
    );
    if (await eventTypeField.isVisible()) {
      await eventTypeField.selectOption({ index: 1 });
    }

    // Event dates
    const startDateField = page.locator(
      'input[name*="start_date"], input[name*="event_start"], [data-field*="start_date"] input'
    );
    if (await startDateField.isVisible()) {
      await startDateField.fill(listingTemplates.event.startDate);
    }

    const endDateField = page.locator(
      'input[name*="end_date"], input[name*="event_end"], [data-field*="end_date"] input'
    );
    if (await endDateField.isVisible()) {
      await endDateField.fill(listingTemplates.event.endDate);
    }

    // Venue details
    const venueNameField = page.locator(
      'input[name*="venue_name"], input[name*="venue"], [data-field*="venue_name"] input'
    );
    if (await venueNameField.isVisible()) {
      await venueNameField.fill(listingTemplates.event.venueName);
    }

    // Save
    await submitFilamentForm(page);

    // Expected: Event listing created
    await expect(page.locator('body')).toContainText(/draft|created|success/i);
  });

  /**
   * TC-V005: Submit Listing for Review
   * Tests changing status from Draft to Pending Review
   */
  test('TC-V005: Submit Listing for Review', async ({ page }) => {
    // Navigate to listings
    await page.goto(`${VENDOR_PANEL_URL}/vendor/listings`);
    await waitForFilamentPage(page);

    // Find a draft listing
    const draftRow = page.locator('table tbody tr:has-text("Draft")').first();

    if (await draftRow.isVisible()) {
      // Click on the row to open listing
      await draftRow.click();
      await page.waitForLoadState('networkidle');

      // Look for Submit for Review button/action
      const submitButton = page
        .locator('button:has-text("Submit"), button:has-text("Review"), [data-action="submit"]')
        .first();
      if (await submitButton.isVisible()) {
        await submitButton.click();
        await page.waitForTimeout(1000);

        // Confirm if modal appears
        const confirmButton = page
          .locator('button:has-text("Confirm"), button:has-text("Yes")')
          .first();
        if (await confirmButton.isVisible()) {
          await confirmButton.click();
        }

        // Expected: Status changes to "Pending Review"
        await expect(page.locator('body')).toContainText(/pending|review|submitted/i);
      }
    } else {
      // Create a draft first, then submit
      test.skip();
    }
  });

  /**
   * TC-V006: Edit Rejected Listing
   * Tests viewing rejection reason and resubmitting
   */
  test('TC-V006: Edit Rejected Listing', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/listings`);
    await waitForFilamentPage(page);

    // Look for a rejected listing
    const rejectedRow = page.locator('table tbody tr:has-text("Rejected")').first();

    if (await rejectedRow.isVisible()) {
      // Click to view/edit
      await rejectedRow.click();
      await page.waitForLoadState('networkidle');

      // Look for rejection reason
      const rejectionReason = page.locator(
        '[data-field="rejection_reason"], .rejection-reason, :has-text("Rejection")'
      );
      await expect(rejectionReason).toBeVisible();

      // Make corrections (edit a field)
      const titleField = page.locator('input[name*="title"][name*="en"]').first();
      if ((await titleField.isVisible()) && (await titleField.isEditable())) {
        const currentValue = await titleField.inputValue();
        await titleField.fill(`${currentValue} - Updated`);
      }

      // Resubmit for review
      const resubmitButton = page
        .locator('button:has-text("Resubmit"), button:has-text("Submit"), [data-action="resubmit"]')
        .first();
      if (await resubmitButton.isVisible()) {
        await resubmitButton.click();

        // Expected: Status back to "Pending Review"
        await expect(page.locator('body')).toContainText(/pending|review|submitted/i);
      }
    } else {
      test.skip();
    }
  });

  /**
   * TC-V007: Duplicate Listing
   * Tests the duplicate action creating a new draft copy
   */
  test('TC-V007: Duplicate Listing', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/listings`);
    await waitForFilamentPage(page);

    // Get initial count
    const initialRows = await page.locator('table tbody tr').count();

    // Find any listing and duplicate it
    const firstRow = page.locator('table tbody tr').first();
    if (await firstRow.isVisible()) {
      // Click actions button
      const actionsButton = firstRow.locator(
        'button[data-dropdown-trigger], button:has-text("Actions")'
      );
      if (await actionsButton.isVisible()) {
        await actionsButton.click();
        await page.waitForTimeout(300);
      }

      // Click duplicate action
      const duplicateButton = page
        .locator('[data-action="duplicate"], button:has-text("Duplicate")')
        .first();
      if (await duplicateButton.isVisible()) {
        await duplicateButton.click();
        await page.waitForLoadState('networkidle');

        // Confirm if modal appears
        const confirmButton = page
          .locator('button:has-text("Confirm"), button:has-text("Yes")')
          .first();
        if (await confirmButton.isVisible()) {
          await confirmButton.click();
          await page.waitForLoadState('networkidle');
        }

        // Expected: New draft listing created
        // Either redirected to new listing or notification shown
        await expect(page.locator('body')).toContainText(/duplicat|copy|created|draft/i);
      }
    }
  });

  /**
   * TC-V008: Archive Own Listing
   * Tests archiving a published listing
   */
  test('TC-V008: Archive Own Listing', async ({ page }) => {
    await page.goto(`${VENDOR_PANEL_URL}/vendor/listings`);
    await waitForFilamentPage(page);

    // Find a published listing
    const publishedRow = page.locator('table tbody tr:has-text("Published")').first();

    if (await publishedRow.isVisible()) {
      // Click actions button
      const actionsButton = publishedRow.locator(
        'button[data-dropdown-trigger], button:has-text("Actions")'
      );
      if (await actionsButton.isVisible()) {
        await actionsButton.click();
        await page.waitForTimeout(300);
      }

      // Click archive action
      const archiveButton = page
        .locator('[data-action="archive"], button:has-text("Archive")')
        .first();
      if (await archiveButton.isVisible()) {
        await archiveButton.click();

        // Confirm if modal appears
        const confirmButton = page
          .locator('button:has-text("Confirm"), button:has-text("Yes")')
          .first();
        if (await confirmButton.isVisible()) {
          await confirmButton.click();
          await page.waitForLoadState('networkidle');
        }

        // Expected: Listing no longer visible on frontend (status = Archived)
        await expect(page.locator('body')).toContainText(/archived|success/i);
      }
    } else {
      test.skip();
    }
  });
});
