# PPP Pricing E2E Tests - Setup Guide

## Prerequisites

- Node.js 18+ installed
- pnpm package manager
- Go Adventure project cloned
- Backend API running on `http://localhost:8000`
- Frontend running on `http://localhost:3000`

## Installation

### Step 1: Install Playwright

```bash
cd apps/web
pnpm add -D @playwright/test
```

### Step 2: Install Playwright Browsers

```bash
pnpm exec playwright install
```

This will download Chromium, Firefox, and WebKit browsers for testing.

### Step 3: Verify Installation

```bash
pnpm playwright --version
```

Expected output: `Version 1.x.x`

## First Test Run (Expected to Fail - RED Phase)

```bash
# Run all PPP pricing tests
pnpm playwright test tests/e2e/ppp-pricing

# Expected result: All tests fail (this is expected in TDD RED phase)
```

### Expected Output

```
Running 30 tests using 1 worker

  ❌ tunisia-user-flow.spec.ts:47:3 › should show TND prices on listing page
    TimeoutError: locator.isVisible: Timeout 30000ms exceeded.
    Element not found: [data-testid="listing-price"]

  ❌ tunisia-user-flow.spec.ts:79:3 › should maintain TND prices through booking flow
    TimeoutError: locator.click: Timeout 30000ms exceeded.
    Element not found: [data-testid="book-now-button"]

  ... (28 more failures)

  30 failed
  0 passed (15m 32s)
```

**This is EXPECTED!** Tests are written first (TDD RED phase), then we implement features to make them pass (GREEN phase).

## Understanding Test Failures

Each failure shows:

1. Which test failed
2. Which element is missing (data-testid)
3. Timeout duration

Example:

```
❌ should show TND prices on listing page
   Element not found: [data-testid="listing-price"]
```

This tells you: Add `data-testid="listing-price"` to the price display component.

## Debugging Tests

### Run Tests in Headed Mode (See Browser)

```bash
pnpm playwright test tests/e2e/ppp-pricing/tunisia-user-flow.spec.ts --headed
```

### Run Tests in Debug Mode (Step Through)

```bash
pnpm playwright test tests/e2e/ppp-pricing/tunisia-user-flow.spec.ts --debug
```

### Run Tests in UI Mode (Interactive)

```bash
pnpm playwright test tests/e2e/ppp-pricing --ui
```

UI mode allows you to:

- See which tests are running
- Watch tests execute live
- Time-travel through test steps
- Inspect elements
- View console logs

### View Test Report

After test run:

```bash
pnpm playwright show-report
```

This opens an HTML report showing:

- Test results
- Screenshots
- Videos
- Traces

### Inspect Specific Failure

```bash
# View trace for specific test
pnpm playwright show-trace test-results/tunisia-user-flow-should-show-TND-prices/trace.zip
```

## Making Tests Pass

### Implementation Checklist

1. **Backend - Geolocation Service**

   ```bash
   # Implement IP-based geolocation
   # Support X-Forwarded-For header
   # Return detected country and currency
   ```

2. **Frontend - Add data-testid Attributes**

   ```tsx
   // Example: ListingCard.tsx
   <div data-testid="listing-price">{formatPrice(price, currency)}</div>
   ```

3. **Frontend - Billing Address Form**

   ```tsx
   // Create new component: BillingAddressForm.tsx
   <select data-testid="billing-country">{/* country options */}</select>
   ```

4. **Frontend - Disclosure Modal**

   ```tsx
   // Create new component: PriceChangeDisclosure.tsx
   <Modal data-testid="price-change-disclosure">{/* modal content */}</Modal>
   ```

5. **Run Tests Again**

   ```bash
   pnpm playwright test tests/e2e/ppp-pricing
   ```

6. **Fix Failures & Repeat**

### Tracking Progress

Use the checklist in `DATA_TESTIDS_CHECKLIST.md`:

```bash
# Open checklist
code tests/e2e/ppp-pricing/DATA_TESTIDS_CHECKLIST.md

# Check off each data-testid as you add it
- [x] listing-price
- [x] book-now-button
- [ ] booking-date-selector (not yet added)
```

## Test Development Workflow

### Adding a New Test

1. **Create test file**

   ```typescript
   // tests/e2e/ppp-pricing/new-feature.spec.ts
   import { test, expect } from '@playwright/test';

   test.describe('New Feature', () => {
     test('should do something', async ({ page }) => {
       // Test code
     });
   });
   ```

2. **Run the new test**

   ```bash
   pnpm playwright test tests/e2e/ppp-pricing/new-feature.spec.ts
   ```

3. **Watch it fail (RED)**

4. **Implement feature**

