# Runtime Error Checklist & Resolution Guide

**Date:** 2025-12-17
**Status:** ✅ Active Monitoring
**Purpose:** Systematic approach to finding and fixing runtime errors

---

## Recently Fixed Runtime Errors

### ✅ FIXED: Missing Translation Keys (6 errors)

**Error**: `MISSING_MESSAGE: Could not resolve home.featured_package_X_title in messages for locale en`

**Location**: `src/components/organisms/FeaturedPackagesSection.tsx`

**Resolution**: Added 6 missing translation keys to `messages/en.json` and `messages/fr.json`

**Keys Added**:

- `home.featured_package_1_title`
- `home.featured_package_1_description`
- `home.featured_package_2_title`
- `home.featured_package_2_description`
- `home.featured_package_3_title`
- `home.featured_package_3_description`

**Status**: ✅ **RESOLVED** (Commit: 27a0f9c)

---

## How to Check for All Runtime Errors

### 1. Development Server Console Errors

**Steps:**

```bash
# Start development server
pnpm dev

# Open browser at http://localhost:3000
# Open Browser DevTools (F12 or Cmd+Option+I)
# Navigate to Console tab
# Check for red errors
```

**What to Look For:**

- ❌ Red errors (blocking issues)
- ⚠️ Yellow warnings (potential issues)
- 🔵 Blue info messages (informational only)

**Common Error Types:**

- Missing translation keys (`MISSING_MESSAGE`)
- Hydration mismatches
- Component render errors
- Network/API failures
- Type errors that TypeScript missed

### 2. Browser Network Tab

**Steps:**

```bash
# Open DevTools > Network tab
# Reload page
# Filter by "All" or "Fetch/XHR"
```

**What to Look For:**

- ❌ Failed requests (404, 500 errors)
- ⚠️ Slow requests (>3s response time)
- Missing images or fonts
- CORS errors

### 3. Next.js Dev Server Terminal Output

**What to Look For:**

```bash
# Terminal where `pnpm dev` is running
```

**Common Warnings:**

- Fast Refresh warnings
- Missing environment variables
- Deprecated API usage
- Image optimization warnings

### 4. Production Build Warnings

**Steps:**

```bash
pnpm build 2>&1 | tee build-output.log
```

**What to Look For:**

- TypeScript errors (should be 0)
- Missing translation warnings
- Bundle size warnings
- Image optimization issues
- Dead code warnings

### 5. Test All Pages Manually

**Checklist:**

**Homepage** (`/en` and `/fr`)

- [ ] No console errors
- [ ] All images load
- [ ] All text is translated
- [ ] Search form works
- [ ] Navigation links work

**Listings Page** (`/en/listings`)

- [ ] No console errors
- [ ] Listings display correctly
- [ ] Filters work
- [ ] Pagination works

**Listing Detail** (`/en/listings/[slug]`)

- [ ] No console errors
- [ ] Map loads correctly
- [ ] Booking widget works
- [ ] Images load

**Blog Pages** (`/en/blog`, `/en/blog/[slug]`)

- [ ] No console errors
- [ ] Posts display
- [ ] Navigation works

**Auth Pages** (`/en/auth/login`, `/en/auth/register`)

- [ ] No console errors
- [ ] Forms validate
- [ ] Submit works

**Dashboard** (`/en/dashboard`)

- [ ] No console errors
- [ ] Protected route works
- [ ] Data loads

---

## Common Runtime Error Categories

### Category 1: Translation Errors ✅ FIXED

**Pattern**: `MISSING_MESSAGE: Could not resolve [key] in messages for locale [locale]`

**How to Fix:**

1. Identify the missing key from error message
2. Find the component using `useTranslations()`
3. Add the key to `messages/en.json`
4. Add the French translation to `messages/fr.json`
5. Verify in browser

**Prevention:**

- Before deploying, grep for all `t(')` calls and verify keys exist
- Use the i18n audit document to track coverage

### Category 2: Hydration Mismatches

**Pattern**: `Hydration failed because the initial UI does not match what was rendered on the server`

**Common Causes:**

- Date/time rendering (server vs client timezone)
- Random content generation
- Browser-only APIs used in SSR
- Conditional rendering based on `window` object

**How to Fix:**

1. Identify the component causing mismatch
2. Use `useEffect` for client-only rendering
3. Use `suppressHydrationWarning` prop if intentional
4. Ensure server and client render same HTML initially

### Category 3: Image Loading Errors

**Pattern**: `Failed to load resource: the server responded with a status of 404`

