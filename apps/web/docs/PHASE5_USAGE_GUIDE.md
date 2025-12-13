# Phase 5 Features - Usage Guide

This guide explains how to use the SEO and performance features implemented in Phase 5.

## JSON-LD Structured Data

### Basic Usage

```tsx
import { JsonLd } from '@/components/seo/JsonLd';

// In your page component
<JsonLd
  data={{
    '@type': 'Product',
    name: 'Mountain Hiking Tour',
    description: 'Experience breathtaking mountain views',
    image: '/images/tour.jpg',
    offers: {
      '@type': 'Offer',
      price: 99.99,
      priceCurrency: 'USD',
    },
  }}
/>;
```

### Convenience Components

Use type-specific components for better developer experience:

```tsx
import { ProductJsonLd, EventJsonLd, BreadcrumbJsonLd } from '@/components/seo/JsonLd';

// Product schema
<ProductJsonLd
  name="Mountain Hiking Tour"
  description="Experience breathtaking mountain views"
  image="/images/tour.jpg"
  offers={{
    '@type': 'Offer',
    price: 99.99,
    priceCurrency: 'USD',
    availability: 'https://schema.org/InStock',
  }}
  aggregateRating={{
    '@type': 'AggregateRating',
    ratingValue: 4.8,
    reviewCount: 156,
  }}
/>

// Event schema
<EventJsonLd
  name="Summer Music Festival"
  description="Annual outdoor music event"
  startDate="2025-07-15T18:00:00"
  endDate="2025-07-15T23:00:00"
  location={{
    '@type': 'Place',
    name: 'Central Park',
    address: 'New York, NY',
  }}
  offers={{
    '@type': 'Offer',
    price: 50,
    priceCurrency: 'USD',
    url: 'https://goadventure.com/en/events/summer-festival',
  }}
/>

// Breadcrumb navigation
<BreadcrumbJsonLd
  itemListElement={[
    { '@type': 'ListItem', position: 1, name: 'Home', item: 'https://goadventure.com' },
    { '@type': 'ListItem', position: 2, name: 'Tours', item: 'https://goadventure.com/listings' },
    { '@type': 'ListItem', position: 3, name: 'Mountain Hike' },
  ]}
/>
```

### Example: Listing Detail Page

```tsx
import { ProductJsonLd } from '@/components/seo/JsonLd';
import type { Metadata } from 'next';

// Dynamic metadata
export async function generateMetadata({ params }): Promise<Metadata> {
  const listing = await fetchListing(params.slug);

  return {
    title: listing.title,
    description: listing.description,
    openGraph: {
      title: listing.title,
      description: listing.description,
      images: [{ url: listing.media[0]?.url }],
    },
  };
}

export default function ListingPage({ listing }) {
  return (
    <>
      <ProductJsonLd
        name={listing.title}
        description={listing.description}
        image={listing.media.map((m) => m.url)}
        brand={{ '@type': 'Brand', name: listing.vendor.name }}
        offers={{
          '@type': 'Offer',
          price: listing.pricing.base,
          priceCurrency: listing.pricing.currency,
          availability: 'https://schema.org/InStock',
        }}
        aggregateRating={
          listing.rating && {
            '@type': 'AggregateRating',
            ratingValue: listing.rating,
            reviewCount: listing.reviewsCount,
          }
        }
      />

      {/* Your page content */}
    </>
  );
}
```

## Analytics

### Tracking Events

```tsx
import { trackEvent, ecommerce } from '@/lib/analytics';

// Generic event
trackEvent('button_clicked', {
  button_name: 'book_now',
  page: 'listing_detail',
});

// E-commerce events
ecommerce.viewListing('listing-123', 'tour', 99.99);
ecommerce.startBooking('listing-123', 'tour', 99.99);
ecommerce.completeBooking('booking-456', 'listing-123', 99.99, 'USD');

// Search
ecommerce.search('hiking tours', {
  location: 'mountains',
  date_range: '2025-07-15_to_2025-07-22',
});
```

### Page Views

```tsx
'use client';

import { useEffect } from 'react';
import { usePathname } from 'next/navigation';
import { trackPageView } from '@/lib/analytics';

export function AnalyticsPageView() {
  const pathname = usePathname();

  useEffect(() => {
    trackPageView(pathname);
  }, [pathname]);

  return null;
}
```

### User Identification

```tsx
import { identifyUser } from '@/lib/analytics';

// After user login
identifyUser(user.id, {
  email: user.email,
  role: user.role,
  created_at: user.createdAt,
});
```

### Integrating Analytics Providers

To integrate with real analytics providers, modify `/src/lib/analytics.ts`:

#### Example: Plausible Analytics

```tsx
// Add to layout or _app
<script defer data-domain="goadventure.com" src="https://plausible.io/js/script.js"></script>;

// In analytics.ts, uncomment:
if (window.plausible) {
  window.plausible(event, { props: properties });
}
```

#### Example: PostHog

```tsx
// Install
pnpm add posthog-js

// Initialize in layout
import posthog from 'posthog-js';

if (typeof window !== 'undefined') {
  posthog.init('YOUR_API_KEY', {
    api_host: 'https://app.posthog.com'
  });
}

// In analytics.ts, uncomment:
if (window.posthog) {
  window.posthog.capture(event, properties);
}
```

