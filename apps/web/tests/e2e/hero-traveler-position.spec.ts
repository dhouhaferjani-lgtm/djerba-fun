/**
 * Running Traveler Position Tests
 *
 * BDD tests to verify the Running Traveler SVG stays on the first line
 * of the travel tips bar regardless of text wrapping.
 *
 * Root Cause Fix: The traveler was previously inline with text (inline-block),
 * causing it to wrap down with long tips. Now it uses absolute positioning
 * with JavaScript-based text width measurement to stay on the first line.
 */

import { test, expect } from '@playwright/test';

const FRONTEND_URL = 'http://localhost:3000';

test.describe('Running Traveler Position Tests', () => {
  test.describe('TC-TRAVELER: Running Traveler Position', () => {
    test('TC-TRAVELER-01: Traveler Y position stays constant during typing', async ({ page }) => {
      // Given: User navigates to homepage
      await page.goto(FRONTEND_URL);
      await page.waitForLoadState('networkidle');

      // When: The travel tip bar loads and typing begins
      const traveler = page.locator('[data-testid="running-traveler"]');

      // Wait for traveler to be visible
      await expect(traveler).toBeVisible({ timeout: 10000 });

      // Get initial Y position
      const initialPosition = await traveler.evaluate((el) => {
        const rect = el.getBoundingClientRect();
        return { top: rect.top, y: rect.y };
      });

      // Wait for some typing to occur (500ms = ~16 characters at 30ms/char)
      await page.waitForTimeout(500);

      // Then: Y position should remain the same
      const laterPosition = await traveler.evaluate((el) => {
        const rect = el.getBoundingClientRect();
        return { top: rect.top, y: rect.y };
      });

      // Allow 2px tolerance for sub-pixel rendering
      expect(Math.abs(laterPosition.top - initialPosition.top)).toBeLessThanOrEqual(2);

      console.log(`TC-TRAVELER-01: Traveler position verified`);
      console.log(`  - Initial Y: ${initialPosition.top}px`);
      console.log(`  - After 500ms Y: ${laterPosition.top}px`);
    });

    test('TC-TRAVELER-02: Traveler stays on first line on mobile viewport', async ({ page }) => {
      // Given: Mobile viewport where tips are likely to wrap
      await page.setViewportSize({ width: 375, height: 812 });
      await page.goto(FRONTEND_URL);
      await page.waitForLoadState('networkidle');

      // When: Travel tip with potential wrapping displays
      const travelTipBar = page.locator('[data-testid="travel-tip-bar"]');
      await expect(travelTipBar).toBeVisible();

      const traveler = page.locator('[data-testid="running-traveler"]');
      await expect(traveler).toBeVisible({ timeout: 10000 });

      // Get the tip bar's top position
      const barTop = await travelTipBar.evaluate((el) => el.getBoundingClientRect().top);

      // Get traveler's top position
      const travelerTop = await traveler.evaluate((el) => el.getBoundingClientRect().top);

      // Then: Traveler should be within the first line (within 40px of bar top, accounting for padding)
      const relativeTop = travelerTop - barTop;
      expect(relativeTop).toBeLessThan(40); // Should be on first line, not wrapped down

      console.log(`TC-TRAVELER-02: Mobile traveler position`);
      console.log(`  - Bar top: ${barTop}px`);
      console.log(`  - Traveler top: ${travelerTop}px`);
      console.log(`  - Relative position: ${relativeTop}px`);
    });

    test('TC-TRAVELER-03: Traveler position consistent across tip rotations', async ({ page }) => {
      // Given: User on homepage with tips rotating
      await page.goto(FRONTEND_URL);
      await page.waitForLoadState('networkidle');

      const traveler = page.locator('[data-testid="running-traveler"]');
      await expect(traveler).toBeVisible({ timeout: 10000 });

      // Record Y position for first tip
      const firstTipY = await traveler.evaluate((el) => el.getBoundingClientRect().top);

      // When: Wait for tip rotation (typing ~3s + pause 3s = 6s total cycle)
      // Wait enough time for at least one rotation
      await page.waitForTimeout(7000);

      // Then: Y position should still be consistent
      const afterRotationY = await traveler.evaluate((el) => el.getBoundingClientRect().top);

      expect(Math.abs(afterRotationY - firstTipY)).toBeLessThanOrEqual(2);

      console.log(`TC-TRAVELER-03: Position after rotation`);
      console.log(`  - First tip Y: ${firstTipY}px`);
      console.log(`  - After rotation Y: ${afterRotationY}px`);
    });

    test('TC-TRAVELER-04: Traveler animation continues while typing', async ({ page }) => {
      // Given: Homepage with typing in progress
      await page.goto(FRONTEND_URL);

      // When: Typing is active (immediately after page load)
      const traveler = page.locator('[data-testid="running-traveler"]');
      await expect(traveler).toBeVisible({ timeout: 10000 });

      // Then: Should have animation keyframes active (motion lines visible)
      const hasMotionLines = await traveler.evaluate((el) => {
        return el.querySelector('g.motion-lines') !== null;
      });

      expect(hasMotionLines).toBe(true);

      console.log(`TC-TRAVELER-04: Animation active during typing`);
    });

    test('TC-TRAVELER-05: Traveler visible on narrow screens', async ({ page }) => {
      // Given: Mobile viewport
      await page.setViewportSize({ width: 375, height: 812 });
      await page.goto(FRONTEND_URL);
      await page.waitForLoadState('networkidle');

      const travelTipBar = page.locator('[data-testid="travel-tip-bar"]');
      const traveler = page.locator('[data-testid="running-traveler"]');

      await expect(travelTipBar).toBeVisible();
      await expect(traveler).toBeVisible();

      // Verify traveler is within viewport
      const isInViewport = await traveler.evaluate((el) => {
        const rect = el.getBoundingClientRect();
        return rect.left >= 0 && rect.right <= window.innerWidth;
      });

      expect(isInViewport).toBe(true);

      console.log(`TC-TRAVELER-05: Traveler visible on narrow screens`);
    });
  });

  test.describe('TC-TRAVELER-REGRESSION: Regression Tests', () => {
    test('TC-TRAVELER-06: Hero section still functions correctly', async ({ page }) => {
      // Given: User navigates to homepage
      await page.goto(FRONTEND_URL);
      await page.waitForLoadState('networkidle');

      // Then: Hero section is visible
      const heroSection = page.locator('section').first();
      await expect(heroSection).toBeVisible();

      // And: Hero image loads correctly
      const heroImage = heroSection.locator('img').first();
      await expect(heroImage).toBeAttached({ timeout: 10000 });

      const imageStatus = await heroImage.evaluate((img: HTMLImageElement) => {
        return {
          complete: img.complete,
          naturalWidth: img.naturalWidth,
        };
      });

      expect(imageStatus.complete).toBe(true);
      expect(imageStatus.naturalWidth).toBeGreaterThan(0);

      // And: Travel tip bar is visible
      const travelTipBar = page.locator('[data-testid="travel-tip-bar"]');
      await expect(travelTipBar).toBeVisible();

      // And: Travel tips text is displayed
      const travelTipText = travelTipBar.locator('span').filter({ hasText: /\w+/ }).first();
      await expect(travelTipText).toBeVisible();

      console.log(`TC-TRAVELER-06: Hero section regression test passed`);
    });

    test('TC-TRAVELER-07: Typewriter effect works correctly', async ({ page }) => {
      // Given: User navigates to homepage
      await page.goto(FRONTEND_URL);

      const travelTipBar = page.locator('[data-testid="travel-tip-bar"]');
      await expect(travelTipBar).toBeVisible();

      // When: We observe the text length over time
      const initialTextLength = await travelTipBar.evaluate((el) => {
        return el.textContent?.trim().length || 0;
      });

      // Wait for more characters to be typed
      await page.waitForTimeout(300);

      const laterTextLength = await travelTipBar.evaluate((el) => {
        return el.textContent?.trim().length || 0;
      });

      // Then: Text should grow (typewriter effect)
      expect(laterTextLength).toBeGreaterThan(initialTextLength);

      console.log(`TC-TRAVELER-07: Typewriter effect working`);
      console.log(`  - Initial length: ${initialTextLength}`);
      console.log(`  - After 300ms: ${laterTextLength}`);
    });
  });
});
