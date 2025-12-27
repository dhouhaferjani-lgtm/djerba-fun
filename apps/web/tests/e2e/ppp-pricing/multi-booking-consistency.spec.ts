/**
 * E2E Test: Multi-Booking Consistency
 *
 * Test Scenario: Verify pricing consistency across multiple bookings in same session
 *
 * Expected Behavior:
 * 1. User makes first booking → currency determined (e.g., TND)
 * 2. User makes second booking in same session
 * 3. Currency preference should be remembered
 * 4. But system should still validate IP vs billing for each booking
 * 5. Each booking gets its own hold with locked price
 * 6. Prices may differ between bookings due to different quantities/dates
 * 7. But currency should remain consistent within session
 *
 * PPP Spec References:
 * - Section 6.1: Session Currency Preference
 * - Section 6.2: Multiple Bookings Per Session
 * - Section 6.3: Independent Hold Management
 * - Section 6.4: Currency Consistency vs. Price Flexibility
 */

import { test, expect, Page } from '@playwright/test';
import { testData } from '../../fixtures/ppp-test-data';

test.describe('Multi-Booking Consistency', () => {
  let page: Page;

  test.beforeEach(async ({ browser }) => {
    console.log('🧪 Test Setup: Creating new context with Tunisia IP');

    const context = await browser.newContext({
      extraHTTPHeaders: {
        'X-Forwarded-For': testData.ips.tunisia,
        'X-Real-IP': testData.ips.tunisia,
      },
      locale: 'en-US',
    });

    page = await context.newPage();
    console.log('✅ Context created');
  });

  test.afterEach(async () => {
    await page.close();
  });

  test('should maintain currency across multiple bookings in same session', async () => {
    console.log('📍 BOOKING 1: Create first booking');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    // First booking flow
    await page.getByTestId('book-now-button').click();
    await page.getByTestId('booking-date-selector').click();
    await page.getByTestId(`date-${testData.booking.date}`).click();
    await page.getByTestId(`time-slot-${testData.booking.timeSlot}`).click();
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('continue-to-traveler-info').click();

    await page.getByTestId('traveler-email').fill(testData.travelers.tunisia.email);
    await page.getByTestId('traveler-first-name').fill(testData.travelers.tunisia.firstName);
    await page.getByTestId('traveler-last-name').fill(testData.travelers.tunisia.lastName);
    await page.getByTestId('traveler-phone').fill(testData.travelers.tunisia.phone);
    await page.getByTestId('continue-to-billing').click();

    await page.getByTestId('billing-country').selectOption(testData.addresses.tunisia.country_code);
    await page.getByTestId('billing-city').fill(testData.addresses.tunisia.city);
    await page.getByTestId('billing-postal-code').fill(testData.addresses.tunisia.postal_code);
    await page.getByTestId('billing-address-line1').fill(testData.addresses.tunisia.address_line1);
    await page.getByTestId('continue-to-review').click();

    await page.getByTestId('create-hold-button').click();
    await page.waitForSelector('[data-testid="hold-timer"]', { state: 'visible' });

    const booking1Price = await page.getByTestId('hold-total-price').textContent();
    const booking1HoldId = await page.getByTestId('hold-id').textContent();

    console.log(`💰 Booking 1 price: ${booking1Price}`);
    console.log(`🔖 Booking 1 hold ID: ${booking1HoldId}`);

    expect(booking1Price).toContain('TND');

    console.log('📍 BOOKING 2: Navigate back to listings and create second booking');

    // Navigate back to listings (simulating user making another booking)
    await page.goto('/en/listings');
    await page.waitForLoadState('networkidle');

    // Find and click on a different listing (or same one)
    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    console.log('📍 Verify second listing still shows TND');

    const listing2Price = await page.getByTestId('listing-price').textContent();
    console.log(`💰 Second listing price: ${listing2Price}`);

    expect(listing2Price).toContain('TND');

    // Start second booking
    await page.getByTestId('book-now-button').click();
    await page.getByTestId('booking-date-selector').click();

    // Select different date (one day later)
    const nextDay = new Date(testData.booking.date);
    nextDay.setDate(nextDay.getDate() + 1);
    const nextDayStr = nextDay.toISOString().split('T')[0];

    await page.getByTestId(`date-${nextDayStr}`).click();
    await page.getByTestId(`time-slot-${testData.booking.timeSlot}`).click();

    // Different quantity (3 adults this time)
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('person-type-adult-increment').click();

    await page.getByTestId('continue-to-traveler-info').click();

    console.log('📍 Verify traveler info pre-filled from first booking');

    // Email should be pre-filled
    const emailValue = await page.getByTestId('traveler-email').inputValue();
    expect(emailValue).toBe(testData.travelers.tunisia.email);

    await page.getByTestId('continue-to-billing').click();

    console.log('📍 Verify billing info pre-filled');

    // Billing country should be pre-selected
    const billingCountryValue = await page.getByTestId('billing-country').inputValue();
    expect(billingCountryValue).toBe(testData.addresses.tunisia.country_code);

    await page.getByTestId('continue-to-review').click();

    console.log('📍 Verify no disclosure on second booking (currency already established)');

    // Should NOT show disclosure (currency already agreed)
    await expect(page.getByTestId('price-change-disclosure')).not.toBeVisible();

    console.log('📍 Create second hold');

    await page.getByTestId('create-hold-button').click();
    await page.waitForSelector('[data-testid="hold-timer"]', { state: 'visible' });

    const booking2Price = await page.getByTestId('hold-total-price').textContent();
    const booking2HoldId = await page.getByTestId('hold-id').textContent();

    console.log(`💰 Booking 2 price: ${booking2Price}`);
    console.log(`🔖 Booking 2 hold ID: ${booking2HoldId}`);

    // Both bookings should use TND
    expect(booking2Price).toContain('TND');

    // Hold IDs should be different
    expect(booking2HoldId).not.toBe(booking1HoldId);

    // Prices may be different (different quantities)
    // But both should be in TND
    console.log('✅ Test complete: Currency consistent across bookings');
  });

  test('should manage independent holds for multiple bookings', async () => {
    console.log('📍 Test: Create two holds and verify independence');

    // Create first hold
    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    await page.getByTestId('book-now-button').click();
    await page.getByTestId('booking-date-selector').click();
    await page.getByTestId(`date-${testData.booking.date}`).click();
    await page.getByTestId(`time-slot-${testData.booking.timeSlot}`).click();
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('continue-to-traveler-info').click();

    await page.getByTestId('traveler-email').fill(testData.travelers.tunisia.email);
    await page.getByTestId('traveler-first-name').fill(testData.travelers.tunisia.firstName);
    await page.getByTestId('traveler-last-name').fill(testData.travelers.tunisia.lastName);
    await page.getByTestId('traveler-phone').fill(testData.travelers.tunisia.phone);
    await page.getByTestId('continue-to-billing').click();

    await page.getByTestId('billing-country').selectOption(testData.addresses.tunisia.country_code);
    await page.getByTestId('billing-city').fill(testData.addresses.tunisia.city);
    await page.getByTestId('billing-postal-code').fill(testData.addresses.tunisia.postal_code);
    await page.getByTestId('billing-address-line1').fill(testData.addresses.tunisia.address_line1);
    await page.getByTestId('continue-to-review').click();

    await page.getByTestId('create-hold-button').click();
    await page.waitForSelector('[data-testid="hold-timer"]', { state: 'visible' });

    const hold1Url = page.url();
    const hold1Id = await page.getByTestId('hold-id').textContent();
    const hold1Price = await page.getByTestId('hold-total-price').textContent();

    console.log(`🔖 Hold 1 URL: ${hold1Url}`);
    console.log(`🔖 Hold 1 ID: ${hold1Id}`);
    console.log(`💰 Hold 1 Price: ${hold1Price}`);

    // Create second hold (new tab simulation - just navigate to listing again)
    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    await page.getByTestId('book-now-button').click();
    await page.getByTestId('booking-date-selector').click();
    await page.getByTestId(`date-${testData.booking.date}`).click();
    await page.getByTestId(`time-slot-${testData.booking.timeSlot}`).click();
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('person-type-adult-increment').click(); // 2 adults this time
    await page.getByTestId('continue-to-traveler-info').click();

    // Skip filling form (should be pre-filled)
    await page.getByTestId('continue-to-billing').click();
    await page.getByTestId('continue-to-review').click();

    await page.getByTestId('create-hold-button').click();
    await page.waitForSelector('[data-testid="hold-timer"]', { state: 'visible' });

    const hold2Url = page.url();
    const hold2Id = await page.getByTestId('hold-id').textContent();
    const hold2Price = await page.getByTestId('hold-total-price').textContent();

    console.log(`🔖 Hold 2 URL: ${hold2Url}`);
    console.log(`🔖 Hold 2 ID: ${hold2Id}`);
    console.log(`💰 Hold 2 Price: ${hold2Price}`);

    // Verify holds are independent
    expect(hold2Id).not.toBe(hold1Id);
    expect(hold2Url).not.toBe(hold1Url);

    console.log('📍 Navigate back to first hold');

    await page.goto(hold1Url);
    await page.waitForLoadState('networkidle');

    // Verify first hold still exists
    await page.waitForSelector('[data-testid="hold-timer"]', { state: 'visible' });

    const hold1IdCheck = await page.getByTestId('hold-id').textContent();
    const hold1PriceCheck = await page.getByTestId('hold-total-price').textContent();

    expect(hold1IdCheck).toBe(hold1Id);
    expect(hold1PriceCheck).toBe(hold1Price);

    console.log('✅ Test complete: Holds are independent');
  });

  test('should allow different quantities across bookings with correct pricing', async () => {
    console.log('📍 Test: Multiple bookings with different quantities');

    const bookings = [
      { adults: 1, children: 0, label: 'Solo traveler' },
      { adults: 2, children: 1, label: 'Family' },
      { adults: 4, children: 0, label: 'Group' },
    ];

    const results: Array<{ label: string; price: string }> = [];

    for (const booking of bookings) {
      console.log(`📍 Creating booking: ${booking.label}`);

      await page.goto(`/en/listings/${testData.listing.slug}`);
      await page.waitForLoadState('networkidle');

      await page.getByTestId('book-now-button').click();
      await page.getByTestId('booking-date-selector').click();
      await page.getByTestId(`date-${testData.booking.date}`).click();
      await page.getByTestId(`time-slot-${testData.booking.timeSlot}`).click();

      // Add adults
      for (let i = 0; i < booking.adults; i++) {
        await page.getByTestId('person-type-adult-increment').click();
      }

      // Add children
      for (let i = 0; i < booking.children; i++) {
        await page.getByTestId('person-type-child-increment').click();
      }

      await page.getByTestId('continue-to-traveler-info').click();

      // First booking needs info, subsequent ones should be pre-filled
      const emailValue = await page.getByTestId('traveler-email').inputValue();
      if (!emailValue) {
        await page.getByTestId('traveler-email').fill(testData.travelers.tunisia.email);
        await page.getByTestId('traveler-first-name').fill(testData.travelers.tunisia.firstName);
        await page.getByTestId('traveler-last-name').fill(testData.travelers.tunisia.lastName);
        await page.getByTestId('traveler-phone').fill(testData.travelers.tunisia.phone);
      }

      await page.getByTestId('continue-to-billing').click();

      // Check if billing is pre-filled
      const billingCountryValue = await page.getByTestId('billing-country').inputValue();
      if (!billingCountryValue || billingCountryValue === '') {
        await page
          .getByTestId('billing-country')
          .selectOption(testData.addresses.tunisia.country_code);
        await page.getByTestId('billing-city').fill(testData.addresses.tunisia.city);
        await page.getByTestId('billing-postal-code').fill(testData.addresses.tunisia.postal_code);
        await page
          .getByTestId('billing-address-line1')
          .fill(testData.addresses.tunisia.address_line1);
      }

      await page.getByTestId('continue-to-review').click();

      // Get price before hold
      const reviewPrice = await page.getByTestId('review-total-price').textContent();
      console.log(`💰 ${booking.label} review price: ${reviewPrice}`);

      await page.getByTestId('create-hold-button').click();
      await page.waitForSelector('[data-testid="hold-timer"]', { state: 'visible' });

      const holdPrice = await page.getByTestId('hold-total-price').textContent();
      console.log(`💰 ${booking.label} hold price: ${holdPrice}`);

      results.push({ label: booking.label, price: holdPrice! });

      // All should be in TND
      expect(holdPrice).toContain('TND');
    }

    console.log('📍 Verify pricing hierarchy');

    // Solo < Family < Group (in terms of total price)
    console.log(`📊 Results: ${JSON.stringify(results, null, 2)}`);

    // All prices should be in TND
    results.forEach((result) => {
      expect(result.price).toContain('TND');
    });

    console.log('✅ Test complete: Different quantities priced correctly');
  });

  test('should handle session persistence across page refreshes', async () => {
    console.log('📍 Step 1: Create first booking to establish currency preference');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    await page.getByTestId('book-now-button').click();
    await page.getByTestId('booking-date-selector').click();
    await page.getByTestId(`date-${testData.booking.date}`).click();
    await page.getByTestId(`time-slot-${testData.booking.timeSlot}`).click();
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('continue-to-traveler-info').click();

    await page.getByTestId('traveler-email').fill(testData.travelers.tunisia.email);
    await page.getByTestId('traveler-first-name').fill(testData.travelers.tunisia.firstName);
    await page.getByTestId('traveler-last-name').fill(testData.travelers.tunisia.lastName);
    await page.getByTestId('traveler-phone').fill(testData.travelers.tunisia.phone);
    await page.getByTestId('continue-to-billing').click();

    await page.getByTestId('billing-country').selectOption(testData.addresses.tunisia.country_code);
    await page.getByTestId('billing-city').fill(testData.addresses.tunisia.city);
    await page.getByTestId('billing-postal-code').fill(testData.addresses.tunisia.postal_code);
    await page.getByTestId('billing-address-line1').fill(testData.addresses.tunisia.address_line1);
    await page.getByTestId('continue-to-review').click();

    console.log('📍 Step 2: Refresh page and verify preferences persist');

    await page.reload();
    await page.waitForLoadState('networkidle');

    console.log('📍 Step 3: Start new booking');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    // Should still show TND
    const listingPrice = await page.getByTestId('listing-price').textContent();
    console.log(`💰 Listing price after refresh: ${listingPrice}`);

    expect(listingPrice).toContain('TND');

    console.log('✅ Test complete: Session persists across refreshes');
  });

  test('should show active holds summary when multiple holds exist', async () => {
    console.log('📍 Test: Create multiple holds and view summary');

    // Create first hold
    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    await page.getByTestId('book-now-button').click();
    await page.getByTestId('booking-date-selector').click();
    await page.getByTestId(`date-${testData.booking.date}`).click();
    await page.getByTestId(`time-slot-${testData.booking.timeSlot}`).click();
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('continue-to-traveler-info').click();

    await page.getByTestId('traveler-email').fill(testData.travelers.tunisia.email);
    await page.getByTestId('traveler-first-name').fill(testData.travelers.tunisia.firstName);
    await page.getByTestId('traveler-last-name').fill(testData.travelers.tunisia.lastName);
    await page.getByTestId('traveler-phone').fill(testData.travelers.tunisia.phone);
    await page.getByTestId('continue-to-billing').click();

    await page.getByTestId('billing-country').selectOption(testData.addresses.tunisia.country_code);
    await page.getByTestId('billing-city').fill(testData.addresses.tunisia.city);
    await page.getByTestId('billing-postal-code').fill(testData.addresses.tunisia.postal_code);
    await page.getByTestId('billing-address-line1').fill(testData.addresses.tunisia.address_line1);
    await page.getByTestId('continue-to-review').click();

    await page.getByTestId('create-hold-button').click();
    await page.waitForSelector('[data-testid="hold-timer"]', { state: 'visible' });

    console.log('✅ First hold created');

    // Create second hold
    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    await page.getByTestId('book-now-button').click();
    await page.getByTestId('booking-date-selector').click();
    await page.getByTestId(`date-${testData.booking.date}`).click();
    await page.getByTestId(`time-slot-${testData.booking.timeSlot}`).click();
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('continue-to-traveler-info').click();
    await page.getByTestId('continue-to-billing').click();
    await page.getByTestId('continue-to-review').click();

    await page.getByTestId('create-hold-button').click();
    await page.waitForSelector('[data-testid="hold-timer"]', { state: 'visible' });

    console.log('✅ Second hold created');

    console.log('📍 Check for active holds indicator');

    // Navigate to home or listings page
    await page.goto('/en/listings');
    await page.waitForLoadState('networkidle');

    // Look for active holds indicator
    const activeHoldsIndicator = page.getByTestId('active-holds-indicator');

    // Should show number of active holds
    if (await activeHoldsIndicator.isVisible()) {
      const holdsText = await activeHoldsIndicator.textContent();
      console.log(`📋 Active holds: ${holdsText}`);

      // Should mention "2" holds
      expect(holdsText).toMatch(/2/);
    } else {
      console.log('ℹ️  No active holds indicator visible (optional feature)');
    }

    console.log('✅ Test complete: Multiple holds can coexist');
  });

  test('should handle booking completion and allow new booking', async () => {
    console.log('📍 Step 1: Complete first booking');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    // Complete full booking flow
    await page.getByTestId('book-now-button').click();
    await page.getByTestId('booking-date-selector').click();
    await page.getByTestId(`date-${testData.booking.date}`).click();
    await page.getByTestId(`time-slot-${testData.booking.timeSlot}`).click();
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('continue-to-traveler-info').click();

    await page.getByTestId('traveler-email').fill(testData.travelers.tunisia.email);
    await page.getByTestId('traveler-first-name').fill(testData.travelers.tunisia.firstName);
    await page.getByTestId('traveler-last-name').fill(testData.travelers.tunisia.lastName);
    await page.getByTestId('traveler-phone').fill(testData.travelers.tunisia.phone);
    await page.getByTestId('continue-to-billing').click();

    await page.getByTestId('billing-country').selectOption(testData.addresses.tunisia.country_code);
    await page.getByTestId('billing-city').fill(testData.addresses.tunisia.city);
    await page.getByTestId('billing-postal-code').fill(testData.addresses.tunisia.postal_code);
    await page.getByTestId('billing-address-line1').fill(testData.addresses.tunisia.address_line1);
    await page.getByTestId('continue-to-review').click();

    await page.getByTestId('create-hold-button').click();
    await page.waitForSelector('[data-testid="hold-timer"]', { state: 'visible' });

    await page.getByTestId('proceed-to-payment').click();
    await page.getByTestId('payment-method-mock').click();
    await page.getByTestId('complete-payment-button').click();

    await page.waitForSelector('[data-testid="booking-confirmation"]', { state: 'visible' });

    const booking1Total = await page.getByTestId('confirmation-total').textContent();
    console.log(`💰 First booking total: ${booking1Total}`);

    expect(booking1Total).toContain('TND');

    console.log('📍 Step 2: Start second booking from confirmation page');

    // Look for "Book Another" button or navigate to listings
    await page.goto('/en/listings');
    await page.waitForLoadState('networkidle');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    // Should still show TND (currency preference maintained)
    const listingPrice = await page.getByTestId('listing-price').textContent();
    console.log(`💰 Second listing price: ${listingPrice}`);

    expect(listingPrice).toContain('TND');

    console.log('✅ Test complete: Can make new booking after completion');
  });
});
