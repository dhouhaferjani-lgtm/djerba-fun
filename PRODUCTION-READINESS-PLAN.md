# Production Readiness Plan - Djerba Fun

**Created:** 2026-03-07
**Purpose:** Guide for agents to complete system onboarding and production readiness
**Priority Order:** Critical → High → Medium → Low

---

## Current Status Summary

### Completed ✅

| Component                | Tests        | Status           |
| ------------------------ | ------------ | ---------------- |
| Blog Management          | 38/38 (100%) | Production Ready |
| Auth/Login               | 6/6 (100%)   | Production Ready |
| Wishlist                 | 16/16 (100%) | Production Ready |
| Booking Flow (core)      | 18/18 (100%) | Production Ready |
| Platform Settings        | 5/5 (100%)   | Production Ready |
| Vendor Check-in Scanner  | 12/12 (100%) | Production Ready |
| Vendor Booking Lifecycle | 6/6 (100%)   | Production Ready |

### Needs Work ⚠️

| Component                | Tests      | Issue               |
| ------------------------ | ---------- | ------------------- |
| Admin Listing Management | 1/6 (17%)  | Selector mismatches |
| Vendor Panel Tests       | 0/8 (0%)   | Login helper broken |
| Search & Filter          | 4/17 (24%) | Missing data-testid |
| Listing Detail           | 2/17 (12%) | Selector mismatches |
| Guest Checkout E2E       | 0/6 (0%)   | Missing data-testid |

---

## Phase 1: Critical E2E Test Fixes (Priority: CRITICAL)

### Task 1.1: Fix Frontend data-testid Attributes

**Estimated Effort:** 2-3 hours
**Files to Modify:**

```
apps/web/src/components/availability/AvailabilityCalendar.tsx
apps/web/src/components/availability/TimeSlotPicker.tsx
apps/web/src/components/booking/BookingWidget.tsx
apps/web/src/components/booking/ParticipantSelector.tsx
apps/web/src/components/booking/PriceDisplay.tsx
apps/web/src/components/checkout/CheckoutForm.tsx
```

**Required data-testid attributes:**

```tsx
// Booking Widget Components
<DatePicker data-testid="booking-date-selector" />
<TimeSlotButton data-testid="time-slot" />
<QuantityInput data-testid="participant-count" />
<PriceDisplay data-testid="total-price" />
<HoldTimer data-testid="hold-timer" />

// Checkout Form
<input data-testid="checkout-email" />
<input data-testid="checkout-first-name" />
<input data-testid="checkout-last-name" />
<button data-testid="complete-checkout" />

// Confirmation
<span data-testid="booking-number" />
```

**Verification Command:**

```bash
pnpm exec playwright test tests/e2e/booking/guest-checkout.spec.ts --project=chromium
```

---

### Task 1.2: Fix Vendor Panel Login Helper

**Estimated Effort:** 1 hour
**File:** `apps/web/tests/fixtures/vendor-helpers.ts`

**Current Issue:**

```typescript
// Failing assertion - .fi-sidebar doesn't exist
await expect(page.locator('.fi-sidebar, .fi-sidebar-nav')).toBeVisible();
```

**Fix Required:**

```typescript
// Use Filament 3 selectors
await expect(page.locator('[data-sidebar], nav[class*="sidebar"]')).toBeVisible();
// OR wait for dashboard content
await expect(page.locator('main, [data-content]')).toBeVisible();
```

**Verification Command:**

```bash
pnpm exec playwright test tests/e2e/vendor-panel/ --project=chromium
```

---

### Task 1.3: Fix Admin Panel Selectors

**Estimated Effort:** 2 hours
**Files:**

```
apps/web/tests/e2e/admin/listing-management.spec.ts
apps/web/tests/fixtures/admin-helpers.ts
```

**Pattern to Apply (already working in blog-management.spec.ts):**

```typescript
// Before (failing)
const tab = page.locator('button:has-text("Details")').first();

// After (working - use ARIA roles)
const tab = page.getByRole('tab', { name: /Details/i });

// Before (failing)
const button = page.locator('button:has-text("Save")');

// After (working)
const button = page.getByRole('button', { name: /Save/i });
```

**Verification Command:**

```bash
pnpm exec playwright test tests/e2e/admin/listing-management.spec.ts --project=chromium
```

---

### Task 1.4: Fix Search & Filter Selectors

**Estimated Effort:** 2 hours
**Files:**

```
apps/web/tests/e2e/search-and-filter.spec.ts
apps/web/src/components/search/SearchFilters.tsx
apps/web/src/components/molecules/TagFilterGroup.tsx
```

**Options:**

1. Add data-testid to frontend components (preferred)
2. Update tests to use visible text/role selectors

