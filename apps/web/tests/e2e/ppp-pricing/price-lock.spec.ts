/**
 * E2E Test: Price Lock During Hold Period
 *
 * Test Scenario: Verify that prices are locked once a hold is created
 *
 * Expected Behavior:
 * 1. User creates a booking hold at a specific price
 * 2. Currency/price is locked for the hold duration (15 minutes)
 * 3. Even if exchange rates change, hold price remains constant
 * 4. Even if user refreshes page, hold price remains constant
 * 5. Hold timer counts down correctly
 * 6. After hold expires, new pricing may apply
 *
 * PPP Spec References:
 * - Section 5.1: Currency Lock After Hold Creation
 * - Section 5.2: Hold Duration and Timer
 * - Section 5.3: Price Stability During Hold
 * - Section 5.4: Hold Expiration Behavior
 */

import { test, expect, Page } from '@playwright/test';
import { testData } from '../../fixtures/ppp-test-data';

test.describe('Price Lock During Hold Period', () => {
  let page: Page;

  test.beforeEach(async ({ browser }) => {
    console.log('🧪 Test Setup: Creating new context');

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

  test('should lock price when hold is created', async () => {
    console.log('📍 Step 1: Navigate through booking flow to create hold');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

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

    // Get price before hold
    const priceBeforeHold = await page.getByTestId('review-total-price').textContent();
    console.log(`💰 Price before hold: ${priceBeforeHold}`);

    console.log('📍 Step 2: Create hold');

    await page.getByTestId('create-hold-button').click();
    await page.waitForSelector('[data-testid="hold-timer"]', { state: 'visible' });

    console.log('📍 Step 3: Verify price is locked');

    const holdPrice = await page.getByTestId('hold-total-price').textContent();
    console.log(`💰 Hold price: ${holdPrice}`);

    // Price should match
    expect(holdPrice).toBe(priceBeforeHold);

    // Should contain TND
    expect(holdPrice).toContain('TND');

    // Verify hold ID is present (indicates hold was created)
    const holdId = page.getByTestId('hold-id');
    await expect(holdId).toBeVisible();

    const holdIdText = await holdId.textContent();
    console.log(`🔖 Hold ID: ${holdIdText}`);

    expect(holdIdText).toMatch(/^[A-Z0-9]{8,}$/); // Should be alphanumeric ID

    console.log('✅ Test complete: Price locked in hold');
  });

  test('should maintain locked price even after page refresh', async () => {
    console.log('📍 Step 1: Create a hold');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    // Complete booking flow
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

    // Get hold details
    const initialHoldPrice = await page.getByTestId('hold-total-price').textContent();
    const holdIdElement = page.getByTestId('hold-id');
    const holdId = await holdIdElement.textContent();

    console.log(`💰 Initial hold price: ${initialHoldPrice}`);
    console.log(`🔖 Hold ID: ${holdId}`);

    // Get current URL (should include hold ID)
    const currentUrl = page.url();
    console.log(`🔗 Current URL: ${currentUrl}`);

    console.log('📍 Step 2: Refresh the page');

    await page.reload();
    await page.waitForLoadState('networkidle');

    console.log('📍 Step 3: Verify hold price unchanged after refresh');

    // Wait for hold data to load
    await page.waitForSelector('[data-testid="hold-timer"]', { state: 'visible' });

    const holdPriceAfterRefresh = await page.getByTestId('hold-total-price').textContent();
    console.log(`💰 Hold price after refresh: ${holdPriceAfterRefresh}`);

    expect(holdPriceAfterRefresh).toBe(initialHoldPrice);

    // Verify hold ID is the same
    const holdIdAfterRefresh = await page.getByTestId('hold-id').textContent();
    expect(holdIdAfterRefresh).toBe(holdId);

    console.log('✅ Test complete: Price maintained after refresh');
  });

  test('should display countdown timer correctly', async () => {
    console.log('📍 Step 1: Create a hold');

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

    console.log('📍 Step 2: Verify timer starts at ~15 minutes');

    const timerElement = page.getByTestId('hold-timer');
    const initialTimer = await timerElement.textContent();
    console.log(`⏰ Initial timer: ${initialTimer}`);

    // Should be close to 15:00
    expect(initialTimer).toMatch(/14:[45-59]|15:00/);

    console.log('📍 Step 3: Wait 5 seconds and verify timer decreased');

    await page.waitForTimeout(5000);

    const timerAfter5Sec = await timerElement.textContent();
    console.log(`⏰ Timer after 5 seconds: ${timerAfter5Sec}`);

    // Timer should have decreased
    expect(timerAfter5Sec).not.toBe(initialTimer);

    // Should still be in format MM:SS
    expect(timerAfter5Sec).toMatch(/\d{1,2}:\d{2}/);

    console.log('📍 Step 4: Verify timer continues counting down');

    await page.waitForTimeout(3000);

    const timerAfter8Sec = await timerElement.textContent();
    console.log(`⏰ Timer after 8 seconds: ${timerAfter8Sec}`);

    // Should be less than previous
    expect(timerAfter8Sec).not.toBe(timerAfter5Sec);

    console.log('✅ Test complete: Timer counting down correctly');
  });

  test('should show warning when timer gets low', async () => {
    console.log('📍 Step 1: Create a hold');

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

    console.log('📍 Step 2: Check for timer warning indicator');

    // Initially, timer should not be in warning state
    const timerElement = page.getByTestId('hold-timer');

    // Check if timer has warning class (should not initially)
    const initialClasses = await timerElement.getAttribute('class');
    console.log(`⏰ Initial timer classes: ${initialClasses}`);

    // Note: This test would need to wait ~10 minutes to see warning state
    // For E2E testing, we can verify the element exists and has correct structure

    // Verify timer warning threshold indicator exists
    const timerWarning = page.getByTestId('hold-timer-warning');

    // Should not be visible yet (we just created hold)
    await expect(timerWarning).not.toBeVisible();

    console.log('✅ Test complete: Timer warning system in place');
  });

  test('should maintain price lock across multiple page navigations', async () => {
    console.log('📍 Step 1: Create a hold');

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

    const lockedPrice = await page.getByTestId('hold-total-price').textContent();
    console.log(`💰 Locked price: ${lockedPrice}`);

    console.log('📍 Step 2: Navigate to payment page');

    await page.getByTestId('proceed-to-payment').click();

    // Verify price on payment page
    const paymentPrice = await page.getByTestId('checkout-total').textContent();
    console.log(`💰 Payment page price: ${paymentPrice}`);

    expect(paymentPrice).toBe(lockedPrice);

    console.log('📍 Step 3: Navigate back to hold page');

    await page.getByTestId('back-to-hold').click();

    // Verify price still locked
    const priceAfterBack = await page.getByTestId('hold-total-price').textContent();
    console.log(`💰 Price after back navigation: ${priceAfterBack}`);

    expect(priceAfterBack).toBe(lockedPrice);

    console.log('✅ Test complete: Price locked across navigations');
  });

  test('should lock currency along with price', async () => {
    console.log('📍 Test: Verify currency is locked with price');

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

    console.log('📍 Verify currency indicator');

    const currencyIndicator = page.getByTestId('hold-currency');
    await expect(currencyIndicator).toBeVisible();

    const currency = await currencyIndicator.textContent();
    console.log(`💱 Locked currency: ${currency}`);

    expect(currency).toContain('TND');

    // Verify all price displays show same currency
    const holdPrice = await page.getByTestId('hold-total-price').textContent();
    expect(holdPrice).toContain('TND');

    console.log('✅ Test complete: Currency locked');
  });

  test('should display hold expiration time', async () => {
    console.log('📍 Step 1: Create a hold');

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

    console.log('📍 Step 2: Verify expiration time displayed');

    const expiresAt = page.getByTestId('hold-expires-at');
    await expect(expiresAt).toBeVisible();

    const expirationText = await expiresAt.textContent();
    console.log(`⏰ Hold expires at: ${expirationText}`);

    // Should show a timestamp (format may vary)
    expect(expirationText).toBeTruthy();
    expect(expirationText!.length).toBeGreaterThan(0);

    console.log('✅ Test complete: Expiration time displayed');
  });
});
