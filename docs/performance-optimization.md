# Performance Optimization Guide

## Completed Optimizations ✅

### 1. Dynamic Imports for Heavy Components

Heavy components are lazy-loaded to reduce initial bundle size:

- **Map Components** (Leaflet ~140KB):
  - `ListingMap` - Dynamically loaded in listing detail
  - `SearchMap` - Dynamically loaded in destination pages
  - `AvailabilityCalendar` - Dynamically loaded in listing detail

- **Itinerary Components**:
  - `ItineraryTimeline` - Dynamically loaded
  - `ElevationProfile` - Dynamically loaded

- **Booking Components**:
  - `BookingPanel` - Dynamically loaded
  - `FixedBookingPanel` - Dynamically loaded

### 2. Image Optimization

**File:** `apps/web/next.config.ts`

- Modern formats enabled: AVIF, WebP
- Optimized device sizes: 640, 750, 828, 1080, 1200, 1920, 2048, 3840
- Image sizes: 16, 32, 48, 64, 96, 128, 256, 384
- Lazy loading by default on all Next.js Image components

### 3. Code Splitting

Next.js automatically code-splits by:

- Route-based splitting (each page is a separate chunk)
- Dynamic imports (on-demand loading)
- Client components vs Server components

## Additional Optimizations to Consider

### Bundle Analysis

Run bundle analyzer to identify large dependencies:

```bash
cd apps/web
ANALYZE=true pnpm build
```

This will open an interactive visualization of the bundle.

### Font Optimization

**Current:** Google Fonts (Inter, Poppins) with `display: 'swap'`

**Improvement Options:**

1. Self-host fonts to reduce external requests
2. Use `font-display: optional` for faster FCP
3. Subset fonts to include only required characters

### React Query Optimization

**File:** `apps/web/src/lib/providers/Providers.tsx`

Current settings are good, but consider:

- Increase `staleTime` for rarely-changing data (listings, locations)
- Add query key factories for better invalidation
- Use `prefetchQuery` for predictable navigation

### Lazy Load Below-the-Fold Content

Components that could be lazy-loaded:

- Footer (lazy load social icons, newsletter form)
- Testimonials section (lazy load when scrolled into view)
- Blog section on homepage (lazy load)

```tsx
const Footer = dynamic(() => import('@/components/organisms/Footer'), {
  ssr: true, // Keep SSR for SEO
});
```

### Optimize Third-Party Scripts

**Current:** Google Analytics loaded with `afterInteractive`

**Improvements:**

- Consider using Partytown for offloading to Web Worker
- Use `next/script` strategy="worker" (experimental)
- Delay non-critical analytics

### Database Query Optimization

Check Laravel API N+1 queries:

- Use eager loading (`with()`) for relationships
- Add database indexes for frequently queried columns
- Consider Redis caching for frequently accessed data

### API Response Caching

**Recommendations:**

1. Add HTTP caching headers to API responses
2. Use Redis for frequently accessed data (listings, locations)
3. Implement stale-while-revalidate pattern

```php
// Laravel example
return response()->json($data)
    ->header('Cache-Control', 'public, max-age=300, stale-while-revalidate=600');
```

### Reduce JavaScript Bundle Size

**Large Dependencies to Review:**

- `leaflet` (~140KB) - ✅ Already lazy loaded
- `react-hook-form` (~50KB) - Used in multiple forms
- `@tanstack/react-query` (~40KB) - Core dependency, needed
- `date-fns` (~70KB) - Consider using `date-fns-tz` only when needed

**Action:** Use tree-shaking and import only needed functions:

```tsx
// Bad
import * as dateFns from 'date-fns';

// Good
import { format, addDays } from 'date-fns';
```

### Enable Compression

**File:** `apps/web/next.config.ts`

```tsx
compress: true, // ✅ Already enabled
```

### Optimize CSS

1. Remove unused Tailwind classes in production
2. Use `@apply` sparingly (increases bundle size)
3. Consider critical CSS extraction for above-the-fold

### Service Worker / PWA

**Manifest:** ✅ Already configured

**Next Steps:**

- Add service worker for offline support
- Implement background sync for bookings
- Cache API responses for offline viewing

### Lighthouse Score Targets

**Performance Metrics:**

- First Contentful Paint (FCP): < 1.8s
- Largest Contentful Paint (LCP): < 2.5s
- Time to Interactive (TTI): < 3.8s
- Cumulative Layout Shift (CLS): < 0.1
- First Input Delay (FID): < 100ms

**Run Lighthouse:**

```bash
# In browser DevTools > Lighthouse
# Or via CLI:
npx lighthouse http://localhost:3000 --view
```

## Production Checklist

Before deploying:

- [ ] Run `ANALYZE=true pnpm build` and review bundle
- [ ] Run Lighthouse audit on key pages (home, listing detail, checkout)
- [ ] Test on slow 3G network (Chrome DevTools > Network)
- [ ] Verify all images use Next.js Image component
- [ ] Check for any console warnings about optimization
- [ ] Enable HTTP/2 or HTTP/3 on CDN
- [ ] Configure CDN caching rules
- [ ] Set up monitoring (Web Vitals, error tracking)

## Monitoring

### Web Vitals

Already implemented: `apps/web/src/app/web-vitals.tsx`

Reports Core Web Vitals to analytics.

### Performance Monitoring Tools

- Google PageSpeed Insights
- WebPageTest.org
- Chrome User Experience Report
- Vercel Analytics (if deployed on Vercel)

## Quick Wins Summary

1. ✅ Dynamic imports for heavy components
2. ✅ Image optimization configured
3. ✅ Compression enabled
4. ⏳ Bundle analysis and tree-shaking
5. ⏳ Database query optimization
6. ⏳ API response caching
7. ⏳ Service worker implementation

## Performance Budget

Recommended budgets:

- **Initial JS:** < 200KB (gzipped)
- **Total JS:** < 500KB (gzipped)
- **CSS:** < 50KB (gzipped)
- **Images (above fold):** < 500KB
- **Total page weight:** < 1.5MB

Use Lighthouse budgets to enforce:

```json
{
  "budgets": [
    {
      "path": "/*",
      "timings": [
        {
          "metric": "interactive",
          "budget": 3000
        }
      ],
      "resourceSizes": [
        {
          "resourceType": "script",
          "budget": 200
        }
      ]
    }
  ]
}
```
