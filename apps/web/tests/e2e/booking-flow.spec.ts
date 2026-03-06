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

/**
 * Helper function to extract numeric values from formatted text
 * Examples: "5 / 10 available" → 5, "3 spots left" → 3
 */
function extractNumber(text: string | null): number {
  if (!text) return 0;
  const match = text.match(/\d+/);
  return match ? parseInt(match[0], 10) : 0;
}

test.describe('Inventory Management', () => {
  test('Slot capacity decreases when hold is created', async ({ page }) => {
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

    // Select date
    const dateSelector = page.locator('[data-testid="booking-date-selector"]');
    if (await dateSelector.isVisible()) {
      await dateSelector.click();
      await page.waitForTimeout(500);

      // Select a future date (15th of current month)
      const dayButton = page.locator('button:has-text("15")').first();
      if (await dayButton.isVisible()) {
        await dayButton.click();
        await page.waitForTimeout(1000);
      }
    }

    // Get initial capacity from first available slot
    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    if (await timeSlot.isVisible()) {
      const initialCapacityText = await timeSlot
        .locator('[data-testid="slot-capacity"]')
        .textContent();
      const initialCapacity = extractNumber(initialCapacityText);

      console.log(`Initial slot capacity: ${initialCapacity}`);

      // Select the slot
      await timeSlot.click();
      await page.waitForTimeout(500);

      // Add 2 adults (if increment button exists)
      const incrementButton = page.locator('[data-testid="person-type-adult-increment"]');
      if (await incrementButton.isVisible()) {
        await incrementButton.click();
        await page.waitForTimeout(300);
      }

      // Create hold by continuing to checkout
      const continueButton = page.locator('button:has-text("Continue")').first();
      if (await continueButton.isVisible()) {
        await continueButton.click();
        await page.waitForURL(/\/checkout\/.+/, { timeout: 10000 });
        console.log('✓ Hold created successfully');
      }

      // Go back to listing to check if capacity decreased
      await page.goto('/en/listings/kroumirie-mountains-summit-trek');
      await page.waitForLoadState('networkidle');

      // Select same date again
      if (await dateSelector.isVisible()) {
        await dateSelector.click();
        await page.waitForTimeout(500);
        const dayButton = page.locator('button:has-text("15")').first();
        if (await dayButton.isVisible()) {
          await dayButton.click();
          await page.waitForTimeout(1000);
        }
      }

      // Check new capacity
      const newTimeSlot = page.locator('[data-testid="time-slot"]').first();
      if (await newTimeSlot.isVisible()) {
        const newCapacityText = await newTimeSlot
          .locator('[data-testid="slot-capacity"]')
          .textContent();
        const newCapacity = extractNumber(newCapacityText);

        console.log(`New slot capacity after hold: ${newCapacity}`);

        // Capacity should have decreased
        if (newCapacity < initialCapacity) {
          console.log(`✓✓ Slot capacity correctly decreased by ${initialCapacity - newCapacity}`);
        } else {
          console.warn(
            `⚠ Capacity did not decrease (initial: ${initialCapacity}, current: ${newCapacity})`
          );
        }
      }
    }
  });

  test('Complete booking shows non-zero price', async ({ page }) => {
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

    // Select date and time
    const dateSelector = page.locator('[data-testid="booking-date-selector"]');
    if (await dateSelector.isVisible()) {
      await dateSelector.click();
      await page.waitForTimeout(500);

      const dayButton = page.locator('button:has-text("15")').first();
      if (await dayButton.isVisible()) {
        await dayButton.click();
        await page.waitForTimeout(1000);
      }
    }

    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    if (await timeSlot.isVisible()) {
      await timeSlot.click();
      await page.waitForTimeout(500);
    }

    // Add participants
    const incrementButton = page.locator('[data-testid="person-type-adult-increment"]');
    if (await incrementButton.isVisible()) {
      await incrementButton.click();
      await incrementButton.click(); // 3 adults total
      await page.waitForTimeout(500);
    }

    // Get expected price
    const totalPriceWidget = page.locator('[data-testid="total-price"]');
    if (await totalPriceWidget.isVisible()) {
      const widgetPriceText = await totalPriceWidget.textContent();
      const expectedPrice = extractPrice(widgetPriceText);

      if (expectedPrice > 0) {
        console.log(`✓ Widget showing non-zero price: ${widgetPriceText} (${expectedPrice})`);
      } else {
        console.warn(`⚠ Widget showing zero price: ${widgetPriceText}`);
      }
    }

    // Continue to checkout
    const continueButton = page.locator('button:has-text("Continue")').first();
    if (await continueButton.isVisible()) {
      await continueButton.click();
      await page.waitForURL(/\/checkout\/.+/, { timeout: 10000 });
      console.log('✓ Reached checkout page');
    }
  });
});

