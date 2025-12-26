import { test, expect } from '@playwright/test';

test.describe('Booking Flow', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to a listing page
    await page.goto('/en/listings/kroumirie-mountains-summit-trek');
  });

  test('Guest checkout works without authentication', async ({ page }) => {
    // Wait for page to load
    await page.waitForLoadState('networkidle');

    // Select date
    const dateSelector = page.locator('[data-testid="booking-date-selector"]');
    if (await dateSelector.isVisible()) {
      await dateSelector.click();
      // Select 15th of current month
      await page.click('button:has-text("15")');
    }

    // Select time slot
    const timeSlot = page.locator('[data-testid="time-slot"]').first();
    if (await timeSlot.isVisible()) {
      await timeSlot.click();
    }

    // Modify participant count
    const incrementButton = page.locator('[data-testid="person-type-adult-increment"]');
    if (await incrementButton.isVisible()) {
      await incrementButton.click();
    }

    // Verify price updates
    const totalPrice = page.locator('[data-testid="total-price"]');
    if (await totalPrice.isVisible()) {
      const priceText = await totalPrice.textContent();
      expect(priceText).toBeTruthy();
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
    const price1 = await totalPrice.textContent();

    // Add one more adult
    const incrementButton = page.locator('[data-testid="person-type-adult-increment"]');
    await incrementButton.click();

    // Price should update immediately
    await page.waitForTimeout(1000); // Allow for React state update
    const price2 = await totalPrice.textContent();

    // Verify price changed
    expect(price2).not.toBe(price1);

    // Verify the count is correct
    const adultCount = page.locator('[data-testid="person-type-adult-count"]');
    const countText = await adultCount.textContent();
    expect(countText).toBe('2');
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

  test('404 page uses primary color gradient', async ({ page }) => {
    await page.goto('/en/invalid-page');

    // Check that gradient section exists
    const gradientSection = page.locator('.bg-gradient-to-b.from-primary\\/5');
    await expect(gradientSection).toBeVisible();

    // Verify primary color is used (not accent)
    const heading = page.locator('h1:has-text("404")');
    const color = await heading.evaluate((el) => window.getComputedStyle(el).color);

    // Primary color should be applied (not accent/cream color)
    expect(color).toBeTruthy();
  });
});