## Dynamic Metadata

### Page-Level Metadata

```tsx
import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Search Tours',
  description: 'Find the perfect tour for your next adventure',
  openGraph: {
    title: 'Search Tours',
    description: 'Find the perfect tour for your next adventure',
    images: [{ url: '/og-search.png' }],
  },
};
```

### Dynamic Metadata with generateMetadata

```tsx
export async function generateMetadata({ params, searchParams }): Promise<Metadata> {
  const listing = await fetchListing(params.slug);

  return {
    title: listing.title,
    description: listing.description,
    keywords: [...listing.tags, listing.location.city],
    openGraph: {
      type: 'product',
      title: listing.title,
      description: listing.description,
      images: listing.media.map((m) => ({
        url: m.url,
        width: 1200,
        height: 630,
        alt: m.alt,
      })),
    },
    twitter: {
      card: 'summary_large_image',
      title: listing.title,
      description: listing.description,
      images: [listing.media[0]?.url],
    },
    alternates: {
      canonical: `/en/listings/${listing.slug}`,
      languages: {
        en: `/en/listings/${listing.slug}`,
        fr: `/fr/listings/${listing.slug}`,
      },
    },
  };
}
```

## Error Handling

### Custom Error Boundaries

```tsx
'use client';

import { useEffect } from 'react';
import { Button } from '@go-adventure/ui';

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    // Log to error tracking service
    console.error('Error:', error);
  }, [error]);

  return (
    <div>
      <h2>Something went wrong!</h2>
      <Button onClick={reset}>Try again</Button>
    </div>
  );
}
```

## Performance Monitoring

### Core Web Vitals

```tsx
// In app/layout.tsx or a component
'use client';

import { useReportWebVitals } from 'next/web-vitals';
import { performance } from '@/lib/analytics';

export function WebVitals() {
  useReportWebVitals((metric) => {
    performance.trackWebVitals(metric);
  });

  return null;
}
```

## Bundle Analysis

Run bundle analysis to identify optimization opportunities:

```bash
pnpm analyze
```

This will:

1. Build the production bundle
2. Open an interactive treemap visualization
3. Show chunk sizes and dependencies

## PWA Installation

The manifest is automatically generated at `/manifest.webmanifest`. To test PWA installation:

1. Build the app: `pnpm build`
2. Start production server: `pnpm start`
3. Open in Chrome DevTools > Application > Manifest
4. Verify manifest loads correctly
5. Test "Install App" functionality

## SEO Verification

### Test Sitemap

```
http://localhost:3000/sitemap.xml
```

### Test Robots.txt

```
http://localhost:3000/robots.txt
```

### Test Structured Data

1. Visit a page with JSON-LD
2. View page source
3. Find `<script type="application/ld+json">`
4. Copy JSON and validate at: https://validator.schema.org/

### Google Rich Results Test

1. Deploy your site
2. Visit: https://search.google.com/test/rich-results
3. Enter your URL
4. Verify structured data is recognized

## Best Practices

### SEO

- Always provide unique, descriptive titles
- Keep descriptions between 150-160 characters
- Use JSON-LD for all listing and event pages
- Include breadcrumbs for navigation
- Use semantic HTML elements

### Performance

- Use Next.js Image component for all images
- Lazy load non-critical components
- Minimize client-side JavaScript
- Use static generation where possible
- Implement proper loading states

### Analytics

- Track meaningful user interactions
- Don't track PII without consent
- Use consistent event naming
- Include relevant context in properties
- Test analytics in development mode

### Error Handling

- Provide clear, actionable error messages
- Never expose sensitive error details
- Always offer a way to recover or go back
- Log errors for debugging
- Test error boundaries thoroughly

## Production Checklist

Before deploying to production:

- [ ] Set `NEXT_PUBLIC_SITE_URL` environment variable
- [ ] Configure analytics provider
- [ ] Set up error tracking (e.g., Sentry)
- [ ] Generate actual icon files (192px, 384px, 512px)
- [ ] Create Open Graph images
- [ ] Run Lighthouse audit (target: 90+ all categories)
- [ ] Test on multiple devices and browsers
- [ ] Verify sitemap generates correctly
- [ ] Test PWA installation
- [ ] Validate structured data
- [ ] Check robots.txt allows necessary crawling
- [ ] Test error pages
- [ ] Verify loading states work
- [ ] Test all analytics events

## Troubleshooting

### Metadata Not Appearing

- Check that metadata is exported from page/layout
- Verify it's not being overridden by parent layout
- Clear Next.js cache: `rm -rf .next`

### Structured Data Not Validating

- Use schema.org validator
- Check for required properties
- Ensure proper nesting of objects
- Verify date formats (ISO 8601)

### Images Not Loading

- Check remote patterns in next.config.ts
- Verify image URLs are accessible
- Check CORS headers if using external CDN

### Analytics Not Tracking

- Check console in development mode
- Verify analytics provider is initialized
- Check for ad blockers
- Ensure events are called client-side only
