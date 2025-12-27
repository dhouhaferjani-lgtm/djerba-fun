# PPP Pricing E2E Tests - Documentation

## Overview

This directory contains comprehensive end-to-end tests for the Purchasing Power Parity (PPP) pricing system. These tests follow Test-Driven Development (TDD) principles and are currently in the **RED phase** - they will fail until frontend components are implemented.

## Test Files

| Test File                           | Purpose                                         | Test Count |
| ----------------------------------- | ----------------------------------------------- | ---------- |
| `tunisia-user-flow.spec.ts`         | Tunisia user sees TND throughout entire journey | 5 tests    |
| `vpn-user-flow.spec.ts`             | Tunisia IP + France billing triggers disclosure | 6 tests    |
| `expat-flow.spec.ts`                | France IP + Tunisia billing triggers disclosure | 6 tests    |
| `price-lock.spec.ts`                | Prices locked during hold period                | 7 tests    |
| `multi-booking-consistency.spec.ts` | Multiple bookings maintain currency consistency | 6 tests    |

**Total Tests:** 30 comprehensive E2E tests

## Running Tests

```bash
# Run all PPP pricing tests
cd apps/web
pnpm playwright test tests/e2e/ppp-pricing

# Run specific test file
pnpm playwright test tests/e2e/ppp-pricing/tunisia-user-flow.spec.ts

# Run in headed mode (see browser)
pnpm playwright test tests/e2e/ppp-pricing --headed

# Run with UI mode (interactive)
pnpm playwright test tests/e2e/ppp-pricing --ui

# Debug a specific test
pnpm playwright test tests/e2e/ppp-pricing/vpn-user-flow.spec.ts --debug
```

## Required data-testid Attributes

### Listing Page

| Element                    | data-testid                | Purpose                                  |
| -------------------------- | -------------------------- | ---------------------------------------- |
| Price display              | `listing-price`            | Shows listing price in detected currency |
| Location hint              | `price-location-hint`      | Shows detected country and currency      |
| Currency info tooltip icon | `currency-info-tooltip`    | Opens explanation of PPP pricing         |
| Currency tooltip content   | `currency-tooltip-content` | Tooltip content explaining PPP           |
| Book Now button            | `book-now-button`          | Starts booking flow                      |

### Booking Flow - Date & Time Selection

| Element       | data-testid             | Purpose                                          |
| ------------- | ----------------------- | ------------------------------------------------ |
| Date selector | `booking-date-selector` | Opens date picker                                |
| Specific date | `date-{YYYY-MM-DD}`     | Individual date button (e.g., `date-2025-12-31`) |
| Time slot     | `time-slot-{HH:MM}`     | Time slot button (e.g., `time-slot-09:00`)       |
| Total price   | `total-price`           | Running total with currency                      |

### Booking Flow - Person Selection

| Element          | data-testid                    | Purpose                  |
| ---------------- | ------------------------------ | ------------------------ |
| Adult increment  | `person-type-adult-increment`  | Add one adult            |
| Adult decrement  | `person-type-adult-decrement`  | Remove one adult         |
| Child increment  | `person-type-child-increment`  | Add one child            |
| Child decrement  | `person-type-child-decrement`  | Remove one child         |
| Infant increment | `person-type-infant-increment` | Add one infant           |
| Infant decrement | `person-type-infant-decrement` | Remove one infant        |
| Continue button  | `continue-to-traveler-info`    | Proceed to traveler info |

### Booking Flow - Traveler Information

| Element          | data-testid              | Purpose                    |
| ---------------- | ------------------------ | -------------------------- |
| Email input      | `traveler-email`         | Traveler email address     |
| First name input | `traveler-first-name`    | Traveler first name        |
| Last name input  | `traveler-last-name`     | Traveler last name         |
| Phone input      | `traveler-phone`         | Traveler phone number      |
| Continue button  | `continue-to-billing`    | Proceed to billing address |
| Back button      | `back-to-date-selection` | Return to previous step    |

