import { test, expect } from '@playwright/test';

test.describe('Logo Loading - No Flash', () => {
  test('should display header logo immediately on homepage without fallback flash', async ({
    page,
  }) => {
    // Navigate to homepage
    await page.goto('/fr');

    // Logo should be visible immediately (within first paint)
    const headerLogo = page.locator('header img').first();
    await expect(headerLogo).toBeVisible({ timeout: 2000 });

    // Verify it's NOT the fallback image
    const logoSrc = await headerLogo.getAttribute('src');
    expect(logoSrc).not.toContain('evasion-djerba-logo.png');
  });

  test('should display header logo immediately on English homepage', async ({ page }) => {
    await page.goto('/en');

    const headerLogo = page.locator('header img').first();
    await expect(headerLogo).toBeVisible({ timeout: 2000 });

    const logoSrc = await headerLogo.getAttribute('src');
    expect(logoSrc).not.toContain('evasion-djerba-logo.png');
  });

  test('should display header logo on listings page without flash', async ({ page }) => {
    await page.goto('/fr/listings');

    const headerLogo = page.locator('header img').first();
    await expect(headerLogo).toBeVisible({ timeout: 2000 });

    const logoSrc = await headerLogo.getAttribute('src');
    expect(logoSrc).not.toContain('evasion-djerba-logo.png');
  });

  test('should display footer logo without flash', async ({ page }) => {
    await page.goto('/fr');

    // Scroll to footer to ensure it's loaded
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));

    const footerLogo = page.locator('footer img').first();
    await expect(footerLogo).toBeVisible();

    const logoSrc = await footerLogo.getAttribute('src');
    expect(logoSrc).not.toContain('evasion-djerba-logo.png');
  });

  test('should still display logo when API is slow (graceful fallback)', async ({ page }) => {
    // Delay API response by 3 seconds to simulate slow network
    await page.route('**/api/v1/platform/settings*', async (route) => {
      await new Promise((resolve) => setTimeout(resolve, 3000));
      await route.continue();
    });

    await page.goto('/fr');

    // Logo should still be visible (from server context, not waiting for client API)
    const headerLogo = page.locator('header img').first();
    await expect(headerLogo).toBeVisible({ timeout: 2000 });
  });

  test('should display fallback logo when API completely fails', async ({ page }) => {
    // Block all platform settings API calls
    await page.route('**/api/v1/platform/settings*', (route) => route.abort());

    await page.goto('/fr');

    // Logo should still appear (fallback)
    const headerLogo = page.locator('header img').first();
    await expect(headerLogo).toBeVisible({ timeout: 5000 });
  });
});