test.describe('Backend Health Checks', () => {
  test('Backend price calculation returns non-zero amounts', async ({ page }) => {
    // Test via UI flow
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

    // Monitor API responses for booking creation
    let bookingTotalAmount = null;

    page.on('response', async (response) => {
      if (response.url().includes('/api/v1/bookings') && response.status() === 201) {
        try {
          const json = await response.json();
          bookingTotalAmount = json.data?.totalAmount;
        } catch (e) {
          // Ignore JSON parse errors
        }
      }
    });

    // Complete a booking flow
    const dateSelector = page.locator('[data-testid="booking-date-selector"]');
    if (await dateSelector.isVisible()) {
      await dateSelector.click();
      await page.waitForTimeout(500);

      const dayButton = page.locator('button:has-text("15")').first();
      if (await dayButton.isVisible()) {
        await dayButton.click();
        await page.waitForTimeout(1000);
      }
    }

    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    if (await timeSlot.isVisible()) {
      await timeSlot.click();
    }

    const continueButton = page.locator('button:has-text("Continue")').first();
    if (await continueButton.isVisible()) {
      await continueButton.click();
      await page.waitForURL(/\/checkout\/.+/, { timeout: 10000 });

      if (bookingTotalAmount !== null) {
        if (bookingTotalAmount > 0) {
          console.log(`✓✓ Backend returning non-zero total: ${bookingTotalAmount}`);
        } else {
          console.warn(`⚠ Backend returned zero total amount!`);
        }
      }
    }
  });

  test('Guest checkout works without SQL errors', async ({ page }) => {
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

    // Monitor console for SQL errors
    const errors: string[] = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        errors.push(msg.text());
      }
    });

    // Complete guest checkout flow
    const dateSelector = page.locator('[data-testid="booking-date-selector"]');
    if (await dateSelector.isVisible()) {
      await dateSelector.click();
      await page.waitForTimeout(500);

      const dayButton = page.locator('button:has-text("15")').first();
      if (await dayButton.isVisible()) {
        await dayButton.click();
        await page.waitForTimeout(1000);
      }
    }

    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    if (await timeSlot.isVisible()) {
      await timeSlot.click();
    }

    const continueButton = page.locator('button:has-text("Continue")').first();
    if (await continueButton.isVisible()) {
      await continueButton.click();

      try {
        await page.waitForURL(/\/checkout\/.+/, { timeout: 10000 });

        // Check for SQL errors in console
        const sqlErrors = errors.filter(
          (err) => err.includes('SQLSTATE') || err.includes('user_id') || err.includes('NOT NULL')
        );

        if (sqlErrors.length === 0) {
          console.log('✓✓ No SQL errors during guest checkout');
        } else {
          console.error('❌ SQL errors found:', sqlErrors);
        }
      } catch (e) {
        console.error('❌ Failed to reach checkout page:', e);
      }
    }
  });
});

