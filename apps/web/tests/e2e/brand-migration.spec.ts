import { test, expect } from '@playwright/test';

/**
 * BDD E2E Tests for Djerba Fun Brand Migration
 *
 * These tests verify that the brand migration from "Go Adventure" / "Evasion Djerba"
 * to "Djerba Fun" is complete across the entire frontend.
 *
 * Brand: Djerba Fun
 * Domain: djerbafun.com
 * Email: contact@djerba.fun
 */

test.describe('Djerba Fun Brand Migration', () => {
  test.describe('Homepage Branding', () => {
    test('should display Djerba Fun in page title', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      const title = await page.title();

      expect(title).toContain('Djerba Fun');
      expect(title).not.toContain('Go Adventure');
      expect(title).not.toContain('Evasion Djerba');
    });

    test('should have Djerba Fun in Organization schema', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      const jsonLdScripts = page.locator('script[type="application/ld+json"]');
      const scriptCount = await jsonLdScripts.count();

      let foundCorrectBrand = false;
      let foundOldBrand = false;

      for (let i = 0; i < scriptCount; i++) {
        const content = await jsonLdScripts.nth(i).textContent();
        try {
          const jsonLd = JSON.parse(content || '{}');
          const contentStr = JSON.stringify(jsonLd);

          if (contentStr.includes('Djerba Fun')) {
            foundCorrectBrand = true;
          }
          if (contentStr.includes('Go Adventure') || contentStr.includes('Evasion Djerba')) {
            foundOldBrand = true;
          }
        } catch (e) {
          // Skip invalid JSON
        }
      }

      expect(foundCorrectBrand).toBe(true);
      expect(foundOldBrand).toBe(false);
    });
  });

  test.describe('Footer Branding', () => {
    test('should show Djerba Fun in footer copyright', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      const footer = page.locator('footer');
      const footerText = await footer.textContent();

      expect(footerText).toContain('Djerba Fun');
      expect(footerText).not.toContain('Go Adventure');
      expect(footerText).not.toContain('Evasion Djerba');
    });

    test('should display contact@djerba.fun email', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      const footer = page.locator('footer');
      const footerHtml = await footer.innerHTML();

      // Check for correct email
      expect(footerHtml).toContain('djerba.fun');
      // Check old emails are removed
      expect(footerHtml).not.toContain('go-adventure.net');
      expect(footerHtml).not.toContain('evasiondjerba.com');
    });
  });

  test.describe('Meta Tags', () => {
    test('should have Djerba Fun in meta description', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      const metaDescription = await page
        .locator('meta[name="description"]')
        .getAttribute('content');

      if (metaDescription) {
        expect(metaDescription).not.toContain('Go Adventure');
        expect(metaDescription).not.toContain('Evasion Djerba');
      }
    });

    test('should have Djerba Fun in OG title', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      const ogTitle = await page.locator('meta[property="og:title"]').getAttribute('content');

      if (ogTitle) {
        expect(ogTitle).toContain('Djerba Fun');
        expect(ogTitle).not.toContain('Go Adventure');
        expect(ogTitle).not.toContain('Evasion Djerba');
      }
    });
  });

  test.describe('Legal Pages', () => {
    test('should display Djerba Fun on Terms page', async ({ page }) => {
      await page.goto('/en/terms');
      await page.waitForLoadState('networkidle');

      const pageContent = await page.textContent('body');

      expect(pageContent).toContain('Djerba Fun');
      expect(pageContent).not.toContain('Go Adventure');
    });

    test('should display Djerba Fun on Privacy page', async ({ page }) => {
      await page.goto('/en/privacy');
      await page.waitForLoadState('networkidle');

      const pageContent = await page.textContent('body');

      expect(pageContent).toContain('Djerba Fun');
      expect(pageContent).not.toContain('Go Adventure');
    });

    test('should have contact@djerba.fun in legal pages', async ({ page }) => {
      await page.goto('/en/terms');
      await page.waitForLoadState('networkidle');

      const pageHtml = await page.innerHTML('body');

      // Check for correct email domain
      expect(pageHtml).toContain('djerba.fun');
      // Check old emails are removed
      expect(pageHtml).not.toContain('go-adventure.net');
      expect(pageHtml).not.toContain('goadventure.tn');
    });
  });

  test.describe('About Page', () => {
    test('should display Djerba Fun brand on about page', async ({ page }) => {
      await page.goto('/en/about');
      await page.waitForLoadState('networkidle');

      const pageContent = await page.textContent('body');

      expect(pageContent).toContain('Djerba Fun');
      expect(pageContent).not.toContain('Go Adventure');
      expect(pageContent).not.toContain('Evasion Djerba');
    });

    test('should have Djerba Fun in about page title', async ({ page }) => {
      await page.goto('/en/about');
      await page.waitForLoadState('networkidle');

      const title = await page.title();

      expect(title).toContain('Djerba Fun');
    });
  });

  test.describe('Auth Pages', () => {
    test('should display Djerba Fun on login page', async ({ page }) => {
      await page.goto('/en/auth/login');
      await page.waitForLoadState('networkidle');

      const pageContent = await page.textContent('body');

      expect(pageContent).toContain('Djerba Fun');
      expect(pageContent).not.toContain('Go Adventure');
    });

    test('should display Djerba Fun on register page', async ({ page }) => {
      await page.goto('/en/auth/register');
      await page.waitForLoadState('networkidle');

      const pageContent = await page.textContent('body');

      expect(pageContent).toContain('Djerba Fun');
      expect(pageContent).not.toContain('Go Adventure');
    });

    test('should display Djerba Fun on verified page', async ({ page }) => {
      await page.goto('/en/auth/verified');
      await page.waitForLoadState('networkidle');

      const pageContent = await page.textContent('body');

      expect(pageContent).toContain('Djerba Fun');
      expect(pageContent).not.toContain('Go Adventure');
    });
  });

  test.describe('Custom Trip Page', () => {
    test('should display Djerba Fun on custom trip page', async ({ page }) => {
      await page.goto('/en/custom-trip');
      await page.waitForLoadState('networkidle');

      const pageContent = await page.textContent('body');

      expect(pageContent).toContain('Djerba Fun');
      expect(pageContent).not.toContain('Go Adventure');
    });
  });

  test.describe('French Locale Branding', () => {
    test('should display Djerba Fun on French homepage', async ({ page }) => {
      await page.goto('/fr');
      await page.waitForLoadState('networkidle');

      const title = await page.title();
      const pageContent = await page.textContent('body');

      expect(title).toContain('Djerba Fun');
      expect(pageContent).toContain('Djerba Fun');
      expect(pageContent).not.toContain('Go Adventure');
      expect(pageContent).not.toContain('Evasion Djerba');
    });

    test('should display Djerba Fun on French about page', async ({ page }) => {
      await page.goto('/fr/a-propos');
      await page.waitForLoadState('networkidle');

      const pageContent = await page.textContent('body');

      expect(pageContent).toContain('Djerba Fun');
      expect(pageContent).not.toContain('Go Adventure');
    });
  });

  test.describe('No Legacy References', () => {
    test('homepage should not contain any evasiondjerba references', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      const pageHtml = await page.innerHTML('body');

      expect(pageHtml.toLowerCase()).not.toContain('evasiondjerba');
    });

    test('homepage should not contain go-adventure package references', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      const pageHtml = await page.innerHTML('body');

      // Package references should never appear in rendered HTML
      expect(pageHtml).not.toContain('@go-adventure');
    });
  });
});
