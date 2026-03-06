# E2E Test Report - 2026-03-06

**Test Run Time**: 10:25 - 10:40 AM
**Environment**: Local Development
**Base URL**: http://localhost:3000

---

## Summary

| Metric               | Count |
| -------------------- | ----- |
| **Total Test Cases** | 201   |
| **Passed**           | 0     |
| **Failed**           | 201   |
| **Skipped**          | 0     |
| **Pass Rate**        | 0%    |

### Browser Distribution

| Browser                 | Failures |
| ----------------------- | -------- |
| Chromium (Desktop)      | 100      |
| Mobile Chrome (Pixel 5) | 101      |

---

## Failures by Test Suite

| Test File                     | Failures | % of Total |
| ----------------------------- | -------- | ---------- |
| ppp-pricing (5 files)         | 58       | 29%        |
| search-and-filter.spec.ts     | 26       | 13%        |
| dashboard-bookings.spec.ts    | 24       | 12%        |
| auth-register.spec.ts         | 19       | 9%         |
| profile-management.spec.ts    | 18       | 9%         |
| auth-login.spec.ts            | 16       | 8%         |
| booking-flow.spec.ts          | 14       | 7%         |
| payment-complete-flow.spec.ts | 12       | 6%         |
| booking-complete-flow.spec.ts | 10       | 5%         |
| inventory-tracking.spec.ts    | 4        | 2%         |

---

## Root Cause Analysis

### Pattern 1: API Connection Failures (HIGH IMPACT)

**Affected Suites**: search-and-filter, booking-flow, booking-complete-flow, payment-complete-flow, ppp-pricing
**Error**: Pages display "Something went wrong" or "Try again" error messages
**Root Cause**: Backend API (port 8000) not responding or returning errors
**Evidence**: Page snapshots show error boundaries triggered in the main content area

```
- paragraph [ref=e40]: Something went wrong
- button "Try again" [ref=e41] [cursor=pointer]
```

### Pattern 2: Authentication Required (MEDIUM IMPACT)

**Affected Suites**: dashboard-bookings, profile-management, payment-complete-flow
**Error**: Redirected to login/register page instead of expected dashboard
**Root Cause**: No authenticated test user session; tests require login but auth state not established
**Evidence**: Payment tests show registration form instead of checkout

### Pattern 3: Missing Test Data (MEDIUM IMPACT)

**Affected Suites**: booking-complete-flow, inventory-tracking
**Error**: No listings or availability data to interact with
**Root Cause**: Test database not seeded with required fixtures
**Evidence**: Empty listing results, no availability slots

### Pattern 4: Selector/Assertion Mismatches (LOW IMPACT)

**Affected Suites**: auth-login, auth-register
**Error**: Pages load correctly but test assertions fail
**Root Cause**: Tests expect different text/selectors than current UI
**Evidence**: Register page shows "Create Account" heading (tests may expect "Register")

---

## Detailed Failure Samples

### 1. Search and Filter Tests

**URL**: `/en/listings`
**Error**: API call to fetch listings fails, error boundary displayed
**Screenshot**: `test-results/search-and-filter-*/test-failed-1.png`

### 2. Booking Flow Tests

**URL**: `/en/listings/[slug]`
**Error**: "Something Went Wrong" error page displayed
**Screenshot**: `test-results/booking-flow-*/test-failed-1.png`

### 3. Dashboard Bookings Tests

**URL**: `/en/dashboard/bookings`
**Error**: Redirected to login - no authenticated session
**Screenshot**: `test-results/dashboard-bookings-*/test-failed-1.png`

### 4. Auth Login Tests

**URL**: `/en/auth/login`
**Error**: Page loads but assertions fail (form validation, button states)
**Screenshot**: `test-results/auth-login-*/test-failed-1.png`

---

## Recommendations

### Immediate Actions (Before Re-run)

1. **Verify API is Running**

   ```bash
   curl http://localhost:8000/api/v1/health
   ```

2. **Seed Test Database**

   ```bash
   make fresh  # Reset and seed database
   ```

3. **Verify Services**
   ```bash
   make health  # Check all services
   ```

### Test Improvements

1. **Add API health check** in test setup (beforeAll hook)
2. **Create dedicated test fixtures** with proper seeding
3. **Update selectors** for auth pages to match current UI
4. **Add retry logic** for flaky API calls

---

## Artifacts

| Artifact         | Location                                    |
| ---------------- | ------------------------------------------- |
| Screenshots      | `apps/web/test-results/*/test-failed-1.png` |
| Videos           | `apps/web/test-results/*/video.webm`        |
| Page Snapshots   | `apps/web/test-results/*/error-context.md`  |
| Last Run Summary | `apps/web/test-results/.last-run.json`      |

---

## Status

# BLOCKER FOUND

**Deployment Status**: NOT READY FOR DEPLOY

**Critical Issues**:

- 100% test failure rate
- Backend API connectivity issues
- Missing test data/fixtures
- Authentication flow broken for protected routes

**Next Steps**:

1. Investigate backend API health
2. Verify database seeding completed
3. Re-run tests after fixing infrastructure issues
4. Address selector mismatches in auth tests

---

_Report generated by Claude Code E2E Test Analyzer_
_Test framework: Playwright_