**Verification Command:**

```bash
pnpm exec playwright test tests/e2e/search-and-filter.spec.ts --project=chromium
```

---

## Phase 2: Seed Data & Database (Priority: HIGH)

### Task 2.1: Create Nautical Listings Seeder

**File to Create:** `apps/laravel-api/database/seeders/NauticalListingsSeeder.php`

**Required Data:**

- 3-5 nautical activity listings (jet ski, boat tours, parasailing)
- Pricing in TND and EUR
- Availability slots
- Hero images

**Run Command:**

```bash
cd apps/laravel-api && php artisan db:seed --class=NauticalListingsSeeder
```

---

### Task 2.2: Create Accommodation Listings Seeder

**File to Create:** `apps/laravel-api/database/seeders/AccommodationListingsSeeder.php`

**Required Data:**

- 3-5 accommodation listings (hotels, villas, apartments)
- Pricing per night in TND and EUR
- Room types and amenities
- Hero images

**Run Command:**

```bash
cd apps/laravel-api && php artisan db:seed --class=AccommodationListingsSeeder
```

---

### Task 2.3: Create Test Bookings Seeder

**File to Create:** `apps/laravel-api/database/seeders/TestBookingsSeeder.php`

**Required Booking States:**

- 2x `pending_payment` - for Mark as Paid tests
- 3x `confirmed` - for Complete/No-Show tests
- 1x `cancelled` - for cancellation reason visibility
- 2x `completed` - for history views

---

## Phase 3: API & Backend Hardening (Priority: HIGH)

### Task 3.1: API Rate Limiting Review

**Files to Check:**

```
apps/laravel-api/routes/api.php
apps/laravel-api/app/Http/Kernel.php
```

**Verify:**

- Auth endpoints: 5 attempts per 15 min
- Public endpoints: 60 requests per min
- Authenticated endpoints: 120 requests per min

---

### Task 3.2: Input Validation Audit

**Run PHPStan:**

```bash
cd apps/laravel-api && ./vendor/bin/phpstan analyse
```

**Check FormRequests exist for:**

- [ ] BookingController
- [ ] PaymentController
- [ ] ReviewController
- [ ] CustomTripController
- [ ] ContactController

---

### Task 3.3: Security Headers Check

**File:** `apps/laravel-api/app/Http/Middleware/SecurityHeaders.php`

**Required Headers:**

```php
'X-Content-Type-Options' => 'nosniff',
'X-Frame-Options' => 'SAMEORIGIN',
'X-XSS-Protection' => '1; mode=block',
'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
'Content-Security-Policy' => "default-src 'self'...",
```

---

## Phase 4: Frontend Production Build (Priority: HIGH)

### Task 4.1: Build Verification

```bash
cd apps/web
pnpm build
pnpm typecheck
pnpm lint
```

**Expected:** No errors

---

### Task 4.2: Bundle Size Analysis

```bash
cd apps/web && pnpm analyze
```

**Targets:**

- First Load JS: < 150KB gzipped
- Largest page: < 300KB gzipped

---

### Task 4.3: Image Optimization Check

**Verify next/image usage:**

- All listing images use `<Image>` component
- Proper width/height or fill
- Priority set for above-fold images

---

### Task 4.4: Translation Completeness

```bash
cd apps/web && pnpm i18n:check
```

**Expected:** All keys present in both `fr.json` and `en.json`

---

## Phase 5: Infrastructure & Deployment (Priority: MEDIUM)

### Task 5.1: Environment Variables Audit

**Frontend (.env.production):**

```env
NEXT_PUBLIC_API_URL=https://api.djerbafun.com/api/v1
NEXT_PUBLIC_TURNSTILE_SITE_KEY=<production-key>
NEXT_PUBLIC_MAPBOX_TOKEN=<production-token>
```

**Backend (.env.production):**

```env
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=pgsql
REDIS_HOST=<production-redis>
AWS_BUCKET=<production-bucket>
MAIL_MAILER=ses
```

---

### Task 5.2: Docker Production Build Test

```bash
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
make health
```

---

### Task 5.3: SSL/TLS Configuration

**Verify:**

- [ ] SSL certificates installed
- [ ] HTTP → HTTPS redirect
- [ ] HSTS header enabled
- [ ] TLS 1.2+ only

---

## Phase 6: Monitoring & Observability (Priority: MEDIUM)

### Task 6.1: Error Tracking Setup

**Options:**

- Sentry (recommended)
- Bugsnag
- Rollbar

**Frontend Integration:**

```typescript
// apps/web/src/lib/sentry.ts
Sentry.init({
  dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
  environment: process.env.NODE_ENV,
});
```