**Common Causes:**

- Broken image URLs
- Missing images in public folder
- Incorrect Next.js Image configuration

**How to Fix:**

1. Verify image URL is correct
2. Check image exists at path
3. Add default/fallback images
4. Use proper Next.js Image component

### Category 4: API/Network Errors

**Pattern**: `Failed to fetch`, `Network error`, `CORS error`

**Common Causes:**

- API endpoint down or incorrect
- Missing CORS headers
- Authentication issues
- Network timeout

**How to Fix:**

1. Verify API endpoint URL
2. Check API is running
3. Verify CORS configuration
4. Add error handling and retries

### Category 5: TypeScript Runtime Errors

**Pattern**: `Cannot read property 'X' of undefined`, `X is not a function`

**Common Causes:**

- Missing null checks
- Incorrect type assumptions
- Optional chaining needed

**How to Fix:**

1. Add null/undefined checks
2. Use optional chaining (`?.`)
3. Add default values
4. Improve TypeScript types

---

## Automated Error Detection

### Using Next.js Error Boundary

The app has global error boundaries in:

- `apps/web/src/app/global-error.tsx` (top-level errors)
- `apps/web/src/app/error.tsx` (page-level errors)
- `apps/web/src/app/not-found.tsx` (404 errors)

**These will catch:**

- Uncaught exceptions
- Component render errors
- Server errors

### Using Browser Console Automation

**Script to capture all errors:**

```javascript
// Run in browser console
let errors = [];
window.addEventListener('error', (e) => {
  errors.push({ type: 'error', message: e.message, stack: e.error?.stack });
  console.error('Captured error:', e.message);
});

window.addEventListener('unhandledrejection', (e) => {
  errors.push({ type: 'promise', message: e.reason });
  console.error('Captured promise rejection:', e.reason);
});

// After navigating the site:
console.table(errors);
copy(JSON.stringify(errors, null, 2));
```

### Playwright E2E Error Detection

Create a test to capture console errors:

```typescript
// tests/e2e/console-errors.spec.ts
import { test, expect } from '@playwright/test';

test('homepage has no console errors', async ({ page }) => {
  const errors: string[] = [];

  page.on('console', (msg) => {
    if (msg.type() === 'error') {
      errors.push(msg.text());
    }
  });

  await page.goto('http://localhost:3000/en');
  await page.waitForLoadState('networkidle');

  expect(errors).toHaveLength(0);
});
```

---

## Current Error Status

### ✅ Resolved Errors

1. **Missing Translation Keys** (6 errors)
   - Component: FeaturedPackagesSection
   - Fixed: 2025-12-17
   - Commit: 27a0f9c

2. **TypeScript Build Errors** (20+ errors)
   - Various components
   - Fixed: 2025-12-17
   - Commit: f90d405

### ⚠️ Known Warnings (Non-Blocking)

1. **Middleware Deprecation Warning** (1 instance)

   ```
   ⚠ The "middleware" file convention is deprecated. Please use "proxy" instead.
   ```

   - **Severity**: Low (deprecation warning, not breaking)
   - **Impact**: None currently
   - **Action**: Can be addressed in future Next.js upgrade