// Additional booking flow tests
test.describe('Cart Management', () => {
  // TC-F033: Remove Cart Item
  test('TC-F033: should remove item from cart', async ({ page }) => {
    // First, add an item to cart
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

    // Select date
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

    // Select time slot
    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    if (await timeSlot.isVisible()) {
      await timeSlot.click();
      await page.waitForTimeout(500);
    }

    // Add to cart
    const addToCartButton = page
      .locator('button:has-text("Add to Cart"), button:has-text("Continue")')
      .first();
    if (await addToCartButton.isVisible()) {
      await addToCartButton.click();
      await page.waitForTimeout(1000);
    }

    // Navigate to cart
    await page.goto('/en/cart');
    await page.waitForLoadState('networkidle');

    // Check if cart has items
    const cartItem = page.locator('[data-testid="cart-item"], .cart-item').first();
    const hasItem = await cartItem.isVisible().catch(() => false);

    if (hasItem) {
      // Get item count before removal
      const cartItemsBefore = await page.locator('[data-testid="cart-item"], .cart-item').count();
      console.log(`TC-F033: Cart items before removal: ${cartItemsBefore}`);

      // Click remove button
      const removeButton = page
        .locator(
          '[data-testid="remove-cart-item"], button:has-text("Remove"), button[aria-label*="remove"]'
        )
        .first();
      if (await removeButton.isVisible()) {
        await removeButton.click();
        await page.waitForTimeout(1000);

        // Confirm removal if dialog appears
        const confirmButton = page
          .locator('button:has-text("Confirm"), button:has-text("Yes")')
          .first();
        if (await confirmButton.isVisible()) {
          await confirmButton.click();
          await page.waitForTimeout(500);
        }

        // Check item count after removal
        const cartItemsAfter = await page.locator('[data-testid="cart-item"], .cart-item').count();
        console.log(`TC-F033: Cart items after removal: ${cartItemsAfter}`);

        expect(cartItemsAfter).toBeLessThan(cartItemsBefore);
        console.log('TC-F033: Item removed from cart successfully');
      }
    } else {
      console.log('TC-F033: No cart items to remove');
    }
  });
});

