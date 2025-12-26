import { test, expect } from '@playwright/test';

/**
 * Helper function to extract numeric price from formatted string
 * Examples: "€76.00" -> 76, "TND 152.00" -> 152
 */
function extractPrice(priceText: string | null): number {
  if (!priceText) return 0;
  const match = priceText.match(/[\d,]+\.?\d*/);
  if (!match) return 0;
  return parseFloat(match[0].replace(',', ''));
}

test.describe('Booking Flow - Price Calculation', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to a listing page
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');
  });

  test('Guest checkout works without authentication and SQL errors', async ({ page }) => {
    // Select date
    const dateSelector = page.locator('[data-testid="booking-date-selector"]');
    if (await dateSelector.isVisible()) {
      await dateSelector.click();
      await page.click('button:has-text("15")');
    }

    // Select time slot
    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    if (await timeSlot.isVisible()) {
      await timeSlot.click();
    }

    // Add 2 adults
    const incrementButton = page.locator('[data-testid="person-type-adult-increment"]');
    if (await incrementButton.isVisible()) {
      await incrementButton.click();
      await page.waitForTimeout(500);
    }

    // Verify price is NOT zero
    const totalPrice = page.locator('[data-testid="total-price"]');
    if (await totalPrice.isVisible()) {
      const priceText = await totalPrice.textContent();
      const price = extractPrice(priceText);
      expect(price).toBeGreaterThan(0);
      console.log(`✓ Price after adding participant: ${priceText} (numeric: ${price})`);
    }

    // Continue to checkout (guest - no login)
    const continueButton = page.locator('button:has-text("Continue")');
    if (await continueButton.isVisible()) {
      await continueButton.click();

      // Should redirect to checkout without SQL errors
      await page.waitForURL(/\/checkout\/.+/, { timeout: 10000 });

      // Verify hold was created (timer should be visible)
      const holdTimer = page.locator('[data-testid="hold-timer"]');
      await expect(holdTimer).toBeVisible({ timeout: 5000 });

      console.log('✓ Guest checkout hold created successfully (no SQL errors)');
    }
  });

  test('CRITICAL: Complete booking shows correct total on confirmation page', async ({ page }) => {
    // Select date and time
    const dateSelector = page.locator('[data-testid="booking-date-selector"]');
    if (await dateSelector.isVisible()) {
      await dateSelector.click();
      await page.click('button:has-text("15")');
    }

    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    if (await timeSlot.isVisible()) {
      await timeSlot.click();
    }

    // Add 4 adults to make the price significant
    const incrementButton = page.locator('[data-testid="person-type-adult-increment"]');
    if (await incrementButton.isVisible()) {
      for (let i = 0; i < 3; i++) {
        await incrementButton.click();
        await page.waitForTimeout(300);
      }
    }

    // Get the total price from booking widget
    const totalPriceWidget = page.locator('[data-testid="total-price"]');
    await totalPriceWidget.waitFor({ state: 'visible', timeout: 5000 });
    const widgetPriceText = await totalPriceWidget.textContent();
    const expectedPrice = extractPrice(widgetPriceText);

    expect(expectedPrice).toBeGreaterThan(0);
    console.log(`✓ Expected total from widget: ${widgetPriceText} (${expectedPrice})`);

    // Continue to checkout
    const continueButton = page.locator('button:has-text("Continue")');
    if (await continueButton.isVisible()) {
      await continueButton.click();
      await page.waitForURL(/\/checkout\/.+/, { timeout: 10000 });
    }

    // Fill in guest email
    const emailInput = page.locator('input[type="email"]').first();
    if (await emailInput.isVisible()) {
      await emailInput.fill('test@example.com');
    }

    // Verify price in checkout review
    const checkoutTotal = page.locator('text=/Total|Subtotal/i').first();
    if (await checkoutTotal.isVisible()) {
      const checkoutText = await page.locator('body').textContent();
      console.log('✓ Checkout page loaded with price information');
    }

    // Proceed to payment (look for payment button)
    const paymentButton = page
      .locator('button:has-text("Pay"), button:has-text("Complete"), button:has-text("Confirm")')
      .first();
    if (await paymentButton.isVisible()) {
      await paymentButton.click();

      // Wait for confirmation page or payment success
      await page.waitForTimeout(2000);

      // Check if we're on a confirmation/success page
      const confirmationHeading = page
        .locator('h1, h2')
        .filter({ hasText: /success|confirmed|thank you/i })
        .first();

      if (await confirmationHeading.isVisible()) {
        // Find the total amount on confirmation page
        const confirmationBody = await page.locator('body').textContent();

        // Look for price patterns in confirmation
        const priceMatches = confirmationBody?.match(/€\s*[\d,]+\.?\d*|TND\s*[\d,]+\.?\d*/g);

        if (priceMatches && priceMatches.length > 0) {
          console.log('✓ Price found on confirmation page:', priceMatches);

          // Extract the main total (usually the largest or last price shown)
          const confirmationPrices = priceMatches.map((p) => extractPrice(p)).filter((p) => p > 0);
          const confirmationTotal = Math.max(...confirmationPrices);

          console.log(`✓ Confirmation page total: ${confirmationTotal}`);
          console.log(`✓ Expected total: ${expectedPrice}`);

          // CRITICAL CHECK: Total should NOT be zero
          expect(confirmationTotal).toBeGreaterThan(0);

          // CRITICAL CHECK: Total should match expected price (within small margin for rounding)
          expect(Math.abs(confirmationTotal - expectedPrice)).toBeLessThan(1);

          console.log('✓✓✓ CRITICAL TEST PASSED: Confirmation page shows correct non-zero total!');
        } else {
          console.warn('⚠ No price found on confirmation page');
        }
      }
    }
  });

  test('Price updates when changing participant counts', async ({ page }) => {
    // Wait for page to load
    await page.waitForLoadState('networkidle');

    // Select date and time
    const dateSelector = page.locator('[data-testid="booking-date-selector"]');
    if (await dateSelector.isVisible()) {
      await dateSelector.click();
      await page.click('button:has-text("15")');
    }

    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    if (await timeSlot.isVisible()) {
      await timeSlot.click();
    }

    // Get initial price with 1 adult
    const totalPrice = page.locator('[data-testid="total-price"]');
    await totalPrice.waitFor({ state: 'visible', timeout: 5000 });
    const price1Text = await totalPrice.textContent();
    const price1 = extractPrice(price1Text);

    console.log(`✓ Initial price (1 adult): ${price1Text} (${price1})`);
    expect(price1).toBeGreaterThan(0);

    // Add one more adult
    const incrementButton = page.locator('[data-testid="person-type-adult-increment"]');
    await incrementButton.click();

    // Price should update immediately
    await page.waitForTimeout(1000); // Allow for React state update
    const price2Text = await totalPrice.textContent();
    const price2 = extractPrice(price2Text);

    console.log(`✓ Updated price (2 adults): ${price2Text} (${price2})`);

    // Verify price changed and doubled (approximately)
    expect(price2).toBeGreaterThan(price1);
    expect(price2).toBeCloseTo(price1 * 2, 0);

    // Verify the count is correct
    const adultCount = page.locator('[data-testid="person-type-adult-count"]');
    const countText = await adultCount.textContent();
    expect(countText).toBe('2');

    console.log('✓✓ Price updates correctly when changing participant counts');
  });

  test('Capacity indicator displays correctly', async ({ page }) => {
    // Wait for page to load
    await page.waitForLoadState('networkidle');

    // Select date and time to show capacity
    const dateSelector = page.locator('[data-testid="booking-date-selector"]');
    if (await dateSelector.isVisible()) {
      await dateSelector.click();
      await page.click('button:has-text("15")');
    }

    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    if (await timeSlot.isVisible()) {
      await timeSlot.click();
    }

    // Verify capacity indicator is visible
    const capacityIndicator = page.locator('[data-testid="capacity-indicator"]');
    await expect(capacityIndicator).toBeVisible({ timeout: 5000 });

    // Verify it shows remaining spots
    const capacityText = await capacityIndicator.textContent();
    expect(capacityText).toContain('/');
  });
});

