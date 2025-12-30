# Performance Testing Guide

This guide provides instructions for testing and validating the performance optimizations implemented in the Next.js application.

## Quick Start

### 1. Bundle Analysis

Analyze the bundle size and composition:

```bash
cd apps/web
ANALYZE=true npm run build
```

This will:

- Build the production bundle
- Open interactive bundle analyzer in browser
- Show breakdown of all JavaScript chunks
- Identify largest dependencies

**What to look for:**

- Main bundle should be < 500KB
- Heavy libraries (Leaflet, Framer Motion) should be in separate chunks
- Code splitting should be evident in multiple chunks

---

### 2. Development Performance Check

Monitor performance during development:

```bash
npm run dev
```

Open Chrome DevTools:

1. Go to **Performance** tab
2. Click **Record**
3. Interact with the app (browse listings, open checkout)
4. Click **Stop**
5. Analyze the flame graph

**Metrics to check:**

- **Scripting time**: Should be minimal
- **Rendering time**: Should be < 50ms for most operations
- **Long tasks**: Should be rare (< 50ms)

---

### 3. Production Build Testing

Test the production build locally:

```bash
# Build for production
npm run build

# Start production server
npm run start
```

Test on localhost:3000 with:

- Chrome DevTools Lighthouse
- Network throttling (Fast 3G)
- CPU throttling (4x slowdown)

---

## Lighthouse Audit

### Running Lighthouse

1. Open page in Chrome
2. Open DevTools (F12)
3. Go to **Lighthouse** tab
4. Select:
   - ✅ Performance
   - ✅ Accessibility
   - ✅ Best Practices
   - ✅ SEO
5. Choose **Mobile** or **Desktop**
6. Click **Analyze page load**

### Target Scores

#### Mobile

- **Performance**: > 85
- **Accessibility**: > 95
- **Best Practices**: > 90
- **SEO**: > 95

#### Desktop

- **Performance**: > 90
- **Accessibility**: > 95
- **Best Practices**: > 90
- **SEO**: > 95

### Key Metrics

| Metric                         | Target (Mobile) | Target (Desktop) |
| ------------------------------ | --------------- | ---------------- |
| First Contentful Paint (FCP)   | < 1.8s          | < 0.9s           |
| Largest Contentful Paint (LCP) | < 2.5s          | < 1.2s           |
| Time to Interactive (TTI)      | < 3.8s          | < 2.0s           |
| Total Blocking Time (TBT)      | < 300ms         | < 150ms          |
| Cumulative Layout Shift (CLS)  | < 0.1           | < 0.1            |
| Speed Index                    | < 3.4s          | < 1.3s           |

---

## React DevTools Profiler

### Setup

1. Install React DevTools browser extension
2. Open DevTools
3. Navigate to **Profiler** tab

### Recording Interactions

1. Click **Record** button (circle icon)
2. Perform user interactions:
   - Browse listings
   - Open listing detail
   - Start checkout flow
   - Add items to cart
3. Click **Stop recording**

### Analyzing Results

#### Flamegraph View

- Shows render hierarchy
- Color indicates render time
- Click components to see details

**What to look for:**

