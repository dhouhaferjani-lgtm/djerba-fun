import { test, expect } from '@playwright/test';

test.describe('User Profile Management', () => {
  const testUser = {
    email: 'testuser@example.com',
    password: 'password123',
    firstName: 'Test',
    lastName: 'User',
  };

  test.beforeEach(async ({ page }) => {
    // Navigate to login page
    await page.goto('/en/auth/login');
  });

  test('user can view their profile', async ({ page }) => {
    // Login
    await page.fill('[data-testid="email-input"]', testUser.email);
    await page.fill('[data-testid="password-input"]', testUser.password);
    await page.click('[data-testid="login-button"]');

    // Wait for redirect to dashboard
    await page.waitForURL('**/dashboard');

    // Navigate to profile
    await page.click('[data-testid="user-menu"]');
    await page.click('[data-testid="profile-link"]');

    // Verify profile information is displayed
    await expect(page.locator('[data-testid="profile-email"]')).toContainText(testUser.email);
    await expect(page.locator('[data-testid="profile-first-name"]')).toHaveValue(
      testUser.firstName
    );
    await expect(page.locator('[data-testid="profile-last-name"]')).toHaveValue(testUser.lastName);
  });

  test('user can update their profile information', async ({ page }) => {
    // Login
    await page.fill('[data-testid="email-input"]', testUser.email);
    await page.fill('[data-testid="password-input"]', testUser.password);
    await page.click('[data-testid="login-button"]');

    await page.waitForURL('**/dashboard');

    // Navigate to profile edit
    await page.goto('/en/dashboard/profile/edit');

    // Update profile fields
    await page.fill('[data-testid="first-name-input"]', 'Updated');
    await page.fill('[data-testid="last-name-input"]', 'Name');
    await page.fill('[data-testid="phone-input"]', '+1234567890');

    // Submit form
    await page.click('[data-testid="save-profile-button"]');

    // Verify success message
    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    await expect(page.locator('[data-testid="success-message"]')).toContainText(
      'Profile updated successfully'
    );

    // Verify changes are persisted
    await page.reload();
    await expect(page.locator('[data-testid="first-name-input"]')).toHaveValue('Updated');
    await expect(page.locator('[data-testid="last-name-input"]')).toHaveValue('Name');
  });

  test('user can update traveler preferences', async ({ page }) => {
    // Login
    await page.fill('[data-testid="email-input"]', testUser.email);
    await page.fill('[data-testid="password-input"]', testUser.password);
    await page.click('[data-testid="login-button"]');

    await page.waitForURL('**/dashboard');

    // Navigate to preferences
    await page.goto('/en/dashboard/preferences');

    // Update currency preference
    await page.selectOption('[data-testid="preferred-currency-select"]', 'EUR');

    // Update newsletter subscription
    await page.check('[data-testid="newsletter-checkbox"]');

    // Add dietary restrictions
    await page.check('[data-testid="dietary-vegetarian"]');

    // Save preferences
    await page.click('[data-testid="save-preferences-button"]');

    // Verify success
    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();

    // Verify preferences are saved
    await page.reload();
    await expect(page.locator('[data-testid="preferred-currency-select"]')).toHaveValue('EUR');
    await expect(page.locator('[data-testid="newsletter-checkbox"]')).toBeChecked();
    await expect(page.locator('[data-testid="dietary-vegetarian"]')).toBeChecked();
  });

  test('user can change their password', async ({ page }) => {
    // Login
    await page.fill('[data-testid="email-input"]', testUser.email);
    await page.fill('[data-testid="password-input"]', testUser.password);
    await page.click('[data-testid="login-button"]');

    await page.waitForURL('**/dashboard');

    // Navigate to security settings
    await page.goto('/en/dashboard/security');

    // Fill password change form
    await page.fill('[data-testid="current-password-input"]', testUser.password);
    await page.fill('[data-testid="new-password-input"]', 'newpassword123');
    await page.fill('[data-testid="confirm-password-input"]', 'newpassword123');

    // Submit form
    await page.click('[data-testid="change-password-button"]');

    // Verify success
    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    await expect(page.locator('[data-testid="success-message"]')).toContainText(
      'Password changed successfully'
    );

    // Logout
    await page.click('[data-testid="user-menu"]');
    await page.click('[data-testid="logout-button"]');

    // Try logging in with new password
    await page.goto('/en/auth/login');
    await page.fill('[data-testid="email-input"]', testUser.email);
    await page.fill('[data-testid="password-input"]', 'newpassword123');
    await page.click('[data-testid="login-button"]');

    // Should successfully login
    await page.waitForURL('**/dashboard');
    await expect(page.locator('[data-testid="dashboard-heading"]')).toBeVisible();
  });

  test('password change requires correct current password', async ({ page }) => {
    // Login
    await page.fill('[data-testid="email-input"]', testUser.email);
    await page.fill('[data-testid="password-input"]', testUser.password);
    await page.click('[data-testid="login-button"]');

    await page.waitForURL('**/dashboard');

    // Navigate to security settings
    await page.goto('/en/dashboard/security');

    // Fill with incorrect current password
    await page.fill('[data-testid="current-password-input"]', 'wrongpassword');
    await page.fill('[data-testid="new-password-input"]', 'newpassword123');
    await page.fill('[data-testid="confirm-password-input"]', 'newpassword123');

    // Submit form
    await page.click('[data-testid="change-password-button"]');

    // Verify error message
    await expect(page.locator('[data-testid="error-message"]')).toBeVisible();
    await expect(page.locator('[data-testid="error-message"]')).toContainText(
      'Current password is incorrect'
    );
  });

  test('user can update emergency contact information', async ({ page }) => {
    // Login
    await page.fill('[data-testid="email-input"]', testUser.email);
    await page.fill('[data-testid="password-input"]', testUser.password);
    await page.click('[data-testid="login-button"]');

    await page.waitForURL('**/dashboard');

    // Navigate to traveler profile
    await page.goto('/en/dashboard/traveler-profile');

    // Update emergency contact
    await page.fill('[data-testid="emergency-contact-name"]', 'John Emergency');
    await page.fill('[data-testid="emergency-contact-phone"]', '+9876543210');
    await page.fill('[data-testid="emergency-contact-relationship"]', 'Brother');

    // Save changes
    await page.click('[data-testid="save-traveler-profile-button"]');

    // Verify success
    await expect(page.locator('[data-testid="success-message"]')).toBeVisible();

    // Verify data persistence
    await page.reload();
    await expect(page.locator('[data-testid="emergency-contact-name"]')).toHaveValue(
      'John Emergency'
    );
    await expect(page.locator('[data-testid="emergency-contact-phone"]')).toHaveValue(
      '+9876543210'
    );
  });

  test('profile form validation works correctly', async ({ page }) => {
    // Login
    await page.fill('[data-testid="email-input"]', testUser.email);
    await page.fill('[data-testid="password-input"]', testUser.password);
    await page.click('[data-testid="login-button"]');

    await page.waitForURL('**/dashboard');

    // Navigate to profile edit
    await page.goto('/en/dashboard/profile/edit');

    // Clear required fields
    await page.fill('[data-testid="first-name-input"]', '');
    await page.fill('[data-testid="last-name-input"]', '');

    // Try to submit
    await page.click('[data-testid="save-profile-button"]');

    // Verify validation errors
    await expect(page.locator('[data-testid="first-name-error"]')).toBeVisible();
    await expect(page.locator('[data-testid="last-name-error"]')).toBeVisible();
  });

  test('user can view their booking history from profile', async ({ page }) => {
    // Login
    await page.fill('[data-testid="email-input"]', testUser.email);
    await page.fill('[data-testid="password-input"]', testUser.password);
    await page.click('[data-testid="login-button"]');

    await page.waitForURL('**/dashboard');

    // Navigate to booking history
    await page.click('[data-testid="user-menu"]');
    await page.click('[data-testid="bookings-link"]');

    // Verify bookings page is displayed
    await expect(page.locator('[data-testid="bookings-heading"]')).toBeVisible();
    await expect(page.locator('[data-testid="bookings-list"]')).toBeVisible();
  });

  test('user can delete their account', async ({ page }) => {
    // Login
    await page.fill('[data-testid="email-input"]', testUser.email);
    await page.fill('[data-testid="password-input"]', testUser.password);
    await page.click('[data-testid="login-button"]');

    await page.waitForURL('**/dashboard');

    // Navigate to account settings
    await page.goto('/en/dashboard/settings');

    // Click delete account button
    await page.click('[data-testid="delete-account-button"]');

    // Confirm deletion in modal
    await expect(page.locator('[data-testid="delete-confirmation-modal"]')).toBeVisible();
    await page.fill('[data-testid="confirm-password-input"]', testUser.password);
    await page.click('[data-testid="confirm-delete-button"]');

    // Should redirect to home page
    await page.waitForURL('**/');

    // Verify success message
    await expect(page.locator('[data-testid="account-deleted-message"]')).toBeVisible();
  });
});
