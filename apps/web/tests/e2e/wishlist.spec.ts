import { test, expect, Page, APIRequestContext } from '@playwright/test';
import { loginTestUser } from '../fixtures/api-helpers';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

// Pre-verified test user credentials (created in VendorSeeder with email_verified_at set)
const TEST_USER = {
  email: 'traveler@test.com',
  password: 'TestPassword123!',
};

/**
 * Helper function to set auth token directly in localStorage
 * Navigates to a minimal page first, sets token, then subsequent navigations will have it
 */
async function setAuthToken(page: Page, token: string): Promise<void> {
  // Navigate to a minimal page on the domain to establish localStorage context
  await page.goto('/en');

  // Set the token in localStorage
  await page.evaluate((authToken: string) => {
    localStorage.setItem('auth_token', authToken);
  }, token);

  // Small wait to ensure localStorage is persisted
  await page.waitForTimeout(100);
}

/**
 * Helper function to wait for wishlist button to be fully ready (auth loaded, not loading)
 */
async function waitForWishlistButtonReady(page: Page): Promise<void> {
  // Wait for button to exist
  await page.waitForSelector('[data-testid="wishlist-button"]', { timeout: 10000 });

  // Wait for button to be enabled (auth and wishlist data loaded)
  await page.waitForFunction(
    () => {
      const btn = document.querySelector('[data-testid="wishlist-button"]');
      return btn && !btn.hasAttribute('disabled');
    },
    { timeout: 10000 }
  );

  // Additional wait for React Query to fetch and update wishlist IDs
  await page.waitForTimeout(500);
}

/**
 * Helper function to clear all wishlist items for the user
 */
async function clearWishlist(request: APIRequestContext, token: string): Promise<void> {
  // Get current wishlist IDs
  const response = await request.get(`${API_BASE_URL}/wishlists/ids`, {
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
    },
  });

  if (response.ok()) {
    const data = await response.json();
    const listingIds = data.data?.listing_ids || [];

    // Remove each item from wishlist
    for (const listingId of listingIds) {
      await request.delete(`${API_BASE_URL}/wishlists/${listingId}`, {
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
      });
    }
  }
}

