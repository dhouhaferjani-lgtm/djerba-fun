# E2E Tests - Go Adventure

This directory contains end-to-end tests for the Go Adventure platform using Playwright.

## Test Coverage

### Booking Flow Tests

1. **Guest Checkout** - Verifies guest checkout works without authentication and no SQL errors occur
2. **Complete Booking with Price Verification** - CRITICAL test that verifies the confirmation page shows correct total (not €0.00)
3. **Price Updates** - Verifies price updates correctly when changing participant counts
4. **Capacity Indicator** - Verifies capacity display works correctly

### 404 Error Page Tests

1. **English Locale** - Verifies 404 page displays correctly in English
2. **French Locale** - Verifies 404 page displays correctly in French
3. **Arabic Locale** - Verifies 404 page displays correctly in Arabic
4. **Primary Color Gradient** - Verifies the page uses primary color (not accent color)

## Prerequisites

Make sure the following are running:

- **Frontend**: `pnpm dev` in `/apps/web` (port 3000)
- **Backend**: Laravel API running (port 8000)
- **Database**: PostgreSQL with seeded data

## Running Tests

### Install Playwright (if not already installed)

```bash
cd apps/web
pnpm add -D @playwright/test
pnpm exec playwright install
```

### Run All Tests

```bash
# From project root
cd apps/web
pnpm exec playwright test

# Or with UI
pnpm exec playwright test --ui
```

### Run Specific Test Suites

```bash
# Only booking flow tests
pnpm exec playwright test tests/e2e/booking-flow.spec.ts --grep "Booking Flow"

# Only 404 page tests
pnpm exec playwright test tests/e2e/booking-flow.spec.ts --grep "404 Error Page"

# Run specific test
pnpm exec playwright test -g "CRITICAL: Complete booking shows correct total"
```

### Run Tests in Headed Mode (with browser visible)

```bash
pnpm exec playwright test --headed
```

### Debug Tests

```bash
pnpm exec playwright test --debug
```

### View Test Report

```bash
pnpm exec playwright show-report
```

## Test Configuration

Configuration is in `playwright.config.ts`:

- Base URL: `http://localhost:3000`
- Browsers: Chromium, Firefox, WebKit
- Mobile: Pixel 5, iPhone 12
- Screenshots: On failure
- Video: On failure
- Trace: On first retry

## Critical Tests

The most important test is:

**"CRITICAL: Complete booking shows correct total on confirmation page"**

This test verifies the bug fix where the confirmation page was showing €0.00 instead of the actual booking total. It:

1. Creates a booking with 4 adults
2. Goes through the complete checkout flow
3. Verifies the confirmation page shows the correct total amount
4. Ensures the total matches what was shown during booking

## Troubleshooting

### Tests Timeout

- Ensure dev server is running on port 3000
- Increase timeout in test if needed: `{ timeout: 30000 }`

### Selectors Not Found

- Check that the listing exists: `/en/listings/kroumirie-mountains-summit-trek`
- Verify data-testid attributes are present in components
- Run `pnpm dev` to ensure latest code is running

### Price Extraction Issues

- The `extractPrice()` helper extracts numeric values from formatted strings
- Supports formats: "€76.00", "TND 152.00", etc.
- If tests fail, check console output for actual price values

## Files

```
tests/e2e/
├── README.md                 # This file
└── booking-flow.spec.ts      # All E2E tests
```

## Adding New Tests

1. Add test to `booking-flow.spec.ts`
2. Use data-testid attributes for reliable selectors
3. Add console.log statements for debugging
4. Verify test passes in all browsers
5. Update this README if adding new test categories