### Booking Flow - Billing Address

| Element           | data-testid             | Purpose                           |
| ----------------- | ----------------------- | --------------------------------- |
| Country selector  | `billing-country`       | Select billing country            |
| City input        | `billing-city`          | Billing city                      |
| Postal code input | `billing-postal-code`   | Billing postal/zip code           |
| Address line 1    | `billing-address-line1` | Primary address line              |
| Address line 2    | `billing-address-line2` | Secondary address line (optional) |
| Continue button   | `continue-to-review`    | Proceed to review                 |
| Back button       | `back-to-traveler-info` | Return to previous step           |

### Disclosure Modal

| Element           | data-testid                    | Purpose                      |
| ----------------- | ------------------------------ | ---------------------------- |
| Modal container   | `price-change-disclosure`      | Main disclosure modal        |
| Modal title       | `disclosure-modal-title`       | Modal heading                |
| Modal explanation | `disclosure-modal-explanation` | Explanation text             |
| Old price display | `disclosure-old-price`         | Previous price (based on IP) |
| New price display | `disclosure-new-price`         | New price (based on billing) |
| Accept button     | `disclosure-accept-button`     | Accept price change          |
| Cancel button     | `disclosure-cancel-button`     | Cancel and return            |

### Booking Review

| Element            | data-testid          | Purpose                    |
| ------------------ | -------------------- | -------------------------- |
| Review total price | `review-total-price` | Total price on review page |
| Create hold button | `create-hold-button` | Create booking hold        |
| Back button        | `back-to-billing`    | Return to billing          |

### Hold Page

| Element            | data-testid          | Purpose                         |
| ------------------ | -------------------- | ------------------------------- |
| Hold timer         | `hold-timer`         | Countdown timer (MM:SS format)  |
| Hold timer warning | `hold-timer-warning` | Warning when timer low (<5 min) |
| Hold ID            | `hold-id`            | Unique hold identifier          |
| Hold total price   | `hold-total-price`   | Locked total price              |
| Hold currency      | `hold-currency`      | Currency indicator              |
| Hold expires at    | `hold-expires-at`    | Absolute expiration timestamp   |
| Proceed to payment | `proceed-to-payment` | Continue to checkout            |
| Back to hold       | `back-to-hold`       | Return to hold page             |

### Checkout/Payment

| Element                 | data-testid               | Purpose                      |
| ----------------------- | ------------------------- | ---------------------------- |
| Checkout total          | `checkout-total`          | Total price on checkout page |
| Payment method (mock)   | `payment-method-mock`     | Mock payment option          |
| Complete payment button | `complete-payment-button` | Submit payment               |

### Confirmation

| Element                | data-testid            | Purpose                     |
| ---------------------- | ---------------------- | --------------------------- |
| Confirmation container | `booking-confirmation` | Confirmation page container |
| Confirmation total     | `confirmation-total`   | Final paid amount           |

### Multiple Bookings

| Element                | data-testid              | Purpose                     |
| ---------------------- | ------------------------ | --------------------------- |
| Active holds indicator | `active-holds-indicator` | Shows count of active holds |

## Test Data

All test data is defined in `/tests/fixtures/ppp-test-data.ts`:

### IP Addresses

- Tunisia: `41.226.25.1`
- France: `88.127.225.1`
- USA: `8.8.8.8`
- Germany: `81.169.145.1`

### Test Countries

- **Tunisia (TN)**: Lower-income country, uses TND currency
- **France (FR)**: High-income country, uses EUR currency
- **USA (US)**: High-income country, uses USD currency
- **Germany (DE)**: High-income country, uses EUR currency

### Sample Travelers

Each country has pre-configured traveler data with appropriate names, emails, and phone formats.

## Test Scenarios

### 1. Tunisia User Flow (tunisia-user-flow.spec.ts)

**Scenario:** User from Tunisia with Tunisia billing address

