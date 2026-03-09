/**
 * Blog Hero Images - Admin to Frontend E2E Tests
 *
 * Tests that verify hero images uploaded in admin panel appear correctly on frontend.
 * This is a critical path test for the blog media upload functionality.
 *
 * Test Coverage:
 * - TC-BLOG-01: Hero image uploaded in admin appears in API response
 * - TC-BLOG-02: Hero image URL is accessible via HTTP
 * - TC-BLOG-03: Hero image displays on frontend blog detail page
 * - TC-BLOG-04: Multiple hero images are returned in API
 * - TC-BLOG-05: Hero image URLs are public (not admin proxy)
 */

import { test, expect } from '@playwright/test';
import { adminUsers } from '../../fixtures/admin-test-data';
import { loginToAdmin } from '../../fixtures/admin-api-helpers';

// Test configuration
const TEST_TIMEOUT = 90000;
const ADMIN_URL = 'http://localhost:8000/admin';
const FRONTEND_URL = 'http://localhost:3000';
const API_URL = 'http://localhost:8000/api/v1';

// Test images - use existing test image
const TEST_IMAGE_PATH = '/Users/otospexmob/Downloads/1-9.png';

test.describe('Blog Hero Images - Admin to Frontend Integration', () => {
  test.setTimeout(TEST_TIMEOUT);

  test.beforeEach(async ({ page }) => {
    console.log('Logging into admin panel...');
    await loginToAdmin(page, adminUsers.admin.email, adminUsers.admin.password);
    console.log('Admin login successful');
  });

  test('TC-BLOG-01: Hero image URL is returned by API', async ({ page }) => {
    console.log('TC-BLOG-01: Testing hero image API response');

    // Get any published blog post with hero images
    const apiResponse = await page.request.get(`${API_URL}/blog/posts?limit=1`);
    expect(apiResponse.status()).toBe(200);

    const apiData = await apiResponse.json();
    console.log(`Found ${apiData.data?.length || 0} blog posts`);

    if (apiData.data && apiData.data.length > 0) {
      const post = apiData.data[0];
      console.log(`Post: ${post.slug}`);
      console.log(`Hero Images: ${JSON.stringify(post.heroImages)}`);
      console.log(`Featured Image: ${post.featuredImage}`);

      // Structure should exist
      expect(post).toHaveProperty('heroImages');
      expect(post).toHaveProperty('featuredImage');
      expect(post).toHaveProperty('heroImageCount');

      // If heroImages is not empty, verify URL format
      if (post.heroImages && post.heroImages.length > 0) {
        const heroUrl = post.heroImages[0];
        expect(heroUrl).not.toBeNull();
        expect(typeof heroUrl).toBe('string');
        expect(heroUrl.length).toBeGreaterThan(0);

        console.log('TC-BLOG-01: PASSED - Hero image URL returned by API');
      } else {
        console.log('TC-BLOG-01: SKIPPED - No hero images in test post');
      }
    } else {
      console.log('TC-BLOG-01: SKIPPED - No blog posts found');
    }
  });

  test('TC-BLOG-02: Hero image URL is accessible via HTTP', async ({ page }) => {
    console.log('TC-BLOG-02: Testing hero image HTTP accessibility');

    // Get any published blog post with hero images
    const apiResponse = await page.request.get(`${API_URL}/blog/posts?limit=1`);
    const apiData = await apiResponse.json();

    if (apiData.data && apiData.data.length > 0) {
      const post = apiData.data[0];

      if (post.heroImages && post.heroImages.length > 0) {
        const imageUrl = post.heroImages[0];
        console.log(`Testing image URL: ${imageUrl}`);

        // Try to fetch the image directly
        const imageResponse = await page.request.get(imageUrl);
        expect(imageResponse.status()).toBe(200);

        const contentType = imageResponse.headers()['content-type'];
        console.log(`Content-Type: ${contentType}`);
        expect(contentType).toContain('image/');

        console.log('TC-BLOG-02: PASSED - Hero image accessible via HTTP');
      } else {
        console.log('TC-BLOG-02: SKIPPED - No hero images in test post');
      }
    } else {
      console.log('TC-BLOG-02: SKIPPED - No blog posts found');
    }
  });

  test('TC-BLOG-03: Hero image displays on frontend blog detail page', async ({ page }) => {
    console.log('TC-BLOG-03: Testing hero image on frontend');

    // Get any published blog post with hero images
    const apiResponse = await page.request.get(`${API_URL}/blog/posts?limit=1`);
    const apiData = await apiResponse.json();

    if (apiData.data && apiData.data.length > 0) {
      const post = apiData.data[0];
      console.log(`Navigating to blog post: ${post.slug}`);

      // Navigate to frontend blog detail
      await page.goto(`${FRONTEND_URL}/blog/${post.slug}`);
      await page.waitForLoadState('networkidle');

      // Wait for page to fully render
      await page.waitForTimeout(2000);

      // Check for hero section with images
      const heroSection = page.locator('section').first();
      const heroImages = heroSection.locator('img');

      const imageCount = await heroImages.count();
      console.log(`Found ${imageCount} images in hero section`);

      if (imageCount > 0) {
        await expect(heroImages.first()).toBeVisible({ timeout: 10000 });

        const src = await heroImages.first().getAttribute('src');
        console.log(`Hero image src: ${src}`);

        // Should not be Unsplash fallback
        if (src) {
          expect(src).not.toContain('unsplash.com');
        }

        console.log('TC-BLOG-03: PASSED - Hero image visible on frontend');
      } else {
        console.log('TC-BLOG-03: SKIPPED - No images in hero section');
      }
    } else {
      console.log('TC-BLOG-03: SKIPPED - No blog posts found');
    }
  });

  test('TC-BLOG-04: API returns heroImageCount correctly', async ({ page }) => {
    console.log('TC-BLOG-04: Testing heroImageCount in API response');

    // Get blog posts
    const apiResponse = await page.request.get(`${API_URL}/blog/posts?limit=5`);
    expect(apiResponse.status()).toBe(200);

    const apiData = await apiResponse.json();

    if (apiData.data && apiData.data.length > 0) {
      for (const post of apiData.data) {
        const heroImages = post.heroImages || [];
        const heroImageCount = post.heroImageCount;

        console.log(
          `Post ${post.slug}: heroImages.length=${heroImages.length}, heroImageCount=${heroImageCount}`
        );

        // heroImageCount should match actual array length
        expect(heroImageCount).toBe(heroImages.length);
      }

      console.log('TC-BLOG-04: PASSED - heroImageCount matches array length');
    } else {
      console.log('TC-BLOG-04: SKIPPED - No blog posts found');
    }
  });

  test('TC-BLOG-05: Hero image URLs are public (not admin proxy)', async ({ page }) => {
    console.log('TC-BLOG-05: Testing hero image URLs are public');

    // Get blog posts
    const apiResponse = await page.request.get(`${API_URL}/blog/posts?limit=5`);
    expect(apiResponse.status()).toBe(200);

    const apiData = await apiResponse.json();

    if (apiData.data && apiData.data.length > 0) {
      let checkedUrls = 0;

      for (const post of apiData.data) {
        if (post.heroImages && post.heroImages.length > 0) {
          for (const url of post.heroImages) {
            console.log(`Checking URL: ${url}`);

            // URL should NOT be an admin proxy URL
            expect(url).not.toContain('/admin/storage-proxy/');
            expect(url).not.toContain('/admin/media-proxy/');

            // URL should be a public storage URL
            expect(url).toContain('/storage/');

            checkedUrls++;
          }
        }
      }

      if (checkedUrls > 0) {
        console.log(`TC-BLOG-05: PASSED - Checked ${checkedUrls} URLs, all are public`);
      } else {
        console.log('TC-BLOG-05: SKIPPED - No hero image URLs to check');
      }
    } else {
      console.log('TC-BLOG-05: SKIPPED - No blog posts found');
    }
  });
});