- Short bars = fast renders ✅
- Long bars = slow renders ❌
- Gray = memoized (didn't render) ✅

#### Ranked View

- Lists components by render time
- Shows most expensive components first

**What to check:**

- Memoized components should render rarely
- Heavy components (maps, charts) should be lazy-loaded
- List items should be memoized

---

## Network Performance

### Testing Image Optimization

1. Open DevTools → **Network** tab
2. Filter by **Img**
3. Load a page with images
4. Check:
   - ✅ Images use AVIF or WebP format
   - ✅ Responsive sizes served correctly
   - ✅ Images lazy-load (load on scroll)
   - ✅ Proper cache headers set

### JavaScript Bundle Analysis

1. Filter by **JS** in Network tab
2. Check:
   - ✅ Main bundle < 500KB
   - ✅ Route-specific chunks loaded on demand
   - ✅ Vendor chunks cached properly
   - ✅ No duplicate libraries

---

## Performance Regression Testing

### Automated Lighthouse CI

Set up Lighthouse CI for continuous monitoring:

```bash
# Install Lighthouse CI
npm install -g @lhci/cli

# Run audit
lhci autorun --config=lighthouserc.js
```

Create `lighthouserc.js`:

```javascript
module.exports = {
  ci: {
    collect: {
      startServerCommand: 'npm run start',
      url: ['http://localhost:3000/en'],
      numberOfRuns: 3,
    },
    assert: {
      assertions: {
        'categories:performance': ['error', { minScore: 0.85 }],
        'categories:accessibility': ['error', { minScore: 0.95 }],
        'first-contentful-paint': ['error', { maxNumericValue: 1800 }],
        'largest-contentful-paint': ['error', { maxNumericValue: 2500 }],
        'cumulative-layout-shift': ['error', { maxNumericValue: 0.1 }],
      },
    },
  },
};
```

---

## Component-Specific Testing

### Testing Memoization

Add console logs to memoized components:

```tsx
function ListingCardComponent({ listing, locale }: ListingCardProps) {
  console.log('ListingCard rendering:', listing.id);
  // Component code...
}
```

**Expected behavior:**

- Initial render: All cards log
- Parent state change: No logs if props unchanged ✅
- Props change: Only affected cards log ✅

### Testing Lazy Loading

Monitor Network tab:

1. Load page
2. Check initial bundles loaded
3. Interact with feature (open map)
4. Verify dynamic chunk loads only then ✅

---

## Performance Monitoring Checklist

### Before Deployment

- [ ] Run bundle analyzer (`ANALYZE=true npm run build`)
- [ ] Check bundle size < 500KB
- [ ] Run Lighthouse audit (score > 85)
- [ ] Test on slow 3G network
- [ ] Test with CPU throttling
- [ ] Verify lazy loading works
- [ ] Check image optimization
- [ ] Test loading states
- [ ] Verify memoization working

### After Deployment

- [ ] Run Lighthouse on production URL
- [ ] Check real user metrics (RUM)
- [ ] Monitor Core Web Vitals
- [ ] Check error rates
- [ ] Verify CDN cache hit rates

---

## Common Performance Issues

### Issue: Large Bundle Size

**Symptoms:**

- Bundle > 500KB
- Slow initial load

**Solutions:**

1. Check for duplicate dependencies
2. Ensure code splitting working
3. Add more dynamic imports
4. Use bundle analyzer to find culprits

### Issue: Slow Component Renders

**Symptoms:**

- Long render times in Profiler
- Janky scrolling

**Solutions:**

1. Add React.memo to components
2. Use useCallback for event handlers
3. Use useMemo for expensive calculations
4. Check for unnecessary re-renders

### Issue: Poor Lighthouse Score

**Symptoms:**

- Performance score < 85
- High TBT or LCP

**Solutions:**

1. Optimize images (use next/image)
2. Add lazy loading
3. Reduce JavaScript bundle
4. Optimize fonts
5. Add loading states

---

## Tools Reference

### Essential Tools

1. **Chrome DevTools**
   - Performance tab
   - Network tab
   - Lighthouse tab

2. **React DevTools**
   - Profiler
   - Components tree

3. **Next.js Bundle Analyzer**
   - `@next/bundle-analyzer`
   - Interactive visualization

4. **Lighthouse CI**
   - Automated audits
   - Regression detection

### Optional Tools

1. **WebPageTest**
   - Real-world testing
   - Multiple locations
   - Various devices

2. **SpeedCurve**
   - Continuous monitoring
   - Historical data
   - Competitive analysis

3. **Calibre**
   - Performance budgets
   - Team collaboration
   - Automated alerts

---

## Performance Budgets

Set performance budgets to prevent regressions:

```json
{
  "budgets": [
    {
      "resourceSizes": [
        { "resourceType": "script", "budget": 500 },
        { "resourceType": "image", "budget": 300 },
        { "resourceType": "font", "budget": 100 },
        { "resourceType": "total", "budget": 1000 }
      ]
    },
    {
      "timings": [
        { "metric": "interactive", "budget": 3800 },
        { "metric": "first-contentful-paint", "budget": 1800 },
        { "metric": "largest-contentful-paint", "budget": 2500 }
      ]
    }
  ]
}
```

---

## Continuous Monitoring

### GitHub Actions Workflow

Create `.github/workflows/performance.yml`:

```yaml
name: Performance Audit

on: [pull_request]

jobs:
  lighthouse:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: actions/setup-node@v2
      - run: npm install
      - run: npm run build
      - uses: treosh/lighthouse-ci-action@v9
        with:
          urls: |
            http://localhost:3000/en
          configPath: './lighthouserc.js'
```

---

## Results Tracking

### Performance Log Template

Keep a log of performance metrics:

| Date       | Page     | FCP  | LCP  | TTI  | TBT   | CLS  | Score |
| ---------- | -------- | ---- | ---- | ---- | ----- | ---- | ----- |
| 2025-12-29 | Home     | 1.2s | 2.0s | 3.1s | 180ms | 0.05 | 92    |
| 2025-12-29 | Listings | 1.4s | 2.3s | 3.5s | 220ms | 0.08 | 88    |
| 2025-12-29 | Checkout | 1.6s | 2.1s | 3.3s | 200ms | 0.06 | 90    |

### Regression Alerts

Set up alerts for:

- Bundle size increases > 10%
- Lighthouse score drops > 5 points
- LCP increases > 500ms
- CLS increases > 0.05

---

**Last Updated**: 2025-12-29
**Maintained By**: Development Team