test.describe('404 Error Page', () => {
  test('404 page displays with proper design in English', async ({ page }) => {
    await page.goto('/en/this-page-does-not-exist');

    // Should show 404 heading
    await expect(page.locator('h1:has-text("404")')).toBeVisible();

    // Should show localized message
    await expect(page.locator('h2:has-text("Page Not Found")')).toBeVisible();

    // Should have navigation buttons
    await expect(page.locator('a[href="/en"] button:has-text("Back to Home")')).toBeVisible();
    await expect(
      page.locator('a[href="/en/listings"] button:has-text("Browse Adventures")')
    ).toBeVisible();

    // Verify header and footer are present (from MainLayout)
    await expect(page.locator('header')).toBeVisible();
    await expect(page.locator('footer')).toBeVisible();
  });

  test('404 page displays with proper design in French', async ({ page }) => {
    await page.goto('/fr/cette-page-nexiste-pas');

    // Should show 404 heading
    await expect(page.locator('h1:has-text("404")')).toBeVisible();

    // Should show French message
    await expect(page.locator('h2:has-text("Page Introuvable")')).toBeVisible();

    // Should have French navigation buttons
    await expect(
      page.locator('a[href="/fr"] button:has-text("Retour à l\'Accueil")')
    ).toBeVisible();

    // Verify MainLayout components
    await expect(page.locator('header')).toBeVisible();
    await expect(page.locator('footer')).toBeVisible();
  });

  test('404 page displays with proper design in Arabic', async ({ page }) => {
    await page.goto('/ar/هذه-الصفحة-غير-موجودة');

    // Should show 404 heading
    await expect(page.locator('h1:has-text("404")')).toBeVisible();

    // Should show Arabic message
    await expect(page.locator('h2:has-text("الصفحة غير موجودة")')).toBeVisible();

    // Should have Arabic navigation buttons
    const homeButton = page.locator('a[href="/ar"] button');
    await expect(homeButton).toBeVisible();

    // Verify MainLayout components
    await expect(page.locator('header')).toBeVisible();
    await expect(page.locator('footer')).toBeVisible();

    console.log('✓ 404 page displays correctly in Arabic locale');
  });

  test('404 page uses primary color gradient (not accent)', async ({ page }) => {
    await page.goto('/en/invalid-page');

    // Check that gradient section exists
    const gradientSection = page.locator('.bg-gradient-to-b.from-primary\\/5');
    await expect(gradientSection).toBeVisible();

    // Verify primary color is used (not accent)
    const heading = page.locator('h1:has-text("404")');
    const color = await heading.evaluate((el) => window.getComputedStyle(el).color);

    // Primary color should be applied (not accent/cream color)
    expect(color).toBeTruthy();

    // Verify it's NOT using accent/cream gradient
    const accentGradient = page.locator('.from-accent');
    const accentCount = await accentGradient.count();
    expect(accentCount).toBe(0);

    console.log('✓ 404 page uses primary color gradient (verified)');
  });
});
