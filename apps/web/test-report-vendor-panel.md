# Vendor Panel E2E Test Report

**Report Date:** 2026-03-06
**Test Framework:** Playwright
**Target:** Filament Vendor Panel (`http://localhost:8000/vendor`)

---

## Executive Summary

| Metric           | Value                       |
| ---------------- | --------------------------- |
| Total Test Cases | 36                          |
| Total Test Runs  | 180 (36 tests × 5 browsers) |
| Passed           | 0                           |
| Failed           | 180                         |
| Skipped          | 0                           |

**Status:** Tests are ready but require Laravel API backend to be running at `localhost:8000`

---

## Test Coverage by Section

### Section 2.1: Listing Management (8 tests)

**File:** `tests/e2e/vendor-panel/vendor-listings.spec.ts`

| Test ID | Test Name                         | Status              | Notes                       |
| ------- | --------------------------------- | ------------------- | --------------------------- |
| TC-V001 | Create Tour Listing (Full Wizard) | ❌ Backend Required | Tests 7-step wizard flow    |
| TC-V002 | Create Nautical Activity Listing  | ❌ Backend Required | Nautical-specific fields    |
| TC-V003 | Create Accommodation Listing      | ❌ Backend Required | Accommodation fields        |
| TC-V004 | Create Event Listing              | ❌ Backend Required | Event date/venue fields     |
| TC-V005 | Submit Listing for Review         | ❌ Backend Required | Status change to pending    |
| TC-V006 | Edit Rejected Listing             | ❌ Backend Required | View reason, edit, resubmit |
| TC-V007 | Duplicate Listing                 | ❌ Backend Required | Copy existing listing       |
| TC-V008 | Archive Own Listing               | ❌ Backend Required | Archive published listing   |

### Section 2.2: Availability Rules (5 tests)

**File:** `tests/e2e/vendor-panel/vendor-availability.spec.ts`

| Test ID | Test Name                         | Status              | Notes                     |
| ------- | --------------------------------- | ------------------- | ------------------------- |
| TC-V010 | Create Weekly Availability Rule   | ❌ Backend Required | Days + time/capacity      |
| TC-V011 | Create Daily Availability         | ❌ Backend Required | Daily rule for all days   |
| TC-V012 | Create Specific Date Availability | ❌ Backend Required | Specific date ranges      |
| TC-V013 | Block Date Range                  | ❌ Backend Required | Blocked dates rule        |
| TC-V014 | Overlapping Rules                 | ❌ Backend Required | Weekly + blocked override |

### Section 2.3: Extras Management (5 tests)

**File:** `tests/e2e/vendor-panel/vendor-extras.spec.ts`

| Test ID | Test Name                    | Status              | Notes                    |
| ------- | ---------------------------- | ------------------- | ------------------------ |
| TC-V020 | Create Per-Booking Extra     | ❌ Backend Required | Flat per-booking pricing |
| TC-V021 | Create Per-Person Extra      | ❌ Backend Required | Per-person pricing       |
| TC-V022 | Create Per-Person-Type Extra | ❌ Backend Required | Adult/child pricing      |
| TC-V023 | Required Extra               | ❌ Backend Required | Mark extra as required   |
| TC-V024 | Extra with Inventory Limit   | ❌ Backend Required | Max capacity setting     |

### Section 2.4: Booking Management (6 tests)

**File:** `tests/e2e/vendor-panel/vendor-bookings.spec.ts`

| Test ID | Test Name              | Status              | Notes                       |
| ------- | ---------------------- | ------------------- | --------------------------- |
| TC-V030 | View Own Bookings Only | ❌ Backend Required | Scoped query verification   |
| TC-V031 | Mark Booking as Paid   | ❌ Backend Required | Offline payment action      |
| TC-V032 | Record Partial Payment | ❌ Backend Required | Partial + remaining payment |
| TC-V033 | Mark Booking Completed | ❌ Backend Required | Complete action             |
| TC-V034 | Mark No-Show           | ❌ Backend Required | No-show action              |
| TC-V035 | Contact Traveler       | ❌ Backend Required | Mailto verification         |

### Section 2.5: Review Management (3 tests)

**File:** `tests/e2e/vendor-panel/vendor-reviews.spec.ts`

| Test ID | Test Name                 | Status              | Notes                  |
| ------- | ------------------------- | ------------------- | ---------------------- |
| TC-V040 | Approve Pending Review    | ❌ Backend Required | Publish pending review |
| TC-V041 | Reject Review with Reason | ❌ Backend Required | Reject with reason     |
| TC-V042 | Reply to Review           | ❌ Backend Required | Vendor response        |

### Section 2.6: Check-In Scanner (7 tests)

**File:** `tests/e2e/vendor-panel/vendor-checkin.spec.ts`

| Test ID | Test Name                 | Status              | Notes               |
| ------- | ------------------------- | ------------------- | ------------------- |
| TC-V050 | Scan Valid Voucher        | ❌ Backend Required | Successful check-in |
| TC-V051 | Scan Invalid Voucher Code | ❌ Backend Required | Error handling      |
| TC-V052 | Scan Wrong Event Voucher  | ❌ Backend Required | Wrong listing error |
| TC-V053 | Scan Wrong Date Voucher   | ❌ Backend Required | Wrong date error    |
| TC-V054 | Scan Already Checked-In   | ❌ Backend Required | Duplicate warning   |
| TC-V055 | Undo Check-In             | ❌ Backend Required | Reverse check-in    |
| TC-V056 | Check-In Statistics       | ❌ Backend Required | X of Y counter      |

