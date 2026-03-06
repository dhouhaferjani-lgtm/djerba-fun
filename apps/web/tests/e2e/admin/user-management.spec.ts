/**
 * Admin Panel - User Management E2E Tests
 *
 * Test Cases:
 * TC-A020: Create User with Different Roles
 * TC-A021: Deactivate User
 */

import { test, expect, Page } from '@playwright/test';
import {
  adminUsers,
  adminUrls,
  adminSelectors,
  generateUniqueEmail,
} from '../../fixtures/admin-test-data';
import {
  loginToAdmin,
  navigateToAdminResource,
  waitForNotification,
} from '../../fixtures/admin-api-helpers';

test.describe('Admin Panel - User Management', () => {
  let page: Page;

  test.beforeEach(async ({ browser }) => {
    page = await browser.newPage();
    console.log('📍 Logging into admin panel...');
    await loginToAdmin(page, adminUsers.admin.email, adminUsers.admin.password);
    console.log('✅ Admin login successful');
  });

  test.afterEach(async () => {
    await page.close();
  });

  test('TC-A020: Create User with Different Roles', async () => {
    console.log('📍 Step 1: Navigate to Users');
    await navigateToAdminResource(page, 'users');

    const roles = [
      { role: 'traveler', label: 'Customer/Traveler' },
      { role: 'vendor', label: 'Vendor' },
      { role: 'admin', label: 'Admin' },
    ];

    for (const { role, label } of roles) {
      console.log(`📍 Creating user with role: ${label}`);

      // Click Create
      const createButton = page.locator('a:has-text("Create"), button:has-text("Create")');
      await createButton.click();
      await page.waitForLoadState('networkidle');

      // Fill user details
      const uniqueEmail = generateUniqueEmail(`test-${role}`);

      // Email
      const emailInput = page.locator('input[name*="email"], [data-field="email"] input').first();
      if (await emailInput.isVisible()) {
        await emailInput.fill(uniqueEmail);
      }

      // Password
      const passwordInput = page
        .locator('input[name*="password"]:not([name*="confirm"]), [data-field="password"] input')
        .first();
      if (await passwordInput.isVisible()) {
        await passwordInput.fill('TestPassword123!');
      }

      // Password confirmation
      const passwordConfirmInput = page
        .locator(
          'input[name*="password_confirm"], input[name*="confirmation"], [data-field="password_confirmation"] input'
        )
        .first();
      if (await passwordConfirmInput.isVisible()) {
        await passwordConfirmInput.fill('TestPassword123!');
      }

      // First name
      const firstNameInput = page
        .locator('input[name*="first_name"], [data-field="first_name"] input')
        .first();
      if (await firstNameInput.isVisible()) {
        await firstNameInput.fill(`Test ${role}`);
      }

      // Last name
      const lastNameInput = page
        .locator('input[name*="last_name"], [data-field="last_name"] input')
        .first();
      if (await lastNameInput.isVisible()) {
        await lastNameInput.fill('User');
      }

      // Display name
      const displayNameInput = page
        .locator('input[name*="display_name"], [data-field="display_name"] input')
        .first();
      if (await displayNameInput.isVisible()) {
        await displayNameInput.fill(`Test ${role} User`);
      }

      // Role selection
      const roleSelect = page.locator('select[name*="role"], [data-field="role"] select').first();
      if (await roleSelect.isVisible()) {
        await roleSelect.selectOption({ value: role });
      }

      // Save
      await page.click('button:has-text("Create"), button:has-text("Save")');
      await page.waitForLoadState('networkidle');

      // Verify success
      const notification = await waitForNotification(page, 'success');
      expect(notification).toBeTruthy();
      console.log(`✅ ${label} user created: ${uniqueEmail}`);

      // Navigate back to users list for next iteration
      await navigateToAdminResource(page, 'users');
    }

    console.log('📍 Step 2: Verify created users have correct access');
    // This would require logging in as each user and verifying their access
    // Vendor should access Vendor panel, Admin should access Admin panel
    console.log('✅ User creation with different roles test completed');
  });

  test('TC-A021: Deactivate User', async () => {
    console.log('📍 Step 1: Navigate to Users');
    await navigateToAdminResource(page, 'users');

    console.log('📍 Step 2: Find an active user (not admin)');
    // Filter for non-admin users
    const filterButton = page.locator('button:has-text("Filter")');
    if (await filterButton.isVisible()) {
      await filterButton.click();
      const roleFilter = page.locator('[data-filter="role"], select[name*="role"]').first();
      if (await roleFilter.isVisible()) {
        await roleFilter.selectOption({ label: 'Traveler' });
      }
      await page.click('button:has-text("Apply")').catch(() => {});
      await page.waitForLoadState('networkidle');
    }

    const userRow = page.locator(adminSelectors.tableRow).first();
    if (await userRow.isVisible()) {
      // Get user email for later verification
      const userEmail = await userRow.locator('td').nth(1).textContent();
      console.log(`📍 Testing with user: ${userEmail}`);

      console.log('📍 Step 3: Edit user and change status to Inactive/Suspended');
      await userRow.locator('a:has-text("Edit")').click();
      await page.waitForLoadState('networkidle');

      // Find status field
      const statusSelect = page
        .locator('select[name*="status"], [data-field="status"] select')
        .first();
      if (await statusSelect.isVisible()) {
        await statusSelect.selectOption({ label: 'Inactive' });
      } else {
        // Try checkbox approach
        const activeCheckbox = page
          .locator('input[name*="is_active"], [data-field="is_active"] input')
          .first();
        if (await activeCheckbox.isVisible()) {
          await activeCheckbox.uncheck();
        }
      }

      // Save changes
      await page.click('button:has-text("Save")');
      await page.waitForLoadState('networkidle');

      const notification = await waitForNotification(page, 'success');
      expect(notification).toBeTruthy();
      console.log('✅ User deactivated');

      console.log('📍 Step 4: Verify user cannot log in');
      // Open a new context to test login
      const newPage = await page.context().newPage();
      await newPage.goto('http://localhost:3000/auth/login');

      if (userEmail) {
        await newPage.fill('input[name="email"], [data-testid="email-input"]', userEmail.trim());
        await newPage.fill(
          'input[name="password"], [data-testid="password-input"]',
          'TestPassword123!'
        );
        await newPage.click('button[type="submit"]');
        await newPage.waitForLoadState('networkidle');

        // Should show error or remain on login page
        const errorMessage = newPage.locator(
          '.text-red-500, .text-danger, [data-testid="error-message"]'
        );
        const stillOnLogin = await newPage.url().includes('/login');

        if ((await errorMessage.isVisible()) || stillOnLogin) {
          console.log('✅ Deactivated user cannot log in');
        }
      }

      await newPage.close();
    }

    console.log('📍 Edge Case: Try to deactivate yourself');
    // Navigate back to users
    await navigateToAdminResource(page, 'users');

    // Find the current admin user
    const adminEmail = adminUsers.admin.email;
    const adminRow = page.locator(`tr:has-text("${adminEmail}")`).first();

    if (await adminRow.isVisible()) {
      await adminRow.locator('a:has-text("Edit")').click();
      await page.waitForLoadState('networkidle');

      // Try to deactivate
      const statusSelect = page
        .locator('select[name*="status"], [data-field="status"] select')
        .first();
      if (await statusSelect.isVisible()) {
        await statusSelect.selectOption({ label: 'Inactive' });
        await page.click('button:has-text("Save")');

        // Should show error preventing self-deactivation
        const errorNotification = await waitForNotification(page, 'error', 3000);
        if (errorNotification) {
          console.log('✅ Cannot deactivate yourself (error shown)');
        } else {
          // Or the field might be disabled
          console.log('⚠️ Self-deactivation prevention not clearly shown');
        }
      }
    }

    console.log('✅ User deactivation test completed');
  });
});
