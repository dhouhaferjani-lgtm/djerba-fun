/**
 * E2E Test: VPN User Flow (Tunisia IP + France Billing)
 *
 * Test Scenario: Tunisia user connects via France VPN, then provides Tunisia billing address
 *
 * Expected Behavior:
 * 1. IP detected as France → prices initially shown in EUR
 * 2. User provides Tunisia billing address
 * 3. System detects mismatch (France IP + Tunisia billing)
 * 4. Disclosure modal appears explaining price will change to TND
 * 5. User accepts → prices update to TND
 * 6. TND prices locked for remainder of flow
 * 7. Payment processed in TND
 *
 * PPP Spec References:
 * - Section 4.2: Mismatch Detection (IP ≠ billing country)
 * - Section 4.3: Disclosure Modal Requirements
 * - Section 4.4: Price Update After Acceptance
 * - Section 5.1: Currency Lock After Disclosure
 */

import { test, expect, Page } from '@playwright/test';
import { testData } from '../../fixtures/ppp-test-data';

test.describe('VPN User Flow - Tunisia IP + France Billing', () => {
  let page: Page;

  test.beforeEach(async ({ browser }) => {
    console.log('🧪 Test Setup: Creating new context with France IP (simulating VPN)');

    // Create context with France IP address (user is in Tunisia but using VPN)
    const context = await browser.newContext({
      extraHTTPHeaders: {
        'X-Forwarded-For': testData.ips.france,
        'X-Real-IP': testData.ips.france,
      },
      locale: 'en-US',
    });

    page = await context.newPage();

    console.log(`✅ Context created with IP: ${testData.ips.france} (VPN)`);
  });

  test.afterEach(async () => {
    await page.close();
  });

  test('should initially show EUR prices (based on France IP)', async () => {
    console.log('📍 Step 1: Navigate to listing page');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    console.log('📍 Step 2: Verify EUR currency detection (from IP)');

    const priceElement = page.getByTestId('listing-price');
    await expect(priceElement).toBeVisible();

    const priceText = await priceElement.textContent();
    console.log(`💰 Initial price displayed: ${priceText}`);

    // Should contain EUR currency (based on France IP)
    expect(priceText).toContain('€');

    const locationHint = page.getByTestId('price-location-hint');
    await expect(locationHint).toBeVisible();

    const hintText = await locationHint.textContent();
    console.log(`📍 Location hint: ${hintText}`);

    expect(hintText).toContain('France');

    console.log('✅ Step 2 complete: EUR currency detected from IP');
  });

  test('should trigger disclosure modal when billing address mismatches IP', async () => {
    console.log('📍 Step 1: Navigate through booking flow to billing address');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    // Start booking
    await page.getByTestId('book-now-button').click();

    // Select date and time
    await page.getByTestId('booking-date-selector').click();
    await page.getByTestId(`date-${testData.booking.date}`).click();
    await page.getByTestId(`time-slot-${testData.booking.timeSlot}`).click();

    // Add people
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('person-type-adult-increment').click();

    // Get initial EUR price
    const initialPrice = await page.getByTestId('total-price').textContent();
    console.log(`💰 Initial price (EUR): ${initialPrice}`);
    expect(initialPrice).toContain('€');

    await page.getByTestId('continue-to-traveler-info').click();

    console.log('📍 Step 2: Fill traveler information');

    await page.getByTestId('traveler-email').fill(testData.travelers.tunisia.email);
    await page.getByTestId('traveler-first-name').fill(testData.travelers.tunisia.firstName);
    await page.getByTestId('traveler-last-name').fill(testData.travelers.tunisia.lastName);
    await page.getByTestId('traveler-phone').fill(testData.travelers.tunisia.phone);

    await page.getByTestId('continue-to-billing').click();

    console.log('📍 Step 3: Fill Tunisia billing address (triggers mismatch)');

    // Fill Tunisia billing address
    await page.getByTestId('billing-country').selectOption(testData.addresses.tunisia.country_code);

    // Give system time to detect mismatch
    await page.waitForTimeout(500);

    await page.getByTestId('billing-city').fill(testData.addresses.tunisia.city);
    await page.getByTestId('billing-postal-code').fill(testData.addresses.tunisia.postal_code);
    await page.getByTestId('billing-address-line1').fill(testData.addresses.tunisia.address_line1);

    await page.getByTestId('continue-to-review').click();

    console.log('📍 Step 4: Verify disclosure modal appears');

    // Disclosure modal should appear
    const disclosureModal = page.getByTestId('price-change-disclosure');
    await expect(disclosureModal).toBeVisible({ timeout: 5000 });

    console.log('✅ Disclosure modal appeared (expected behavior)');

    console.log('📍 Step 5: Verify disclosure modal content');

    // Check modal title
    const modalTitle = page.getByTestId('disclosure-modal-title');
    await expect(modalTitle).toBeVisible();
    const titleText = await modalTitle.textContent();
    console.log(`📋 Modal title: ${titleText}`);
    expect(titleText).toMatch(/price.*change/i);

    // Check explanation text
    const modalExplanation = page.getByTestId('disclosure-modal-explanation');
    await expect(modalExplanation).toBeVisible();
    const explanationText = await modalExplanation.textContent();
    console.log(`📋 Explanation: ${explanationText}`);

    // Should mention both currencies
    expect(explanationText).toMatch(/EUR|€/);
    expect(explanationText).toContain('TND');

    // Should mention Tunisia (billing country)
    expect(explanationText).toContain('Tunisia');

    // Check old price display
    const oldPriceDisplay = page.getByTestId('disclosure-old-price');
    await expect(oldPriceDisplay).toBeVisible();
    const oldPriceText = await oldPriceDisplay.textContent();
    console.log(`💰 Old price: ${oldPriceText}`);
    expect(oldPriceText).toContain('€');

    // Check new price display
    const newPriceDisplay = page.getByTestId('disclosure-new-price');
    await expect(newPriceDisplay).toBeVisible();
    const newPriceText = await newPriceDisplay.textContent();
    console.log(`💰 New price: ${newPriceText}`);
    expect(newPriceText).toContain('TND');

    console.log('✅ Step 5 complete: Modal content validated');
  });

  test('should update prices to TND after disclosure acceptance', async () => {
    console.log('📍 Step 1: Navigate through booking flow to disclosure');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    // Complete booking flow to trigger disclosure
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

    // Fill Tunisia billing address
    await page.getByTestId('billing-country').selectOption(testData.addresses.tunisia.country_code);
    await page.getByTestId('billing-city').fill(testData.addresses.tunisia.city);
    await page.getByTestId('billing-postal-code').fill(testData.addresses.tunisia.postal_code);
    await page.getByTestId('billing-address-line1').fill(testData.addresses.tunisia.address_line1);
    await page.getByTestId('continue-to-review').click();

    // Wait for disclosure modal
    const disclosureModal = page.getByTestId('price-change-disclosure');
    await expect(disclosureModal).toBeVisible({ timeout: 5000 });

    console.log('📍 Step 2: Accept price change');

    // Click accept button
    const acceptButton = page.getByTestId('disclosure-accept-button');
    await expect(acceptButton).toBeVisible();
    await acceptButton.click();

    console.log('📍 Step 3: Verify modal closes and prices updated to TND');

    // Modal should close
    await expect(disclosureModal).not.toBeVisible({ timeout: 3000 });

    console.log('✅ Modal closed');

    // Verify we're on review page with TND prices
    const reviewTotal = page.getByTestId('review-total-price');
    await expect(reviewTotal).toBeVisible();

    const reviewTotalText = await reviewTotal.textContent();
    console.log(`💰 Review total (after acceptance): ${reviewTotalText}`);

    expect(reviewTotalText).toContain('TND');
    expect(reviewTotalText).not.toContain('€');

    console.log('✅ Step 3 complete: Prices updated to TND');

    console.log('📍 Step 4: Create hold and verify TND maintained');

    await page.getByTestId('create-hold-button').click();
    await page.waitForSelector('[data-testid="hold-timer"]', { state: 'visible' });

    const holdTotal = page.getByTestId('hold-total-price');
    const holdTotalText = await holdTotal.textContent();
    console.log(`💰 Hold total: ${holdTotalText}`);

    expect(holdTotalText).toContain('TND');

    console.log('✅ Test complete: TND prices maintained after disclosure acceptance');
  });

  test('should allow user to cancel disclosure and go back', async () => {
    console.log('📍 Step 1: Navigate through booking flow to disclosure');

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

    // Fill Tunisia billing address to trigger disclosure
    await page.getByTestId('billing-country').selectOption(testData.addresses.tunisia.country_code);
    await page.getByTestId('billing-city').fill(testData.addresses.tunisia.city);
    await page.getByTestId('billing-postal-code').fill(testData.addresses.tunisia.postal_code);
    await page.getByTestId('billing-address-line1').fill(testData.addresses.tunisia.address_line1);
    await page.getByTestId('continue-to-review').click();

    const disclosureModal = page.getByTestId('price-change-disclosure');
    await expect(disclosureModal).toBeVisible({ timeout: 5000 });

    console.log('📍 Step 2: Click cancel button');

    const cancelButton = page.getByTestId('disclosure-cancel-button');
    await expect(cancelButton).toBeVisible();
    await cancelButton.click();

    console.log('📍 Step 3: Verify modal closes and user stays on billing page');

    // Modal should close
    await expect(disclosureModal).not.toBeVisible({ timeout: 3000 });

    // Should still be on billing page (can edit address)
    const billingCountry = page.getByTestId('billing-country');
    await expect(billingCountry).toBeVisible();

    console.log('✅ Test complete: User can cancel disclosure and return to billing');
  });

  test('should complete full flow with TND after disclosure acceptance', async () => {
    console.log('📍 Step 1: Complete booking flow with disclosure acceptance');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    // Booking flow
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

    // Accept disclosure
    await page.waitForSelector('[data-testid="price-change-disclosure"]', { state: 'visible' });
    await page.getByTestId('disclosure-accept-button').click();

    // Create hold
    await page.getByTestId('create-hold-button').click();
    await page.waitForSelector('[data-testid="hold-timer"]', { state: 'visible' });

    console.log('📍 Step 2: Proceed to payment');

    await page.getByTestId('proceed-to-payment').click();

    console.log('📍 Step 3: Verify checkout in TND');

    const checkoutTotal = page.getByTestId('checkout-total');
    const checkoutTotalText = await checkoutTotal.textContent();
    console.log(`💰 Checkout total: ${checkoutTotalText}`);

    expect(checkoutTotalText).toContain('TND');

    console.log('📍 Step 4: Complete payment');

    await page.getByTestId('payment-method-mock').click();
    await page.getByTestId('complete-payment-button').click();

    console.log('📍 Step 5: Verify confirmation in TND');

    await page.waitForSelector('[data-testid="booking-confirmation"]', { state: 'visible' });

    const confirmationTotal = page.getByTestId('confirmation-total');
    const confirmationTotalText = await confirmationTotal.textContent();
    console.log(`💰 Confirmation total: ${confirmationTotalText}`);

    expect(confirmationTotalText).toContain('TND');

    console.log('✅ Test complete: Full flow completed with TND after disclosure');
  });

  test('should show disclosure only once per booking session', async () => {
    console.log('📍 Step 1: Trigger disclosure modal');

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

    // Wait for disclosure
    await page.waitForSelector('[data-testid="price-change-disclosure"]', { state: 'visible' });

    console.log('📍 Step 2: Accept disclosure');

    await page.getByTestId('disclosure-accept-button').click();

    console.log('📍 Step 3: Go back and forward - disclosure should not appear again');

    // Go back to billing
    await page.getByTestId('back-to-billing').click();

    // Go forward to review again
    await page.getByTestId('continue-to-review').click();

    // Disclosure should NOT appear again
    const disclosureModal = page.getByTestId('price-change-disclosure');
    await expect(disclosureModal).not.toBeVisible();

    console.log('✅ Test complete: Disclosure shown only once per session');
  });
});
