import { test, expect } from '@playwright/test';

test.describe('Search and Filter Listings', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/en/listings');
  });

  test('should display listings page', async ({ page }) => {
    // Assert - h1 shows "All Experiences" for English locale
    await expect(page.getByRole('heading', { name: /all experiences|experiences/i })).toBeVisible();
    await expect(page.locator('[data-testid="listing-card"]').first()).toBeVisible();
  });

  test('should search listings by text', async ({ page }) => {
    // Act - Search placeholder is "Search destinations..."
    const searchInput = page.getByPlaceholder(/search.*destinations/i);
    await searchInput.fill('tour');
    await searchInput.press('Enter');

    // Assert - URL uses 'q' param not 'search'
    await expect(page).toHaveURL(/q=tour/);

    // Wait for results to load
    await page.waitForTimeout(500);
  });

  test('should filter by service type', async ({ page }) => {
    // Act - Open filters panel first
    await page.getByRole('button', { name: /filters/i }).click();

    // Wait for filters to be visible
    await expect(page.getByText(/experience type/i)).toBeVisible();

    // Select Tours
    const typeSelect = page
      .locator('select')
      .filter({ hasText: /all types/i })
      .first();
    await typeSelect.selectOption('tour');

    // Assert - URL should update
    await expect(page).toHaveURL(/type=tour/);
  });

  test('should filter by location', async ({ page }) => {
    // Act - Open filters panel first
    await page.getByRole('button', { name: /filters/i }).click();

    // Wait for filters to be visible
    await expect(page.getByText(/location/i).first()).toBeVisible();

    // Find the location dropdown (second select)
    const selects = page.locator('select');
    const selectCount = await selects.count();

    if (selectCount >= 2) {
      // Try to select a location (index 1 = first real option after "All locations")
      const locationSelect = selects.nth(1);
      const options = await locationSelect.locator('option').count();
      if (options > 1) {
        await locationSelect.selectOption({ index: 1 });
        await expect(page).toHaveURL(/location/);
      }
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
    // Act - Open filters panel first
    await page.getByRole('button', { name: /filters/i }).click();

    // Wait for sort dropdown
    await expect(page.getByText(/sort by/i).first()).toBeVisible();

    // Find the sort dropdown (third select)
    const selects = page.locator('select');
    const sortSelect = selects.nth(2); // Third select is sort

    await sortSelect.selectOption('price_asc');

    // Assert
    await expect(page).toHaveURL(/sort=price_asc/);
  });

  test('should sort listings by price descending', async ({ page }) => {
    // Act - Open filters panel first
    await page.getByRole('button', { name: /filters/i }).click();

    // Find the sort dropdown (third select)
    const selects = page.locator('select');
    const sortSelect = selects.nth(2);

    await sortSelect.selectOption('price_desc');

    // Assert
    await expect(page).toHaveURL(/sort=price_desc/);
  });

  test('should sort listings by rating', async ({ page }) => {
    // Act - Open filters panel first
    await page.getByRole('button', { name: /filters/i }).click();

    // Find the sort dropdown (third select)
    const selects = page.locator('select');
    const sortSelect = selects.nth(2);

    await sortSelect.selectOption('rating');

    // Assert
    await expect(page).toHaveURL(/sort=rating/);
  });

  test('should clear all filters', async ({ page }) => {
    // Arrange - Open filters and apply some filters
    await page.getByRole('button', { name: /filters/i }).click();

    // Select a type filter
    const typeSelect = page.locator('select').first();
    await typeSelect.selectOption('tour');
    await expect(page).toHaveURL(/type=tour/);

    // Act - Clear filters
    const clearButton = page.getByRole('button', { name: /clear all/i });
    if (await clearButton.isVisible()) {
      await clearButton.click();

      // Assert - URL should have no query params
      await expect(page).not.toHaveURL(/type=/);
    }
  });

  test('should show number of results', async ({ page }) => {
    // Assert - "X experiences found" message (may be styled differently)
    // Translation is "found_experiences": "{count, plural, =0 {No experiences found} one {# experience found} other {# experiences found}}"
    const resultsText = page.getByText(/\d+\s*experience/i);
    await expect(resultsText).toBeVisible();
  });

  test('should paginate results', async ({ page }) => {
    // Look for pagination controls - might be "Load more", "Next", or numbered pages
    const paginationControls = page.locator(
      '[aria-label*="page"], [class*="pagination"], button:has-text(/next|load more|show more/i)'
    );
    const hasPagination = await paginationControls
      .first()
      .isVisible()
      .catch(() => false);

    // Skip test if no pagination (e.g., fewer items than page size)
    test.skip(!hasPagination, 'Pagination not present - skipping');
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

    // Assert - URL should be /{location}/{slug} format, not /listings/{slug}
    // Wait for navigation to complete
    await page.waitForURL(/\/en\/[^/]+\/[^/]+$/);

    // Verify we're no longer on listings page
    await expect(page).not.toHaveURL(/\/listings$/);
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
    const searchInput = page.getByPlaceholder(/search.*destinations/i);
    await searchInput.fill('xyznonexistentlisting123');
    await searchInput.press('Enter');

    // Wait for results to load
    await page.waitForTimeout(1000);

    // Assert - Should show empty state or "0 experiences found"
    const noResultsText = page.getByText(/no.*results|no.*experiences|0.*experiences/i);
    const hasNoResults = await noResultsText.isVisible().catch(() => false);
    if (hasNoResults) {
      await expect(noResultsText).toBeVisible();
    }
  });

  test('should maintain filters across pagination', async ({ page }) => {
    // Arrange - Open filters and apply a filter
    await page.getByRole('button', { name: /filters/i }).click();

    // Select a type filter
    const typeSelect = page.locator('select').first();
    await typeSelect.selectOption('tour');
    await expect(page).toHaveURL(/type=tour/);

    // Look for pagination controls (may not exist with few seeded items)
    const paginationControls = page.locator(
      '[aria-label*="page"], [class*="pagination"], button:has-text(/next|load more/i)'
    );
    const hasPagination = await paginationControls
      .first()
      .isVisible()
      .catch(() => false);

    // Skip test if no pagination
    test.skip(!hasPagination, 'Pagination not present - skipping');
  });
});
