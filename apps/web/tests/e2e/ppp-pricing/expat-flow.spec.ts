/**
 * E2E Test: Expat Flow (France IP + Tunisia Billing)
 *
 * Test Scenario: French expat living in Tunisia, browsing from Tunisia with Tunisia billing
 *
 * Expected Behavior:
 * 1. IP detected as France → prices initially shown in EUR
 * 2. User provides Tunisia billing address
 * 3. System detects mismatch (France IP + Tunisia billing)
 * 4. Disclosure modal appears explaining price will change to TND
 * 5. User accepts → prices update to TND
 * 6. This is the inverse of the VPN scenario but should behave identically
 *
 * PPP Spec References:
 * - Section 4.2: Mismatch Detection (IP ≠ billing country)
 * - Section 4.3: Disclosure Modal Requirements
 * - Section 4.4: Price Update After Acceptance
 * - Section 5.1: Currency Lock After Disclosure
 *
 * Note: This is intentionally similar to vpn-user-flow.spec.ts
 * The difference is the user's actual location vs. billing address is reversed,
 * but the system behavior should be identical.
 */

import { test, expect, Page } from '@playwright/test';
import { testData } from '../../fixtures/ppp-test-data';

test.describe('Expat Flow - France IP + Tunisia Billing', () => {
  let page: Page;

  test.beforeEach(async ({ browser }) => {
    console.log('🧪 Test Setup: Creating new context with France IP (expat browsing from France)');

    // Create context with France IP (expat temporarily in France)
    const context = await browser.newContext({
      extraHTTPHeaders: {
        'X-Forwarded-For': testData.ips.france,
        'X-Real-IP': testData.ips.france,
      },
      locale: 'en-US',
    });

    page = await context.newPage();

    console.log(`✅ Context created with IP: ${testData.ips.france} (France location)`);
  });

  test.afterEach(async () => {
    await page.close();
  });

  test('should show EUR initially then trigger disclosure when Tunisia billing provided', async () => {
    console.log('📍 Step 1: Navigate to listing and verify EUR prices');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    const priceElement = page.getByTestId('listing-price');
    await expect(priceElement).toBeVisible();

    const initialPrice = await priceElement.textContent();
    console.log(`💰 Initial price (based on France IP): ${initialPrice}`);

    expect(initialPrice).toContain('€');

    console.log('📍 Step 2: Start booking flow');

    await page.getByTestId('book-now-button').click();
    await page.getByTestId('booking-date-selector').click();
    await page.getByTestId(`date-${testData.booking.date}`).click();
    await page.getByTestId(`time-slot-${testData.booking.timeSlot}`).click();
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('continue-to-traveler-info').click();

    console.log('📍 Step 3: Fill traveler info (French expat)');

    // Use French name but Tunisia will be billing address
    await page.getByTestId('traveler-email').fill(testData.travelers.france.email);
    await page.getByTestId('traveler-first-name').fill(testData.travelers.france.firstName);
    await page.getByTestId('traveler-last-name').fill(testData.travelers.france.lastName);
    await page.getByTestId('traveler-phone').fill(testData.travelers.france.phone);
    await page.getByTestId('continue-to-billing').click();

    console.log('📍 Step 4: Provide Tunisia billing address (residence)');

    await page.getByTestId('billing-country').selectOption(testData.addresses.tunisia.country_code);
    await page.getByTestId('billing-city').fill(testData.addresses.tunisia.city);
    await page.getByTestId('billing-postal-code').fill(testData.addresses.tunisia.postal_code);
    await page.getByTestId('billing-address-line1').fill(testData.addresses.tunisia.address_line1);
    await page.getByTestId('continue-to-review').click();

    console.log('📍 Step 5: Verify disclosure modal appears');

    const disclosureModal = page.getByTestId('price-change-disclosure');
    await expect(disclosureModal).toBeVisible({ timeout: 5000 });

    console.log('✅ Disclosure modal appeared');

    // Verify modal explains the change from EUR to TND
    const modalExplanation = page.getByTestId('disclosure-modal-explanation');
    const explanationText = await modalExplanation.textContent();
    console.log(`📋 Explanation: ${explanationText}`);

    expect(explanationText).toContain('Tunisia');
    expect(explanationText).toContain('TND');

    const oldPrice = await page.getByTestId('disclosure-old-price').textContent();
    const newPrice = await page.getByTestId('disclosure-new-price').textContent();
    console.log(`💰 Old price: ${oldPrice} → New price: ${newPrice}`);

    expect(oldPrice).toContain('€');
    expect(newPrice).toContain('TND');

    console.log('✅ Test complete: Expat flow triggers disclosure correctly');
  });

  test('should allow expat to complete booking in TND after acceptance', async () => {
    console.log('📍 Step 1: Navigate through booking to disclosure');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    await page.getByTestId('book-now-button').click();
    await page.getByTestId('booking-date-selector').click();
    await page.getByTestId(`date-${testData.booking.date}`).click();
    await page.getByTestId(`time-slot-${testData.booking.timeSlot}`).click();
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('continue-to-traveler-info').click();

    await page.getByTestId('traveler-email').fill(testData.travelers.france.email);
    await page.getByTestId('traveler-first-name').fill(testData.travelers.france.firstName);
    await page.getByTestId('traveler-last-name').fill(testData.travelers.france.lastName);
    await page.getByTestId('traveler-phone').fill(testData.travelers.france.phone);
    await page.getByTestId('continue-to-billing').click();

    await page.getByTestId('billing-country').selectOption(testData.addresses.tunisia.country_code);
    await page.getByTestId('billing-city').fill(testData.addresses.tunisia.city);
    await page.getByTestId('billing-postal-code').fill(testData.addresses.tunisia.postal_code);
    await page.getByTestId('billing-address-line1').fill(testData.addresses.tunisia.address_line1);
    await page.getByTestId('continue-to-review').click();

    await page.waitForSelector('[data-testid="price-change-disclosure"]', { state: 'visible' });

    console.log('📍 Step 2: Accept disclosure');

    await page.getByTestId('disclosure-accept-button').click();

    console.log('📍 Step 3: Verify prices updated to TND');

    await expect(page.getByTestId('price-change-disclosure')).not.toBeVisible();

    const reviewTotal = page.getByTestId('review-total-price');
    const reviewTotalText = await reviewTotal.textContent();
    console.log(`💰 Review total: ${reviewTotalText}`);

    expect(reviewTotalText).toContain('TND');

    console.log('📍 Step 4: Create hold in TND');

    await page.getByTestId('create-hold-button').click();
    await page.waitForSelector('[data-testid="hold-timer"]', { state: 'visible' });

    const holdTotal = page.getByTestId('hold-total-price');
    const holdTotalText = await holdTotal.textContent();
    console.log(`💰 Hold total: ${holdTotalText}`);

    expect(holdTotalText).toContain('TND');

    console.log('📍 Step 5: Complete payment in TND');

    await page.getByTestId('proceed-to-payment').click();
    await page.getByTestId('payment-method-mock').click();
    await page.getByTestId('complete-payment-button').click();

    await page.waitForSelector('[data-testid="booking-confirmation"]', { state: 'visible' });

    const confirmationTotal = page.getByTestId('confirmation-total');
    const confirmationTotalText = await confirmationTotal.textContent();
    console.log(`💰 Confirmation total: ${confirmationTotalText}`);

    expect(confirmationTotalText).toContain('TND');

    console.log('✅ Test complete: Expat completed booking in TND');
  });

  test('should handle multiple country mismatches consistently', async () => {
    console.log('📍 Test: Try different billing countries and verify disclosure');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    await page.getByTestId('book-now-button').click();
    await page.getByTestId('booking-date-selector').click();
    await page.getByTestId(`date-${testData.booking.date}`).click();
    await page.getByTestId(`time-slot-${testData.booking.timeSlot}`).click();
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('continue-to-traveler-info').click();

    await page.getByTestId('traveler-email').fill(testData.travelers.france.email);
    await page.getByTestId('traveler-first-name').fill(testData.travelers.france.firstName);
    await page.getByTestId('traveler-last-name').fill(testData.travelers.france.lastName);
    await page.getByTestId('traveler-phone').fill(testData.travelers.france.phone);
    await page.getByTestId('continue-to-billing').click();

    console.log('📍 Scenario 1: Tunisia billing (should trigger disclosure)');

    await page.getByTestId('billing-country').selectOption(testData.addresses.tunisia.country_code);
    await page.getByTestId('billing-city').fill(testData.addresses.tunisia.city);
    await page.getByTestId('billing-postal-code').fill(testData.addresses.tunisia.postal_code);
    await page.getByTestId('billing-address-line1').fill(testData.addresses.tunisia.address_line1);
    await page.getByTestId('continue-to-review').click();

    // Should show disclosure
    await expect(page.getByTestId('price-change-disclosure')).toBeVisible({ timeout: 5000 });
    console.log('✅ Disclosure shown for Tunisia billing');

    // Cancel and try different country
    await page.getByTestId('disclosure-cancel-button').click();
    await expect(page.getByTestId('price-change-disclosure')).not.toBeVisible();

    console.log('📍 Scenario 2: France billing (should NOT trigger disclosure - IP matches)');

    // Change to France billing
    await page.getByTestId('billing-country').selectOption(testData.addresses.france.country_code);
    await page.getByTestId('billing-city').fill(testData.addresses.france.city);
    await page.getByTestId('billing-postal-code').fill(testData.addresses.france.postal_code);
    await page.getByTestId('billing-address-line1').fill(testData.addresses.france.address_line1);
    await page.getByTestId('continue-to-review').click();

    // Should NOT show disclosure (IP and billing match)
    await expect(page.getByTestId('price-change-disclosure')).not.toBeVisible();
    console.log('✅ No disclosure for France billing (matches IP)');

    // Should be on review page now
    await expect(page.getByTestId('review-total-price')).toBeVisible();

    console.log('✅ Test complete: Mismatch detection works correctly');
  });

  test('should persist currency choice if user goes back after acceptance', async () => {
    console.log('📍 Step 1: Accept disclosure to switch to TND');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    await page.getByTestId('book-now-button').click();
    await page.getByTestId('booking-date-selector').click();
    await page.getByTestId(`date-${testData.booking.date}`).click();
    await page.getByTestId(`time-slot-${testData.booking.timeSlot}`).click();
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('continue-to-traveler-info').click();

    await page.getByTestId('traveler-email').fill(testData.travelers.france.email);
    await page.getByTestId('traveler-first-name').fill(testData.travelers.france.firstName);
    await page.getByTestId('traveler-last-name').fill(testData.travelers.france.lastName);
    await page.getByTestId('traveler-phone').fill(testData.travelers.france.phone);
    await page.getByTestId('continue-to-billing').click();

    await page.getByTestId('billing-country').selectOption(testData.addresses.tunisia.country_code);
    await page.getByTestId('billing-city').fill(testData.addresses.tunisia.city);
    await page.getByTestId('billing-postal-code').fill(testData.addresses.tunisia.postal_code);
    await page.getByTestId('billing-address-line1').fill(testData.addresses.tunisia.address_line1);
    await page.getByTestId('continue-to-review').click();

    await page.waitForSelector('[data-testid="price-change-disclosure"]', { state: 'visible' });
    await page.getByTestId('disclosure-accept-button').click();

    // Verify TND on review page
    let reviewTotal = await page.getByTestId('review-total-price').textContent();
    console.log(`💰 Review total (TND): ${reviewTotal}`);
    expect(reviewTotal).toContain('TND');

    console.log('📍 Step 2: Go back to traveler info');

    await page.getByTestId('back-to-traveler-info').click();

    console.log('📍 Step 3: Go forward again to review');

    await page.getByTestId('continue-to-billing').click();
    await page.getByTestId('continue-to-review').click();

    // Should NOT show disclosure again
    await expect(page.getByTestId('price-change-disclosure')).not.toBeVisible();

    // Should still show TND
    reviewTotal = await page.getByTestId('review-total-price').textContent();
    console.log(`💰 Review total (should still be TND): ${reviewTotal}`);
    expect(reviewTotal).toContain('TND');

    console.log('✅ Test complete: Currency choice persisted');
  });

  test('should show appropriate messaging for expat users', async () => {
    console.log('📍 Test: Verify disclosure messaging is appropriate for expats');

    await page.goto(`/en/listings/${testData.listing.slug}`);
    await page.waitForLoadState('networkidle');

    await page.getByTestId('book-now-button').click();
    await page.getByTestId('booking-date-selector').click();
    await page.getByTestId(`date-${testData.booking.date}`).click();
    await page.getByTestId(`time-slot-${testData.booking.timeSlot}`).click();
    await page.getByTestId('person-type-adult-increment').click();
    await page.getByTestId('continue-to-traveler-info').click();

    await page.getByTestId('traveler-email').fill(testData.travelers.france.email);
    await page.getByTestId('traveler-first-name').fill(testData.travelers.france.firstName);
    await page.getByTestId('traveler-last-name').fill(testData.travelers.france.lastName);
    await page.getByTestId('traveler-phone').fill(testData.travelers.france.phone);
    await page.getByTestId('continue-to-billing').click();

    await page.getByTestId('billing-country').selectOption(testData.addresses.tunisia.country_code);
    await page.getByTestId('billing-city').fill(testData.addresses.tunisia.city);
    await page.getByTestId('billing-postal-code').fill(testData.addresses.tunisia.postal_code);
    await page.getByTestId('billing-address-line1').fill(testData.addresses.tunisia.address_line1);
    await page.getByTestId('continue-to-review').click();

    await page.waitForSelector('[data-testid="price-change-disclosure"]', { state: 'visible' });

    console.log('📍 Verify disclosure messaging');

    const modalExplanation = page.getByTestId('disclosure-modal-explanation');
    const explanationText = await modalExplanation.textContent();
    console.log(`📋 Explanation: ${explanationText}`);

    // Should mention billing country
    expect(explanationText).toContain('Tunisia');

    // Should explain why price is changing
    expect(explanationText).toMatch(/billing.*address/i);

    // Should mention both currencies
    expect(explanationText).toMatch(/EUR|€/);
    expect(explanationText).toContain('TND');

    // Should be clear and professional
    expect(explanationText.length).toBeGreaterThan(50);

    console.log('✅ Test complete: Messaging is appropriate');
  });
});
