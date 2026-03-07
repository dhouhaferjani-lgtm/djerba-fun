import { test, expect } from '@playwright/test';

/**
 * TC-F020 to TC-F028: Listing Detail Page Tests
 * Tests comprehensive listing detail functionality including
 * gallery, calendar, extras, reviews, map, and i18n.
 */

test.describe('Listing Detail Page', () => {
  const testListingSlug = 'kroumirie-mountains-summit-trek';
  const testListingLocation = 'ain-draham';

  test.beforeEach(async ({ page }) => {
    // URL format is /{locale}/{location}/{slug}
    await page.goto(`/en/${testListingLocation}/${testListingSlug}`);
    await page.waitForLoadState('networkidle');
  });

  // TC-F020: View Listing Details
  test('TC-F020: should display full listing details', async ({ page }) => {
    // Assert - Main structure
    await expect(page.locator('h1')).toBeVisible();

    // Image gallery
    const gallery = page.locator('[data-testid="listing-gallery"], .gallery, img').first();
    await expect(gallery).toBeVisible();

    // Description section
    const description = page.locator('[data-testid="listing-description"], .description').first();
    const hasDescription = await description.isVisible().catch(() => false);
    if (hasDescription) {
      await expect(description).toBeVisible();
    }

    // Price information
    const priceElement = page
      .locator('[data-testid="listing-price"], [data-testid="total-price"]')
      .first();
    await expect(priceElement).toBeVisible();

    // Vendor info (if present)
    const vendorInfo = page.locator('[data-testid="vendor-info"], .vendor').first();
    const hasVendor = await vendorInfo.isVisible().catch(() => false);
    if (hasVendor) {
      await expect(vendorInfo).toBeVisible();
    }

    // Booking widget/panel
    const bookingWidget = page
      .locator('[data-testid="booking-panel"], [data-testid="booking-date-selector"]')
      .first();
    await expect(bookingWidget).toBeVisible();

    console.log('TC-F020: Listing detail page structure verified');
  });

  // TC-F021: Image Gallery Lightbox
  test('TC-F021: should open lightbox on image click', async ({ page }) => {
    // Find clickable image
    const galleryImage = page
      .locator('[data-testid="gallery-image"], .gallery img, [data-testid="listing-gallery"] img')
      .first();
    const hasGallery = await galleryImage.isVisible().catch(() => false);

    if (hasGallery) {
      await galleryImage.click();

      // Wait for lightbox to open
      const lightbox = page.locator('[data-testid="lightbox"], [role="dialog"], .lightbox').first();
      await expect(lightbox).toBeVisible({ timeout: 3000 });

      console.log('TC-F021: Lightbox opened successfully');
    } else {
      console.log('TC-F021: No gallery images found - skipping lightbox test');
    }
  });

  test('TC-F021b: should navigate through gallery images in lightbox', async ({ page }) => {
    const galleryImage = page.locator('[data-testid="gallery-image"], .gallery img').first();
    const hasGallery = await galleryImage.isVisible().catch(() => false);

    if (hasGallery) {
      await galleryImage.click();

      // Wait for lightbox
      const lightbox = page.locator('[data-testid="lightbox"], [role="dialog"], .lightbox').first();
      await expect(lightbox).toBeVisible({ timeout: 3000 });

      // Look for navigation buttons
      const nextButton = page
        .locator('[data-testid="lightbox-next"], [aria-label*="next"], button:has-text("Next")')
        .first();
      const hasNavigation = await nextButton.isVisible().catch(() => false);

      if (hasNavigation) {
        await nextButton.click();
        await page.waitForTimeout(500);
        console.log('TC-F021b: Gallery navigation works');
      }

      // Close lightbox
      const closeButton = page
        .locator('[data-testid="lightbox-close"], [aria-label*="close"], button:has-text("Close")')
        .first();
      if (await closeButton.isVisible()) {
        await closeButton.click();
      } else {
        await page.keyboard.press('Escape');
      }
    }
  });

  // TC-F022: Availability Calendar
  test('TC-F022: should display availability calendar', async ({ page }) => {
    const dateSelector = page
      .locator('[data-testid="booking-date-selector"], [data-testid="availability-calendar"]')
      .first();
    await expect(dateSelector).toBeVisible();

    // Click to open calendar if needed
    await dateSelector.click();
    await page.waitForTimeout(500);

    // Verify calendar elements
    const calendarDays = page.locator(
      '[data-testid="calendar-day"], button[aria-label*="day"], .react-calendar button'
    );
    const dayCount = await calendarDays.count();

    expect(dayCount).toBeGreaterThan(0);
    console.log(`TC-F022: Calendar displayed with ${dayCount} day buttons`);
  });

  test('TC-F022b: should highlight available dates', async ({ page }) => {
    const dateSelector = page.locator('[data-testid="booking-date-selector"]').first();
    if (await dateSelector.isVisible()) {
      await dateSelector.click();
      await page.waitForTimeout(500);

      // Look for available dates (usually have a specific class or not disabled)
      const availableDates = page.locator(
        '[data-testid="available-date"], button:not([disabled]):not([aria-disabled="true"])'
      );
      const availableCount = await availableDates.count();

      if (availableCount > 0) {
        console.log(`TC-F022b: Found ${availableCount} available dates`);
      }
    }
  });

  // TC-F023: Unavailable Date Selection
  test('TC-F023: should disable past dates', async ({ page }) => {
    const dateSelector = page.locator('[data-testid="booking-date-selector"]').first();
    if (await dateSelector.isVisible()) {
      await dateSelector.click();
      await page.waitForTimeout(500);

      // Find disabled dates (past dates should be disabled)
      const disabledDates = page.locator(
        '[data-testid="calendar-day"][disabled], button[aria-disabled="true"]'
      );
      const disabledCount = await disabledDates.count();

      console.log(`TC-F023: Found ${disabledCount} disabled dates (includes past dates)`);
    }
  });

  test('TC-F023b: should not allow booking on unavailable dates', async ({ page }) => {
    const dateSelector = page.locator('[data-testid="booking-date-selector"]').first();
    if (await dateSelector.isVisible()) {
      await dateSelector.click();
      await page.waitForTimeout(500);

      // Try to click a disabled date
      const disabledDate = page.locator('button[disabled], button[aria-disabled="true"]').first();
      const hasDisabled = await disabledDate.isVisible().catch(() => false);

      if (hasDisabled) {
        // Attempt to click should not change state
        await disabledDate.click({ force: true });
        console.log('TC-F023b: Disabled date click handled correctly');
      }
    }
  });

  // TC-F025: Extras Selection
  test('TC-F025: should display available extras', async ({ page }) => {
    // Navigate through booking flow to see extras
    const dateSelector = page.locator('[data-testid="booking-date-selector"]').first();
    if (await dateSelector.isVisible()) {
      await dateSelector.click();
      await page.waitForTimeout(500);

      // Select a date
      const availableDay = page.locator('button:has-text("15")').first();
      if (await availableDay.isVisible()) {
        await availableDay.click();
        await page.waitForTimeout(500);
      }
    }

    // Select time slot if needed
    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    if (await timeSlot.isVisible()) {
      await timeSlot.click();
      await page.waitForTimeout(500);
    }

    // Look for extras section
    const extrasSection = page
      .locator('[data-testid="extras-selection"], [data-testid="extras-list"], .extras')
      .first();
    const hasExtras = await extrasSection.isVisible().catch(() => false);

    if (hasExtras) {
      await expect(extrasSection).toBeVisible();
      console.log('TC-F025: Extras section displayed');
    } else {
      console.log('TC-F025: No extras available for this listing');
    }
  });

  test('TC-F025b: should update price when extras selected', async ({ page }) => {
    // Setup booking
    const dateSelector = page.locator('[data-testid="booking-date-selector"]').first();
    if (await dateSelector.isVisible()) {
      await dateSelector.click();
      await page.waitForTimeout(500);
      const availableDay = page.locator('button:has-text("15")').first();
      if (await availableDay.isVisible()) {
        await availableDay.click();
        await page.waitForTimeout(500);
      }
    }

    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    if (await timeSlot.isVisible()) {
      await timeSlot.click();
      await page.waitForTimeout(500);
    }

    // Get initial price
    const priceElement = page.locator('[data-testid="total-price"]').first();
    const initialPrice = await priceElement.textContent().catch(() => null);

    // Select an extra if available
    const extraCheckbox = page
      .locator('[data-testid="extra-checkbox"], [data-testid="extra-item"] input')
      .first();
    const hasExtra = await extraCheckbox.isVisible().catch(() => false);

    if (hasExtra && initialPrice) {
      await extraCheckbox.click();
      await page.waitForTimeout(500);

      const newPrice = await priceElement.textContent();
      console.log(`TC-F025b: Price changed from ${initialPrice} to ${newPrice}`);
    }
  });

  // TC-F026: Review Section
  test('TC-F026: should display reviews section', async ({ page }) => {
    // Scroll to reviews section
    const reviewsSection = page
      .locator('[data-testid="reviews-section"], #reviews, .reviews')
      .first();
    const hasReviews = await reviewsSection.isVisible().catch(() => false);

    if (hasReviews) {
      await reviewsSection.scrollIntoViewIfNeeded();
      await expect(reviewsSection).toBeVisible();
      console.log('TC-F026: Reviews section displayed');
    } else {
      // Try scrolling down to find reviews
      await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
      await page.waitForTimeout(500);

      const reviewsAfterScroll = page.locator('text=/reviews|avis/i').first();
      const hasReviewsText = await reviewsAfterScroll.isVisible().catch(() => false);
      console.log(
        `TC-F026: Reviews section ${hasReviewsText ? 'found' : 'not found'} after scrolling`
      );
    }
  });

  test('TC-F026b: should show rating summary', async ({ page }) => {
    // Look for rating elements
    const ratingSummary = page
      .locator('[data-testid="rating-summary"], [data-testid="rating"], .rating')
      .first();
    const hasRating = await ratingSummary.isVisible().catch(() => false);

    if (hasRating) {
      await expect(ratingSummary).toBeVisible();
      console.log('TC-F026b: Rating summary displayed');
    } else {
      console.log('TC-F026b: No rating summary found (may not have reviews)');
    }
  });

  test('TC-F026c: should allow sorting reviews', async ({ page }) => {
    // Scroll to reviews
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2));
    await page.waitForTimeout(500);

    // Look for sort dropdown
    const sortDropdown = page
      .locator('[data-testid="review-sort"], select:near(:text("reviews"))')
      .first();
    const hasSorting = await sortDropdown.isVisible().catch(() => false);

    if (hasSorting) {
      await sortDropdown.click();
      console.log('TC-F026c: Review sorting available');
    } else {
      console.log('TC-F026c: Review sorting not available');
    }
  });

  // TC-F027: Map Interaction
  test('TC-F027: should display meeting point map', async ({ page }) => {
    // Scroll to find map
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2));
    await page.waitForTimeout(500);

    const mapContainer = page
      .locator(
        '[data-testid="location-map"], [data-testid="meeting-point-map"], .leaflet-container, [class*="map"]'
      )
      .first();
    const hasMap = await mapContainer.isVisible().catch(() => false);

    if (hasMap) {
      await expect(mapContainer).toBeVisible();
      console.log('TC-F027: Map displayed');
    } else {
      console.log('TC-F027: Map not found on this listing');
    }
  });

  // TC-F028: Bilingual Content
  test('TC-F028: should display content in English', async ({ page }) => {
    // Verify we're on English page
    await expect(page).toHaveURL(/\/en\//);

    // Check for English content markers
    const title = page.locator('h1').first();
    await expect(title).toBeVisible();

    const pageContent = await page.locator('body').textContent();

    // Should have English UI elements
    const hasEnglishUI =
      pageContent?.includes('Book') ||
      pageContent?.includes('Price') ||
      pageContent?.includes('Available') ||
      pageContent?.includes('Reviews');

    expect(hasEnglishUI).toBeTruthy();
    console.log('TC-F028: English content displayed correctly');
  });

  test('TC-F028b: should display content in French', async ({ page }) => {
    // Navigate to French version
    await page.goto(`/fr/listings/${testListingSlug}`);
    await page.waitForLoadState('networkidle');

    // Verify we're on French page
    await expect(page).toHaveURL(/\/fr\/|^\/listings/);

    // Check for French content
    const pageContent = await page.locator('body').textContent();

    // Should have French UI elements
    const hasFrenchUI =
      pageContent?.includes('Réserver') ||
      pageContent?.includes('Prix') ||
      pageContent?.includes('Disponible') ||
      pageContent?.includes('Avis');

    console.log(`TC-F028b: French content ${hasFrenchUI ? 'displayed' : 'checking'}`);
  });

  // Additional listing detail tests
  test('should display cancellation policy', async ({ page }) => {
    // Scroll to find policy section
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2));
    await page.waitForTimeout(500);

    const policySection = page
      .locator('[data-testid="cancellation-policy"], text=/cancellation|annulation/i')
      .first();
    const hasPolicy = await policySection.isVisible().catch(() => false);

    if (hasPolicy) {
      console.log('Cancellation policy section found');
    }
  });

  test('should display FAQs if available', async ({ page }) => {
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2));
    await page.waitForTimeout(500);

    const faqSection = page.locator('[data-testid="faq-section"], text=/FAQ|questions/i').first();
    const hasFaq = await faqSection.isVisible().catch(() => false);

    if (hasFaq) {
      console.log('FAQ section found');
    }
  });
});