- ✅ Shows TND on listing page
- ✅ Maintains TND through booking flow
- ✅ No disclosure modal (IP and billing match)
- ✅ Price locked during hold
- ✅ Payment completed in TND

**Expected Behavior:** Smooth flow, no currency changes, no disclosures.

### 2. VPN User Flow (vpn-user-flow.spec.ts)

**Scenario:** Tunisia user using France VPN

- ✅ Initially shows EUR (based on France IP)
- ✅ User provides Tunisia billing address
- ✅ Disclosure modal appears
- ✅ User accepts → prices update to TND
- ✅ TND locked for remainder
- ✅ Can cancel disclosure and return

**Expected Behavior:** System detects mismatch and requires user confirmation.

### 3. Expat Flow (expat-flow.spec.ts)

**Scenario:** French expat living in Tunisia

- ✅ Initially shows EUR (based on France IP)
- ✅ User provides Tunisia billing address
- ✅ Disclosure modal appears
- ✅ User accepts → prices update to TND
- ✅ Can complete booking in TND
- ✅ Appropriate messaging for expats

**Expected Behavior:** Identical to VPN flow (mismatch detection works both ways).

### 4. Price Lock (price-lock.spec.ts)

**Scenario:** Verify price stability during holds

- ✅ Price locked when hold created
- ✅ Price maintained after page refresh
- ✅ Timer counts down correctly
- ✅ Warning shown when timer low
- ✅ Price locked across navigations
- ✅ Currency locked with price
- ✅ Expiration time displayed

**Expected Behavior:** Once hold created, price cannot change.

### 5. Multi-Booking Consistency (multi-booking-consistency.spec.ts)

**Scenario:** Multiple bookings in same session

- ✅ Currency maintained across bookings
- ✅ Independent holds for each booking
- ✅ Different quantities priced correctly
- ✅ Session persists across refreshes
- ✅ Active holds summary shown
- ✅ Can make new booking after completion

**Expected Behavior:** Consistent currency, independent pricing.

## Mock API Responses

Tests mock IP geolocation using Playwright's `setExtraHTTPHeaders`:

```typescript
const context = await browser.newContext({
  extraHTTPHeaders: {
    'X-Forwarded-For': '41.226.25.1', // Tunisia IP
    'X-Real-IP': '41.226.25.1',
  },
});
```

Backend should detect these headers and use them for geolocation.

## Current Status: RED Phase (TDD)

These tests are **expected to fail** because:

1. Frontend components don't exist yet
2. `data-testid` attributes not added yet
3. Billing address step not implemented
4. Disclosure modal not created
5. PPP pricing logic not integrated

## Next Steps: GREEN Phase

To make these tests pass, implement:

1. **Geolocation detection** in API
2. **Currency detection** based on IP and billing
3. **Billing address form** in booking flow
4. **Disclosure modal** component
5. **Price locking** in holds
6. **All data-testid attributes** from the table above

## PPP Spec References

Tests validate requirements from:

- Section 3.1: Currency Detection
- Section 3.2: Price Display
- Section 4.1: No disclosure when IP and billing match
- Section 4.2: Mismatch Detection
- Section 4.3: Disclosure Modal Requirements
- Section 4.4: Price Update After Acceptance
- Section 5.1: Currency Lock After Hold Creation
- Section 5.2: Hold Duration and Timer
- Section 6.1: Session Currency Preference
- Section 6.2: Multiple Bookings Per Session

## Debugging

When tests fail, check:

1. **Screenshots**: `apps/web/test-results/**/*.png`
2. **Videos**: `apps/web/test-results/**/*.webm`
3. **Traces**: Open in Playwright trace viewer
4. **Console logs**: Tests include detailed logging
5. **Network requests**: Check API calls in trace

## Contributing

When adding new tests:

1. Follow existing test structure
2. Add descriptive console.log statements
3. Use meaningful assertions
4. Update this README with new data-testid requirements
5. Include comments explaining what each test validates
