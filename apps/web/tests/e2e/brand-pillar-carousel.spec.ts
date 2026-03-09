import { test, expect } from '@playwright/test';

test.describe('Brand Pillar Carousel', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('should show all 3 pillar cards', async ({ page }) => {
    const cards = page.locator('[data-testid="pillar-card"]');
    await expect(cards).toHaveCount(3);
  });

  test('should have carousel container with horizontal scroll', async ({ page }) => {
    const carousel = page.locator('[data-testid="pillar-carousel"]');
    await expect(carousel).toBeVisible();
    await expect(carousel).toHaveCSS('overflow-x', 'auto');
  });

  test('should use warm color scheme on first pillar', async ({ page }) => {
    const overlay = page.locator('[data-testid="pillar-overlay"]').first();
    await expect(overlay).toBeVisible();

    // Verify the background is coral (warm sunset tone)
    const bgColor = await overlay.evaluate((el) => getComputedStyle(el).backgroundColor);
    // rgba(205, 92, 92, 0.92) - coral
    expect(bgColor).toMatch(/rgba?\s*\(\s*205\s*,\s*92\s*,\s*92/);
  });

  test('should maintain snake-line animation element', async ({ page }) => {
    const animatedLine = page.locator('.snake-line').first();
    await expect(animatedLine).toBeVisible();
  });

  test('should display CMS text content on pillars', async ({ page }) => {
    const pillarCards = page.locator('[data-testid="pillar-card"]');

    // Each pillar should have a title (h3) and description (p)
    for (let i = 0; i < 3; i++) {
      const card = pillarCards.nth(i);
      const title = card.locator('h3');
      const description = card.locator('p');

      await expect(title).toBeVisible();
      await expect(description).toBeVisible();
    }
  });

  test('should have proper accessibility attributes on carousel', async ({ page }) => {
    const carousel = page.locator('[data-testid="pillar-carousel"]');

    await expect(carousel).toHaveAttribute('role', 'region');
    await expect(carousel).toHaveAttribute('aria-roledescription', 'carousel');
    await expect(carousel).toHaveAttribute('aria-label', 'Brand pillars');
  });

  test.describe('Mobile viewport', () => {
    test.use({ viewport: { width: 375, height: 812 } });

    test('should show scroll indicator dots on mobile', async ({ page }) => {
      const dots = page.locator('[role="tablist"] [role="tab"]');
      await expect(dots).toHaveCount(3);
    });

    test('cards should be visible on mobile', async ({ page }) => {
      const firstCard = page.locator('[data-testid="pillar-card"]').first();
      await expect(firstCard).toBeVisible();

      // Verify the card has the responsive width class
      await expect(firstCard).toHaveClass(/w-\[85vw\]/);
    });

    test('should be horizontally scrollable on mobile', async ({ page }) => {
      const carousel = page.locator('[data-testid="pillar-carousel"]');

      // Get initial scroll position
      const initialScroll = await carousel.evaluate((el) => el.scrollLeft);
      expect(initialScroll).toBe(0);

      // Scroll right
      await carousel.evaluate((el) => el.scrollBy({ left: 300, behavior: 'instant' }));
      await page.waitForTimeout(100);

      const newScroll = await carousel.evaluate((el) => el.scrollLeft);
      expect(newScroll).toBeGreaterThan(0);
    });

    test('clicking dot should scroll to corresponding card', async ({ page }) => {
      const dots = page.locator('[role="tablist"] [role="tab"]');
      const carousel = page.locator('[data-testid="pillar-carousel"]');

      // Click second dot
      await dots.nth(1).click();
      await page.waitForTimeout(500); // Wait for smooth scroll

      const scrollPosition = await carousel.evaluate((el) => el.scrollLeft);
      expect(scrollPosition).toBeGreaterThan(0);
    });
  });

  test.describe('Desktop viewport', () => {
    test.use({ viewport: { width: 1280, height: 800 } });

    test('should hide scroll indicator dots on desktop', async ({ page }) => {
      const dotsContainer = page.locator('[role="tablist"]');
      // On desktop (md:hidden), the dots should not be visible
      await expect(dotsContainer).not.toBeVisible();
    });

    test('should show all 3 cards in a horizontal flex layout', async ({ page }) => {
      const carousel = page.locator('[data-testid="pillar-carousel"]');
      const cards = page.locator('[data-testid="pillar-card"]');

      // Verify carousel uses flex layout
      await expect(carousel).toHaveCSS('display', 'flex');

      // Verify all 3 cards exist
      await expect(cards).toHaveCount(3);

      // Verify cards have the responsive width class for desktop
      const firstCard = cards.first();
      await expect(firstCard).toHaveClass(/md:w-\[calc\(33\.333%-1rem\)\]/);
    });
  });

  test.describe('Hover effects', () => {
    test('image should have hover scale class', async ({ page }) => {
      const firstCard = page.locator('[data-testid="pillar-card"]').first();
      const image = firstCard.locator('img');

      // Verify the image has the hover scale class applied
      await expect(image).toHaveClass(/group-hover:scale-110/);
    });
  });
});
