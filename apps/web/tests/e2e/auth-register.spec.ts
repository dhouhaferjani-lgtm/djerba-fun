import { test, expect } from '@playwright/test';
import { testUsers } from '../fixtures/test-data';

test.describe('Authentication - Registration', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/en/auth/register');
  });

  test('should display registration form', async ({ page }) => {
    // Assert
    await expect(page.getByRole('heading', { name: /create account/i })).toBeVisible();
    await expect(page.getByLabel(/email/i)).toBeVisible();
    await expect(page.getByLabel(/password/i)).toBeVisible();
    await expect(page.getByLabel(/first name/i)).toBeVisible();
    await expect(page.getByLabel(/last name/i)).toBeVisible();
    await expect(page.getByRole('button', { name: /create account/i })).toBeVisible();
  });

  test('should register new traveler account', async ({ page }) => {
    // Arrange
    const uniqueEmail = `traveler-${Date.now()}@test.com`;

    // Act
    await page.getByLabel(/email/i).fill(uniqueEmail);
    await page.getByLabel(/^password$/i).fill(testUsers.traveler.password);
    await page.getByLabel(/confirm password/i).fill(testUsers.traveler.password);
    await page.getByLabel(/first name/i).fill(testUsers.traveler.firstName);
    await page.getByLabel(/last name/i).fill(testUsers.traveler.lastName);
    await page.getByLabel(/phone/i).fill(testUsers.traveler.phone);
    await page.getByRole('button', { name: /create account/i }).click();

    // Assert - Should redirect to dashboard after successful registration
    await expect(page).toHaveURL(/\/dashboard/, { timeout: 10000 });
    await expect(page.getByText(testUsers.traveler.firstName)).toBeVisible();
  });

  test('should show error when email already exists', async ({ page, request }) => {
    // Arrange - Create a user first
    const email = `existing-${Date.now()}@test.com`;
    await request.post('http://localhost:8000/api/v1/auth/register', {
      data: {
        email,
        password: 'Password123!',
        password_confirmation: 'Password123!',
        role: 'traveler',
        first_name: 'Test',
        last_name: 'User',
        display_name: 'Test User',
      },
    });

    // Act - Try to register with same email
    await page.getByLabel(/email/i).fill(email);
    await page.getByLabel(/^password$/i).fill(testUsers.traveler.password);
    await page.getByLabel(/confirm password/i).fill(testUsers.traveler.password);
    await page.getByLabel(/first name/i).fill('New');
    await page.getByLabel(/last name/i).fill('User');
    await page.getByRole('button', { name: /create account/i }).click();

    // Assert
    await expect(page.getByText(/email.*already.*taken/i)).toBeVisible();
  });

  test('should show error when passwords do not match', async ({ page }) => {
    // Act
    await page.getByLabel(/email/i).fill('new@test.com');
    await page.getByLabel(/^password$/i).fill('Password123!');
    await page.getByLabel(/confirm password/i).fill('DifferentPassword123!');
    await page.getByRole('button', { name: /create account/i }).click();

    // Assert
    await expect(page.getByText(/passwords.*match/i)).toBeVisible();
  });

  test('should show error for weak password', async ({ page }) => {
    // Act
    await page.getByLabel(/email/i).fill('new@test.com');
    await page.getByLabel(/^password$/i).fill('weak');
    await page.getByLabel(/confirm password/i).fill('weak');
    await page.getByLabel(/first name/i).fill('Test');
    await page.getByLabel(/last name/i).fill('User');
    await page.getByRole('button', { name: /create account/i }).click();

    // Assert
    await expect(
      page.getByText(/password.*at least.*characters|password.*too short/i)
    ).toBeVisible();
  });

  test('should validate email format', async ({ page }) => {
    // Act
    await page.getByLabel(/email/i).fill('invalid-email');
    await page.getByLabel(/^password$/i).fill(testUsers.traveler.password);
    await page.getByRole('button', { name: /create account/i }).click();

    // Assert
    await expect(page.getByText(/invalid email/i)).toBeVisible();
  });

  test('should show validation errors for empty required fields', async ({ page }) => {
    // Act - Submit empty form
    await page.getByRole('button', { name: /create account/i }).click();

    // Assert - Check for required field errors
    await expect(page.getByText(/email.*required/i)).toBeVisible();
    await expect(page.getByText(/password.*required/i)).toBeVisible();
    await expect(page.getByText(/first name.*required/i)).toBeVisible();
    await expect(page.getByText(/last name.*required/i)).toBeVisible();
  });

  test('should navigate to login page', async ({ page }) => {
    // Act
    await page.getByRole('link', { name: /already have.*account/i }).click();

    // Assert
    await expect(page).toHaveURL(/\/auth\/login/);
  });

  test('should accept terms and conditions checkbox', async ({ page }) => {
    // Arrange
    const termsCheckbox = page.getByLabel(/accept.*terms/i);

    // Assert - Initially unchecked
    await expect(termsCheckbox).not.toBeChecked();

    // Act
    await termsCheckbox.check();

    // Assert
    await expect(termsCheckbox).toBeChecked();
  });

  test('should not submit if terms not accepted', async ({ page }) => {
    // Arrange
    const uniqueEmail = `test-${Date.now()}@test.com`;

    // Act - Fill form but don't check terms
    await page.getByLabel(/email/i).fill(uniqueEmail);
    await page.getByLabel(/^password$/i).fill(testUsers.traveler.password);
    await page.getByLabel(/confirm password/i).fill(testUsers.traveler.password);
    await page.getByLabel(/first name/i).fill('Test');
    await page.getByLabel(/last name/i).fill('User');

    // Ensure terms checkbox is not checked
    const termsCheckbox = page.getByLabel(/accept.*terms/i);
    if (await termsCheckbox.isChecked()) {
      await termsCheckbox.uncheck();
    }

    await page.getByRole('button', { name: /create account/i }).click();

    // Assert
    await expect(page.getByText(/accept.*terms/i)).toBeVisible();
  });

  test('should show password strength indicator', async ({ page }) => {
    // Act
    const passwordInput = page.getByLabel(/^password$/i);
    await passwordInput.fill('weak');

    // Assert - Weak password
    await expect(page.getByText(/weak password/i)).toBeVisible();

    // Act - Strong password
    await passwordInput.fill('StrongPassword123!');

    // Assert - Strong password
    await expect(page.getByText(/strong password/i)).toBeVisible();
  });
});