2. **CMS Page Not Found** (2 instances)
   ```
   Page with code HOME not found
   ```

   - **Location**: `src/lib/api/cms.ts:85`
   - **Severity**: Low (expected behavior when CMS page doesn't exist)
   - **Impact**: None (graceful fallback)
   - **Action**: Create CMS page with code "HOME" or suppress warning

### ❌ Open Errors

1. **Unsplash Image 404 Errors** (5 unique images)

   ```
   ⨯ upstream image response failed for https://images.unsplash.com/... 404
   ```

   **Failed Images**:
   - `photo-1484199316225-b0f50e1b1e6e?w=800` (Cultural Tours)
   - `photo-1548020920-3e8e6d2b7d0e?w=800` (Destinations)
   - `photo-1590059390047-f5e617690a0b?w=800` (Blog/Destinations)
   - `photo-1590059390047-f5e617690a0b?w=600` (Same, different size)
   - `photo-1563308780-5fa633445448?w=1200` (Featured Packages/Blog)

   **Locations**:
   - `src/components/home/CategoriesGridSection.tsx`
   - `src/components/home/DestinationsSection.tsx`
   - `src/components/organisms/FeaturedPackagesSection.tsx`
   - `src/components/home/BlogSection.tsx`
   - `src/data/blog-posts.ts`

   **Severity**: Medium (affects visual presentation)
   **Impact**: Broken images on homepage and blog
   **Action Required**: Replace with valid Unsplash URLs or local images

---

## Prevention Best Practices

### Before Committing

```bash
# 1. TypeScript check
pnpm typecheck

# 2. Build check
pnpm build

# 3. Manual test in browser
pnpm dev
# Navigate to all major pages
# Check console for errors

# 4. Translation check
grep -r "t('" apps/web/src --include="*.tsx" | \
  grep -o "t('[^']*')" | \
  sed "s/t('//g" | \
  sed "s/')//g" | \
  sort -u > /tmp/used-keys.txt

# Compare with keys in messages/en.json
```

### During Development

1. **Keep DevTools Console Open** - Catch errors immediately
2. **Test Both Locales** - Check `/en` and `/fr` routes
3. **Use TypeScript Strict Mode** - Catch more issues at compile time
4. **Add Error Boundaries** - Graceful degradation
5. **Log Errors** - Send to monitoring service (Sentry, LogRocket)

### For Production

1. **Enable Error Tracking** - Sentry or similar
2. **Monitor Real User Data** - Core Web Vitals, error rates
3. **Set Up Alerts** - Get notified of new errors
4. **Regular Audits** - Weekly error review
5. **User Feedback** - Bug report mechanism

---

## Error Monitoring Setup (Future)

### Sentry Integration (Recommended)

```bash
# Install Sentry
pnpm add @sentry/nextjs

# Configure
npx @sentry/wizard@latest -i nextjs
```

**Benefits:**

- Automatic error capture
- Source maps for stack traces
- Performance monitoring
- User session replay
- Release tracking

### LogRocket (Alternative)

**Benefits:**

- Session replay
- Console log capture
- Network monitoring
- Redux state tracking

---

## Testing Checklist

Before marking "Zero Errors":

### Development Environment

- [ ] No console errors on homepage (both locales)
- [ ] No console errors on all main pages
- [ ] No failed network requests
- [ ] No hydration warnings
- [ ] All images load correctly
- [ ] All translations resolve correctly

### Production Build

- [ ] `pnpm build` succeeds with 0 errors
- [ ] No missing translation warnings
- [ ] All routes generate correctly
- [ ] Bundle size within limits

### Manual Testing

- [ ] Tested all pages in Chrome
- [ ] Tested all pages in Safari
- [ ] Tested all pages in Firefox
- [ ] Tested mobile responsive
- [ ] Tested all interactive features

### Automated Testing

- [ ] Unit tests pass (if any)
- [ ] E2E tests pass (if any)
- [ ] Type checking passes
- [ ] Lint passes

---

## Summary

### Current Status: ⚠️ Minor Issues

**Last Checked**: 2025-12-17 11:10 AM
**Build Status**: ✅ Passing
**TypeScript**: ✅ 0 errors
**Runtime Errors**: ❌ 5 image loading errors
**Missing Translations**: ✅ 0 missing keys
**Warnings**: ⚠️ 2 non-blocking warnings

### Breakdown

**✅ Resolved (Build Time)**:

- All TypeScript build errors fixed (20+ errors)
- All translation keys present (6 keys added)

**⚠️ Warnings (Non-Breaking)**:

- Middleware deprecation warning (can defer)
- CMS page not found (expected behavior)

**❌ Open Errors (Requires Fix)**:

- 5 Unsplash image 404 errors (broken images visible on site)

### Next Steps

1. ✅ **DONE**: Fix TypeScript build errors
2. ✅ **DONE**: Fix missing featured package translations
3. ✅ **DONE**: Document all runtime errors
4. ❌ **TODO**: Replace 5 broken Unsplash image URLs
5. **TODO**: Set up automated error monitoring (Sentry)
6. **TODO**: Add E2E tests for critical paths
7. **TODO**: Create error report dashboard

### Recommendation

The application build is **passing**, but has **5 runtime image loading errors** that need to be fixed:

**Critical Path to Zero Errors**:

1. ❌ **Fix broken Unsplash images** (affects user experience)
2. ⚠️ **Optional**: Suppress CMS page warning or create HOME page
3. ⚠️ **Optional**: Update to Next.js proxy convention

**For true "zero errors" confidence**, recommend:

1. Fix the 5 image URLs (highest priority)
2. Manual testing of all pages (checklist above)
3. Automated E2E test suite
4. Production error monitoring setup

---

**Maintainer**: Design System Team
**Last Updated**: 2025-12-17
**Next Review**: Weekly or after major changes