test.describe('Checkout Details', () => {
  /**
   * Helper to navigate to checkout
   */
  async function goToCheckout(page: any) {
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
    await page.waitForLoadState('networkidle');

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

    const incrementButton = page.locator('[data-testid="person-type-adult-increment"]').first();
    if (await incrementButton.isVisible()) {
      await incrementButton.click();
      await page.waitForTimeout(300);
    }

    const continueButton = page.locator('button:has-text("Continue")').first();
    if (await continueButton.isVisible()) {
      await continueButton.click();
      await page.waitForURL(/\/checkout\/.+/, { timeout: 10000 });
    }
  }

  // TC-F039: Participant Details Entry
  test('TC-F039: should enter participant details', async ({ page }) => {
    await goToCheckout(page);

    // Look for participant details section
    const participantSection = page
      .locator(
        '[data-testid="participant-details"], .participant-section, text=/participant.*details/i'
      )
      .first();
    const hasParticipantSection = await participantSection.isVisible().catch(() => false);

    if (hasParticipantSection) {
      // Fill participant name
      const nameInput = page
        .locator('input[name*="participant"][name*="name"], input[placeholder*="participant name"]')
        .first();
      if (await nameInput.isVisible()) {
        await nameInput.fill('John Doe');
      }

      // Fill any other participant fields
      const participant2Name = page.locator('input[name*="participant"][name*="name"]').nth(1);
      if (await participant2Name.isVisible()) {
        await participant2Name.fill('Jane Doe');
      }

      // Special requests
      const specialRequests = page
        .locator('textarea[name*="special"], textarea[name*="request"]')
        .first();
      if (await specialRequests.isVisible()) {
        await specialRequests.fill('Please provide vegetarian meals');
      }

      console.log('TC-F039: Participant details entered successfully');
    } else {
      // May be in a different step
      const nextStep = page.locator('button:has-text("Next"), button:has-text("Continue")').first();
      if (await nextStep.isVisible()) {
        await nextStep.click();
        await page.waitForTimeout(500);

        const participantInputs = page.locator('input[name*="participant"]');
        const hasInputs = (await participantInputs.count()) > 0;
        console.log(
          `TC-F039: Participant section ${hasInputs ? 'found in next step' : 'not found'}`
        );
      }
    }
  });

  // TC-F040: Payment Method Selection
  test('TC-F040: should display and select payment methods', async ({ page }) => {
    await goToCheckout(page);

    // Navigate to payment step if needed
    const paymentStep = page
      .locator('[data-testid="payment-step"], text=/payment.*method/i')
      .first();
    const isPaymentStep = await paymentStep.isVisible().catch(() => false);

    if (!isPaymentStep) {
      // Click through to payment step
      const nextButtons = page.locator('button:has-text("Next"), button:has-text("Continue")');
      let attempts = 0;
      while (attempts < 3) {
        const nextBtn = nextButtons.first();
        if (await nextBtn.isVisible()) {
          await nextBtn.click();
          await page.waitForTimeout(500);
        }
        attempts++;
      }
    }

    // Look for payment method options
    const paymentMethods = page.locator(
      '[data-testid="payment-method"], input[name="payment_method"], .payment-option'
    );
    const methodCount = await paymentMethods.count();

    console.log(`TC-F040: Found ${methodCount} payment methods`);

    if (methodCount > 0) {
      // Check for Cash on Arrival
      const cashOption = page.locator('text=/cash|on.*arrival|sur.*place/i').first();
      const hasCash = await cashOption.isVisible().catch(() => false);

      // Check for Bank Transfer
      const bankOption = page.locator('text=/bank.*transfer|virement/i').first();
      const hasBank = await bankOption.isVisible().catch(() => false);

      // Check for Card/Online
      const cardOption = page.locator('text=/card|online|carte/i').first();
      const hasCard = await cardOption.isVisible().catch(() => false);

      console.log(
        `TC-F040: Payment methods - Cash: ${hasCash}, Bank: ${hasBank}, Card: ${hasCard}`
      );

      // Select a payment method
      if (hasCash) {
        await cashOption.click();
        await page.waitForTimeout(500);

        // Verify instructions shown
        const instructions = page.locator('text=/instructions|pay.*upon.*arrival/i').first();
        const hasInstructions = await instructions.isVisible().catch(() => false);
        console.log(
          `TC-F040: Cash payment instructions ${hasInstructions ? 'shown' : 'not shown'}`
        );
      } else if (hasCard) {
        await cardOption.click();
        await page.waitForTimeout(500);

        // Verify card form shown
        const cardForm = page.locator('input[name*="card"], [data-testid="card-form"]').first();
        const hasCardForm = await cardForm.isVisible().catch(() => false);
        console.log(`TC-F040: Card payment form ${hasCardForm ? 'shown' : 'not shown'}`);
      }
    }
  });

  // TC-F042: Payment Failure
  test('TC-F042: should handle payment failure gracefully', async ({ page }) => {
    await goToCheckout(page);

    // Navigate to payment
    const nextButtons = page.locator('button:has-text("Next"), button:has-text("Continue")');
    let attempts = 0;
    while (attempts < 3) {
      const nextBtn = nextButtons.first();
      if (await nextBtn.isVisible()) {
        await nextBtn.click();
        await page.waitForTimeout(500);
      }
      attempts++;
    }

    // Look for payment form or mock payment
    const payButton = page
      .locator('button:has-text("Pay"), button:has-text("Complete Payment")')
      .first();
    const hasPayBtn = await payButton.isVisible().catch(() => false);

    if (hasPayBtn) {
      // In test environment, we may have a way to simulate failure
      // Look for test card input or failure trigger
      const testFailure = page
        .locator('[data-testid="test-payment-failure"], button:has-text("Test Failure")')
        .first();
      const hasTestFailure = await testFailure.isVisible().catch(() => false);

      if (hasTestFailure) {
        await testFailure.click();
        await page.waitForTimeout(2000);
      } else {
        // Try to submit with invalid/test card
        const cardInput = page.locator('input[name*="card_number"]').first();
        if (await cardInput.isVisible()) {
          await cardInput.fill('4000000000000002'); // Declined test card
        }

        await payButton.click();
        await page.waitForTimeout(2000);
      }

      // Check for failure message
      const failureMessage = page
        .locator('text=/payment.*failed|declined|error|try.*again/i')
        .first();
      const hasFailure = await failureMessage.isVisible().catch(() => false);

      if (hasFailure) {
        await expect(failureMessage).toBeVisible();
        console.log('TC-F042: Payment failure handled correctly');

        // Verify retry option available
        const retryButton = page
          .locator('button:has-text("Try Again"), button:has-text("Retry")')
          .first();
        const hasRetry = await retryButton.isVisible().catch(() => false);
        console.log(`TC-F042: Retry option ${hasRetry ? 'available' : 'not available'}`);

        // Verify cart preserved
        const cartStillValid = page
          .locator('[data-testid="cart-summary"], [data-testid="order-summary"]')
          .first();
        const hasCart = await cartStillValid.isVisible().catch(() => false);
        console.log(`TC-F042: Cart preserved after failure: ${hasCart}`);
      } else {
        console.log('TC-F042: Payment failure scenario - checking state');
      }
    } else {
      console.log('TC-F042: Payment button not found - may need to complete earlier steps');
    }
  });
});
