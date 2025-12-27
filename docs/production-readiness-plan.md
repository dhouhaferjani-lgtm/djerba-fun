# Go Adventure - Production Readiness Plan

**Created**: December 24, 2025
**Based on**: QA Report from Playwright Testing
**Estimated Total Effort**: 2-3 developer days (~20-24 hours)

---

## Table of Contents

1. [Phase 0: Critical Fixes (P0)](#phase-0-critical-fixes-p0)
2. [Phase 1: High Priority - Translations (P1)](#phase-1-high-priority---translations-p1)
3. [Phase 2: Medium Priority - UX & Errors (P2)](#phase-2-medium-priority---ux--errors-p2)
4. [Phase 3: Low Priority - Polish (P3)](#phase-3-low-priority---polish-p3)
5. [Phase 4: Testing & Validation](#phase-4-testing--validation)
6. [Phase 5: Deployment Preparation](#phase-5-deployment-preparation)

---

## Phase 0: Critical Fixes (P0)

**Estimated Time**: 4-6 hours
**Must Complete Before**: Any production deployment

### CRITICAL-001: Fix Pricing Display Bug

**Issue**: Tour price shows €0.00 instead of €38.00 in booking wizard

**Root Cause Investigation**:

1. Check if listings have correct pricing in database
2. Verify AvailabilitySlot has base_price populated
3. Verify BookingHold includes slot pricing
4. Check frontend pricing calculation logic

**Files to Investigate**:

```
Backend:
- apps/laravel-api/database/seeders/ListingSeeder.php
- apps/laravel-api/database/seeders/AvailabilitySeeder.php
- apps/laravel-api/app/Models/AvailabilitySlot.php
- apps/laravel-api/app/Http/Resources/AvailabilitySlotResource.php
- apps/laravel-api/app/Http/Resources/BookingHoldResource.php

Frontend:
- apps/web/src/components/booking/BookingWizard.tsx
- apps/web/src/components/availability/GuestSelector.tsx
- apps/web/src/components/availability/TimeSlotPicker.tsx
```

**Action Plan**:

1. **Verify Database Pricing** (30 min)

   ```bash
   # Check listings table
   psql -d go_adventure -c "SELECT id, title, pricing FROM listings LIMIT 5;"

   # Check availability_slots table
   psql -d go_adventure -c "SELECT id, listing_id, base_price, currency FROM availability_slots LIMIT 5;"
   ```

2. **Fix Seeder if Needed** (1 hour)
   - Update `AvailabilitySeeder.php` to set base_price from listing pricing
   - Re-run seeder: `php artisan db:seed --class=AvailabilitySeeder`

3. **Verify API Response** (30 min)

   ```bash
   # Check if slot includes pricing
   curl http://localhost:8000/api/v1/listings/[slug]/availability?month=2025-12

   # Check hold response
   curl http://localhost:8000/api/v1/holds/[hold-id]
   ```

4. **Fix Frontend Pricing Display** (2 hours)
   - Update GuestSelector to use correct price from slot
   - Verify pricing calculation in BookingWizard
   - Add fallback for missing prices
   - Test with multiple listings

5. **Add Pricing Validation** (1 hour)
   - Add backend validation: price must be > 0
   - Add frontend warning if price is 0
   - Add unit tests for pricing calculations

**Acceptance Criteria**:

- ✅ All listings show correct price per person
- ✅ Total price calculates correctly based on guest count
- ✅ Price displays in correct currency (EUR/TND)
- ✅ No €0.00 or "Free" labels unless intentional
- ✅ Tests pass for pricing calculations

---

## Phase 1: High Priority - Translations (P1)

**Estimated Time**: 16-24 hours
**Must Complete Before**: Production launch

### Translation Coverage Target: 100%

**Current Coverage**: ~65%
**Gap**: ~35% of UI remains untranslated

---

### HIGH-001: Complete Homepage Translations

**Estimated Time**: 4 hours

**Files to Update**:

- `apps/web/messages/fr.json`

**Missing Translation Keys**:

```json
{
  "home": {
    "features": {
      "sustainable_title": "Voyage Durable",
      "sustainable_desc": "Aventures écoresponsables qui protègent notre planète",
      "authentic_title": "Expériences Authentiques",
      "authentic_desc": "Connectez-vous aux cultures et traditions locales",
      "epic_title": "Aventures Épiques",
      "epic_desc": "Voyages inoubliables dans des paysages à couper le souffle"
    },
    "upcoming": {
      "view_details": "Voir les Détails →",
      "view_all": "Tout Voir"
    },
    "event_of_year": {
      "badge": "Événement de l'Année",
      "learn_more": "En Savoir Plus",
      "register_now": "S'Inscrire Maintenant"
    },
    "categories": {
      "trail_running": "Course en Sentier",
      "hiking_trekking": "Randonnée & Trekking",
      "cycling_tours": "Tours à Vélo",
      "cultural_tours": "Tours Culturels",
      "packages_count": "{count} Forfaits"
    },
    "blog": {
      "read_more": "Lire la Suite →"
    },
    "travel_tip": "Astuce de Voyage : La meilleure période pour visiter le Sahara est d'octobre à avril"
  }
}
```

**Action Steps**:

1. Add all missing keys to fr.json
2. Verify translation quality with native speaker
3. Test homepage in French
4. Take screenshots for comparison

---

### HIGH-002: Listings Page Translations

**Estimated Time**: 2 hours

**Missing Keys**:

```json
{
  "listings": {
    "page_title": "Tours & Activités",
    "results_count": "{count} expériences trouvées",
    "filters": "Filtres",
    "sort_by": "Trier par",
    "no_results": "Aucune expérience trouvée",
    "clear_filters": "Effacer les Filtres"
  }
}
```

---

### HIGH-003: Listing Detail Page Translations

**Estimated Time**: 4 hours

**Missing Section Headings**:

```json
{
  "listing": {
    "about_title": "À Propos de Cette Expérience",
    "highlights_title": "Points Forts de l'Expérience",
    "route_itinerary_title": "Itinéraire & Parcours",
    "trail_map_tab": "Carte du Sentier",
    "itinerary_tab": "Itinéraire",
    "whats_included_title": "Inclus",
    "not_included_title": "Non Inclus",
    "requirements_title": "Exigences Importantes",
    "safety_title": "Sécurité",
    "accessibility_title": "Accessibilité",
    "faq_title": "Questions Fréquentes"
  }
}
```

**FAQ Translations** (Consider CMS approach):

```json
{
  "listing": {
    "faq": {
      "best_time_question": "Quelle est la meilleure période de l'année pour ce trek ?",
      "experience_question": "Ai-je besoin d'expérience en randonnée ?",
      "what_to_bring_question": "Que dois-je apporter ?",
      "bad_weather_question": "Que se passe-t-il en cas de mauvais temps ?"
    }
  }
}
```

**Note**: For dynamic FAQ content from database, consider:

1. Adding `faq_translations` JSON column to listings table
2. OR creating separate `listing_faqs` table with translatable content
3. OR using CMS for content management

---

### HIGH-004: Booking Wizard Calendar Translations

**Estimated Time**: 3 hours

**Implementation Strategy**: Use `date-fns` for locale-aware formatting

**Files to Update**:

- `apps/web/src/components/availability/AvailabilityCalendar.tsx`
- `package.json` (add date-fns dependency if not present)

**Code Changes**:

```typescript
import { format } from 'date-fns';
import { fr, enUS } from 'date-fns/locale';
import { useLocale } from 'next-intl';

export function AvailabilityCalendar({ ... }) {
  const locale = useLocale();
  const dateLocale = locale === 'fr' ? fr : enUS;

  // Format month/year
  const monthYearText = format(currentDate, 'MMMM yyyy', { locale: dateLocale });

  // Format day names
  const dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map((_, i) =>
    format(addDays(startOfWeek(new Date()), i), 'EEE', { locale: dateLocale })
  );

  // ... rest of component
}
```

**Translation Keys for Buttons**:

```json
{
  "calendar": {
    "previous_month": "Mois précédent",
    "next_month": "Mois suivant",
    "select_date": "Sélectionner une date",
    "availability_legend": "Légende de disponibilité",
    "available": "Disponible",
    "limited": "Places limitées",
    "unavailable": "Non disponible"
  }
}
```

---

### HIGH-005: Navigation Cart Translation

**Estimated Time**: 30 minutes

**Missing Key**:

```json
{
  "nav": {
    "cart": "Panier ({count} articles)",
    "cart_empty": "Panier vide"
  }
}
```

**File to Update**:

- `apps/web/src/components/layout/Header.tsx`

---

### Translation Infrastructure Improvements

**Estimated Time**: 4 hours

**1. Create Translation Coverage Checker** (2 hours)

Create: `scripts/check-translations.js`

```javascript
#!/usr/bin/env node
const fs = require('fs');
const path = require('path');

const enPath = path.join(__dirname, '../apps/web/messages/en.json');
const frPath = path.join(__dirname, '../apps/web/messages/fr.json');

const enMessages = JSON.parse(fs.readFileSync(enPath, 'utf-8'));
const frMessages = JSON.parse(fs.readFileSync(frPath, 'utf-8'));

function flattenKeys(obj, prefix = '') {
  let keys = [];
  for (const [key, value] of Object.entries(obj)) {
    const fullKey = prefix ? `${prefix}.${key}` : key;
    if (typeof value === 'object' && value !== null) {
      keys = keys.concat(flattenKeys(value, fullKey));
    } else {
      keys.push(fullKey);
    }
  }
  return keys;
}

const enKeys = new Set(flattenKeys(enMessages));
const frKeys = new Set(flattenKeys(frMessages));

const missingInFr = [...enKeys].filter((k) => !frKeys.has(k));
const extraInFr = [...frKeys].filter((k) => !enKeys.has(k));

console.log('Translation Coverage Report\n');
console.log(`Total English keys: ${enKeys.size}`);
console.log(`Total French keys: ${frKeys.size}`);
console.log(`Coverage: ${((frKeys.size / enKeys.size) * 100).toFixed(1)}%\n`);

if (missingInFr.length > 0) {
  console.log(`Missing in French (${missingInFr.length}):`);
  missingInFr.forEach((k) => console.log(`  - ${k}`));
  console.log();
}

if (extraInFr.length > 0) {
  console.log(`Extra in French (${extraInFr.length}) - may be deprecated:`);
  extraInFr.forEach((k) => console.log(`  - ${k}`));
}

process.exit(missingInFr.length > 0 ? 1 : 0);
```

**Add to package.json**:

```json
{
  "scripts": {
    "i18n:check": "node scripts/check-translations.js"
  }
}
```

**2. Add CI Translation Check** (1 hour)

Create: `.github/workflows/translations.yml`

```yaml
name: Translation Coverage

on: [pull_request]

jobs:
  check-translations:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: '20'
      - name: Check translation coverage
        run: npm run i18n:check
```

**3. Document Translation Strategy** (1 hour)

Create: `docs/translation-guide.md`

```markdown
# Translation Guide

## Strategy

- **UI Elements**: Always translate (buttons, labels, navigation)
- **Content Headings**: Always translate
- **Dynamic Content**: Translate via CMS/database
- **Legal Text**: Consult legal team before translating
- **Technical Terms**: Keep in English if standard (e.g., "API", "OAuth")

## Process

1. Add English key to `apps/web/messages/en.json`
2. Add French translation to `apps/web/messages/fr.json`
3. Use `useTranslations()` hook in components
4. Run `pnpm i18n:check` to verify coverage
5. Test in both languages before committing

## Naming Conventions

- Use dot notation: `section.subsection.key`
- Keep keys semantic: `listing.about_title` not `listing.title1`
- Group related keys together
```

---

## Phase 2: Medium Priority - UX & Errors (P2)

**Estimated Time**: 8-12 hours

---

### MED-001: Fix Console Errors

**Estimated Time**: 4 hours

#### 2.1: Handle Auth 401 Gracefully (1.5 hours)

**File**: `apps/web/src/lib/api/client.ts`

**Current Issue**: Unauthenticated /auth/me calls show 401 errors

**Fix**:

```typescript
export const authApi = {
  me: async () => {
    try {
      const response = await fetchApi('/auth/me');
      return response.data;
    } catch (error) {
      // Suppress 401 errors for unauthenticated users
      if (error?.response?.status === 401) {
        return null; // Not logged in, expected behavior
      }
      throw error; // Re-throw other errors
    }
  },
};
```

**File**: `apps/web/src/lib/contexts/AuthContext.tsx`

```typescript
const {
  data: user,
  isLoading,
  error,
} = useQuery({
  queryKey: ['currentUser'],
  queryFn: authApi.me,
  retry: false, // Don't retry 401s
  staleTime: 5 * 60 * 1000, // 5 minutes
});

// Don't log errors for expected 401s
useEffect(() => {
  if (error && error?.response?.status !== 401) {
    console.error('Auth error:', error);
  }
}, [error]);
```

#### 2.2: Add Missing Manifest Icons (1 hour)

**Files to Create**:

```
apps/web/public/icon-192.png
apps/web/public/icon-512.png
apps/web/public/apple-touch-icon.png
```

**Tool**: Use an existing logo and generate icons

```bash
# Using ImageMagick (if available)
convert apps/web/public/logo.png -resize 192x192 apps/web/public/icon-192.png
convert apps/web/public/logo.png -resize 512x512 apps/web/public/icon-512.png
convert apps/web/public/logo.png -resize 180x180 apps/web/public/apple-touch-icon.png
```

**Update**: `apps/web/src/app/manifest.ts`

```typescript
export default function manifest(): MetadataRoute.Manifest {
  return {
    name: 'Go Adventure',
    short_name: 'Go Adventure',
    description: 'Discover and book unique tours, activities, and events',
    start_url: '/',
    display: 'standalone',
    background_color: '#ffffff',
    theme_color: '#0D642E',
    icons: [
      {
        src: '/icon-192.png',
        sizes: '192x192',
        type: 'image/png',
      },
      {
        src: '/icon-512.png',
        sizes: '512x512',
        type: 'image/png',
      },
      {
        src: '/apple-touch-icon.png',
        sizes: '180x180',
        type: 'image/png',
      },
    ],
  };
}
```

#### 2.3: Fix Image Component Warnings (1.5 hours)

**Issue**: Next.js Image "fill" prop warnings

**Files to Audit**:

```bash
grep -r "fill" apps/web/src/components --include="*.tsx"
```

**Fix Pattern**:

```tsx
// BEFORE (warning)
<Image src={image} fill alt="..." />

// AFTER (fixed)
<Image
  src={image}
  fill
  alt="..."
  sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
  className="object-cover"
/>
```

**Files Likely Needing Updates**:

- `apps/web/src/components/listings/ListingCard.tsx`
- `apps/web/src/components/listings/ListingGallery.tsx`
- Any component using hero images

---

### MED-002: Content Consistency

**Estimated Time**: 2 hours

#### 2.1: Standardize Contact Email (30 min)

**Decision**: Choose one email for all languages

**Recommendation**: `hello@goadventure.tn` (more friendly than "info")

**Files to Update**:

```
apps/web/messages/en.json
apps/web/messages/fr.json
```

**Change**:

```json
{
  "footer": {
    "contact_email": "hello@goadventure.tn"
  }
}
```

#### 2.2: Standardize Address (30 min)

**Decision**: Use full address in all languages

**Files to Update**:

```
apps/web/messages/fr.json
```

**Change**:

```json
{
  "footer": {
    "address": "15 Avenue Habib Bourguiba, Tunis 1000, Tunisie"
  }
}
```

#### 2.3: Review Mixed Language Content (1 hour)

**Task**: Audit all pages for inconsistent translations

**Checklist**:

- [ ] Homepage features section
- [ ] Listing cards (verify titles translate)
- [ ] Blog section (verify "Read More" consistency)
- [ ] Category labels
- [ ] Button text throughout site

---

## Phase 3: Low Priority - Polish (P3)

**Estimated Time**: 4-6 hours

---

### LOW-001: Fix Page Title Duplication

**Estimated Time**: 1 hour

**Issue**: "Trek au Sommet | Go Adventure | Go Adventure"

**File**: `apps/web/src/app/[locale]/[location]/[slug]/page.tsx`

**Current Code** (likely):

```typescript
export async function generateMetadata({ params }): Promise<Metadata> {
  return {
    title: `${listing.title} | Go Adventure`,
    // ...
  };
}
```

**Root Layout** (likely adding separator):

```typescript
// apps/web/src/app/layout.tsx
export const metadata: Metadata = {
  title: {
    template: '%s | Go Adventure',
    default: 'Go Adventure',
  },
};
```

**Fix**: Remove duplicate separator from page

```typescript
export async function generateMetadata({ params }): Promise<Metadata> {
  return {
    title: listing.title, // Let template handle separator
    // ...
  };
}
```

---

### LOW-002: Performance Optimization

**Estimated Time**: 2 hours

#### 2.1: Enable Static Generation for Homepage (1 hour)

**File**: `apps/web/src/app/[locale]/page.tsx`

**Add**:

```typescript
export const revalidate = 3600; // Revalidate every hour

export async function generateStaticParams() {
  return [{ locale: 'en' }, { locale: 'fr' }];
}
```

#### 2.2: Optimize Images (1 hour)

**Current**: Hotlinking Unsplash images
**Better**: Download and host locally

**Script to Download Images**:

```bash
# Create image directory
mkdir -p apps/web/public/images/listings

# Download sample images (replace with actual URLs)
# Then update image references in code
```

**Update Seeder**:

```php
// Instead of:
'image_url' => 'https://images.unsplash.com/...'

// Use:
'image_url' => '/images/listings/kroumirie-mountains.jpg'
```

---

### LOW-003: Accessibility Improvements

**Estimated Time**: 2 hours

#### 3.1: Add ARIA Labels to Interactive Elements (1 hour)

**Files to Update**:

- `apps/web/src/components/availability/AvailabilityCalendar.tsx`
- `apps/web/src/components/maps/MapContainer.tsx`

**Changes**:

```tsx
// Calendar navigation
<button
  onClick={previousMonth}
  aria-label={t('calendar.previous_month')}
  className="..."
>
  <ChevronLeft />
</button>

// Map container
<div
  role="region"
  aria-label={t('listing.map_region')}
  className="..."
>
  <MapContainer {...props} />
</div>
```

#### 3.2: Keyboard Navigation for Map (1 hour)

**File**: `apps/web/src/components/maps/MapContainer.tsx`

**Add**:

```tsx
<div
  tabIndex={0}
  onKeyDown={(e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      // Handle interaction
    }
  }}
>
  {/* Map markers */}
</div>
```

---

## Phase 4: Testing & Validation

**Estimated Time**: 4 hours

---

### 4.1: Manual Testing Checklist (2 hours)

**Test in Both Languages (EN & FR)**:

#### Homepage

- [ ] All sections translated
- [ ] Features display correctly
- [ ] Categories show correct labels
- [ ] Blog "Read More" links work
- [ ] Navigation works
- [ ] Footer displays correctly

#### Listings Page

- [ ] Page title translated
- [ ] Results count shows correct number
- [ ] Filters work in both languages
- [ ] Listing cards display prices correctly
- [ ] Language toggle preserves filters

#### Listing Detail

- [ ] All section headings translated
- [ ] FAQ questions/answers display
- [ ] Map loads correctly
- [ ] Gallery works
- [ ] Pricing shows correct amount (not €0.00)

#### Booking Flow

- [ ] Calendar shows localized dates
- [ ] Time slots display
- [ ] Guest selection shows correct pricing
- [ ] Total calculates correctly
- [ ] Can complete booking

#### Edge Cases

- [ ] Test with 0 results search
- [ ] Test with missing images
- [ ] Test on slow network
- [ ] Test with JavaScript disabled (graceful degradation)

---

### 4.2: Automated Testing (2 hours)

**Create E2E Test Suite**:

Create: `apps/web/tests/e2e/booking-flow.spec.ts`

```typescript
import { test, expect } from '@playwright/test';

test.describe('Booking Flow', () => {
  test('should display correct pricing', async ({ page }) => {
    await page.goto('http://localhost:3000/en/ain-draham/kroumirie-mountains-summit-trek');

    // Click "Book Now"
    await page.click('text=Book Now');

    // Select date
    await page.click('[data-testid="calendar-next-month"]');
    await page.click('[data-date="2025-12-15"]');

    // Select time
    await page.click('[data-time="09:00"]');

    // Check pricing
    const priceText = await page.textContent('[data-testid="price-per-person"]');
    expect(priceText).not.toContain('€0.00');
    expect(priceText).toContain('€38.00');
  });

  test('should calculate total correctly', async ({ page }) => {
    // ... booking flow

    // Select 2 adults
    await page.fill('[data-testid="adults-count"]', '2');

    const total = await page.textContent('[data-testid="total-amount"]');
    expect(total).toContain('€76.00');
  });
});

test.describe('Translations', () => {
  test('should display French calendar', async ({ page }) => {
    await page.goto('http://localhost:3000/fr/listings');

    // Open booking modal
    await page.click('text=Réserver Maintenant');

    // Check month name is French
    const monthText = await page.textContent('[data-testid="calendar-month"]');
    expect(monthText).toMatch(
      /janvier|février|mars|avril|mai|juin|juillet|août|septembre|octobre|novembre|décembre/i
    );
  });
});
```

**Add to package.json**:

```json
{
  "scripts": {
    "test:e2e": "playwright test",
    "test:e2e:ui": "playwright test --ui"
  }
}
```

---

## Phase 5: Deployment Preparation

**Estimated Time**: 4 hours

---

### 5.1: Environment Configuration (1 hour)

**Create Production .env.example**:

```bash
# API
API_URL=https://api.goadventure.tn/api/v1
API_TIMEOUT=30000

# Frontend
NEXT_PUBLIC_API_URL=https://api.goadventure.tn/api/v1
NEXT_PUBLIC_SITE_URL=https://goadventure.tn
NEXT_PUBLIC_DEFAULT_LOCALE=en

# Analytics (if applicable)
NEXT_PUBLIC_GA_ID=
NEXT_PUBLIC_HOTJAR_ID=

# Sentry (error tracking)
NEXT_PUBLIC_SENTRY_DSN=
```

---

### 5.2: Build & Deploy Checklist (1 hour)

**Pre-Deployment Checklist**:

```markdown
## Backend

- [ ] Database migrations run successfully
- [ ] Seeders populate pricing correctly
- [ ] All API endpoints return correct data
- [ ] Rate limiting configured
- [ ] CORS configured for production domain
- [ ] Laravel Horizon running
- [ ] Queue workers running
- [ ] Scheduled jobs configured
- [ ] Error logging configured (Sentry/Bugsnag)
- [ ] SSL certificate installed
- [ ] Environment variables set

## Frontend

- [ ] Build succeeds: `pnpm build`
- [ ] No TypeScript errors
- [ ] No console errors in production build
- [ ] All images optimized
- [ ] All translations complete (100% coverage)
- [ ] Manifest icons present
- [ ] Sitemap generated
- [ ] robots.txt configured
- [ ] Analytics tracking active
- [ ] Error tracking active (Sentry)
- [ ] Environment variables set

## Testing

- [ ] All E2E tests pass
- [ ] Manual testing complete (both languages)
- [ ] Mobile responsive (test on real devices)
- [ ] Performance score >90 (Lighthouse)
- [ ] Accessibility score >90 (Lighthouse)
- [ ] SEO score >90 (Lighthouse)

## Security

- [ ] Security headers configured
- [ ] HTTPS enforced
- [ ] API keys rotated
- [ ] Sensitive data masked in logs
- [ ] Rate limiting active
```

---

### 5.3: Monitoring Setup (1 hour)

**Add Error Tracking**:

1. Install Sentry:

```bash
pnpm add @sentry/nextjs @sentry/node
```

2. Configure Sentry:

```typescript
// apps/web/instrumentation.ts
import * as Sentry from '@sentry/nextjs';

export function register() {
  if (process.env.NEXT_RUNTIME === 'nodejs') {
    Sentry.init({
      dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
      environment: process.env.NODE_ENV,
      tracesSampleRate: 0.1,
    });
  }
}
```

**Add Uptime Monitoring**:

- Configure UptimeRobot or similar
- Monitor: `/api/health`, homepage, API endpoints
- Alert on: >5 minute downtime, >500 error rate

---

### 5.4: Documentation (1 hour)

**Update README.md**:

````markdown
# Go Adventure - Tourism Marketplace

## Production Deployment

### Prerequisites

- Node.js 20+
- PHP 8.3+
- PostgreSQL 15+
- Redis 7+

### Environment Setup

1. Copy `.env.example` to `.env`
2. Configure database credentials
3. Set API URL and frontend URL
4. Configure mail settings

### Build

```bash
# Backend
cd apps/laravel-api
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Frontend
cd apps/web
pnpm install
pnpm build
```
````

### Deploy

[Add deployment instructions for your hosting platform]

## Translation Management

Run translation coverage check:

```bash
pnpm i18n:check
```

## Testing

Run E2E tests:

```bash
pnpm test:e2e
```

```

---

## Implementation Timeline

### Week 1: Critical + High Priority

**Day 1-2** (16 hours):
- [ ] Fix pricing bug (CRITICAL-001)
- [ ] Add missing homepage translations (HIGH-001)
- [ ] Add listing page translations (HIGH-002)

**Day 3-4** (16 hours):
- [ ] Complete listing detail translations (HIGH-003)
- [ ] Implement calendar localization (HIGH-004)
- [ ] Add navigation translations (HIGH-005)
- [ ] Build translation checker script

### Week 2: Medium Priority + Testing

**Day 5-6** (16 hours):
- [ ] Fix console errors (MED-001)
- [ ] Content consistency fixes (MED-002)
- [ ] Page title fix (LOW-001)
- [ ] Manual testing in both languages

**Day 7** (8 hours):
- [ ] Create E2E test suite
- [ ] Run full regression testing
- [ ] Performance optimization
- [ ] Deployment preparation

---

## Success Criteria

### Before Production Launch:

✅ **Critical**:
- All prices display correctly (no €0.00)
- Booking flow completes successfully
- Payment processing works

✅ **High Priority**:
- Translation coverage: 100%
- All UI elements translated in EN & FR
- Calendar shows localized dates
- No missing translation warnings in console

✅ **Medium Priority**:
- No 401/404 errors in console
- All manifest icons present
- Contact information consistent

✅ **Testing**:
- Manual testing passed for all critical flows
- E2E tests passing
- No regression bugs introduced

### Performance Targets:

- Lighthouse Performance: >90
- Lighthouse Accessibility: >90
- Lighthouse SEO: >90
- First Contentful Paint: <1.5s
- Time to Interactive: <3.5s

---

## Risk Assessment

### High Risk Items:

1. **Pricing Bug**: If not database-related, may require significant refactoring
   - **Mitigation**: Allocate buffer time for investigation

2. **Translation Volume**: 35% gaps = ~200+ translation keys
   - **Mitigation**: Use translation service or hire translator

3. **Dynamic Content Translation**: FAQs, descriptions need CMS strategy
   - **Mitigation**: Document limitation, plan for v1.1

### Medium Risk Items:

1. **Image Performance**: Hotlinking Unsplash may cause issues
   - **Mitigation**: Host images locally, add CDN

2. **Date Localization**: Library integration might have edge cases
   - **Mitigation**: Thorough testing of calendar component

---

## Post-Launch Monitoring

### First 48 Hours:

- Monitor error rates (target: <0.5%)
- Check translation coverage reports
- Monitor pricing accuracy
- Watch for user-reported bugs
- Check performance metrics

### First Week:

- Gather user feedback
- Monitor conversion rates
- Check booking completion rates
- Review analytics for pain points
- Plan iteration based on data

---

## Appendix: Quick Reference

### Files Most Likely to Need Changes:

**Translations**:
- `apps/web/messages/en.json`
- `apps/web/messages/fr.json`

**Pricing**:
- `apps/laravel-api/database/seeders/AvailabilitySeeder.php`
- `apps/web/src/components/availability/GuestSelector.tsx`
- `apps/web/src/components/booking/BookingWizard.tsx`

**Calendar**:
- `apps/web/src/components/availability/AvailabilityCalendar.tsx`

**Error Handling**:
- `apps/web/src/lib/api/client.ts`
- `apps/web/src/lib/contexts/AuthContext.tsx`

---

**Plan Created**: December 24, 2025
**Last Updated**: December 24, 2025
**Status**: Ready for Implementation
```