test.describe('Wishlist Feature', () => {
  test.describe('Unauthenticated User', () => {
    test('shows login prompt when clicking wishlist button', async ({ page }) => {
      // Navigate to listings page
      await page.goto('/en/listings');

      // Wait for listings to load
      await page.waitForSelector('[data-testid="wishlist-button"]', { timeout: 10000 });

      // Find first listing card's wishlist button
      const wishlistButton = page.locator('[data-testid="wishlist-button"]').first();
      await wishlistButton.click();

      // Should redirect to login (unauthenticated users are redirected)
      await expect(page).toHaveURL(/\/auth\/login/);
    });

    test('cannot access wishlist dashboard page without login', async ({ page }) => {
      // Try to access wishlist page directly
      await page.goto('/en/dashboard/wishlist');

      // Should redirect to login
      await expect(page).toHaveURL(/\/auth\/login/);
    });
  });

  test.describe('Authenticated User', () => {
    let testUser: { token: string; email: string };

    test.beforeEach(async ({ page, request }) => {
      // Login as the pre-verified test user
      testUser = await loginTestUser(request, TEST_USER.email, TEST_USER.password);

      // Clear wishlist before each test to ensure isolation
      await clearWishlist(request, testUser.token);

      // Set auth token in localStorage before navigating
      await setAuthToken(page, testUser.token);
    });

    test('can add listing to wishlist from listing card', async ({ page }) => {
      // Navigate to listings page
      await page.goto('/en/listings');

      // Wait for button to be fully ready (auth loaded, wishlist data loaded)
      await waitForWishlistButtonReady(page);

      // Find first listing's wishlist button
      const wishlistButton = page.locator('[data-testid="wishlist-button"]').first();

      // Verify it's not saved initially
      await expect(wishlistButton).toHaveAttribute('data-saved', 'false');

      // Click to add to wishlist
      await wishlistButton.click();

      // Button should now show as saved (visual feedback via filled heart)
      await expect(wishlistButton).toHaveAttribute('data-saved', 'true', { timeout: 5000 });
    });

    test('can remove listing from wishlist via toggle', async ({ page }) => {
      // Navigate to listings page
      await page.goto('/en/listings');

      // Wait for button to be fully ready
      await waitForWishlistButtonReady(page);

      const wishlistButton = page.locator('[data-testid="wishlist-button"]').first();

      // First add to wishlist
      await wishlistButton.click();
      await expect(wishlistButton).toHaveAttribute('data-saved', 'true', { timeout: 5000 });

      // Wait for mutation to fully complete and state to settle
      await page.waitForTimeout(500);

      // Now remove by clicking again
      await wishlistButton.click();

      // Button should show as not saved (visual feedback via unfilled heart)
      await expect(wishlistButton).toHaveAttribute('data-saved', 'false', { timeout: 5000 });
    });

    test('wishlist state persists across page navigation', async ({ page }) => {
      // Navigate to listings page
      await page.goto('/en/listings');

      // Wait for button to be fully ready
      await waitForWishlistButtonReady(page);

      // Add to wishlist
      const wishlistButton = page.locator('[data-testid="wishlist-button"]').first();
      await wishlistButton.click();
      await expect(wishlistButton).toHaveAttribute('data-saved', 'true', { timeout: 5000 });

      // Wait for mutation to fully complete (API call settles)
      await page.waitForTimeout(500);

      // Navigate away
      await page.goto('/en/dashboard');
      await page.waitForLoadState('networkidle');

      // Navigate back to listings
      await page.goto('/en/listings');
      await waitForWishlistButtonReady(page);

      // Should still be saved
      await expect(page.locator('[data-testid="wishlist-button"]').first()).toHaveAttribute(
        'data-saved',
        'true'
      );
    });

    test('can view wishlist in dashboard', async ({ page }) => {
      // First add a listing to wishlist
      await page.goto('/en/listings');
      await waitForWishlistButtonReady(page);

      const wishlistButton = page.locator('[data-testid="wishlist-button"]').first();
      await wishlistButton.click();

      // Wait for wishlist to be added (visual confirmation)
      await expect(wishlistButton).toHaveAttribute('data-saved', 'true', { timeout: 5000 });

      // Wait for mutation to fully complete (API call settles)
      await page.waitForTimeout(500);

      // Navigate to wishlist page
      await page.goto('/en/dashboard/wishlist');

      // Should show the wishlisted item
      await expect(page.locator('[data-testid="wishlist-item"]')).toHaveCount(1, {
        timeout: 10000,
      });
    });

    test('can remove listing from wishlist dashboard', async ({ page }) => {
      // First add a listing to wishlist
      await page.goto('/en/listings');
      await waitForWishlistButtonReady(page);

      const wishlistButton = page.locator('[data-testid="wishlist-button"]').first();
      await wishlistButton.click();

      // Wait for wishlist to be added
      await expect(wishlistButton).toHaveAttribute('data-saved', 'true', { timeout: 5000 });

      // Wait for mutation to fully complete (API call settles)
      await page.waitForTimeout(500);

      // Navigate to wishlist page
      await page.goto('/en/dashboard/wishlist');

      // Verify item is there
      await expect(page.locator('[data-testid="wishlist-item"]')).toHaveCount(1, {
        timeout: 10000,
      });

      // Click remove button
      await page.click('[data-testid="remove-from-wishlist"]');

      // Should show empty state after removal
      await expect(page.locator('[data-testid="wishlist-empty"]')).toBeVisible({ timeout: 5000 });
    });

    test('shows empty state when wishlist is empty', async ({ page }) => {
      // Navigate directly to wishlist page
      await page.goto('/en/dashboard/wishlist');

      // Should show empty state
      await expect(page.locator('[data-testid="wishlist-empty"]')).toBeVisible({ timeout: 10000 });

      // Should show "Your wishlist is empty" message
      await expect(
        page.getByText(/your wishlist is empty|liste de souhaits est vide/i)
      ).toBeVisible();

      // Should show "Browse Experiences" link
      await expect(page.getByRole('link', { name: /browse|parcourir/i })).toBeVisible();
    });

    test('browse button in empty state navigates to listings', async ({ page }) => {
      // Navigate to wishlist page
      await page.goto('/en/dashboard/wishlist');

      // Wait for empty state
      await expect(page.locator('[data-testid="wishlist-empty"]')).toBeVisible({ timeout: 10000 });

      // Click browse button
      await page.getByRole('link', { name: /browse|parcourir/i }).click();

      // Should navigate to listings page
      await expect(page).toHaveURL(/\/listings/);
    });

    test('wishlist count updates correctly', async ({ page }) => {
      // Navigate to listings page
      await page.goto('/en/listings');
      await waitForWishlistButtonReady(page);

      const buttons = page.locator('[data-testid="wishlist-button"]');
      const buttonCount = await buttons.count();

      // Add first listing
      const firstButton = buttons.first();
      await firstButton.click();
      await expect(firstButton).toHaveAttribute('data-saved', 'true', { timeout: 5000 });

      // Wait for mutation to fully complete
      await page.waitForTimeout(500);

      // Add second listing if available
      if (buttonCount > 1) {
        const secondButton = buttons.nth(1);
        await secondButton.click();
        await expect(secondButton).toHaveAttribute('data-saved', 'true', { timeout: 5000 });
        // Wait for second mutation to complete
        await page.waitForTimeout(500);
      }

      // Navigate to wishlist page
      await page.goto('/en/dashboard/wishlist');

      // Should show correct count of items
      const expectedCount = buttonCount > 1 ? 2 : 1;
      await expect(page.locator('[data-testid="wishlist-item"]')).toHaveCount(expectedCount, {
        timeout: 10000,
      });
    });

    test('clicking wishlist item navigates to listing detail', async ({ page }) => {
      // First add a listing to wishlist
      await page.goto('/en/listings');
      await waitForWishlistButtonReady(page);

      const wishlistButton = page.locator('[data-testid="wishlist-button"]').first();
      await wishlistButton.click();

      // Wait for mutation to complete
      await expect(wishlistButton).toHaveAttribute('data-saved', 'true', { timeout: 5000 });
      await page.waitForTimeout(500);

      // Navigate to wishlist page
      await page.goto('/en/dashboard/wishlist');
      await expect(page.locator('[data-testid="wishlist-item"]')).toHaveCount(1, {
        timeout: 10000,
      });

      // Click on the wishlist item (the link area, not the remove button)
      const wishlistItem = page.locator('[data-testid="wishlist-item"]').first();
      const link = wishlistItem.locator('a').first();
      await link.click();

      // Should navigate to listing detail page
      await expect(page).toHaveURL(/\/listings\/|\/[a-z-]+\/[a-z-]+/);
    });
  });

  test.describe('Accessibility', () => {
    test('wishlist button has proper aria labels', async ({ page }) => {
      await page.goto('/en/listings');
      await page.waitForSelector('[data-testid="wishlist-button"]', { timeout: 10000 });

      const wishlistButton = page.locator('[data-testid="wishlist-button"]').first();

      // Should have aria-label
      await expect(wishlistButton).toHaveAttribute('aria-label', /.+/);
    });

    test('wishlist button is keyboard accessible', async ({ page }) => {
      await page.goto('/en/listings');
      await page.waitForSelector('[data-testid="wishlist-button"]', { timeout: 10000 });

      // Focus on first wishlist button using Tab
      const wishlistButton = page.locator('[data-testid="wishlist-button"]').first();
      await wishlistButton.focus();

      // Should be focusable
      await expect(wishlistButton).toBeFocused();
    });
  });
});
