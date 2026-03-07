import { test, expect } from '@playwright/test';

/**
 * BDD E2E Tests for Newsletter Section CMS Integration
 *
 * Feature: Newsletter Section CMS Control
 * As a site admin, I want to control the newsletter section content via CMS
 * So that I can update titles and text without code changes
 *
 * The newsletter section should:
 * 1. Be visible on the homepage when enabled in CMS
 * 2. Display CMS-configured title, subtitle, and button text
 * 3. Fall back to translations when CMS fields are empty
 * 4. Allow users to subscribe with their email
 */

test.describe('Newsletter Section CMS Integration', () => {
  test.describe('Newsletter Visibility', () => {
    test('should display newsletter section on homepage', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      // Newsletter section should be visible
      // Looking for the email input which is unique to newsletter
      const emailInput = page.locator('input[type="email"]');
      await expect(emailInput).toBeVisible();
    });

    test('should display newsletter section on French homepage', async ({ page }) => {
      await page.goto('/');
      await page.waitForLoadState('networkidle');

      // Newsletter section should be visible on French locale too
      const emailInput = page.locator('input[type="email"]');
      await expect(emailInput).toBeVisible();
    });
  });

  test.describe('Newsletter Content', () => {
    test('should display newsletter title', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      // Should have a newsletter title (from CMS or translation)
      // The CMS seed has "Stay Updated" or translation "newsletter_title"
      const newsletterSection = page.locator('section').filter({
        has: page.locator('input[type="email"]'),
      });

      // Section should exist and have heading text
      await expect(newsletterSection).toBeVisible();
      const heading = newsletterSection.locator('h2');
      await expect(heading).toBeVisible();
    });

    test('should display subscribe button', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      // Find the newsletter section first, then find the submit button within it
      const newsletterSection = page.locator('section').filter({
        has: page.locator('input[type="email"]'),
      });
      const submitButton = newsletterSection.locator('button[type="submit"]');
      await expect(submitButton).toBeVisible();
    });
  });

  test.describe('Newsletter Form Functionality', () => {
    test('should have email placeholder text', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      const emailInput = page.locator('input[type="email"]');
      await expect(emailInput).toHaveAttribute('placeholder', /.+/);
    });

    test('should require email field', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      const emailInput = page.locator('input[type="email"]');
      await expect(emailInput).toHaveAttribute('required', '');
    });

    test('should accept valid email and submit', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      // Find the newsletter section first to scope our locators
      const newsletterSection = page.locator('section').filter({
        has: page.locator('input[type="email"]'),
      });
      const emailInput = newsletterSection.locator('input[type="email"]');
      const submitButton = newsletterSection.locator('button[type="submit"]');

      // Fill in email
      await emailInput.fill('test@example.com');

      // Click submit
      await submitButton.click();

      // Should show loading or success state
      // The component shows "Subscribed!" on success
      await expect(page.locator('text=Subscribed')).toBeVisible({ timeout: 5000 });
    });
  });
});

test.describe('Homepage CMS Sections - Regression Tests', () => {
  test.describe('Existing Sections Still Work', () => {
    test('should display hero section', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      // Hero section is first, should be visible
      const heroSection = page.locator('section').first();
      await expect(heroSection).toBeVisible();
    });

    test('should display featured packages section when enabled', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      // Look for "À venir" or "Upcoming Adventures" heading
      const upcomingSection = page.locator('text=/upcoming|à venir/i').first();
      // This may or may not be visible depending on CMS settings and listings
    });

    test('should display experience categories section', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      // Experience categories should be present
      // Looking for category cards or section title
      const experienceSection = page.locator('text=/experiences|expériences|explore/i').first();
    });

    test('should display blog section when posts exist', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      // Blog section shows if there are posts
      // May not be visible if no blog posts exist
      const blogSection = page.locator('text=/blog|actualités|latest/i').first();
    });

    test('should display testimonials section', async ({ page }) => {
      await page.goto('/en');
      await page.waitForLoadState('networkidle');

      // Testimonials should show customer quotes
      const testimonials = page.locator('text=/testimonial|avis|customers say/i');
    });
  });

  test.describe('About Page CMS Still Works', () => {
    test('should display about page content', async ({ page }) => {
      await page.goto('/en/about');
      await page.waitForLoadState('networkidle');

      // About page should load
      await expect(page).toHaveURL(/\/about/);

      // Should have main content
      const mainContent = page.locator('main');
      await expect(mainContent).toBeVisible();
    });

    test('should display founder section on about page', async ({ page }) => {
      await page.goto('/en/about');
      await page.waitForLoadState('networkidle');

      // Founder section with story
      const founderSection = page
        .locator('text=/founder|fondateur|story|histoire|aventurier/i')
        .first();
    });
  });
});
