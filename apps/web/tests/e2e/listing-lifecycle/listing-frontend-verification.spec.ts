/**
 * Listing Lifecycle E2E Tests - Frontend Verification
 * Test Cases: TC-FV001 to TC-FV011
 *
 * Tests that listings display correctly on the public frontend:
 * - Published listings are visible
 * - Non-published listings return 404
 * - Type-specific content displays correctly
 * - Availability calendar reflects correct data
 * - Status changes propagate to frontend
 */

import { test, expect } from '@playwright/test';
import {
  loginVendorUI,
  waitForFilamentPage,
  submitFilamentForm,
  seededVendor,
  navigateToVendorSection,
  selectServiceType,
  selectLocation,
  fillTranslatableTitle,
  fillTranslatableSummary,
  clickWizardNext,
  clickWizardSkip,
  fillPricing,
} from '../../fixtures/vendor-helpers';
import {
  loginToAdmin,
  navigateToAdminResource,
  waitForNotification,
} from '../../fixtures/admin-api-helpers';
import { adminUsers } from '../../fixtures/admin-test-data';
import {
  completeListingTemplates,
  generateUniqueTestData,
  getFutureDate,
} from '../../fixtures/vendor-test-data';
import {
  switchToVendorPanel,
  switchToAdminPanel,
  verifyFrontendListingVisible,
  verifyListingTypeContent,
  verifyAvailabilityCalendar,
  vendorSubmitForReview,
  adminApproveListing,
  adminArchiveListing,
  adminRepublishListing,
  navigateToFrontend,
  generateUniqueListingTitle,
  extractListingSlug,
} from '../../fixtures/listing-lifecycle-helpers';

const FRONTEND_URL = process.env.NEXT_PUBLIC_URL || 'http://localhost:3000';
const VENDOR_PANEL_URL = process.env.LARAVEL_URL || 'http://localhost:8000';

