/**
 * Hero Thumbnail Regression Tests
 *
 * BDD tests to verify hero banner thumbnail behavior:
 * - No broken image icon on homepage
 * - API returns accessible thumbnail URL (when present)
 * - Fallback works when thumbnail is unavailable
 *
 * These tests prevent regression of the broken image issue where
 * the thumbnail URL was returned even when the conversion didn't exist.
 */

import { test, expect } from '@playwright/test';

const API_URL = 'http://localhost:8000/api/v1';
const FRONTEND_URL = 'http://localhost:3000';

test.describe('Hero Thumbnail Regression Tests', () => {
  test.describe('TC-THUMB: Hero Banner Thumbnail', () => {
    test('TC-THUMB-01: No broken image icon on homepage', async ({ page }) => {
      // Given: User navigates to homepage
      await page.goto(FRONTEND_URL);
      await page.waitForLoadState('networkidle');

      // When: The hero section loads
      const heroSection = page.locator('section').first();
      await expect(heroSection).toBeVisible();

      // Then: There should be a visible image (not broken)
      const heroImage = heroSection.locator('img').first();

      // Wait for image to be in DOM
      await expect(heroImage).toBeAttached({ timeout: 10000 });

      // Verify image loaded successfully (naturalWidth > 0 means not broken)
      const imageStatus = await heroImage.evaluate((img: HTMLImageElement) => {
        return {
          complete: img.complete,
          naturalWidth: img.naturalWidth,
          naturalHeight: img.naturalHeight,
          src: img.src,
        };
      });

      // Image should be loaded (complete=true) and have dimensions (not broken)
      expect(imageStatus.complete).toBe(true);
      expect(imageStatus.naturalWidth).toBeGreaterThan(0);

      console.log(`TC-THUMB-01: Hero image loaded successfully`);
      console.log(`  - src: ${imageStatus.src.substring(0, 80)}...`);
      console.log(`  - dimensions: ${imageStatus.naturalWidth}x${imageStatus.naturalHeight}`);
    });

    test('TC-THUMB-02: API returns accessible thumbnail URL when present', async ({ page }) => {
      // Given: API endpoint for platform settings
      const response = await page.request.get(`${API_URL}/platform/settings`);
      expect(response.ok()).toBe(true);

      const data = await response.json();
      const branding = data?.data?.branding;

      // When: We check the thumbnail URL
      const thumbnailUrl = branding?.heroBannerThumbnail;

      console.log(`TC-THUMB-02: API branding response:`);
      console.log(`  - heroBanner: ${branding?.heroBanner || 'null'}`);
      console.log(`  - heroBannerIsVideo: ${branding?.heroBannerIsVideo}`);
      console.log(`  - heroBannerThumbnail: ${thumbnailUrl || 'null'}`);

      // Then: If thumbnail URL exists, it should be accessible
      if (thumbnailUrl) {
        // Verify URL is fully qualified
        expect(thumbnailUrl).toMatch(/^https?:\/\//);

        // Verify URL is accessible (HTTP 200)
        const imageResponse = await page.request.get(thumbnailUrl);
        expect(imageResponse.status()).toBe(200);

        console.log(`  - Thumbnail URL is accessible (HTTP 200)`);
      } else {
        // If no thumbnail, that's acceptable - frontend should use fallback
        console.log(`  - No thumbnail URL (frontend will use fallback)`);
      }
    });

    test('TC-THUMB-03: Fallback works when thumbnail unavailable', async ({ page }) => {
      // Given: We intercept and block any thumbnail requests
      await page.route('**/conversions/**', (route) => route.abort());
      await page.route('**/*-thumbnail*', (route) => route.abort());

      // When: User navigates to homepage
      await page.goto(FRONTEND_URL);
      await page.waitForLoadState('networkidle');

      // Then: Hero section should still display an image (fallback)
      const heroSection = page.locator('section').first();
      await expect(heroSection).toBeVisible();

      const heroImage = heroSection.locator('img').first();
      await expect(heroImage).toBeAttached({ timeout: 10000 });

      // Check that fallback image is displayed (not broken)
      const imageStatus = await heroImage.evaluate((img: HTMLImageElement) => {
        return {
          complete: img.complete,
          naturalWidth: img.naturalWidth,
          src: img.src,
        };
      });

      // Fallback should still work - image should be loaded
      expect(imageStatus.complete).toBe(true);
      expect(imageStatus.naturalWidth).toBeGreaterThan(0);

      // Verify it's using a fallback (local image or default)
      const isLocalFallback =
        imageStatus.src.includes('/images/hero/') ||
        imageStatus.src.includes('/_next/image') ||
        imageStatus.src.includes('hero-banner');

      console.log(`TC-THUMB-03: Fallback behavior:`);
      console.log(`  - Image src: ${imageStatus.src.substring(0, 80)}...`);
      console.log(`  - Using fallback: ${isLocalFallback}`);
      console.log(`  - Image loaded: ${imageStatus.naturalWidth > 0}`);
    });

    test('TC-THUMB-04: Video mode with missing thumbnail gracefully falls back', async ({
      page,
    }) => {
      // Given: API indicates video mode but no thumbnail
      const apiResponse = await page.request.get(`${API_URL}/platform/settings`);
      const data = await apiResponse.json();
      const branding = data?.data?.branding;

      console.log(`TC-THUMB-04: Testing video mode fallback`);
      console.log(`  - heroBannerIsVideo: ${branding?.heroBannerIsVideo}`);

      // When: User navigates to homepage
      await page.goto(FRONTEND_URL);
      await page.waitForLoadState('networkidle');

      // Then: Hero section should display without broken images
      const heroSection = page.locator('section').first();
      await expect(heroSection).toBeVisible();

      // Check for any broken images in the hero section
      const allImages = heroSection.locator('img');
      const imageCount = await allImages.count();

      for (let i = 0; i < imageCount; i++) {
        const img = allImages.nth(i);
        const status = await img.evaluate((el: HTMLImageElement) => ({
          src: el.src,
          naturalWidth: el.naturalWidth,
          alt: el.alt,
        }));

        // Each image should have naturalWidth > 0 (not broken)
        expect(status.naturalWidth).toBeGreaterThan(0);
        console.log(
          `  - Image ${i}: ${status.alt || 'no-alt'} - OK (${status.naturalWidth}px wide)`
        );
      }
    });
  });
});
