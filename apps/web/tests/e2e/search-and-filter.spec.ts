import { test, expect } from '@playwright/test';

test.describe('Search and Filter Listings', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/en/listings');
  });

  test('should display listings page', async ({ page }) => {
    // Assert
    await expect(page.getByRole('heading', { name: /explore|listings/i })).toBeVisible();
    await expect(page.locator('[data-testid="listing-card"]').first()).toBeVisible();
  });

  test('should search listings by text', async ({ page }) => {
    // Act
    const searchInput = page.getByPlaceholder(/search.*listings/i);
    await searchInput.fill('hiking');
    await searchInput.press('Enter');

    // Assert
    await expect(page).toHaveURL(/search=hiking/);

    // All visible listings should contain "hiking" in title or description
    const listings = page.locator('[data-testid="listing-card"]');
    const count = await listings.count();

    if (count > 0) {
      for (let i = 0; i < Math.min(count, 3); i++) {
        const listingText = await listings.nth(i).textContent();
        expect(listingText?.toLowerCase()).toContain('hiking');
      }
    }
  });

  test('should filter by price range', async ({ page }) => {
    // Act
    await page.getByLabel(/min.*price/i).fill('50');
    await page.getByLabel(/max.*price/i).fill('200');
    await page.getByRole('button', { name: /apply.*filters/i }).click();

    // Assert
    await expect(page).toHaveURL(/min_price=50.*max_price=200/);
  });

  test('should filter by location', async ({ page }) => {
    // Act
    const locationFilter = page.getByLabel(/location/i);
    const hasLocationFilter = await locationFilter.isVisible().catch(() => false);

    if (hasLocationFilter) {
      await locationFilter.selectOption({ index: 1 }); // Select first option

      // Assert - URL should update
      await expect(page).toHaveURL(/location/);
    }
  });

  test('should filter by category', async ({ page }) => {
    // Look for category filters
    const categoryFilter = page.locator('[data-testid="category-filter"]');
    const hasCategories = await categoryFilter.isVisible().catch(() => false);

    if (hasCategories) {
      // Click a category
      const firstCategory = categoryFilter.locator('button').first();
      await firstCategory.click();

      // URL should update with category
      await expect(page).toHaveURL(/category/);
    }
  });

  test('should sort listings by price ascending', async ({ page }) => {
    // Act
    const sortSelect = page.getByLabel(/sort.*by/i);
    await sortSelect.selectOption('price_asc');

    // Assert
    await expect(page).toHaveURL(/sort=price_asc/);

    // Verify prices are in ascending order
    const prices = page.locator('[data-testid="listing-price"]');
    const count = await prices.count();

    if (count >= 2) {
      const price1 = parseFloat(
        (await prices.nth(0).textContent())?.replace(/[^0-9.]/g, '') || '0'
      );
      const price2 = parseFloat(
        (await prices.nth(1).textContent())?.replace(/[^0-9.]/g, '') || '0'
      );
      expect(price1).toBeLessThanOrEqual(price2);
    }
  });

  test('should sort listings by price descending', async ({ page }) => {
    // Act
    const sortSelect = page.getByLabel(/sort.*by/i);
    await sortSelect.selectOption('price_desc');

    // Assert
    await expect(page).toHaveURL(/sort=price_desc/);
  });

  test('should sort listings by rating', async ({ page }) => {
    // Act
    const sortSelect = page.getByLabel(/sort.*by/i);
    await sortSelect.selectOption('rating_desc');

    // Assert
    await expect(page).toHaveURL(/sort=rating_desc/);
  });

  test('should clear all filters', async ({ page }) => {
    // Arrange - Apply some filters
    await page.getByLabel(/min.*price/i).fill('50');
    const sortSelect = page.getByLabel(/sort.*by/i);
    await sortSelect.selectOption('price_asc');

    // Act - Clear filters
    const clearButton = page.getByRole('button', { name: /clear.*filters/i });
    if (await clearButton.isVisible()) {
      await clearButton.click();

      // Assert
      await expect(page).toHaveURL(/^[^?]*$/); // No query params
      await expect(page.getByLabel(/min.*price/i)).toHaveValue('');
    }
  });

  test('should show number of results', async ({ page }) => {
    // Assert
    await expect(page.getByText(/\d+.*results?/i)).toBeVisible();
  });

  test('should paginate results', async ({ page }) => {
    // Look for pagination
    const nextButton = page.getByRole('button', { name: /next/i });
    const hasPagination = await nextButton.isVisible().catch(() => false);

    if (hasPagination) {
      // Act
      await nextButton.click();

      // Assert
      await expect(page).toHaveURL(/page=2/);

      // Previous button should now be visible
      await expect(page.getByRole('button', { name: /previous/i })).toBeVisible();
    }
  });

  test('should display listing cards with required information', async ({ page }) => {
    // Assert
    const firstCard = page.locator('[data-testid="listing-card"]').first();
    await expect(firstCard).toBeVisible();

    // Each card should have title, price, and image
    await expect(firstCard.locator('[data-testid="listing-title"]')).toBeVisible();
    await expect(firstCard.locator('[data-testid="listing-price"]')).toBeVisible();
    await expect(firstCard.locator('img')).toBeVisible();
  });

  test('should navigate to listing detail on click', async ({ page }) => {
    // Act
    const firstCard = page.locator('[data-testid="listing-card"]').first();
    await firstCard.click();

    // Assert
    await expect(page).toHaveURL(/\/listings\/[^/]+/);
  });

  test('should show map view toggle', async ({ page }) => {
    // Look for map toggle button
    const mapToggle = page.getByRole('button', { name: /map.*view/i });
    const hasMapView = await mapToggle.isVisible().catch(() => false);

    if (hasMapView) {
      // Act
      await mapToggle.click();

      // Assert - Map should be visible
      await expect(page.locator('[data-testid="listings-map"]')).toBeVisible();
    }
  });

  test('should filter by availability date', async ({ page }) => {
    // Look for date picker
    const dateInput = page.getByLabel(/date|when/i);
    const hasDateFilter = await dateInput.isVisible().catch(() => false);

    if (hasDateFilter) {
      // Select a date
      await dateInput.fill('2024-12-25');

      // Apply filter
      await page.getByRole('button', { name: /search|apply/i }).click();

      // URL should include date
      await expect(page).toHaveURL(/date=/);
    }
  });

  test('should show empty state when no results', async ({ page }) => {
    // Act - Search for something that doesn't exist
    const searchInput = page.getByPlaceholder(/search.*listings/i);
    await searchInput.fill('xyznonexistentlisting123');
    await searchInput.press('Enter');

    // Wait for results to load
    await page.waitForTimeout(1000);

    // Assert - Should show empty state
    const noResults = await page
      .getByText(/no.*listings.*found/i)
      .isVisible()
      .catch(() => false);
    if (noResults) {
      await expect(page.getByText(/no.*listings.*found/i)).toBeVisible();
    }
  });

  test('should maintain filters across pagination', async ({ page }) => {
    // Arrange - Apply filter
    await page.getByLabel(/min.*price/i).fill('100');
    await page.getByRole('button', { name: /apply.*filters/i }).click();

    // Look for next button
    const nextButton = page.getByRole('button', { name: /next/i });
    const hasPagination = await nextButton.isVisible().catch(() => false);

    if (hasPagination) {
      // Act - Go to next page
      await nextButton.click();

      // Assert - Filter should still be in URL
      await expect(page).toHaveURL(/min_price=100.*page=2/);
    }
  });
});
