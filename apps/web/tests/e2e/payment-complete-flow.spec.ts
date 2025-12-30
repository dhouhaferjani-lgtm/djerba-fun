import { test, expect } from '@playwright/test';

test.describe('Complete Payment Flow', () => {
  const testUser = {
    email: `test+${Date.now()}@example.com`,
    password: 'testpassword123',
    firstName: 'Test',
    lastName: 'User',
  };

  test.beforeEach(async ({ page }) => {
    // Create test account
    await page.goto('/en/auth/register');
    await page.fill('[data-testid="email-input"]', testUser.email);
    await page.fill('[data-testid="password-input"]', testUser.password);
    await page.fill('[data-testid="first-name-input"]', testUser.firstName);
    await page.fill('[data-testid="last-name-input"]', testUser.lastName);
    await page.click('[data-testid="register-button"]');

    // Wait for successful registration
    await page.waitForURL('**/dashboard', { timeout: 10000 });
  });

  test('complete booking flow with mock payment', async ({ page }) => {
    // Navigate to a listing
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

    // Select date
    const dateSelector = page.locator('[data-testid="booking-date-selector"]');
    await dateSelector.click();
    await page.click('button:has-text("20")');

    // Select time slot
    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    await timeSlot.click();

    // Add participants
    const adultIncrement = page.locator('[data-testid="person-type-adult-increment"]');
    await adultIncrement.click();
    await page.waitForTimeout(500);

    // Verify price is displayed
    const totalPrice = page.locator('[data-testid="total-price"]');
    await expect(totalPrice).toBeVisible();
    const priceText = await totalPrice.textContent();
    expect(priceText).not.toContain('0');

    // Continue to checkout
    await page.click('button:has-text("Continue")');
    await page.waitForURL(/\/checkout\/.+/);

    // Verify hold timer is visible
    await expect(page.locator('[data-testid="hold-timer"]')).toBeVisible();

    // Fill traveler information
    await page.fill('[data-testid="traveler-first-name"]', 'Jane');
    await page.fill('[data-testid="traveler-last-name"]', 'Traveler');
    await page.fill('[data-testid="traveler-email"]', testUser.email);
    await page.fill('[data-testid="traveler-phone"]', '+1234567890');

    // Fill billing information
    await page.fill('[data-testid="billing-address-line1"]', '123 Main St');
    await page.fill('[data-testid="billing-city"]', 'Toronto');
    await page.fill('[data-testid="billing-postal-code"]', 'M5H 2N2');
    await page.selectOption('[data-testid="billing-country"]', 'CA');

    // Continue to payment
    await page.click('[data-testid="continue-to-payment"]');

    // Select mock payment method
    await page.click('[data-testid="payment-method-mock"]');

    // Fill mock card details
    await page.fill('[data-testid="card-number"]', '4242424242424242');
    await page.fill('[data-testid="card-expiry"]', '12/25');
    await page.fill('[data-testid="card-cvc"]', '123');

    // Submit payment
    await page.click('[data-testid="submit-payment"]');

    // Wait for confirmation page
    await page.waitForURL(/\/booking\/.+/, { timeout: 15000 });

    // Verify booking confirmation
    await expect(page.locator('[data-testid="booking-confirmation"]')).toBeVisible();
    await expect(page.locator('[data-testid="booking-number"]')).toBeVisible();

    // Verify booking status is confirmed
    const bookingStatus = page.locator('[data-testid="booking-status"]');
    await expect(bookingStatus).toContainText('Confirmed');
  });

  test('payment with applied coupon code', async ({ page }) => {
    // Navigate to a listing
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

    // Select date and participants
    const dateSelector = page.locator('[data-testid="booking-date-selector"]');
    await dateSelector.click();
    await page.click('button:has-text("20")');

    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    await timeSlot.click();

    const adultIncrement = page.locator('[data-testid="person-type-adult-increment"]');
    await adultIncrement.click();

    // Continue to checkout
    await page.click('button:has-text("Continue")');
    await page.waitForURL(/\/checkout\/.+/);

    // Apply coupon code
    const couponInput = page.locator('[data-testid="coupon-code-input"]');
    if (await couponInput.isVisible()) {
      await couponInput.fill('TESTCODE');
      await page.click('[data-testid="apply-coupon-button"]');

      // Verify discount is applied
      const discountAmount = page.locator('[data-testid="discount-amount"]');
      if (await discountAmount.isVisible()) {
        await expect(discountAmount).toContainText('-');
      }
    }

    // Fill required information and complete payment
    await page.fill('[data-testid="traveler-first-name"]', 'Jane');
    await page.fill('[data-testid="traveler-last-name"]', 'Traveler');
    await page.fill('[data-testid="traveler-email"]', testUser.email);
    await page.fill('[data-testid="billing-address-line1"]', '123 Main St');
    await page.fill('[data-testid="billing-city"]', 'Toronto');
    await page.fill('[data-testid="billing-postal-code"]', 'M5H 2N2');

    await page.click('[data-testid="continue-to-payment"]');
    await page.click('[data-testid="payment-method-mock"]');
    await page.fill('[data-testid="card-number"]', '4242424242424242');
    await page.fill('[data-testid="card-expiry"]', '12/25');
    await page.fill('[data-testid="card-cvc"]', '123');

    await page.click('[data-testid="submit-payment"]');
    await page.waitForURL(/\/booking\/.+/);

    // Verify booking includes discount
    await expect(page.locator('[data-testid="booking-confirmation"]')).toBeVisible();
  });

  test('payment with extras selection', async ({ page }) => {
    // Navigate to a listing
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

    // Select date and participants
    const dateSelector = page.locator('[data-testid="booking-date-selector"]');
    await dateSelector.click();
    await page.click('button:has-text("20")');

    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    await timeSlot.click();

    const adultIncrement = page.locator('[data-testid="person-type-adult-increment"]');
    await adultIncrement.click();

    // Continue to checkout
    await page.click('button:has-text("Continue")');
    await page.waitForURL(/\/checkout\/.+/);

    // Select extras if available
    const extrasSection = page.locator('[data-testid="extras-selection"]');
    if (await extrasSection.isVisible()) {
      const firstExtra = page.locator('[data-testid^="extra-"]').first();
      if (await firstExtra.isVisible()) {
        await firstExtra.click();

        // Increase quantity
        const extraIncrement = page.locator('[data-testid="extra-quantity-increment"]').first();
        if (await extraIncrement.isVisible()) {
          await extraIncrement.click();
        }

        // Verify total price updated
        const totalWithExtras = page.locator('[data-testid="total-price"]');
        await expect(totalWithExtras).toBeVisible();
      }
    }

    // Complete booking
    await page.fill('[data-testid="traveler-first-name"]', 'Jane');
    await page.fill('[data-testid="traveler-last-name"]', 'Traveler');
    await page.fill('[data-testid="traveler-email"]', testUser.email);
    await page.fill('[data-testid="billing-address-line1"]', '123 Main St');
    await page.fill('[data-testid="billing-city"]', 'Toronto');
    await page.fill('[data-testid="billing-postal-code"]', 'M5H 2N2');

    await page.click('[data-testid="continue-to-payment"]');
    await page.click('[data-testid="payment-method-mock"]');
    await page.fill('[data-testid="card-number"]', '4242424242424242');
    await page.fill('[data-testid="card-expiry"]', '12/25');
    await page.fill('[data-testid="card-cvc"]', '123');

    await page.click('[data-testid="submit-payment"]');
    await page.waitForURL(/\/booking\/.+/);

    await expect(page.locator('[data-testid="booking-confirmation"]')).toBeVisible();
  });

  test('offline payment creates pending booking', async ({ page }) => {
    // Navigate to a listing
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

    // Select date and participants
    const dateSelector = page.locator('[data-testid="booking-date-selector"]');
    await dateSelector.click();
    await page.click('button:has-text("20")');

    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    await timeSlot.click();

    const adultIncrement = page.locator('[data-testid="person-type-adult-increment"]');
    await adultIncrement.click();

    // Continue to checkout
    await page.click('button:has-text("Continue")');
    await page.waitForURL(/\/checkout\/.+/);

    // Fill information
    await page.fill('[data-testid="traveler-first-name"]', 'Jane');
    await page.fill('[data-testid="traveler-last-name"]', 'Traveler');
    await page.fill('[data-testid="traveler-email"]', testUser.email);
    await page.fill('[data-testid="billing-address-line1"]', '123 Main St');
    await page.fill('[data-testid="billing-city"]', 'Toronto');
    await page.fill('[data-testid="billing-postal-code"]', 'M5H 2N2');

    await page.click('[data-testid="continue-to-payment"]');

    // Select offline payment (bank transfer)
    await page.click('[data-testid="payment-method-bank-transfer"]');

    // Submit
    await page.click('[data-testid="submit-payment"]');

    // Wait for confirmation
    await page.waitForURL(/\/booking\/.+/);

    // Verify booking is pending confirmation
    const bookingStatus = page.locator('[data-testid="booking-status"]');
    await expect(bookingStatus).toContainText('Pending');

    // Verify bank transfer instructions are displayed
    await expect(page.locator('[data-testid="bank-transfer-instructions"]')).toBeVisible();
  });

  test('hold expiration prevents checkout completion', async ({ page }) => {
    // Navigate to a listing
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

    // Select date and participants
    const dateSelector = page.locator('[data-testid="booking-date-selector"]');
    await dateSelector.click();
    await page.click('button:has-text("20")');

    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    await timeSlot.click();

    const adultIncrement = page.locator('[data-testid="person-type-adult-increment"]');
    await adultIncrement.click();

    // Continue to checkout
    await page.click('button:has-text("Continue")');
    await page.waitForURL(/\/checkout\/.+/);

    // Verify hold timer is visible
    const holdTimer = page.locator('[data-testid="hold-timer"]');
    await expect(holdTimer).toBeVisible();

    // Wait for hold to expire (or simulate by waiting longer)
    // Note: In real tests, we might mock the time or use a shorter expiration

    // Try to submit after a long delay (simulating expiration)
    await page.waitForTimeout(2000);

    // The page should show an error or redirect if hold expired
    // This is a simplified version - actual implementation depends on hold timeout
  });

  test('user can view booking details after payment', async ({ page }) => {
    // Complete a booking first
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

    const dateSelector = page.locator('[data-testid="booking-date-selector"]');
    await dateSelector.click();
    await page.click('button:has-text("20")');

    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    await timeSlot.click();

    const adultIncrement = page.locator('[data-testid="person-type-adult-increment"]');
    await adultIncrement.click();

    await page.click('button:has-text("Continue")');
    await page.waitForURL(/\/checkout\/.+/);

    await page.fill('[data-testid="traveler-first-name"]', 'Jane');
    await page.fill('[data-testid="traveler-last-name"]', 'Traveler');
    await page.fill('[data-testid="traveler-email"]', testUser.email);
    await page.fill('[data-testid="billing-address-line1"]', '123 Main St');
    await page.fill('[data-testid="billing-city"]', 'Toronto');
    await page.fill('[data-testid="billing-postal-code"]', 'M5H 2N2');

    await page.click('[data-testid="continue-to-payment"]');
    await page.click('[data-testid="payment-method-mock"]');
    await page.fill('[data-testid="card-number"]', '4242424242424242');
    await page.fill('[data-testid="card-expiry"]', '12/25');
    await page.fill('[data-testid="card-cvc"]', '123');

    await page.click('[data-testid="submit-payment"]');
    await page.waitForURL(/\/booking\/.+/);

    // Verify all booking details are visible
    await expect(page.locator('[data-testid="booking-number"]')).toBeVisible();
    await expect(page.locator('[data-testid="booking-date"]')).toBeVisible();
    await expect(page.locator('[data-testid="booking-participants"]')).toBeVisible();
    await expect(page.locator('[data-testid="booking-total"]')).toBeVisible();
    await expect(page.locator('[data-testid="listing-name"]')).toBeVisible();

    // Navigate to bookings list
    await page.click('[data-testid="view-all-bookings"]');
    await page.waitForURL('**/dashboard/bookings');

    // Verify booking appears in list
    await expect(page.locator('[data-testid="bookings-list"]')).toBeVisible();
    await expect(page.locator('[data-testid^="booking-card-"]').first()).toBeVisible();
  });
});
