# Fallback Data Strategy

**Date:** 2025-12-17
**Status:** ✅ Documented
**Scope:** How the application handles missing CMS content

## Overview

The Go Adventure frontend implements a **graceful degradation** strategy where content can come from three sources, in order of priority:

1. **CMS Content** (Filament Admin) - Primary source
2. **Translation Files** (i18n) - UI text and labels
3. **Hardcoded Fallbacks** - Last resort for development/demo

This ensures the site always works, even if CMS content hasn't been created yet.

---

## Data Source Hierarchy

```
┌─────────────────────────────────────┐
│     1. CMS Content (Filament)       │
│  ✅ Editable by content managers    │
│  ✅ Multilingual support           │
│  ✅ Version controlled in DB       │
└──────────────┬──────────────────────┘
               │ If not found ↓
┌─────────────────────────────────────┐
│   2. Translation Files (i18n)       │
│  ✅ UI labels and static text      │
│  ✅ Git-controlled                 │
│  ✅ Developer-friendly             │
└──────────────┬──────────────────────┘
               │ If not found ↓
┌─────────────────────────────────────┐
│   3. Hardcoded Fallbacks            │
│  ⚠️  Development/demo only          │
│  ⚠️  Should be replaced with CMS    │
│  ✅ Ensures site never breaks      │
└─────────────────────────────────────┘
```

---

## Implementation by Content Type

### 1. Page Content (Homepage, etc.)

**Strategy:** CMS-first with component fallback

**File:** `apps/web/src/app/[locale]/page.tsx`

```typescript
export default async function HomePage({ params }: HomePageProps) {
  const { locale } = params;

  // Try to fetch CMS page
  try {
    const page = await getPageByCode('HOME', locale);

    if (page && page.blocks && page.blocks.length > 0) {
      // ✅ Render CMS content
      return (
        <MainLayout locale={locale}>
          <BlockRenderer blocks={page.blocks} />
        </MainLayout>
      );
    }
  } catch (error) {
    console.warn('Could not fetch HOME page from CMS, using fallback');
  }

  // ⚠️ Fallback to hardcoded sections
  return (
    <MainLayout locale={locale}>
      <HeroSection locale={locale} />
      <CategoriesGridSection locale={locale} />
      <PromoBannerSection locale={locale} />
      {/* ...more sections */}
    </MainLayout>
  );
}
```

**Why:** Allows development to continue without CMS setup, production uses CMS.

### 2. Blog Content

**Strategy:** API-first with static data fallback

**File:** `apps/web/src/components/home/BlogSection.tsx`

```typescript
const fallbackPosts = [
  {
    id: 1,
    title: 'Hidden Gems of Tunisia',
    slug: 'hidden-gems-tunisia',
    excerpt: 'Discover the lesser-known treasures...',
    // ...
  },
  // More posts
];

export function BlogSection({ locale }: BlogSectionProps) {
  const [posts, setPosts] = useState(fallbackPosts);

  useEffect(() => {
    // Try to fetch from API
    getFeaturedBlogPosts(3, locale)
      .then(response => {
        if (response.data && response.data.length > 0) {
          setPosts(response.data); // ✅ Use CMS content
        }
      })
      .catch(() => {
        // ⚠️ Keep using fallback
        console.warn('Using fallback blog posts');
      });
  }, [locale]);

  return (
    <section>
      {posts.map(post => <BlogCard key={post.id} {...post} />)}
    </section>
  );
}
```

**Why:** Blog posts are dynamic content that should come from CMS, but fallback ensures demo works.

### 3. UI Labels & Text

**Strategy:** i18n-only (no fallback)

**File:** Any component using `useTranslations`

```typescript
export function CategoriesGridSection({ locale }: Props) {
  const t = useTranslations('home');

  return (
    <section>
      <h2>{t('categories_title')}</h2>  {/* ✅ Always from i18n */}
      <p>{t('categories_subtitle')}</p>
    </section>
  );
}
```

**Translation Files:**

- `apps/web/messages/en.json` (English)
- `apps/web/messages/fr.json` (French)

**Why:** UI text is development-controlled and should not be in CMS.

### 4. Dynamic Data (Listings, Tours)

**Strategy:** API-only (no fallback)

**File:** `apps/web/src/app/[locale]/listings/page.tsx`

```typescript
export default async function ListingsPage() {
  // Always fetch from API
  const listings = await getListings({ locale });

  if (!listings || listings.length === 0) {
    return <EmptyState />;  // ✅ Show empty state, don't fake data
  }

  return <ListingGrid listings={listings} />;
}
```

**Why:** Real business data should never be faked. Show empty states instead.

---

## Content Type Classification

### ✅ Must Come from CMS

Content that business users need to edit:

- **Page Blocks** (homepage sections via flexible content)
- **Blog Posts** (articles, guides)
- **Promotional Banners** (events, offers)
- **Tours/Listings** (activity content)
- **Media Assets** (images, videos)

**Fallback:** Hardcoded demo content for development only

### ✅ Must Come from i18n

Content that developers control:

- **UI Labels** ("Search", "Book Now", "Loading...")
- **Validation Messages** ("Email is required")
- **Error Messages** ("Something went wrong")
- **Button Text** ("Submit", "Cancel")
- **Form Labels** ("First Name", "Email Address")

**Fallback:** None - missing keys show as `[missing: key_name]`

### ❌ Never Hardcoded

Content that should always be dynamic:

- **User Data** (bookings, profiles)
- **Live Pricing** (tour costs)
- **Availability** (booking slots)
- **Reviews** (user feedback)
- **Search Results**

**Fallback:** None - show loading states or empty states

---

## Fallback Data Locations

### Hardcoded Sections (Development Only)

| File                                                     | Content             | Should Be CMS?                   |
| -------------------------------------------------------- | ------------------- | -------------------------------- |
| `apps/web/src/components/home/CategoriesGridSection.tsx` | Category data       | ✅ Yes - via CategoriesGridBlock |
| `apps/web/src/components/home/DestinationsBentoGrid.tsx` | Destination data    | ✅ Yes - needs DestinationsBlock |
| `apps/web/src/components/home/BlogSection.tsx`           | Sample blog posts   | ✅ Yes - via BlogPost API        |
| `apps/web/src/data/blog-posts.ts`                        | Blog post fallbacks | ✅ Yes - only for dev            |

### Translation Files (Permanent)

| File                        | Content         | Purpose    |
| --------------------------- | --------------- | ---------- |
| `apps/web/messages/en.json` | English UI text | Production |
| `apps/web/messages/fr.json` | French UI text  | Production |

**Note:** These are NOT fallbacks - they are the primary source for UI text.

---

## Environment-Based Strategy

### Development (`NODE_ENV=development`)

- ✅ Use fallback data liberally
- ✅ Log when fallbacks are used
- ✅ Show warnings in console
- ✅ Allow missing CMS content

```typescript
if (process.env.NODE_ENV === 'development') {
  console.warn('Using fallback content for:', componentName);
}
```

### Production (`NODE_ENV=production`)

- ⚠️ Log errors but don't crash
- ✅ Use fallbacks only for non-critical content
- ❌ Never fake business data
- ✅ Show empty states for missing content

```typescript
if (!cmsContent) {
  // Log to monitoring service
  logger.warn('Missing CMS content', { page, locale });

  // Use fallback if acceptable
  if (isCritical) {
    return <ErrorState />;
  }
  return <FallbackContent />;
}
```

---

## Migration Path

### Phase 1: Development (Current)

- ✅ Hardcoded fallbacks in components
- ✅ CMS blocks defined but optional
- ✅ Site works without Filament setup

### Phase 2: Staging

- ⚠️ Create all CMS content in Filament
- ⚠️ Test CMS → Frontend rendering
- ⚠️ Verify translations load correctly
- ⚠️ Remove fallback warnings

### Phase 3: Production

- ❌ Remove hardcoded category/destination data
- ❌ Remove fallback blog posts
- ✅ Keep i18n translations (permanent)
- ✅ Keep empty state components
- ⚠️ Add monitoring for missing CMS content

---

## Code Examples

### Good: CMS with Fallback

```typescript
export default async function Component() {
  let content;

  try {
    content = await getCMSContent();
  } catch (error) {
    content = FALLBACK_CONTENT; // ✅ Graceful degradation
  }

  return <Display content={content} />;
}
```

### Good: i18n (No Fallback Needed)

```typescript
export function Component() {
  const t = useTranslations('namespace');

  return <h1>{t('title')}</h1>; // ✅ Always works
}
```

### Bad: Hardcoded English

```typescript
export function Component() {
  return <h1>Welcome to Go Adventure</h1>; // ❌ Not translated
}
```

### Bad: Fake Business Data

```typescript
export function Component() {
  const listings = fakeListings(); // ❌ Never fake real data
  return <ListingGrid listings={listings} />;
}
```

---

## Monitoring & Alerts

### Metrics to Track

1. **CMS Fallback Rate**
   - How often fallbacks are used in production
   - Target: < 1% of page loads

2. **Missing Translation Keys**
   - Log when `[missing: key]` appears
   - Target: 0 missing keys

3. **API Failures**
   - Track CMS API response times
   - Alert on > 5% error rate

### Implementation

```typescript
// apps/web/src/lib/monitoring.ts
export function trackFallbackUsage(component: string, reason: string) {
  analytics.track('cms_fallback_used', {
    component,
    reason,
    timestamp: new Date().toISOString(),
  });
}

// Usage
try {
  content = await getCMSContent();
} catch (error) {
  trackFallbackUsage('HomePage', 'API timeout');
  content = FALLBACK_CONTENT;
}
```

---

## Summary

### ✅ Completed

- Documented three-tier fallback strategy
- Classified content types (CMS, i18n, dynamic)
- Listed all fallback locations
- Defined migration path to production

### Key Principles

1. **CMS-first** for editable content
2. **i18n-only** for UI text
3. **Never fake** business data
4. **Graceful degradation** always

### Phase 3.4 Status

**✅ Complete** - Fallback data strategy fully documented.
