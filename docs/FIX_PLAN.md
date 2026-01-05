# Go Adventure - Known Issues Fix Plan

> **Created**: 2026-01-05
> **Priority**: High - Blocking production deployment
> **Estimated Total Effort**: 8-12 hours

---

## 🎯 Executive Summary

After comprehensive Playwright testing, we identified 1 critical issue and 2 performance optimizations needed before production deployment.

### Issues Overview

| Issue                                                   | Severity    | Impact                            | Effort | Priority |
| ------------------------------------------------------- | ----------- | --------------------------------- | ------ | -------- |
| **Listing Detail Page - useTranslations Context Error** | 🔴 Critical | Blocks entire listing detail flow | 4-6h   | P0       |
| **Large Contentful Paint (LCP) Optimization**           | 🟡 Medium   | SEO & UX impact                   | 2-3h   | P1       |
| **Cumulative Layout Shift (CLS) Fix**                   | 🟡 Medium   | UX impact                         | 2-3h   | P1       |

---

## 🔴 P0: Fix Listing Detail Page useTranslations Error

### Problem Statement

**Error**: `Failed to call useTranslations because the context from NextIntlClientProvider is missing`

**Impact**:

- All listing detail pages (`/en/[location]/[slug]`) show error page
- Complete booking flow is broken
- Users cannot view any listing details

**Root Cause**: The `ListingDetailClient` component uses `useTranslations` hook but the context is not properly provided when the component renders.

### Investigation Steps

1. **Verify NextIntlClientProvider hierarchy**

   ```typescript
   // Check locale layout wraps all pages
   apps / web / src / app / [locale] / layout.tsx;
   ```

2. **Check dynamic route structure**

   ```typescript
   // Verify locale parameter is passed correctly
   apps / web / src / app / [locale] / [location] / [slug] / page.tsx;
   ```

3. **Inspect ListingDetailClient usage**

   ```typescript
   // Check if locale prop is passed
   apps / web / src / app / [locale] / listings / [slug] / listing - detail - client.tsx;
   ```

4. **Test with static locale**
   - Temporarily hardcode locale to verify context issue
   - Check if dynamic import affects context

### Potential Solutions

#### Solution A: Pass locale through Client Component props ✅ Recommended

```typescript
// apps/web/src/app/[locale]/[location]/[slug]/page.tsx
export default async function ListingDetailPage({ params }: Props) {
  const { slug, locale, location } = await params;

  return (
    <ListingDetailClient
      listing={listing}
      locale={locale}  // ✅ Ensure locale is passed
      slug={slug}
    />
  );
}
```

```typescript
// apps/web/src/app/[locale]/listings/[slug]/listing-detail-client.tsx
'use client';

import { NextIntlClientProvider } from 'next-intl';
import { useTranslations } from 'next-intl';

export default function ListingDetailClient({ listing, locale, slug }: Props) {
  const t = useTranslations('listing');

  // If error persists, wrap with explicit provider:
  // return (
  //   <NextIntlClientProvider locale={locale} messages={messages}>
  //     {/* content */}
  //   </NextIntlClientProvider>
  // );
}
```

#### Solution B: Use setRequestLocale in page component

```typescript
// apps/web/src/app/[locale]/[location]/[slug]/page.tsx
import { setRequestLocale } from 'next-intl/server';

export default async function ListingDetailPage({ params }: Props) {
  const { slug, locale, location } = await params;

  // ✅ Explicitly set locale for this page
  setRequestLocale(locale);

  // ... rest of code
}
```

#### Solution C: Check if dynamic imports break context

The issue might be caused by dynamic imports in ListingDetailClient. Try:

1. Temporarily remove all dynamic imports
2. Test if error persists
3. If fixed, re-add dynamic imports with `ssr: true` option

### Testing Plan

1. Fix the issue using Solution A or B
2. Test all listing detail URLs:
   - `/en/sahara-desert/sahara-desert-camel-trek`
   - `/en/djerba/djerba-island-discovery-tour`
   - `/en/tunis/medina-tunis-walking-tour`
3. Verify no console errors
4. Test complete booking flow:
   - View listing detail
   - Select date/time
   - Add participants
   - Proceed to checkout

### Success Criteria

- ✅ No `useTranslations` context errors
- ✅ Listing detail page loads successfully
- ✅ All dynamic components render
- ✅ Booking flow works end-to-end
- ✅ Both EN and FR locales work

### Estimated Effort

**4-6 hours**

- Investigation: 1-2h
- Implementation: 2-3h
- Testing: 1h

---

## 🟡 P1: Optimize Largest Contentful Paint (LCP)

### Problem Statement

**Current Performance**:

- Best: 492ms ✅ Good
- Worst: 3240ms ❌ Needs Improvement
- Target: < 2500ms for "Good" rating

**Impact**: Poor LCP affects:

- SEO rankings (Core Web Vitals)
- User perception of speed
- Conversion rates

### Investigation

Check what's causing slow LCP:

```bash
# Use Lighthouse to identify LCP element
npm run build
npm run start
# Open Chrome DevTools > Lighthouse > Performance
```

### Common Causes & Solutions

#### 1. Large Hero Images

**Problem**: Hero images on homepage/listings not optimized

**Fix**:

```typescript
// Use Next.js Image with priority
<Image
  src="/hero.jpg"
  alt="Hero"
  priority  // ✅ Preload critical images
  quality={85}
  fill
/>
```