5. **Run test again**

6. **Watch it pass (GREEN)**

7. **Refactor if needed (REFACTOR)**

### Updating Existing Test

1. **Edit test file**
2. **Run specific test**
   ```bash
   pnpm playwright test tests/e2e/ppp-pricing/tunisia-user-flow.spec.ts --grep "should show TND"
   ```
3. **Verify changes**

## Continuous Integration

### Run Tests in CI

```bash
# package.json script
"test:e2e:ppp": "playwright test tests/e2e/ppp-pricing"

# In CI pipeline
pnpm test:e2e:ppp
```

### CI Configuration

```yaml
# .github/workflows/e2e-tests.yml
name: E2E Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: pnpm/action-setup@v2
      - uses: actions/setup-node@v3
        with:
          node-version: 18
          cache: 'pnpm'

      - name: Install dependencies
        run: pnpm install

      - name: Install Playwright browsers
        run: pnpm exec playwright install --with-deps

      - name: Run E2E tests
        run: pnpm test:e2e:ppp

      - name: Upload test results
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: playwright-report
          path: playwright-report/
```

## Troubleshooting

### Tests Timeout

**Problem:** Tests take too long and timeout

**Solution:**

- Increase timeout in `playwright.config.ts`
- Check if backend API is running
- Check if frontend is running
- Check network connectivity

### Element Not Found

**Problem:** `Element not found: [data-testid="..."]`

**Solution:**

- Add the data-testid to the component
- Check spelling (kebab-case)
- Ensure element is visible when test runs
- Use `--headed` mode to see what's on screen

### Tests Flaky

**Problem:** Tests sometimes pass, sometimes fail

**Solution:**

- Add explicit waits: `await page.waitForSelector(...)`
- Wait for network idle: `await page.waitForLoadState('networkidle')`
- Use `toBeVisible()` before interacting
- Increase timeouts for slow operations

### Browser Not Found

**Problem:** `Executable doesn't exist at /path/to/browser`

**Solution:**

```bash
pnpm exec playwright install
```

### Port Already in Use

**Problem:** Frontend not running on localhost:3000

**Solution:**

- Stop other processes using port 3000
- Update `baseURL` in `playwright.config.ts`
- Use different port for tests

## Best Practices

### 1. Use data-testid for Test Selectors

✅ Good:

```typescript
await page.getByTestId('booking-date-selector').click();
```

❌ Bad:

```typescript
await page.locator('.calendar-icon').click(); // CSS class might change
```

### 2. Wait for Elements

✅ Good:

```typescript
await expect(page.getByTestId('listing-price')).toBeVisible();
const price = await page.getByTestId('listing-price').textContent();
```

❌ Bad:

```typescript
const price = await page.getByTestId('listing-price').textContent(); // Might not be loaded yet
```

### 3. Add Descriptive Console Logs

✅ Good:

```typescript
console.log('📍 Step 1: Navigate to listing page');
await page.goto('/listings/desert-tour');
console.log('✅ Step 1 complete');
```

### 4. Use Test Data Fixtures

✅ Good:

```typescript
import { testData } from '../../fixtures/ppp-test-data';
await page.fill('[data-testid="traveler-email"]', testData.travelers.tunisia.email);
```

❌ Bad:

```typescript
await page.fill('[data-testid="traveler-email"]', 'test@example.com'); // Hardcoded
```

### 5. Keep Tests Independent

✅ Good: Each test creates its own data
❌ Bad: Tests depend on previous test state

### 6. Clean Up After Tests

```typescript
test.afterEach(async () => {
  await page.close();
  // Clean up any created data
});
```

## Resources

- [Playwright Documentation](https://playwright.dev/)
- [Playwright Best Practices](https://playwright.dev/docs/best-practices)
- [Test Data Fixtures](./ppp-test-data.ts)
- [Required data-testid Checklist](./DATA_TESTIDS_CHECKLIST.md)
- [Test Summary](./TEST_SUMMARY.md)

## Support

If you encounter issues:

1. Check test logs with console.log output
2. Run in `--headed` mode to see browser
3. Use `--debug` mode to step through
4. View screenshots in test-results/
5. View videos in test-results/
6. Open HTML report with `pnpm playwright show-report`

## Next Steps

1. ✅ Install Playwright (you are here)
2. ⏳ Run tests to see failures
3. ⏳ Implement geolocation backend
4. ⏳ Add data-testid attributes
5. ⏳ Create billing address form
6. ⏳ Create disclosure modal
7. ⏳ Run tests again
8. ⏳ Fix failures until all pass
9. ⏳ Celebrate 30 passing tests! 🎉

Good luck implementing the PPP pricing system!
