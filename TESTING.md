# Go Adventure Marketplace - Testing Guide

> **Testing Coverage**: Target 70%+
> **Framework**: PHPUnit (Backend), Playwright (Frontend)
> **Last Updated**: 2025-12-29

---

## Table of Contents

- [Backend Testing](#backend-testing)
- [Frontend Testing](#frontend-testing)
- [Running Tests](#running-tests)
- [Coverage Reports](#coverage-reports)
- [CI/CD Integration](#cicd-integration)
- [Test Organization](#test-organization)

---

## Backend Testing

### Test Infrastructure

The backend uses **PHPUnit** with an in-memory SQLite database for fast test execution.

#### Configuration

- **Database**: SQLite (in-memory) for tests
- **Config**: `apps/laravel-api/phpunit.xml`
- **Test Namespace**: `Tests\`

#### Environment Variables

```xml
<env name="APP_ENV" value="testing"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="MAIL_MAILER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="SESSION_DRIVER" value="array"/>
```

### Test Structure

```
tests/
├── Unit/                    # Unit tests
│   ├── Models/             # Model tests (relationships, scopes, accessors)
│   ├── Services/           # Service tests (business logic)
│   └── Middleware/         # Middleware tests
├── Feature/                 # Feature/Integration tests
│   └── Api/                # API endpoint tests
├── Helpers/                # Test helper classes
└── TestCase.php           # Base test case
```

### Available Test Suites

#### Unit Tests

**Services**

- `BookingServiceTest` - Booking creation, cancellation, confirmation
- `PriceCalculationServiceTest` - Price calculation with PPP pricing
- `CouponServiceTest` - Coupon validation and discount calculation
- `ExtrasServiceTest` - Extras availability and pricing
- `PaymentGatewayManagerTest` - Gateway registration and selection
- `MagicAuthServiceTest` - Magic link authentication
- `GeoPricingServiceTest` - Geographic pricing logic
- `IncomePricingServiceTest` - Income-based pricing

**Models**

- `BookingTest` - Booking model relationships and scopes
- `ListingTest` - Listing model relationships and queries
- `UserTest` - User model authentication and profiles

**Middleware**

- `PartnerAuthMiddlewareTest` - Partner API authentication

#### Feature Tests

**Authentication**

- `AuthTest` - Login, register, logout, token management
- `MagicLinkTest` - Magic link authentication flow

**Booking Flow**

- `BookingFlowTest` - Complete booking lifecycle
- `AvailabilityTest` - Availability slots and holds
- `PaymentTest` - Payment processing with multiple methods

**API Endpoints**

- `ListingTest` - Listing CRUD and search
- `ProfileTest` - User profile management
- `CartTest` - Shopping cart operations

**PPP Pricing**

- `PppPricingIntegrationTest` - Purchase power parity pricing

### Factories

All major models have factories for test data generation:

```php
// Example usage
$user = User::factory()->create();
$booking = Booking::factory()->confirmed()->create();
$listing = Listing::factory()->create();
$payment = PaymentIntent::factory()->succeeded()->create();
```

Available factories:

- `UserFactory`
- `BookingFactory`
- `ListingFactory`
- `AvailabilitySlotFactory`
- `BookingHoldFactory`
- `PaymentIntentFactory`
- `PayoutFactory`
- `CouponFactory`
- `ReviewFactory`
- `PartnerFactory`
- `VendorProfileFactory`
- `TravelerProfileFactory`
- `CartFactory`
- `CartItemFactory`
- `LocationFactory`
- `IncomePricingConfigFactory`

### Running Backend Tests

```bash
# All tests
cd apps/laravel-api
php artisan test

# Specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Specific test file
php artisan test tests/Unit/Services/BookingServiceTest.php

# With coverage
php artisan test --coverage

# Parallel execution
php artisan test --parallel

# Filter by test name
php artisan test --filter=test_booking_stores_currency_from_hold
```

### Writing New Tests

#### Unit Test Example

```php
<?php

namespace Tests\Unit\Services;

use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BookingService::class);
    }

    public function test_creates_booking_from_hold(): void
    {
        // Arrange
        $hold = BookingHold::factory()->create();

        // Act
        $booking = $this->service->createFromHold($hold, [
            ['email' => 'test@example.com']
        ]);

        // Assert
        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals($hold->listing_id, $booking->listing_id);
    }
}
```

#### Feature Test Example

```php
<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_booking(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $response = $this->actingAs($user)
            ->postJson('/api/v1/bookings', [
                'hold_id' => $hold->id,
                'traveler_info' => [
                    'email' => 'test@example.com',
                ],
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure(['booking']);
    }
}
```

---

## Frontend Testing

### Test Infrastructure

The frontend uses **Playwright** for end-to-end testing.

#### Configuration

- **Config**: `apps/web/playwright.config.ts`
- **Test Directory**: `apps/web/tests/e2e/`
- **Browsers**: Chromium, Firefox, WebKit, Mobile Chrome, Mobile Safari

#### Playwright Configuration

```typescript
export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: 'html',
  use: {
    baseURL: 'http://localhost:3000',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
});
```

### Test Structure

```
tests/
├── e2e/                          # End-to-end tests
│   ├── auth-login.spec.ts       # Login flow
│   ├── auth-register.spec.ts    # Registration flow
│   ├── booking-flow.spec.ts     # Complete booking flow
│   ├── booking-complete-flow.spec.ts  # Detailed booking scenarios
│   ├── payment-complete-flow.spec.ts  # Payment processing
│   ├── profile-management.spec.ts     # User profile
│   ├── search-and-filter.spec.ts      # Search functionality
│   ├── dashboard-bookings.spec.ts     # Booking management
│   ├── inventory-tracking.spec.ts     # Inventory management
│   └── ppp-pricing/              # PPP pricing tests
│       ├── tunisia-user-flow.spec.ts
│       ├── expat-flow.spec.ts
│       └── price-lock.spec.ts
└── fixtures/                     # Test fixtures
    ├── test-data.ts             # Test data
    ├── api-helpers.ts           # API helpers
    └── ppp-test-data.ts         # PPP test data
```

### Available E2E Tests

#### Authentication

- **Login Flow** - User authentication with email/password
- **Registration Flow** - New user account creation
- **Magic Link** - Passwordless authentication
- **Logout** - Session termination

#### Booking Flow

- **Complete Booking** - End-to-end booking creation
- **Guest Checkout** - Booking without authentication
- **Hold Management** - Temporary slot reservations
- **Extras Selection** - Add-ons and upgrades
- **Coupon Application** - Discount code validation

#### Payment

- **Mock Payment** - Test payment processing
- **Offline Payment** - Bank transfer/cash payments
- **Payment with Discount** - Coupon-based discounts
- **Payment Failure** - Error handling

#### Profile Management

- **View Profile** - Display user information
- **Update Profile** - Edit personal details
- **Change Password** - Password updates
- **Traveler Preferences** - Currency, language, dietary restrictions
- **Emergency Contact** - Safety information

#### Search & Discovery

- **Listing Search** - Find activities
- **Filter by Date** - Date-based availability
- **Filter by Location** - Geographic search
- **Filter by Price** - Price range filtering
- **Sort Results** - Sort by various criteria

#### Dashboard

- **View Bookings** - Booking history
- **Booking Details** - Individual booking information
- **Cancel Booking** - Booking cancellation
- **Leave Review** - Post-experience reviews

#### PPP Pricing

- **Tunisia User Flow** - Local pricing
- **Expat Flow** - Expatriate pricing
- **VPN Detection** - VPN user handling
- **Price Lock** - Currency consistency
- **Multi-Booking** - Consistent pricing across bookings

### Running Frontend Tests

```bash
# All tests
cd apps/web
pnpm test:e2e

# Specific browser
pnpm test:e2e --project=chromium
pnpm test:e2e --project=firefox

# Headed mode (see browser)
pnpm test:e2e --headed

# Debug mode
pnpm test:e2e --debug

# Specific test file
pnpm test:e2e tests/e2e/booking-flow.spec.ts

# UI mode (interactive)
pnpm test:e2e --ui

# Generate report
pnpm test:e2e && pnpm playwright show-report
```

### Writing New E2E Tests

```typescript
import { test, expect } from '@playwright/test';

test.describe('Feature Name', () => {
  test.beforeEach(async ({ page }) => {
    // Setup
    await page.goto('/en');
  });

  test('should perform action', async ({ page }) => {
    // Arrange
    await page.fill('[data-testid="input"]', 'value');

    // Act
    await page.click('[data-testid="submit-button"]');

    // Assert
    await expect(page.locator('[data-testid="result"]')).toBeVisible();
    await expect(page.locator('[data-testid="result"]')).toContainText('Expected');
  });
});
```

### Test Data Attributes

Use `data-testid` attributes for reliable element selection:

```tsx
<button data-testid="submit-booking-button">Submit Booking</button>
```

---

## Running Tests

### Development Environment

```bash
# Backend tests
cd apps/laravel-api
php artisan test

# Frontend tests (requires dev server running)
cd apps/web
pnpm dev  # In one terminal
pnpm test:e2e  # In another terminal
```

### Docker Environment

```bash
# Run backend tests in Docker
docker compose exec laravel php artisan test

# Run frontend tests in Docker
docker compose exec web pnpm test:e2e
```

### Quick Test Commands

```bash
# Run all tests (backend + frontend)
make test

# Backend only
make test-backend

# Frontend only
make test-frontend

# With coverage
make test-coverage
```

---

## Coverage Reports

### Backend Coverage

```bash
cd apps/laravel-api

# Generate coverage report
php artisan test --coverage

# HTML coverage report
php artisan test --coverage-html coverage/

# Open report
open coverage/index.html
```

### Frontend Coverage

Playwright doesn't provide code coverage by default. For frontend coverage:

```bash
cd apps/web

# Run tests with coverage (if configured)
pnpm test:e2e --coverage

# Or use Vitest for unit tests
pnpm test:unit --coverage
```

### Coverage Goals

| Component               | Target Coverage | Current Status |
| ----------------------- | --------------- | -------------- |
| Backend Services        | 80%+            | 🟢             |
| Backend Models          | 75%+            | 🟢             |
| Backend Controllers     | 70%+            | 🟡             |
| API Endpoints           | 85%+            | 🟢             |
| Frontend Critical Paths | 70%+            | 🟢             |

---

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  backend-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - name: Install Dependencies
        run: cd apps/laravel-api && composer install
      - name: Run Tests
        run: cd apps/laravel-api && php artisan test --coverage

  frontend-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: '20'
      - name: Install Dependencies
        run: pnpm install
      - name: Install Playwright
        run: cd apps/web && pnpm exec playwright install --with-deps
      - name: Run Tests
        run: cd apps/web && pnpm test:e2e
```

---

## Test Organization

### Best Practices

1. **Descriptive Test Names**: Use clear, descriptive names

   ```php
   test_booking_stores_currency_from_hold()
   test_user_can_update_profile_information()
   ```

2. **AAA Pattern**: Arrange, Act, Assert

   ```php
   // Arrange
   $user = User::factory()->create();

   // Act
   $result = $service->performAction($user);

   // Assert
   $this->assertTrue($result);
   ```

3. **One Assertion Per Test**: Test one thing at a time (when practical)

4. **Use Factories**: Generate test data with factories

   ```php
   $booking = Booking::factory()->confirmed()->create();
   ```

5. **Clean Up**: Use `RefreshDatabase` trait

   ```php
   use RefreshDatabase;
   ```

6. **Mock External Services**: Mock APIs, emails, etc.
   ```php
   Mail::fake();
   Http::fake();
   ```

### Test Data Management

- **Factories**: Use for model creation
- **Seeders**: Use for complex data scenarios
- **Fixtures**: Use for frontend E2E tests
- **Helpers**: Create test helper classes for common operations

### Debugging Tests

```bash
# Backend - verbose output
php artisan test --filter=test_name --verbose

# Backend - stop on failure
php artisan test --stop-on-failure

# Frontend - debug mode
pnpm test:e2e --debug

# Frontend - headed mode
pnpm test:e2e --headed

# Frontend - slow motion
pnpm test:e2e --slow-mo=1000
```

---

## Continuous Improvement

### Coverage Monitoring

- Track coverage trends over time
- Set coverage requirements for new code
- Review uncovered critical paths

### Test Maintenance

- Update tests when features change
- Remove obsolete tests
- Refactor flaky tests
- Keep test data current

### Performance

- Optimize slow tests
- Use parallel execution
- Mock expensive operations
- Use in-memory databases

---

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Testing](https://laravel.com/docs/testing)
- [Playwright Documentation](https://playwright.dev/)
- [Test-Driven Development](https://en.wikipedia.org/wiki/Test-driven_development)

---

**Last Updated**: December 29, 2025
**Maintainer**: Development Team