**Backend Integration:**

```php
// apps/laravel-api/config/sentry.php
'dsn' => env('SENTRY_LARAVEL_DSN'),
```

---

### Task 6.2: Performance Monitoring

**Frontend:**

- Vercel Analytics or
- Custom Web Vitals reporting

**Backend:**

- Laravel Telescope (dev only)
- Laravel Horizon dashboard
- New Relic or Datadog APM

---

### Task 6.3: Uptime Monitoring

**Setup:**

- API health endpoint monitoring
- Frontend availability checks
- SSL certificate expiry alerts
- Database connection monitoring

---

## Phase 7: Documentation (Priority: LOW)

### Task 7.1: API Documentation

```bash
cd apps/laravel-api && php artisan openapi:generate
```

**Verify:** Swagger/OpenAPI docs accessible at `/api/docs`

---

### Task 7.2: Deployment Runbook

**Create:** `docs/DEPLOYMENT.md`

**Contents:**

- Pre-deployment checklist
- Deployment steps
- Rollback procedure
- Post-deployment verification

---

### Task 7.3: Operations Runbook

**Create:** `docs/OPERATIONS.md`

**Contents:**

- Common issues and fixes
- Log locations
- Restart procedures
- Scaling guidelines

---

## Verification Checklist

### Before Going Live:

#### E2E Tests (must all pass)

- [ ] `pnpm exec playwright test tests/e2e/auth-login.spec.ts`
- [ ] `pnpm exec playwright test tests/e2e/booking-flow.spec.ts`
- [ ] `pnpm exec playwright test tests/e2e/wishlist.spec.ts`
- [ ] `pnpm exec playwright test tests/e2e/admin/blog-management.spec.ts`
- [ ] `pnpm exec playwright test tests/e2e/admin/platform-settings.spec.ts`

#### Build & Types

- [ ] `pnpm build` (no errors)
- [ ] `pnpm typecheck` (no errors)
- [ ] `pnpm lint` (no errors)

#### Backend

- [ ] `php artisan test` (all passing)
- [ ] `./vendor/bin/phpstan analyse` (no errors)
- [ ] `./vendor/bin/pint --test` (formatted)

#### Manual Testing

- [ ] Complete booking flow (guest checkout)
- [ ] Complete booking flow (authenticated)
- [ ] Admin panel CRUD operations
- [ ] Vendor panel operations
- [ ] Check-in scanner functionality
- [ ] Mobile responsiveness
- [ ] French/English language switching

---

## Quick Reference Commands

```bash
# Run all critical E2E tests
pnpm exec playwright test tests/e2e/auth-login.spec.ts tests/e2e/booking-flow.spec.ts tests/e2e/wishlist.spec.ts --project=chromium

# Full test suite
pnpm exec playwright test --project=chromium

# API tests
cd apps/laravel-api && php artisan test

# Build everything
pnpm build && pnpm typecheck && pnpm lint

# Docker health check
make health

# Fresh database
make fresh
```

---

## Agent Handoff Notes

### Key Files to Know:

- `CLAUDE.md` - Project overview and coding guidelines
- `TEST-STATUS-SUMMARY.md` - Current test status
- `apps/web/tests/fixtures/` - Test helpers (patterns to follow)
- `apps/laravel-api/app/Filament/` - Admin/Vendor panels

### Working Patterns:

1. **Filament 3 Selectors:** Use `getByRole()` not CSS selectors
2. **Livewire Timing:** Wait 1500ms+ for debounced fields
3. **FilePond Uploads:** Wait 2000ms after completion for Livewire sync
4. **Frontend Pages:** Navigate main page, don't use `newPage()`

### Common Pitfalls:

- Don't navigate away from edit page after `createViaUI` - use returned slug
- Filament locale switcher is a `<select>`, not tabs
- TinyMCE content is in an iframe - use `frameLocator()`

---

## Priority Execution Order

1. **Day 1 (Critical):**
   - Task 1.1: Frontend data-testid attributes
   - Task 1.2: Vendor panel login fix
   - Task 1.3: Admin panel selectors

2. **Day 2 (High):**
   - Task 1.4: Search & filter selectors
   - Task 2.1-2.3: Seed data
   - Task 4.1-4.4: Frontend build verification

3. **Day 3 (Medium):**
   - Task 3.1-3.3: API hardening
   - Task 5.1-5.3: Infrastructure
   - Task 6.1-6.3: Monitoring

4. **Day 4 (Low):**
   - Task 7.1-7.3: Documentation
   - Final verification checklist
   - Production deployment

---

**Document Version:** 1.0
**Last Updated:** 2026-03-07
**Author:** Claude Code (Opus 4.5)
