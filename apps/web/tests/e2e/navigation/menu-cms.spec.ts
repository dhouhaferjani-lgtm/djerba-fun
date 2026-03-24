import { test, expect, Page } from '@playwright/test';

/**
 * BDD E2E Tests for CMS-Managed Menus
 *
 * These tests verify that the CMS-managed navigation menus
 * display correctly and all links work in both locales.
 *
 * Menu codes tested:
 * - header: Main navigation menu
 * - footer-company: Footer company links
 * - footer-support: Footer support links
 * - footer-legal: Footer bottom bar links
 */

/**
 * Helper to dismiss cookie consent banner if present
 */
async function dismissCookieConsent(page: Page) {
  const cookieBanner = page
    .locator('[data-testid="cookie-consent"], .cookie-consent, [class*="cookie"]')
    .first();
  try {
    // Check if cookie banner exists and has an accept button
    const acceptButton = page.getByRole('button', { name: /accept|accepter|ok|agree/i }).first();
    if (await acceptButton.isVisible({ timeout: 1000 })) {
      await acceptButton.click();
      await page.waitForTimeout(300); // Allow animation
    }
  } catch {
    // Cookie banner not present or already dismissed
  }
}

test.describe('CMS-Managed Menus', () => {
  test.describe('Header Navigation', () => {
    test('should display all header navigation links (English)', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      const nav = page.locator('header nav');

      // Verify expected navigation links are present
      await expect(nav.getByRole('link', { name: 'Home' })).toBeVisible();
      await expect(nav.getByRole('link', { name: 'Activities' })).toBeVisible();
      await expect(nav.getByRole('link', { name: 'Nautical' })).toBeVisible();
      await expect(nav.getByRole('link', { name: 'Accommodations' })).toBeVisible();
      await expect(nav.getByRole('link', { name: 'Events' })).toBeVisible();
      await expect(nav.getByRole('link', { name: 'Blog' })).toBeVisible();
      await expect(nav.getByRole('link', { name: 'Request Custom Trip' })).toBeVisible();
    });

    test('should display all header navigation links (French)', async ({ page }) => {
      await page.goto('/fr');
      await page.waitForLoadState('networkidle');

      const nav = page.locator('header nav');

      // Verify expected navigation links are present in French
      await expect(nav.getByRole('link', { name: 'Accueil' })).toBeVisible();
      await expect(nav.getByRole('link', { name: 'Activités' })).toBeVisible();
      await expect(nav.getByRole('link', { name: 'Nautique' })).toBeVisible();
      await expect(nav.getByRole('link', { name: 'Hébergements' })).toBeVisible();
      await expect(nav.getByRole('link', { name: 'Événements' })).toBeVisible();
      await expect(nav.getByRole('link', { name: 'Blog' })).toBeVisible();
      await expect(nav.getByRole('link', { name: 'Voyage Sur Mesure' })).toBeVisible();
    });

    test('should navigate to Activities page from header', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      await page.locator('header nav').getByRole('link', { name: 'Activities' }).click();
      await page.waitForURL(/listings\?type=tour/);

      expect(page.url()).toContain('/listings?type=tour');
    });

    test('should navigate to Blog page from header', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      await page.locator('header nav').getByRole('link', { name: 'Blog' }).click();
      await page.waitForURL(/\/blog/);

      expect(page.url()).toContain('/blog');
    });

    test('should navigate to Custom Trip page from header', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      await page.locator('header nav').getByRole('link', { name: 'Request Custom Trip' }).click();
      await page.waitForURL(/\/custom-trip/);

      expect(page.url()).toContain('/custom-trip');
    });
  });

  test.describe('Footer Company Links', () => {
    test('should display company links in footer (English)', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');
      await dismissCookieConsent(page);

      const footer = page.locator('footer');

      await expect(footer.getByRole('link', { name: 'About Us' })).toBeVisible();
      await expect(footer.getByRole('link', { name: 'Blog' })).toBeVisible();
    });

    test('should display company links in footer (French)', async ({ page }) => {
      await page.goto('/fr');
      await page.waitForLoadState('networkidle');
      await dismissCookieConsent(page);

      const footer = page.locator('footer');

      await expect(footer.getByRole('link', { name: 'Qui sommes-nous' })).toBeVisible();
      await expect(footer.getByRole('link', { name: 'Blog' })).toBeVisible();
    });

    test('should navigate to About page from footer', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');
      await dismissCookieConsent(page);

      await page.locator('footer').getByRole('link', { name: 'About Us' }).click();
      await page.waitForURL(/\/about/);

      expect(page.url()).toContain('/about');
    });
  });

  test.describe('Footer Support Links', () => {
    test('should display support links in footer (English)', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');
      await dismissCookieConsent(page);

      const footer = page.locator('footer');

      await expect(footer.getByRole('link', { name: 'My Account' })).toBeVisible();
      await expect(footer.getByRole('link', { name: 'Terms & Conditions' }).first()).toBeVisible();
      await expect(footer.getByRole('link', { name: 'Contact Us' })).toBeVisible();
    });

    test('should display support links in footer (French)', async ({ page }) => {
      await page.goto('/fr');
      await page.waitForLoadState('networkidle');
      await dismissCookieConsent(page);

      const footer = page.locator('footer');

      await expect(footer.getByRole('link', { name: 'Mon compte' })).toBeVisible();
      await expect(footer.getByRole('link', { name: 'CGU' }).first()).toBeVisible();
      await expect(footer.getByRole('link', { name: 'Nous contacter' })).toBeVisible();
    });

    test('should navigate to Contact page from footer', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');
      await dismissCookieConsent(page);

      await page.locator('footer').getByRole('link', { name: 'Contact Us' }).click();
      await page.waitForURL(/\/contact/);

      expect(page.url()).toContain('/contact');
    });
  });

  test.describe('Footer Legal Links (Bottom Bar)', () => {
    test('should display legal links in footer bottom bar (English)', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');
      await dismissCookieConsent(page);

      const footer = page.locator('footer');

      // Legal links should be visible (may appear multiple times)
      const termsLinks = footer.getByRole('link', { name: 'Terms & Conditions' });
      const privacyLinks = footer.getByRole('link', { name: 'Privacy Policy' });

      await expect(termsLinks.first()).toBeVisible();
      await expect(privacyLinks.first()).toBeVisible();
    });

    test('should display legal links in footer bottom bar (French)', async ({ page }) => {
      await page.goto('/fr');
      await page.waitForLoadState('networkidle');
      await dismissCookieConsent(page);

      const footer = page.locator('footer');

      const cguLinks = footer.getByRole('link', { name: 'CGU' });
      const privacyLinks = footer.getByRole('link', { name: 'Confidentialité' });

      await expect(cguLinks.first()).toBeVisible();
      await expect(privacyLinks.first()).toBeVisible();
    });

    test('should navigate to Terms page from footer', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');
      await dismissCookieConsent(page);

      // Click the first Terms link found
      await page
        .locator('footer')
        .getByRole('link', { name: 'Terms & Conditions' })
        .first()
        .click();
      await page.waitForURL(/\/terms/);

      expect(page.url()).toContain('/terms');
    });

    test('should navigate to Privacy page from footer', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');
      await dismissCookieConsent(page);

      await page.locator('footer').getByRole('link', { name: 'Privacy Policy' }).first().click();
      await page.waitForURL(/\/privacy/);

      expect(page.url()).toContain('/privacy');
    });
  });

  test.describe('Mobile Menu', () => {
    test.use({ viewport: { width: 375, height: 667 } }); // iPhone SE size

    test('should display mobile menu with navigation links', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');
      await dismissCookieConsent(page);

      // Open mobile menu - the button has aria-label="Open mobile menu"
      const menuButton = page.locator('button[aria-label="Open mobile menu"]');
      await expect(menuButton).toBeVisible({ timeout: 10000 });
      await menuButton.click();

      // Wait for mobile menu to be visible (the nav inside the mobile overlay)
      const mobileNav = page.locator('.md\\:hidden nav');
      await expect(mobileNav).toBeVisible({ timeout: 5000 });

      // Verify navigation links are visible in mobile menu (scope to mobile nav)
      await expect(mobileNav.getByRole('link', { name: 'Home' })).toBeVisible();
      await expect(mobileNav.getByRole('link', { name: 'Activities' })).toBeVisible();
      await expect(mobileNav.getByRole('link', { name: 'Blog' })).toBeVisible();
    });

    test('should navigate from mobile menu', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');
      await dismissCookieConsent(page);

      // Open mobile menu
      const menuButton = page.locator('button[aria-label="Open mobile menu"]');
      await expect(menuButton).toBeVisible({ timeout: 10000 });
      await menuButton.click();

      // Wait for mobile menu
      const mobileNav = page.locator('.md\\:hidden nav');
      await expect(mobileNav).toBeVisible({ timeout: 5000 });

      // Click Blog link in mobile menu (scoped to mobile nav)
      await mobileNav.getByRole('link', { name: 'Blog' }).click();
      await page.waitForURL(/\/blog/);

      expect(page.url()).toContain('/blog');
    });
  });

  test.describe('Locale Switching with Menu', () => {
    test('should maintain menu language after locale switch', async ({ page }) => {
      // Start in English
      await page.goto('/en');
      await page.waitForLoadState('networkidle');
      await dismissCookieConsent(page);

      const nav = page.locator('header nav');
      await expect(nav.getByRole('link', { name: 'Activities' })).toBeVisible();

      // Switch to French
      await page.goto('/fr');
      await page.waitForLoadState('networkidle');

      // Menu should now be in French
      await expect(nav.getByRole('link', { name: 'Activités' })).toBeVisible();
    });
  });
});