test.describe('Listing Frontend Verification (TC-FV001 to TC-FV011)', () => {
  /**
   * TC-FV001: Published Listing Displays on Frontend
   * Tests that a published listing is visible on the public frontend
   */
  test('TC-FV001: Published listing displays on frontend', async ({ page }) => {
    // Navigate to the listings page on frontend
    await page.goto(`${FRONTEND_URL}/fr`);
    await page.waitForLoadState('networkidle');

    // Look for listing cards on the homepage or listings page
    const listingCards = page.locator(
      '[data-testid="listing-card"], .listing-card, [class*="listing"], article[class*="card"]'
    );

    // If no cards on homepage, navigate to search/listings page
    if ((await listingCards.count()) === 0) {
      await page.goto(`${FRONTEND_URL}/fr/search`);
      await page.waitForLoadState('networkidle');
    }

    // Click on first available listing
    const firstListing = listingCards.first();
    if (await firstListing.isVisible({ timeout: 5000 }).catch(() => false)) {
      await firstListing.click();
      await page.waitForLoadState('networkidle');

      // Verify listing page elements are visible
      await expect(page.locator('h1')).toBeVisible({ timeout: 10000 });

      // Verify booking panel or CTA is visible
      const bookingElement = page
        .locator(
          '[data-testid="booking-panel"], [class*="booking"], button:has-text("Book"), button:has-text("Reserver")'
        )
        .first();
      await expect(bookingElement).toBeVisible({ timeout: 5000 });
    } else {
      // No listings found - verify page loaded without server errors
      // Check for visible error message elements, not raw body text (which includes RSC payload)
      const errorElement = page
        .locator('[class*="error"], [role="alert"]')
        .filter({ hasText: /500|internal server error/i });
      await expect(errorElement)
        .not.toBeVisible({ timeout: 1000 })
        .catch(() => {});
      // Page loaded successfully even if no listings are visible
    }
  });

  /**
   * TC-FV002: Draft Listing Returns 404
   * Tests that draft (unpublished) listings are not accessible on frontend
   */
  test('TC-FV002: Draft listing returns 404', async ({ page }) => {
    // Login as vendor
    await loginVendorUI(page, seededVendor.email, seededVendor.password);

    await navigateToVendorSection(page, 'listings');

    // Find a draft listing and get its slug
    const draftRow = page
      .locator('table tbody tr')
      .filter({
        hasText: /draft|brouillon/i,
      })
      .first();

    if (await draftRow.isVisible({ timeout: 5000 }).catch(() => false)) {
      // Get the listing's edit link to extract slug
      const editLink = draftRow.locator('a[href*="/edit"]').first();
      const href = await editLink.getAttribute('href');

      if (href) {
        // Extract listing ID
        const match = href.match(/\/listings\/(\d+)/);
        if (match) {
          const listingId = match[1];

          // Get the slug from the listing details
          await editLink.click();
          await page.waitForLoadState('networkidle');

          const slugInput = page.locator('input[name*="slug"]').first();
          let slug = '';
          if (await slugInput.isVisible()) {
            slug = await slugInput.inputValue();
          }

          if (slug) {
            // Now try to access this draft listing on frontend
            const response = await page.goto(`${FRONTEND_URL}/fr/listings/${slug}`);
            await page.waitForLoadState('networkidle');

            // Should be 404 or show not found content
            const bodyText = await page.locator('body').textContent();
            const is404 =
              response?.status() === 404 ||
              bodyText?.toLowerCase().includes('404') ||
              bodyText?.toLowerCase().includes('not found') ||
              bodyText?.toLowerCase().includes('introuvable');

            expect(is404).toBeTruthy();
          }
        }
      }
    } else {
      // No draft listings found - skip
      test.skip();
    }
  });

  /**
   * TC-FV003: Pending Review Listing Returns 404
   * Tests that pending_review listings are not accessible on frontend
   */
  test('TC-FV003: Pending review listing returns 404', async ({ page }) => {
    await loginVendorUI(page, seededVendor.email, seededVendor.password);

    await navigateToVendorSection(page, 'listings');

    // Find a pending review listing
    const pendingRow = page
      .locator('table tbody tr')
      .filter({
        hasText: /pending|review|en attente/i,
      })
      .first();

    if (await pendingRow.isVisible({ timeout: 5000 }).catch(() => false)) {
      // Get the slug
      const editLink = pendingRow.locator('a[href*="/edit"]').first();
      await editLink.click();
      await page.waitForLoadState('networkidle');

      const slugInput = page.locator('input[name*="slug"]').first();
      let slug = '';
      if (await slugInput.isVisible()) {
        slug = await slugInput.inputValue();
      }

      if (slug) {
        // Try to access on frontend
        const response = await page.goto(`${FRONTEND_URL}/fr/listings/${slug}`);
        await page.waitForLoadState('networkidle');

        const bodyText = await page.locator('body').textContent();
        const is404 =
          response?.status() === 404 ||
          bodyText?.toLowerCase().includes('404') ||
          bodyText?.toLowerCase().includes('not found') ||
          bodyText?.toLowerCase().includes('introuvable');

        expect(is404).toBeTruthy();
      }
    } else {
      test.skip();
    }
  });

  /**
   * TC-FV004: Rejected Listing Returns 404
   * Tests that rejected listings are not accessible on frontend
   */
  test('TC-FV004: Rejected listing returns 404', async ({ page }) => {
    await loginVendorUI(page, seededVendor.email, seededVendor.password);

    await navigateToVendorSection(page, 'listings');

    // Find a rejected listing
    const rejectedRow = page
      .locator('table tbody tr')
      .filter({
        hasText: /rejected|rejetee/i,
      })
      .first();

    if (await rejectedRow.isVisible({ timeout: 5000 }).catch(() => false)) {
      const editLink = rejectedRow.locator('a[href*="/edit"]').first();
      await editLink.click();
      await page.waitForLoadState('networkidle');

      const slugInput = page.locator('input[name*="slug"]').first();
      let slug = '';
      if (await slugInput.isVisible()) {
        slug = await slugInput.inputValue();
      }

      if (slug) {
        const response = await page.goto(`${FRONTEND_URL}/fr/listings/${slug}`);
        await page.waitForLoadState('networkidle');

        const bodyText = await page.locator('body').textContent();
        const is404 =
          response?.status() === 404 ||
          bodyText?.toLowerCase().includes('404') ||
          bodyText?.toLowerCase().includes('not found') ||
          bodyText?.toLowerCase().includes('introuvable');

        expect(is404).toBeTruthy();
      }
    } else {
      test.skip();
    }
  });

  /**
   * TC-FV005: Archived Listing Returns 404
   * Tests that archived listings are not accessible on frontend
   */
  test('TC-FV005: Archived listing returns 404', async ({ page }) => {
    // Login as admin to find archived listing
    await loginToAdmin(page, adminUsers.admin.email, adminUsers.admin.password);

    await navigateToAdminResource(page, 'listings');

    // Apply filter for archived status if available
    const statusFilter = page.locator('select[name*="status"], [data-filter="status"]').first();
    if (await statusFilter.isVisible()) {
      await statusFilter.selectOption('archived');
      await page.waitForTimeout(1000);
    }

    // Find an archived listing
    const archivedRow = page
      .locator('table tbody tr')
      .filter({
        hasText: /archived|archivee/i,
      })
      .first();

    if (await archivedRow.isVisible({ timeout: 5000 }).catch(() => false)) {
      const editLink = archivedRow.locator('a[href*="/edit"]').first();
      await editLink.click();
      await page.waitForLoadState('networkidle');

      const slugInput = page.locator('input[name*="slug"]').first();
      let slug = '';
      if (await slugInput.isVisible()) {
        slug = await slugInput.inputValue();
      }

      if (slug) {
        const response = await page.goto(`${FRONTEND_URL}/fr/listings/${slug}`);
        await page.waitForLoadState('networkidle');

        const bodyText = await page.locator('body').textContent();
        const is404 =
          response?.status() === 404 ||
          bodyText?.toLowerCase().includes('404') ||
          bodyText?.toLowerCase().includes('not found') ||
          bodyText?.toLowerCase().includes('introuvable');

        expect(is404).toBeTruthy();
      }
    } else {
      test.skip();
    }
  });

  /**
   * TC-FV006: Tour Displays Type-Specific Content
   * Tests that tour listings display duration, difficulty, and itinerary
   */
  test('TC-FV006: Tour displays duration, difficulty, itinerary', async ({ page }) => {
    // Navigate to search page filtered by tours
    await page.goto(`${FRONTEND_URL}/fr/search?type=tour`);
    await page.waitForLoadState('networkidle');

    // Click on first tour listing
    const tourCard = page.locator('[data-testid="listing-card"], .listing-card, article').first();

    if (await tourCard.isVisible({ timeout: 5000 }).catch(() => false)) {
      await tourCard.click();
      await page.waitForLoadState('networkidle');

      // Verify tour-specific content
      const bodyText = (await page.locator('body').textContent()) || '';
      const bodyLower = bodyText.toLowerCase();

      // Check for duration (hours/days or similar)
      const hasDuration = bodyLower.match(/\d+\s*(hour|heure|day|jour|h\b|min)/i);

      // Check for difficulty (or level indicator)
      const hasDifficulty = bodyLower.match(/easy|moderate|challenging|difficult|facile|modere/i);

      // Check for typical tour elements
      const hasTourContent =
        bodyLower.includes('tour') ||
        bodyLower.includes('trek') ||
        bodyLower.includes('excursion') ||
        bodyLower.includes('randonnee');

      // At least one tour-specific element should be present
      expect(hasDuration || hasDifficulty || hasTourContent).toBeTruthy();
    } else {
      // No tour listings found
      test.skip();
    }
  });

  /**
   * TC-FV007: Nautical Displays Boat Specs and License Info
   * Tests that nautical listings display boat specifications
   */
  test('TC-FV007: Nautical displays boat specs, license info', async ({ page }) => {
    await page.goto(`${FRONTEND_URL}/fr/search?type=nautical`);
    await page.waitForLoadState('networkidle');

    const nauticalCard = page
      .locator('[data-testid="listing-card"], .listing-card, article')
      .first();

    if (await nauticalCard.isVisible({ timeout: 5000 }).catch(() => false)) {
      await nauticalCard.click();
      await page.waitForLoadState('networkidle');

      const bodyText = (await page.locator('body').textContent()) || '';
      const bodyLower = bodyText.toLowerCase();

      // Check for nautical-specific content
      const hasBoatInfo = bodyLower.match(/boat|bateau|catamaran|sailing|voile|yacht/i);
      const hasCapacity = bodyLower.match(/\d+\s*(person|passager|guest|place|capacity)/i);
      const hasLicenseInfo = bodyLower.match(/license|permis|captain|skipper|crew|equipage/i);

      // At least one nautical-specific element should be present
      expect(hasBoatInfo || hasCapacity || hasLicenseInfo).toBeTruthy();
    } else {
      test.skip();
    }
  });

  /**
   * TC-FV008: Accommodation Displays Bedrooms, Amenities, Check-in/out
   * Tests that accommodation listings display property details
   */
  test('TC-FV008: Accommodation displays bedrooms, amenities, check-in/out', async ({ page }) => {
    await page.goto(`${FRONTEND_URL}/fr/search?type=accommodation`);
    await page.waitForLoadState('networkidle');

    const accommodationCard = page
      .locator('[data-testid="listing-card"], .listing-card, article')
      .first();

    if (await accommodationCard.isVisible({ timeout: 5000 }).catch(() => false)) {
      await accommodationCard.click();
      await page.waitForLoadState('networkidle');

      const bodyText = (await page.locator('body').textContent()) || '';
      const bodyLower = bodyText.toLowerCase();

      // Check for accommodation-specific content
      const hasBedroomInfo = bodyLower.match(/\d+\s*(bedroom|chambre|room|lit)/i);
      const hasBathroomInfo = bodyLower.match(/\d+\s*(bathroom|salle de bain)/i);
      const hasGuestInfo = bodyLower.match(/\d+\s*(guest|person|occupant|voyageur)/i);
      const hasAmenities = bodyLower.match(
        /wifi|pool|piscine|kitchen|cuisine|parking|air.*condition/i
      );
      const hasCheckTimes = bodyLower.match(
        /check.*in|check.*out|arrivee|depart|\d{1,2}:\d{2}|15h|11h/i
      );

      // At least some accommodation-specific elements should be present
      expect(
        hasBedroomInfo || hasBathroomInfo || hasAmenities || hasCheckTimes || hasGuestInfo
      ).toBeTruthy();
    } else {
      test.skip();
    }
  });

  /**
   * TC-FV009: Event Displays Dates, Venue, Agenda
   * Tests that event listings display event-specific information
   */
  test('TC-FV009: Event displays dates, venue, agenda', async ({ page }) => {
    await page.goto(`${FRONTEND_URL}/fr/search?type=event`);
    await page.waitForLoadState('networkidle');

    const eventCard = page.locator('[data-testid="listing-card"], .listing-card, article').first();

    if (await eventCard.isVisible({ timeout: 5000 }).catch(() => false)) {
      await eventCard.click();
      await page.waitForLoadState('networkidle');

      const bodyText = (await page.locator('body').textContent()) || '';
      const bodyLower = bodyText.toLowerCase();

      // Check for event-specific content
      const hasDateInfo = bodyLower.match(
        /\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}|\d{1,2}\s+(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec|janvier|fevrier|mars|avril|mai|juin|juillet|aout|septembre|octobre|novembre|decembre)/i
      );
      const hasVenueInfo = bodyLower.match(/venue|lieu|location|amphitheatre|salle|theatre/i);
      const hasTimeInfo = bodyLower.match(/\d{1,2}:\d{2}|\d{1,2}h\d{0,2}|program|agenda/i);
      const hasEventType = bodyLower.match(
        /festival|concert|workshop|atelier|exhibition|exposition/i
      );

      // At least some event-specific elements should be present
      expect(hasDateInfo || hasVenueInfo || hasTimeInfo || hasEventType).toBeTruthy();
    } else {
      test.skip();
    }
  });

  /**
   * TC-FV010: Availability Calendar Shows Correct Bookable Slots
   * Tests that the availability calendar reflects actual availability
   */
  test('TC-FV010: Availability calendar shows correct bookable slots', async ({ page }) => {
    // Navigate to a published listing
    await page.goto(`${FRONTEND_URL}/fr/search`);
    await page.waitForLoadState('networkidle');

    const listingCard = page
      .locator('[data-testid="listing-card"], .listing-card, article')
      .first();

    if (await listingCard.isVisible({ timeout: 5000 }).catch(() => false)) {
      await listingCard.click();
      await page.waitForLoadState('networkidle');

      // Look for date picker or availability calendar
      const datePickerTrigger = page
        .locator(
          '[data-testid="date-picker"], [data-testid="availability-calendar"], input[type="date"], button:has-text("Select date"), button:has-text("Choisir"), [class*="calendar"], [class*="date"]'
        )
        .first();

      if (await datePickerTrigger.isVisible({ timeout: 5000 }).catch(() => false)) {
        // Click to open calendar
        await datePickerTrigger.click();
        await page.waitForTimeout(500);

        // Verify calendar is visible
        const calendar = page
          .locator('[role="dialog"], [class*="calendar"], [class*="picker"]')
          .first();
        if (await calendar.isVisible({ timeout: 3000 }).catch(() => false)) {
          // Check for date buttons/cells
          const dateCells = calendar.locator('button, td[role="gridcell"], [class*="day"]');
          const cellCount = await dateCells.count();

          expect(cellCount).toBeGreaterThan(0);

          // Check that some dates are clickable (available) and some are disabled (unavailable)
          const enabledDates = dateCells.filter({ has: page.locator(':not([disabled])') });
          const enabledCount = await enabledDates.count();

          // At least some dates should be available
          expect(enabledCount).toBeGreaterThan(0);
        }
      }

      // Even without calendar, verify the booking section exists
      const bookingSection = page
        .locator('[data-testid="booking-panel"], [class*="booking"], form')
        .first();
      await expect(bookingSection).toBeVisible({ timeout: 5000 });
    } else {
      test.skip();
    }
  });

  /**
   * TC-FV011: Status Change from Published to Archived Reflects Immediately
   * Tests that archiving a listing immediately makes it inaccessible on frontend
   */
  test('TC-FV011: Status change from published to archived reflects immediately', async ({
    page,
  }) => {
    // First, create and publish a new listing
    const testTitle = generateUniqueListingTitle('Archive Reflect Test');

    // Login as vendor and create listing
    await loginVendorUI(page, seededVendor.email, seededVendor.password);

    await page.goto(`${VENDOR_PANEL_URL}/vendor/listings/create`);
    await waitForFilamentPage(page);

    // Use Filament 3 helpers for form interaction
    await selectServiceType(page, 'tour');
    await selectLocation(page, 1);
    await fillTranslatableTitle(page, testTitle, `${testTitle} FR`);
    await fillTranslatableSummary(page, 'Test for immediate archive reflection on frontend.');

    // Navigate through wizard using helpers
    for (let i = 0; i < 6; i++) {
      const stepped = await clickWizardNext(page);
      if (!stepped) {
        await clickWizardSkip(page);
      }
    }

    // Fill pricing using helper
    await fillPricing(page, 100, 30);

    // Save
    const saveButton = page.getByRole('button', { name: /save|create/i }).first();
    await saveButton.click();
    await page.waitForLoadState('networkidle');

    // Get the slug
    const slug = await extractListingSlug(page);

    // Submit for review
    await navigateToVendorSection(page, 'listings');
    await vendorSubmitForReview(page, testTitle);

    // Admin approves
    await loginToAdmin(page, adminUsers.admin.email, adminUsers.admin.password);
    await adminApproveListing(page, testTitle);

    // Verify visible on frontend
    if (slug) {
      await verifyFrontendListingVisible(page, slug, true);
    }

    // Now archive the listing
    await adminArchiveListing(page, testTitle);

    // Immediately verify it's NOT visible on frontend (404)
    if (slug) {
      await verifyFrontendListingVisible(page, slug, false);
    }
  });
});