#### 2. Render-Blocking Resources

**Fix**:

```typescript
// next.config.ts
export default {
  experimental: {
    optimizeCss: true, // ✅ Inline critical CSS
  },
};
```

#### 3. Slow API Calls

**Fix**:

```typescript
// Use ISR for frequently accessed pages
export const revalidate = 60; // Revalidate every 60 seconds
```

### Testing

1. Run Lighthouse on homepage
2. Run Lighthouse on listings page
3. Verify LCP < 2500ms on both

### Estimated Effort

**2-3 hours**

---

## 🟡 P2: Fix Cumulative Layout Shift (CLS)

### Problem Statement

**Current Score**: 0.41 ❌ Poor
**Target**: < 0.1 ✅ Good

**Impact**: Layout shifts cause:

- Accidental clicks
- Poor UX
- Lower conversion

### Common Causes

1. **Images without dimensions**

   ```typescript
   // ❌ Bad
   <img src="/image.jpg" />

   // ✅ Good
   <Image src="/image.jpg" width={800} height={600} alt="..." />
   ```

2. **Dynamically injected content**
   - Cookie consent banner
   - Ads/embeds without reserved space

3. **Web fonts causing FOIT/FOUT**

### Investigation

Use Chrome DevTools to record layout shifts:

```
DevTools > Performance > Record page load
Look for red "Layout Shift" entries
```

### Fixes

#### 1. Reserve space for images

```typescript
// Add aspect-ratio to prevent shift
<div className="aspect-[16/9] relative">
  <Image src="..." fill className="object-cover" />
</div>
```

#### 2. Use font-display: optional

```typescript
// apps/web/src/app/layout.tsx
const inter = Inter({
  display: 'optional', // ✅ Prevents FOIT
  subsets: ['latin'],
});
```

#### 3. Reserve space for dynamic content

```typescript
// Cookie banner - absolute positioned to not shift content
<div className="fixed bottom-0 inset-x-0 z-50">
  <CookieConsentBanner />
</div>
```

### Testing

1. Record page load with DevTools
2. Check for layout shift entries
3. Verify CLS < 0.1

### Estimated Effort

**2-3 hours**

---

## 📋 Implementation Roadmap

### Phase 1: Critical Fix (P0)

**Timeline**: Day 1
**Effort**: 4-6 hours

- [ ] Investigate useTranslations context error
- [ ] Implement Solution A or B
- [ ] Test listing detail pages
- [ ] Verify booking flow works
- [ ] Test both locales (EN/FR)
- [ ] Deploy to staging
- [ ] Get user acceptance

### Phase 2: Performance Optimization (P1)

**Timeline**: Day 2
**Effort**: 4-6 hours

- [ ] Run Lighthouse audits
- [ ] Optimize LCP (images, CSS, fonts)
- [ ] Fix CLS (reserve space, fonts)
- [ ] Re-test Web Vitals
- [ ] Deploy to staging
- [ ] Monitor real-user metrics

### Phase 3: Validation

**Timeline**: Day 3
**Effort**: 2-3 hours

- [ ] Run all regression tests
- [ ] Manual QA testing
- [ ] Performance regression testing
- [ ] Cross-browser testing (Chrome, Safari, Firefox)
- [ ] Mobile testing (iOS, Android)
- [ ] Accessibility audit
- [ ] Sign-off for production

---

## 🧪 Testing Checklist

### Before Starting

- [ ] Pull latest from main
- [ ] Run `pnpm install`
- [ ] Start both servers (API + Web)
- [ ] Verify no existing errors

### After Each Fix

- [ ] Run regression tests: `pnpm test`
- [ ] Manual testing in browser
- [ ] Check console for errors
- [ ] Test in both EN and FR
- [ ] Verify no new issues introduced

### Before Deployment

- [ ] All regression tests pass
- [ ] No console errors
- [ ] Lighthouse score > 90
- [ ] LCP < 2500ms
- [ ] CLS < 0.1
- [ ] Manual QA sign-off

---

## 🚀 Success Metrics

### Completion Criteria

| Metric                       | Before   | Target     | Status          |
| ---------------------------- | -------- | ---------- | --------------- |
| Listing Detail Pages Working | ❌ Error | ✅ Working | Pending         |
| LCP (Homepage)               | 3240ms   | < 2500ms   | Pending         |
| LCP (Listings)               | 492ms    | < 2500ms   | ✅ Already good |
| CLS                          | 0.41     | < 0.1      | Pending         |
| Lighthouse Score             | Unknown  | > 90       | Pending         |

### Post-Deployment Monitoring

- Monitor error logs for 48h
- Track Core Web Vitals in Google Search Console
- Monitor conversion rates
- Collect user feedback

---

## 📞 Escalation & Support

### If Blocked

1. **useTranslations error persists after Solution A/B**
   - Check Next.js 16 + next-intl compatibility
   - Review next-intl documentation for App Router
   - Consider creating minimal reproduction

2. **Performance doesn't improve**
   - Use Chrome DevTools Performance profiler
   - Check for third-party scripts blocking render
   - Consider using CDN for static assets

3. **Need help**
   - Check CLAUDE.md debugging checklist
   - Review Next.js 16 migration guide
   - Consult next-intl documentation

---

**End of Fix Plan**
