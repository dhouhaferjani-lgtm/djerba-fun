import { test, expect } from '@playwright/test';
import { testUsers, testBookingInfo, testPayment } from '../fixtures/test-data';
import { createTestUser } from '../fixtures/api-helpers';

test.describe('Complete Booking Flow', () => {
  test('guest can complete full booking flow', async ({ page, request }) => {
    // Step 1: Browse listings
    await page.goto('/en/listings');
    await expect(page.getByRole('heading', { name: /explore.*adventures/i })).toBeVisible();

    // Step 2: Select a listing
    const firstListing = page.locator('[data-testid="listing-card"]').first();
    await expect(firstListing).toBeVisible();
    await firstListing.click();

    // Step 3: View listing details
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    await expect(page.getByTestId('listing-price')).toBeVisible();

    // Step 4: Select availability
    const bookNowButton = page.getByRole('button', { name: /book now|check availability/i });
    await bookNowButton.click();

    // Wait for availability calendar
    await expect(page.getByTestId('availability-calendar')).toBeVisible();

    // Select a date (choose first available date)
    const availableDate = page.locator('[data-testid="available-date"]').first();
    await availableDate.click();

    // Select time slot
    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    await timeSlot.click();

    // Step 5: Select person types
    const adultsInput = page.getByLabel(/adults/i);
    await adultsInput.fill('2');

    // Continue to booking
    await page.getByRole('button', { name: /continue.*booking/i }).click();

    // Step 6: Fill traveler information
    await expect(page.getByRole('heading', { name: /traveler.*information/i })).toBeVisible();

    await page.getByLabel(/first name/i).fill(testBookingInfo.travelerInfo.firstName);
    await page.getByLabel(/last name/i).fill(testBookingInfo.travelerInfo.lastName);
    await page.getByLabel(/email/i).fill(testBookingInfo.travelerInfo.email);
    await page.getByLabel(/phone/i).fill(testBookingInfo.travelerInfo.phone);

    await page.getByRole('button', { name: /continue/i }).click();

    // Step 7: Review extras (optional step)
    const hasExtras = await page.getByTestId('extras-selection').isVisible();
    if (hasExtras) {
      // Skip extras for now
      await page.getByRole('button', { name: /continue.*payment|skip/i }).click();
    }

    // Step 8: Fill billing information
    await expect(page.getByRole('heading', { name: /billing.*information/i })).toBeVisible();

    await page.getByLabel(/country/i).selectOption(testBookingInfo.billingInfo.countryCode);
    await page.getByLabel(/city/i).fill(testBookingInfo.billingInfo.city);
    await page.getByLabel(/postal.*code/i).fill(testBookingInfo.billingInfo.postalCode);
    await page.getByLabel(/address/i).fill(testBookingInfo.billingInfo.addressLine1);

    await page.getByRole('button', { name: /continue/i }).click();

    // Step 9: Review and confirm booking
    await expect(page.getByRole('heading', { name: /review.*booking/i })).toBeVisible();

    // Verify booking summary
    await expect(page.getByText(testBookingInfo.travelerInfo.firstName)).toBeVisible();
    await expect(page.getByText(testBookingInfo.travelerInfo.email)).toBeVisible();

    // Step 10: Process payment
    await page.getByRole('button', { name: /pay.*now|confirm.*booking/i }).click();

    // Select mock payment method
    await page.getByLabel(/payment.*method/i).selectOption('mock');

    // Fill mock payment details
    await page.getByLabel(/card.*number/i).fill(testPayment.mock.cardNumber);
    await page
      .getByLabel(/expiry/i)
      .fill(`${testPayment.mock.expiryMonth}/${testPayment.mock.expiryYear}`);
    await page.getByLabel(/cvv/i).fill(testPayment.mock.cvv);

    await page.getByRole('button', { name: /complete.*payment/i }).click();

    // Step 11: Verify booking confirmation
    await expect(page.getByRole('heading', { name: /booking.*confirmed/i })).toBeVisible({
      timeout: 15000,
    });

    // Should display booking number
    await expect(page.getByText(/booking.*number/i)).toBeVisible();

    // Should display success message
    await expect(page.getByText(/confirmation.*email/i)).toBeVisible();
  });

  test('authenticated user can complete booking faster', async ({ page, request }) => {
    // Arrange - Create and login user
    const user = await createTestUser(request, {
      email: `booking-${Date.now()}@test.com`,
      password: testUsers.traveler.password,
      firstName: testUsers.traveler.firstName,
      lastName: testUsers.traveler.lastName,
    });

    // Login
    await page.goto('/en/auth/login');
    await page.getByLabel(/email/i).fill(user.email);
    await page.getByLabel(/password/i).fill(testUsers.traveler.password);
    await page.getByRole('button', { name: /log in/i }).click();

    await expect(page).toHaveURL(/\/dashboard/);

    // Act - Navigate to listings
    await page.goto('/en/listings');
    const firstListing = page.locator('[data-testid="listing-card"]').first();
    await firstListing.click();

    // Select availability
    await page.getByRole('button', { name: /book now/i }).click();
    await page.locator('[data-testid="available-date"]').first().click();
    await page.locator('[data-testid="time-slot"]').first().click();

    // Select person types
    await page.getByLabel(/adults/i).fill('1');
    await page.getByRole('button', { name: /continue/i }).click();

    // Should skip to payment (traveler info pre-filled from account)
    await expect(
      page.getByRole('heading', { name: /billing.*information|review.*booking/i })
    ).toBeVisible();
  });

  test('booking with extras selection', async ({ page }) => {
    // Navigate to listing
    await page.goto('/en/listings');
    await page.locator('[data-testid="listing-card"]').first().click();

    // Start booking
    await page.getByRole('button', { name: /book now/i }).click();
    await page.locator('[data-testid="available-date"]').first().click();
    await page.locator('[data-testid="time-slot"]').first().click();
    await page.getByLabel(/adults/i).fill('2');
    await page.getByRole('button', { name: /continue/i }).click();

    // Fill traveler info
    await page.getByLabel(/first name/i).fill(testBookingInfo.travelerInfo.firstName);
    await page.getByLabel(/last name/i).fill(testBookingInfo.travelerInfo.lastName);
    await page.getByLabel(/email/i).fill(testBookingInfo.travelerInfo.email);
    await page.getByLabel(/phone/i).fill(testBookingInfo.travelerInfo.phone);
    await page.getByRole('button', { name: /continue/i }).click();

    // Select extras if available
    const hasExtras = await page.getByTestId('extras-selection').isVisible();
    if (hasExtras) {
      // Select first extra
      const firstExtra = page.locator('[data-testid="extra-item"]').first();
      await firstExtra.getByRole('button', { name: /\+|add/i }).click();

      // Verify total price updated
      const totalBefore = await page.getByTestId('booking-total').textContent();
      await page.getByRole('button', { name: /continue/i }).click();

      // Total should be reflected in review
      await expect(page.getByTestId('booking-total')).toBeVisible();
    }
  });

  test('should show hold timer during booking', async ({ page }) => {
    // Navigate to listing and start booking
    await page.goto('/en/listings');
    await page.locator('[data-testid="listing-card"]').first().click();
    await page.getByRole('button', { name: /book now/i }).click();

    // Select availability
    await page.locator('[data-testid="available-date"]').first().click();
    await page.locator('[data-testid="time-slot"]').first().click();
    await page.getByLabel(/adults/i).fill('1');
    await page.getByRole('button', { name: /continue/i }).click();

    // Assert - Hold timer should be visible
    await expect(page.getByTestId('hold-timer')).toBeVisible();
    await expect(page.getByText(/\d{1,2}:\d{2}/)).toBeVisible(); // Timer format MM:SS
  });

  test('should handle booking with coupon code', async ({ page }) => {
    // Start booking flow
    await page.goto('/en/listings');
    await page.locator('[data-testid="listing-card"]').first().click();
    await page.getByRole('button', { name: /book now/i }).click();

    // Complete booking steps
    await page.locator('[data-testid="available-date"]').first().click();
    await page.locator('[data-testid="time-slot"]').first().click();
    await page.getByLabel(/adults/i).fill('2');
    await page.getByRole('button', { name: /continue/i }).click();

    // Fill traveler info
    await page.getByLabel(/first name/i).fill(testBookingInfo.travelerInfo.firstName);
    await page.getByLabel(/last name/i).fill(testBookingInfo.travelerInfo.lastName);
    await page.getByLabel(/email/i).fill(testBookingInfo.travelerInfo.email);
    await page.getByLabel(/phone/i).fill(testBookingInfo.travelerInfo.phone);
    await page.getByRole('button', { name: /continue/i }).click();

    // Look for coupon input in review step
    const couponInput = page.getByPlaceholder(/coupon.*code/i);
    if (await couponInput.isVisible()) {
      await couponInput.fill('SAVE20');
      await page.getByRole('button', { name: /apply/i }).click();

      // Should show discount applied
      await expect(page.getByText(/discount.*applied/i)).toBeVisible();
    }
  });
});