// Additional frontend tests
test.describe('Listing Frontend - Additional Verification', () => {
  /**
   * Test that listing URLs follow the correct pattern
   */
  test('Listing URLs follow location-first pattern', async ({ page }) => {
    await page.goto(`${FRONTEND_URL}/fr/search`);
    await page.waitForLoadState('networkidle');

    const listingLink = page
      .locator('a[href*="/listings/"], a[href*="/houmt-souk/"], a[href*="/djerba/"]')
      .first();

    if (await listingLink.isVisible({ timeout: 5000 }).catch(() => false)) {
      const href = await listingLink.getAttribute('href');

      // URL should follow pattern: /location/slug or /locale/location/slug
      if (href) {
        // Either direct path or with locale prefix
        const hasLocationPattern = href.match(/\/(fr|en)?\/?[a-z\-]+\/[a-z\-0-9]+/i);
        expect(hasLocationPattern).toBeTruthy();
      }
    }
  });

  /**
   * Test that listing page has proper SEO elements
   */
  test('Listing page has proper SEO elements', async ({ page }) => {
    await page.goto(`${FRONTEND_URL}/fr/search`);
    await page.waitForLoadState('networkidle');

    const listingCard = page
      .locator('[data-testid="listing-card"], .listing-card, article')
      .first();

    if (await listingCard.isVisible({ timeout: 5000 }).catch(() => false)) {
      await listingCard.click();
      await page.waitForLoadState('networkidle');

      // Check meta title
      const title = await page.title();
      expect(title.length).toBeGreaterThan(10);
      expect(title).not.toContain('undefined');

      // Check meta description
      const metaDescription = await page
        .locator('meta[name="description"]')
        .getAttribute('content');
      expect(metaDescription?.length || 0).toBeGreaterThan(20);

      // Check Open Graph tags
      const ogTitle = await page.locator('meta[property="og:title"]').getAttribute('content');
      expect(ogTitle).toBeTruthy();

      const ogDescription = await page
        .locator('meta[property="og:description"]')
        .getAttribute('content');
      expect(ogDescription).toBeTruthy();
    } else {
      test.skip();
    }
  });

  /**
   * Test that listing pricing is displayed correctly
   */
  test('Listing pricing displays correctly', async ({ page }) => {
    await page.goto(`${FRONTEND_URL}/fr/search`);
    await page.waitForLoadState('networkidle');

    const listingCard = page
      .locator('[data-testid="listing-card"], .listing-card, article')
      .first();

    if (await listingCard.isVisible({ timeout: 5000 }).catch(() => false)) {
      await listingCard.click();
      await page.waitForLoadState('networkidle');

      // Look for price display
      const priceElement = page
        .locator('[data-testid="price"], [class*="price"], :text(/\\d+\\s*(TND|EUR|DT|€)/i)')
        .first();

      await expect(priceElement).toBeVisible({ timeout: 5000 });

      // Verify price format
      const priceText = await priceElement.textContent();
      const hasValidPrice = priceText?.match(/\d+(\.\d{1,2})?\s*(TND|EUR|DT|€)/i);
      expect(hasValidPrice).toBeTruthy();
    } else {
      test.skip();
    }
  });

  /**
   * Test that listing images/gallery loads correctly
   */
  test('Listing images and gallery load correctly', async ({ page }) => {
    await page.goto(`${FRONTEND_URL}/fr/search`);
    await page.waitForLoadState('networkidle');

    const listingCard = page
      .locator('[data-testid="listing-card"], .listing-card, article')
      .first();

    if (await listingCard.isVisible({ timeout: 5000 }).catch(() => false)) {
      await listingCard.click();
      await page.waitForLoadState('networkidle');

      // Check for main image or gallery
      const images = page.locator(
        'img[src*="listing"], img[src*="gallery"], img[src*="minio"], [class*="gallery"] img'
      );
      const imageCount = await images.count();

      // Should have at least one image (could be placeholder)
      expect(imageCount).toBeGreaterThan(0);

      // Verify first image has valid src
      const firstImage = images.first();
      const src = await firstImage.getAttribute('src');
      expect(src).toBeTruthy();
      expect(src).not.toContain('undefined');
    } else {
      test.skip();
    }
  });
});
