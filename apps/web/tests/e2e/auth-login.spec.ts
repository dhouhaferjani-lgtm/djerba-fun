import { test, expect } from '@playwright/test';
import { testUsers } from '../fixtures/test-data';
import { createTestUser } from '../fixtures/api-helpers';

test.describe('Authentication - Login', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to login page before each test
    await page.goto('/en/auth/login');
  });

  test('should display login form', async ({ page }) => {
    // Assert
    await expect(page.getByRole('heading', { name: /log in/i })).toBeVisible();
    await expect(page.getByLabel(/email/i)).toBeVisible();
    await expect(page.getByLabel(/password/i)).toBeVisible();
    await expect(page.getByRole('button', { name: /log in/i })).toBeVisible();
  });

  test('should login with valid credentials', async ({ page, request }) => {
    // Arrange - Create a test user
    const user = await createTestUser(request, {
      email: testUsers.traveler.email,
      password: testUsers.traveler.password,
      firstName: testUsers.traveler.firstName,
      lastName: testUsers.traveler.lastName,
    });

    // Act - Fill in login form
    await page.getByLabel(/email/i).fill(testUsers.traveler.email);
    await page.getByLabel(/password/i).fill(testUsers.traveler.password);
    await page.getByRole('button', { name: /log in/i }).click();

    // Assert - Should redirect to dashboard
    await expect(page).toHaveURL(/\/dashboard/);
    await expect(page.getByText(testUsers.traveler.firstName)).toBeVisible();
  });

  test('should show error with invalid credentials', async ({ page }) => {
    // Act
    await page.getByLabel(/email/i).fill('invalid@example.com');
    await page.getByLabel(/password/i).fill('wrongpassword');
    await page.getByRole('button', { name: /log in/i }).click();

    // Assert
    await expect(page.getByText(/invalid credentials/i)).toBeVisible();
    await expect(page).toHaveURL(/\/auth\/login/);
  });

  test('should show validation errors for empty fields', async ({ page }) => {
    // Act - Click login without filling fields
    await page.getByRole('button', { name: /log in/i }).click();

    // Assert
    await expect(page.getByText(/email is required/i)).toBeVisible();
    await expect(page.getByText(/password is required/i)).toBeVisible();
  });

  test('should show validation error for invalid email format', async ({ page }) => {
    // Act
    await page.getByLabel(/email/i).fill('invalid-email');
    await page.getByLabel(/password/i).fill('password123');
    await page.getByRole('button', { name: /log in/i }).click();

    // Assert
    await expect(page.getByText(/invalid email/i)).toBeVisible();
  });

  test('should toggle password visibility', async ({ page }) => {
    // Arrange
    const passwordInput = page.getByLabel(/password/i);

    // Assert - Initially password is hidden
    await expect(passwordInput).toHaveAttribute('type', 'password');

    // Act - Click show password button
    await page.getByRole('button', { name: /show password/i }).click();

    // Assert - Password is now visible
    await expect(passwordInput).toHaveAttribute('type', 'text');

    // Act - Click hide password button
    await page.getByRole('button', { name: /hide password/i }).click();

    // Assert - Password is hidden again
    await expect(passwordInput).toHaveAttribute('type', 'password');
  });

  test('should navigate to register page', async ({ page }) => {
    // Act
    await page.getByRole('link', { name: /create an account/i }).click();

    // Assert
    await expect(page).toHaveURL(/\/auth\/register/);
  });

  test('should navigate to forgot password page', async ({ page }) => {
    // Act
    await page.getByRole('link', { name: /forgot password/i }).click();

    // Assert
    await expect(page).toHaveURL(/\/auth\/forgot-password/);
  });

  test('should persist email in form after failed login', async ({ page }) => {
    // Act
    const email = 'test@example.com';
    await page.getByLabel(/email/i).fill(email);
    await page.getByLabel(/password/i).fill('wrongpassword');
    await page.getByRole('button', { name: /log in/i }).click();

    // Wait for error
    await page.waitForSelector('text=/invalid credentials/i');

    // Assert - Email should still be in the input
    await expect(page.getByLabel(/email/i)).toHaveValue(email);
  });
});