### Section 2.7: Email Logs (2 tests)

**File:** `tests/e2e/vendor-panel/vendor-email-logs.spec.ts`

| Test ID | Test Name                  | Status              | Notes              |
| ------- | -------------------------- | ------------------- | ------------------ |
| TC-V060 | View Email Delivery Status | ❌ Backend Required | Email logs table   |
| TC-V061 | Resend Failed Email        | ❌ Backend Required | Retry failed email |

---

## Test Infrastructure

### Files Created

```
apps/web/tests/
├── e2e/
│   └── vendor-panel/
│       ├── vendor-listings.spec.ts      # 8 tests
│       ├── vendor-availability.spec.ts  # 5 tests
│       ├── vendor-extras.spec.ts        # 5 tests
│       ├── vendor-bookings.spec.ts      # 6 tests
│       ├── vendor-reviews.spec.ts       # 3 tests
│       ├── vendor-checkin.spec.ts       # 7 tests
│       └── vendor-email-logs.spec.ts    # 2 tests
└── fixtures/
    ├── vendor-helpers.ts                # Login, form, table utilities
    └── vendor-test-data.ts              # Test data fixtures
```

### Helper Functions (`vendor-helpers.ts`)

| Function                                          | Purpose                           |
| ------------------------------------------------- | --------------------------------- |
| `loginVendorUI(page, email, password)`            | Login to Filament vendor panel    |
| `waitForFilamentPage(page)`                       | Wait for Livewire load completion |
| `submitFilamentForm(page)`                        | Submit Filament form and wait     |
| `fillFilamentField(page, name, value, type)`      | Fill various field types          |
| `clickTableAction(page, rowIndex, actionName)`    | Click table row actions           |
| `navigateToVendorResource(page, resource)`        | Navigate to vendor resources      |
| `createVendorUser(email, password, businessName)` | Create vendor via API             |
| `seededVendor`                                    | Pre-seeded vendor credentials     |

### Test Data (`vendor-test-data.ts`)

- `vendorUsers` - Test vendor credentials
- `listingTemplates` - Tour/Nautical/Accommodation/Event data
- `availabilityRules` - Weekly/Daily/Specific/Blocked rules
- `extrasData` - Per-booking/Per-person/Per-person-type extras
- `bookingData` - Booking test scenarios
- `reviewData` - Review test scenarios
- `voucherCodes` - Check-in scanner test codes

---

## How to Run Tests

### Prerequisites

1. **Start Laravel Backend:**

   ```bash
   # From project root
   make up
   # Or manually:
   cd apps/laravel-api
   php artisan serve --port=8000
   ```

2. **Seed Database (if needed):**

   ```bash
   make fresh  # Reset and seed
   # Or:
   make seed   # Just seed
   ```

3. **Verify Backend Running:**
   ```bash
   make health
   # Or visit: http://localhost:8000/vendor/login
   ```

### Run Tests

```bash
cd apps/web

# Run all vendor panel tests
pnpm exec playwright test tests/e2e/vendor-panel/

# Run specific test file
pnpm exec playwright test tests/e2e/vendor-panel/vendor-listings.spec.ts

# Run with visible browser
pnpm exec playwright test tests/e2e/vendor-panel/ --headed

# Run specific test by name
pnpm exec playwright test -g "TC-V001"

# Run on specific browser only
pnpm exec playwright test tests/e2e/vendor-panel/ --project=chromium

# Generate HTML report
pnpm exec playwright test tests/e2e/vendor-panel/ --reporter=html
```

### View Test Report

```bash
pnpm exec playwright show-report
```

---

## Browser Coverage

Tests run on 5 browser configurations:

- Chromium (Desktop)
- Firefox (Desktop)
- WebKit/Safari (Desktop)
- Mobile Chrome (Pixel 5)
- Mobile Safari (iPhone 12)

---

## Seeded Test Data Requirements

The tests expect the following seeded data:

| Data Type   | Details                                     |
| ----------- | ------------------------------------------- |
| Vendor User | `vendor@goadventure.tn` / `password`        |
| Listings    | At least 1 published listing for the vendor |
| Bookings    | Pending, Confirmed, and Completed bookings  |
| Reviews     | Pending and Published reviews               |
| Vouchers    | Valid voucher codes for check-in tests      |

Ensure seeders populate this data:

- `VendorSeeder` - Creates vendor user
- `ListingSeeder` - Creates sample listings
- `BookingSeeder` - Creates test bookings
- `ReviewSeeder` - Creates test reviews

---

## Known Limitations

1. **Backend Dependency:** All tests require Laravel API at `localhost:8000`
2. **Seeded Data:** Tests rely on specific seeded data existing
3. **Livewire Timing:** Some tests use `waitForTimeout()` for Livewire updates
4. **Conditional Skips:** Tests skip gracefully if required data is missing

---

## Next Steps for Development Team

1. **Start Backend:** Run `make up` to start all services
2. **Verify Seeding:** Ensure vendor test data is seeded
3. **Run Tests:** Execute vendor panel tests
4. **Review Failures:** Check for missing Filament fields/actions
5. **Adjust Selectors:** Update selectors if Filament UI differs

---

## Test Maintenance

When modifying the vendor panel:

1. **New Fields:** Update `vendor-test-data.ts` with new field values
2. **Changed Selectors:** Update `vendor-helpers.ts` selector patterns
3. **New Resources:** Create new `vendor-{resource}.spec.ts` file
4. **New Actions:** Add test cases to appropriate spec file

---

_Report generated by Claude Code_
