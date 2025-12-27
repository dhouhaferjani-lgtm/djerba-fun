/**
 * E2E Test: Tunisia User Flow
 *
 * Test Scenario: User from Tunisia sees TND prices throughout entire booking journey
 *
 * Expected Behavior:
 * 1. IP detected as Tunisia → prices shown in TND
 * 2. Billing address in Tunisia → confirms TND pricing
 * 3. No disclosure modal (IP and billing match)
 * 4. TND prices locked during hold period
 * 5. Payment processed in TND
 * 6. Confirmation shows TND
 *
 * PPP Spec References:
 * - Section 3.1: Currency Detection
 * - Section 3.2: Price Display
 * - Section 4.1: No disclosure when IP and billing match
 */

import { test, expect, Page } from '@playwright/test';
import { testData, calculateExpectedTotal, formatPrice } from '../../fixtures/ppp-test-data';

test.describe('Tunisia User Flow - TND Throughout', () => {
  let page: Page;

  test.beforeEach(async ({ browser }) => {
    console.log('🧪 Test Setup: Creating new context with Tunisia IP');

    // Create context with Tunisia IP address
    const context = await browser.newContext({
      extraHTTPHeaders: {
        'X-Forwarded-For': testData.ips.tunisia,
        'X-Real-IP': testData.ips.tunisia,
      },
      locale: 'en-US',
    });

    page = await context.newPage();

    console.log(`✅ Context created with IP: ${testData.ips.tunisia}`);
  });

  test.afterEach(async () => {
    await page.close();
  });

  test('should show TND prices on listing page', async () => {
    console.log('📍 Step 1: Navigate to listing page');

    // Navigate to listing
    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    console.log('📍 Step 2: Verify TND currency detection');

    // Check that price is displayed in TND
    const priceElement = page.getByTestId('listing-price');
    await expect(priceElement).toBeVisible();

    const priceText = await priceElement.textContent();
    console.log(`💰 Price displayed: ${priceText}`);

    // Should contain TND currency
    expect(priceText).toContain('TND');

    // Check location hint
    const locationHint = page.getByTestId('price-location-hint');
    await expect(locationHint).toBeVisible();

    const hintText = await locationHint.textContent();
    console.log(`📍 Location hint: ${hintText}`);

    expect(hintText).toContain('Tunisia');
    expect(hintText).toContain('TND');

    console.log('✅ Step 2 complete: TND currency detected and displayed');
  });

  test('should maintain TND prices through booking flow', async () => {
    console.log('📍 Step 1: Navigate to listing and start booking');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    // Click "Book Now" button
    const bookNowButton = page.getByTestId('book-now-button');
    await expect(bookNowButton).toBeVisible();
    await bookNowButton.click();

    console.log('📍 Step 2: Select date and time');

    // Select date
    await page.getByTestId('booking-date-selector').click();
    await page.getByTestId(`date-${testData.booking.date}`).click();

    // Select time slot
    await page.getByTestId(`time-slot-${testData.booking.timeSlot}`).click();

    console.log('📍 Step 3: Select number of people');

    // Add adults
    for (let i = 0; i < testData.booking.adults; i++) {
      await page.getByTestId('person-type-adult-increment').click();
    }

    // Add children
    for (let i = 0; i < testData.booking.children; i++) {
      await page.getByTestId('person-type-child-increment').click();
    }

    console.log('📍 Step 4: Verify total price in TND');

    const totalPrice = page.getByTestId('total-price');
    await expect(totalPrice).toBeVisible();

    const totalText = await totalPrice.textContent();
    console.log(`💰 Total price: ${totalText}`);

    expect(totalText).toContain('TND');

    // Click continue to traveler info
    await page.getByTestId('continue-to-traveler-info').click();

    console.log('📍 Step 5: Fill traveler information');

    await page.getByTestId('traveler-email').fill(testData.travelers.tunisia.email);
    await page.getByTestId('traveler-first-name').fill(testData.travelers.tunisia.firstName);
    await page.getByTestId('traveler-last-name').fill(testData.travelers.tunisia.lastName);
    await page.getByTestId('traveler-phone').fill(testData.travelers.tunisia.phone);

    // Click continue to billing
    await page.getByTestId('continue-to-billing').click();

    console.log('📍 Step 6: Fill billing address (Tunisia)');

    await page.getByTestId('billing-country').selectOption(testData.addresses.tunisia.country_code);
    await page.getByTestId('billing-city').fill(testData.addresses.tunisia.city);
    await page.getByTestId('billing-postal-code').fill(testData.addresses.tunisia.postal_code);
    await page.getByTestId('billing-address-line1').fill(testData.addresses.tunisia.address_line1);

    console.log('📍 Step 7: Verify no disclosure modal (IP and billing match)');

    // Should NOT see disclosure modal
    const disclosureModal = page.getByTestId('price-change-disclosure');
    await expect(disclosureModal).not.toBeVisible();

    console.log('✅ No disclosure modal shown (expected behavior)');

    // Continue to review
    await page.getByTestId('continue-to-review').click();

    console.log('📍 Step 8: Review and verify TND prices');

    const reviewTotal = page.getByTestId('review-total-price');
    await expect(reviewTotal).toBeVisible();

    const reviewTotalText = await reviewTotal.textContent();
    console.log(`💰 Review total: ${reviewTotalText}`);

    expect(reviewTotalText).toContain('TND');

    // Create hold
    await page.getByTestId('create-hold-button').click();

    console.log('📍 Step 9: Verify hold created with TND prices');

    // Wait for hold confirmation
    await page.waitForSelector('[data-testid="hold-timer"]', { state: 'visible' });

    const holdTotal = page.getByTestId('hold-total-price');
    const holdTotalText = await holdTotal.textContent();
    console.log(`💰 Hold total: ${holdTotalText}`);

    expect(holdTotalText).toContain('TND');

    console.log('✅ Step 9 complete: Hold created with TND prices');
  });

  test('should lock TND price during hold period', async () => {
    console.log('📍 Step 1: Create a booking hold');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    // Go through booking flow (abbreviated)
    await page.getByTestId('book-now-button').click();
    await page.getByTestId('booking-date-selector').click();
    await page.getByTestId(`date-${testData.booking.date}`).click();
    await page.getByTestId(`time-slot-${testData.booking.timeSlot}`).click();
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('continue-to-traveler-info').click();

    // Fill traveler info
    await page.getByTestId('traveler-email').fill(testData.travelers.tunisia.email);
    await page.getByTestId('traveler-first-name').fill(testData.travelers.tunisia.firstName);
    await page.getByTestId('traveler-last-name').fill(testData.travelers.tunisia.lastName);
    await page.getByTestId('traveler-phone').fill(testData.travelers.tunisia.phone);
    await page.getByTestId('continue-to-billing').click();

    // Fill billing address
    await page.getByTestId('billing-country').selectOption(testData.addresses.tunisia.country_code);
    await page.getByTestId('billing-city').fill(testData.addresses.tunisia.city);
    await page.getByTestId('billing-postal-code').fill(testData.addresses.tunisia.postal_code);
    await page.getByTestId('billing-address-line1').fill(testData.addresses.tunisia.address_line1);
    await page.getByTestId('continue-to-review').click();

    // Create hold
    await page.getByTestId('create-hold-button').click();
    await page.waitForSelector('[data-testid="hold-timer"]', { state: 'visible' });

    // Get initial price
    const initialPrice = await page.getByTestId('hold-total-price').textContent();
    console.log(`💰 Initial hold price: ${initialPrice}`);

    console.log('📍 Step 2: Wait 30 seconds and verify price unchanged');

    // Wait 30 seconds
    await page.waitForTimeout(30000);

    // Verify price is still the same
    const currentPrice = await page.getByTestId('hold-total-price').textContent();
    console.log(`💰 Current hold price: ${currentPrice}`);

    expect(currentPrice).toBe(initialPrice);

    console.log('✅ Price locked during hold period');

    // Verify timer is counting down
    const timerElement = page.getByTestId('hold-timer');
    const timerText = await timerElement.textContent();
    console.log(`⏰ Timer: ${timerText}`);

    // Timer should show less than 15 minutes
    expect(timerText).toMatch(/1[0-4]:|0\d:/);

    console.log('✅ Test complete: TND price locked during hold');
  });

  test('should complete payment in TND', async () => {
    console.log('📍 Step 1: Navigate through booking flow to payment');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    // Complete booking flow (abbreviated for brevity)
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

    console.log('📍 Step 2: Proceed to payment');

    await page.getByTestId('proceed-to-payment').click();

    console.log('📍 Step 3: Verify checkout total in TND');

    const checkoutTotal = page.getByTestId('checkout-total');
    await expect(checkoutTotal).toBeVisible();

    const checkoutTotalText = await checkoutTotal.textContent();
    console.log(`💰 Checkout total: ${checkoutTotalText}`);

    expect(checkoutTotalText).toContain('TND');

    console.log('📍 Step 4: Complete payment');

    // Select payment method (mock)
    await page.getByTestId('payment-method-mock').click();

    // Complete payment
    await page.getByTestId('complete-payment-button').click();

    console.log('📍 Step 5: Verify confirmation shows TND');

    // Wait for confirmation page
    await page.waitForSelector('[data-testid="booking-confirmation"]', { state: 'visible' });

    const confirmationTotal = page.getByTestId('confirmation-total');
    await expect(confirmationTotal).toBeVisible();

    const confirmationTotalText = await confirmationTotal.textContent();
    console.log(`💰 Confirmation total: ${confirmationTotalText}`);

    expect(confirmationTotalText).toContain('TND');

    console.log('✅ Test complete: Payment processed in TND, confirmation shows TND');
  });

  test('should show currency explanation for Tunisia users', async () => {
    console.log('📍 Step 1: Navigate to listing page');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    console.log('📍 Step 2: Check for currency info tooltip');

    // Look for info icon next to price
    const currencyInfo = page.getByTestId('currency-info-tooltip');
    await expect(currencyInfo).toBeVisible();

    // Click to open tooltip
    await currencyInfo.click();

    console.log('📍 Step 3: Verify tooltip content');

    const tooltipContent = page.getByTestId('currency-tooltip-content');
    await expect(tooltipContent).toBeVisible();

    const tooltipText = await tooltipContent.textContent();
    console.log(`ℹ️ Tooltip: ${tooltipText}`);

    // Should mention PPP pricing
    expect(tooltipText).toMatch(/purchasing power/i);
    expect(tooltipText).toContain('TND');

    console.log('✅ Test complete: Currency explanation shown');
  });
});
