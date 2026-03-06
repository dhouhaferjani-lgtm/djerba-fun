# Frontend Agent Instructions

> **Model**: Claude Sonnet 4.5
> **Scope**: Next.js 16 App Router, React 19 components, Tailwind styling
> **Reports to**: Orchestrator (Opus 4.5)

---

## 🎯 Your Responsibilities

1. Next.js pages and layouts (App Router)
2. React components following Atomic Design
3. Design system in `packages/ui`
4. API integration via generated SDK
5. Internationalization (French + English)
6. Map and elevation components (Leaflet)
7. Responsive, mobile-first styling
8. Vitest unit tests + Playwright E2E

---

## 🎨 Design System

### Brand Colors

```typescript
// packages/ui/src/tokens/colors.ts
export const colors = {
  primary: {
    50: '#e8f5e9',
    100: '#c8e6c9',
    200: '#a5d6a7',
    300: '#81c784',
    400: '#66bb6a',
    500: '#8BC34A', // Light green - primary light
    600: '#7cb342',
    700: '#689f38',
    800: '#0D642E', // Dark forest green - primary DEFAULT
    900: '#0a5025',
    950: '#063d1b',
  },
  secondary: {
    cream: '#f5f0d1',
    'cream-dark': '#e8e2bc',
    'cream-light': '#faf8e8',
  },
  neutral: {
    white: '#ffffff',
    50: '#fafafa',
    100: '#f5f5f5',
    200: '#e5e5e5',
    300: '#d4d4d4',
    400: '#a3a3a3',
    500: '#737373',
    600: '#525252',
    700: '#404040',
    800: '#262626',
    900: '#171717',
    950: '#0a0a0a',
  },
  // Semantic
  success: '#22c55e',
  warning: '#f59e0b',
  error: '#ef4444',
  info: '#3b82f6',
} as const;
```

### Typography

```typescript
// packages/ui/src/tokens/typography.ts
export const typography = {
  fontFamily: {
    sans: ['Inter', 'system-ui', 'sans-serif'],
    display: ['Poppins', 'system-ui', 'sans-serif'],
  },
  fontSize: {
    xs: ['0.75rem', { lineHeight: '1rem' }],
    sm: ['0.875rem', { lineHeight: '1.25rem' }],
    base: ['1rem', { lineHeight: '1.5rem' }],
    lg: ['1.125rem', { lineHeight: '1.75rem' }],
    xl: ['1.25rem', { lineHeight: '1.75rem' }],
    '2xl': ['1.5rem', { lineHeight: '2rem' }],
    '3xl': ['1.875rem', { lineHeight: '2.25rem' }],
    '4xl': ['2.25rem', { lineHeight: '2.5rem' }],
    '5xl': ['3rem', { lineHeight: '1' }],
  },
} as const;
```

### Tailwind Config Extension

```typescript
// apps/web/tailwind.config.ts
import { colors, typography } from '@djerba-fun/ui/tokens';

export default {
  content: ['./src/**/*.{js,ts,jsx,tsx}', '../../packages/ui/src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        primary: colors.primary,
        secondary: colors.secondary,
        ...colors.neutral,
      },
      fontFamily: typography.fontFamily,
    },
  },
};
```

---

## 📁 Directory Structure

```
apps/web/
├── src/
│   ├── app/
│   │   ├── [locale]/
│   │   │   ├── layout.tsx
│   │   │   ├── page.tsx
│   │   │   ├── listings/
│   │   │   │   ├── page.tsx
│   │   │   │   └── [slug]/
│   │   │   │       └── page.tsx
│   │   │   ├── bookings/
│   │   │   │   ├── page.tsx
│   │   │   │   └── [code]/
│   │   │   │       └── page.tsx
│   │   │   └── auth/
│   │   │       ├── login/
│   │   │       └── register/
│   │   └── api/
│   ├── components/
│   │   ├── atoms/
│   │   │   ├── Button/
│   │   │   ├── Input/
│   │   │   ├── Badge/
│   │   │   └── Icon/
│   │   ├── molecules/
│   │   │   ├── SearchBar/
│   │   │   ├── ListingCard/
│   │   │   ├── PriceDisplay/
│   │   │   └── RatingStars/
│   │   ├── organisms/
│   │   │   ├── Header/
│   │   │   ├── Footer/
│   │   │   ├── ListingGrid/
│   │   │   ├── BookingWizard/
│   │   │   └── MapView/
│   │   └── templates/
│   │       ├── MainLayout/
│   │       └── DashboardLayout/
│   ├── lib/
│   │   ├── api/
│   │   │   └── client.ts
│   │   ├── hooks/
│   │   └── utils/
│   ├── i18n/
│   │   ├── request.ts
│   │   └── routing.ts
│   └── styles/
│       └── globals.css
├── messages/
│   ├── en.json
│   └── fr.json
├── public/
└── tests/
    ├── unit/
    └── e2e/

packages/ui/
├── src/
│   ├── tokens/
│   │   ├── colors.ts
│   │   ├── typography.ts
│   │   └── spacing.ts
│   ├── components/
│   │   ├── Button/
│   │   │   ├── Button.tsx
│   │   │   ├── Button.test.tsx
│   │   │   └── index.ts
│   │   └── ...
│   └── index.ts
└── package.json
```

---

## 🧩 Atomic Design Components

### Atoms (Basic building blocks)

```tsx
// packages/ui/src/components/Button/Button.tsx
import { cva, type VariantProps } from 'class-variance-authority';
import { forwardRef } from 'react';
import { cn } from '../../utils/cn';

const buttonVariants = cva(
  'inline-flex items-center justify-center rounded-lg font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 disabled:pointer-events-none disabled:opacity-50',
  {
    variants: {
      variant: {
        primary: 'bg-primary-800 text-white hover:bg-primary-900',
        secondary: 'bg-primary-500 text-white hover:bg-primary-600',
        outline: 'border-2 border-primary-800 text-primary-800 hover:bg-primary-50',
        ghost: 'text-primary-800 hover:bg-primary-50',
        cream: 'bg-secondary-cream text-primary-800 hover:bg-secondary-cream-dark',
      },
      size: {
        sm: 'h-9 px-3 text-sm',
        md: 'h-11 px-4 text-base',
        lg: 'h-13 px-6 text-lg',
        icon: 'h-10 w-10',
      },
    },
    defaultVariants: {
      variant: 'primary',
      size: 'md',
    },
  }
);

interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>, VariantProps<typeof buttonVariants> {
  isLoading?: boolean;
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant, size, isLoading, children, ...props }, ref) => {
    return (
      <button
        ref={ref}
        className={cn(buttonVariants({ variant, size }), className)}
        disabled={isLoading || props.disabled}
        {...props}
      >
        {isLoading ? <span className="mr-2 animate-spin">⟳</span> : null}
        {children}
      </button>
    );
  }
);

Button.displayName = 'Button';
```

### Molecules (Component combinations)

```tsx
// apps/web/src/components/molecules/ListingCard/ListingCard.tsx
import Image from 'next/image';
import Link from 'next/link';
import { Badge, RatingStars } from '@djerba-fun/ui';
import { PriceDisplay } from '../PriceDisplay';
import type { ListingSummary } from '@djerba-fun/schemas';

interface ListingCardProps {
  listing: ListingSummary;
  locale: string;
}

export function ListingCard({ listing, locale }: ListingCardProps) {
  return (
    <Link
      href={`/${locale}/listings/${listing.slug}`}
      className="group block overflow-hidden rounded-xl bg-white shadow-md transition-all hover:shadow-xl"
    >
      <div className="relative aspect-[4/3] overflow-hidden">
        <Image
          src={listing.media[0]?.url ?? '/placeholder.jpg'}
          alt={listing.media[0]?.alt ?? listing.title}
          fill
          className="object-cover transition-transform group-hover:scale-105"
        />
        <Badge variant="cream" className="absolute left-3 top-3">
          {listing.serviceType}
        </Badge>
      </div>

      <div className="p-4">
        <div className="mb-2 flex items-center gap-2">
          <RatingStars rating={listing.rating} />
          <span className="text-sm text-neutral-500">({listing.reviewsCount})</span>
        </div>

        <h3 className="mb-1 font-display text-lg font-semibold text-neutral-900 line-clamp-2">
          {listing.title}
        </h3>

        <p className="mb-3 text-sm text-neutral-600">{listing.location.name}</p>

        <PriceDisplay from={listing.pricing.from} currency={listing.pricing.currency} />
      </div>
    </Link>
  );
}
```

### Organisms (Complex components)

```tsx
// apps/web/src/components/organisms/MapView/MapView.tsx
'use client';

import { useEffect, useRef } from 'react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import type { MapMarker, ItineraryStop } from '@djerba-fun/schemas';

interface MapViewProps {
  center: [number, number];
  zoom?: number;
  markers?: MapMarker[];
  itinerary?: ItineraryStop[];
  showRoute?: boolean;
  className?: string;
}

export function MapView({
  center,
  zoom = 13,
  markers = [],
  itinerary = [],
  showRoute = false,
  className,
}: MapViewProps) {
  const mapRef = useRef<HTMLDivElement>(null);
  const mapInstance = useRef<L.Map | null>(null);

  useEffect(() => {
    if (!mapRef.current || mapInstance.current) return;

    // Initialize map
    mapInstance.current = L.map(mapRef.current).setView(center, zoom);

    // Custom tile layer with brand styling
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors',
    }).addTo(mapInstance.current);

    return () => {
      mapInstance.current?.remove();
      mapInstance.current = null;
    };
  }, []);

  // Add markers
  useEffect(() => {
    if (!mapInstance.current) return;

    const markerGroup = L.layerGroup().addTo(mapInstance.current);

    markers.forEach((marker) => {
      const icon = createCustomIcon(marker.type);
      const m = L.marker([marker.lat, marker.lng], { icon })
        .addTo(markerGroup)
        .bindPopup(createPopupContent(marker));
    });

    // Add itinerary stops with connecting line
    if (showRoute && itinerary.length > 0) {
      const coords = itinerary.map((stop) => [stop.lat, stop.lng] as [number, number]);

      L.polyline(coords, {
        color: '#0D642E',
        weight: 3,
        opacity: 0.8,
        dashArray: '10, 10',
      }).addTo(markerGroup);

      itinerary.forEach((stop, index) => {
        const icon = createStopIcon(stop.stopType, index + 1);
        L.marker([stop.lat, stop.lng], { icon })
          .addTo(markerGroup)
          .bindPopup(createStopPopup(stop));
      });
    }

    return () => {
      markerGroup.clearLayers();
    };
  }, [markers, itinerary, showRoute]);

  return <div ref={mapRef} className={cn('h-[400px] w-full rounded-xl', className)} />;
}

// Custom marker icons matching brand
function createCustomIcon(type: string) {
  return L.divIcon({
    className: 'custom-marker',
    html: `
      <div class="w-8 h-8 bg-primary-800 rounded-full flex items-center justify-center text-white shadow-lg">
        ${getMarkerIcon(type)}
      </div>
    `,
    iconSize: [32, 32],
    iconAnchor: [16, 32],
  });
}

function createStopIcon(type: string, number: number) {
  const bgColor = type === 'start' ? '#0D642E' : type === 'end' ? '#8BC34A' : '#f5f0d1';
  const textColor = type === 'highlight' ? '#0D642E' : '#ffffff';

  return L.divIcon({
    className: 'stop-marker',
    html: `
      <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold shadow-lg"
           style="background-color: ${bgColor}; color: ${textColor};">
        ${number}
      </div>
    `,
    iconSize: [32, 32],
    iconAnchor: [16, 32],
  });
}
```

---

## 📈 Elevation Profile Component

```tsx
// apps/web/src/components/organisms/ElevationProfile/ElevationProfile.tsx
'use client';

import { useMemo } from 'react';
import { ResponsiveContainer, AreaChart, Area, XAxis, YAxis, Tooltip } from 'recharts';
import type { ElevationPoint } from '@djerba-fun/schemas';

interface ElevationProfileProps {
  points: ElevationPoint[];
  totalDistance: number; // in meters
  unit?: 'metric' | 'imperial';
  className?: string;
}

export function ElevationProfile({
  points,
  totalDistance,
  unit = 'metric',
  className,
}: ElevationProfileProps) {
  const data = useMemo(() => {
    return points.map((point) => ({
      distance:
        unit === 'metric'
          ? point.distance / 1000 // km
          : point.distance / 1609.34, // miles
      elevation:
        unit === 'metric'
          ? point.elevation // meters
          : point.elevation * 3.28084, // feet
    }));
  }, [points, unit]);

  const stats = useMemo(() => {
    const elevations = points.map((p) => p.elevation);
    let ascent = 0;
    let descent = 0;

    for (let i = 1; i < points.length; i++) {
      const diff = points[i].elevation - points[i - 1].elevation;
      if (diff > 0) ascent += diff;
      else descent += Math.abs(diff);
    }

    return {
      max: Math.max(...elevations),
      min: Math.min(...elevations),
      ascent,
      descent,
    };
  }, [points]);

  return (
    <div className={cn('rounded-xl bg-white p-4 shadow-md', className)}>
      <div className="mb-4 grid grid-cols-4 gap-4 text-center">
        <StatBox
          label={unit === 'metric' ? 'Max Alt.' : 'Max Elev.'}
          value={formatElevation(stats.max, unit)}
        />
        <StatBox
          label={unit === 'metric' ? 'Min Alt.' : 'Min Elev.'}
          value={formatElevation(stats.min, unit)}
        />
        <StatBox label="Ascent" value={formatElevation(stats.ascent, unit)} icon="↑" />
        <StatBox label="Descent" value={formatElevation(stats.descent, unit)} icon="↓" />
      </div>

      <ResponsiveContainer width="100%" height={200}>
        <AreaChart data={data}>
          <defs>
            <linearGradient id="elevationGradient" x1="0" y1="0" x2="0" y2="1">
              <stop offset="5%" stopColor="#8BC34A" stopOpacity={0.8} />
              <stop offset="95%" stopColor="#8BC34A" stopOpacity={0.1} />
            </linearGradient>
          </defs>
          <XAxis
            dataKey="distance"
            tickFormatter={(v) => `${v.toFixed(1)} ${unit === 'metric' ? 'km' : 'mi'}`}
            stroke="#737373"
          />
          <YAxis tickFormatter={(v) => `${v.toFixed(0)}`} stroke="#737373" />
          <Tooltip
            formatter={(value: number) => [formatElevation(value, unit), 'Elevation']}
            labelFormatter={(label) =>
              `Distance: ${label.toFixed(2)} ${unit === 'metric' ? 'km' : 'mi'}`
            }
          />
          <Area
            type="monotone"
            dataKey="elevation"
            stroke="#0D642E"
            strokeWidth={2}
            fill="url(#elevationGradient)"
          />
        </AreaChart>
      </ResponsiveContainer>
    </div>
  );
}

function StatBox({ label, value, icon }: { label: string; value: string; icon?: string }) {
  return (
    <div className="rounded-lg bg-secondary-cream p-2">
      <div className="text-xs text-neutral-600">{label}</div>
      <div className="font-display font-semibold text-primary-800">
        {icon && <span className="mr-1">{icon}</span>}
        {value}
      </div>
    </div>
  );
}

function formatElevation(meters: number, unit: 'metric' | 'imperial'): string {
  if (unit === 'imperial') {
    return `${Math.round(meters * 3.28084)} ft`;
  }
  return `${Math.round(meters)} m`;
}
```

---

## 🌐 Internationalization (i18n)

### Setup with next-intl

```typescript
// apps/web/src/i18n/routing.ts
import { defineRouting } from 'next-intl/routing';
import { createNavigation } from 'next-intl/navigation';

export const routing = defineRouting({
  locales: ['en', 'fr'],
  defaultLocale: 'en',
  localePrefix: 'always',
});

export const { Link, redirect, usePathname, useRouter } = createNavigation(routing);
```

### Message Files

```json
// messages/en.json
{
  "common": {
    "search": "Search",
    "book_now": "Book Now",
    "from": "From",
    "per_person": "per person",
    "reviews": "{count, plural, =0 {No reviews} =1 {1 review} other {# reviews}}"
  },
  "home": {
    "hero_title": "Discover Your Next Adventure",
    "hero_subtitle": "Explore unforgettable tours, events, and experiences",
    "search_placeholder": "Where do you want to go?"
  },
  "listing": {
    "highlights": "Highlights",
    "itinerary": "Itinerary",
    "included": "What's Included",
    "not_included": "Not Included",
    "meeting_point": "Meeting Point",
    "duration": "Duration",
    "difficulty": "Difficulty",
    "group_size": "Group Size"
  },
  "booking": {
    "select_date": "Select a Date",
    "travelers": "Travelers",
    "traveler_info": "Traveler Information",
    "payment": "Payment",
    "confirm": "Confirm Booking",
    "total": "Total"
  }
}
```

```json
// messages/fr.json
{
  "common": {
    "search": "Rechercher",
    "book_now": "Réserver",
    "from": "À partir de",
    "per_person": "par personne",
    "reviews": "{count, plural, =0 {Aucun avis} =1 {1 avis} other {# avis}}"
  },
  "home": {
    "hero_title": "Découvrez Votre Prochaine Aventure",
    "hero_subtitle": "Explorez des tours, événements et expériences inoubliables",
    "search_placeholder": "Où voulez-vous aller ?"
  },
  "listing": {
    "highlights": "Points Forts",
    "itinerary": "Itinéraire",
    "included": "Inclus",
    "not_included": "Non Inclus",
    "meeting_point": "Point de Rencontre",
    "duration": "Durée",
    "difficulty": "Difficulté",
    "group_size": "Taille du Groupe"
  },
  "booking": {
    "select_date": "Sélectionnez une Date",
    "travelers": "Voyageurs",
    "traveler_info": "Informations Voyageur",
    "payment": "Paiement",
    "confirm": "Confirmer la Réservation",
    "total": "Total"
  }
}
```

### Usage in Components

```tsx
import { useTranslations } from 'next-intl';

export function BookingButton() {
  const t = useTranslations('common');

  return <Button variant="primary">{t('book_now')}</Button>;
}
```

---

## 🔌 API Integration

### SDK Client Setup

```typescript
// apps/web/src/lib/api/client.ts
import { createClient } from '@djerba-fun/sdk';

export const api = createClient({
  baseUrl: process.env.NEXT_PUBLIC_API_URL!,
  getToken: async () => {
    // Get token from auth context or cookies
    return getAuthToken();
  },
});
```

### TanStack Query Hooks

```typescript
// apps/web/src/lib/hooks/useListings.ts
import { useQuery } from '@tanstack/react-query';
import { api } from '../api/client';
import type { ListingSearchParams } from '@djerba-fun/schemas';

export function useListings(params: ListingSearchParams) {
  return useQuery({
    queryKey: ['listings', params],
    queryFn: () => api.listings.search(params),
  });
}

export function useListing(slug: string) {
  return useQuery({
    queryKey: ['listing', slug],
    queryFn: () => api.listings.getBySlug(slug),
  });
}

export function useAvailability(listingId: string, params: AvailabilityParams) {
  return useQuery({
    queryKey: ['availability', listingId, params],
    queryFn: () => api.listings.getAvailability(listingId, params),
  });
}
```

---

## 🧪 Testing

### Unit Test Example (Vitest)

```typescript
// apps/web/tests/unit/components/ListingCard.test.tsx
import { render, screen } from '@testing-library/react';
import { ListingCard } from '@/components/molecules/ListingCard';
import { mockListing } from '../mocks/listings';

describe('ListingCard', () => {
  it('renders listing title', () => {
    render(<ListingCard listing={mockListing} locale="en" />);
    expect(screen.getByText(mockListing.title)).toBeInTheDocument();
  });

  it('displays rating and review count', () => {
    render(<ListingCard listing={mockListing} locale="en" />);
    expect(screen.getByText(`(${mockListing.reviewsCount})`)).toBeInTheDocument();
  });

  it('shows price from', () => {
    render(<ListingCard listing={mockListing} locale="en" />);
    expect(screen.getByText(/From/)).toBeInTheDocument();
  });
});
```

### E2E Test Example (Playwright)

```typescript
// apps/web/tests/e2e/booking.spec.ts
import { test, expect } from '@playwright/test';

test.describe('Booking Flow', () => {
  test('complete booking as guest', async ({ page }) => {
    // Navigate to listing
    await page.goto('/en/listings/atlas-mountain-hike');

    // Select date
    await page.click('[data-testid="availability-calendar"]');
    await page.click('text=15'); // Select 15th

    // Click book
    await page.click('text=Book Now');

    // Fill traveler info
    await page.fill('[name="travelers.0.firstName"]', 'John');
    await page.fill('[name="travelers.0.lastName"]', 'Doe');
    await page.fill('[name="travelers.0.email"]', 'john@example.com');

    // Continue to payment
    await page.click('text=Continue');

    // Select mock payment
    await page.click('[data-testid="payment-mock"]');

    // Confirm
    await page.click('text=Confirm Booking');

    // Should see confirmation
    await expect(page.locator('[data-testid="booking-confirmed"]')).toBeVisible();
    await expect(page.locator('text=GA-')).toBeVisible(); // Booking code
  });
});
```

---

## ✅ Checklist Before Completion

For each component/page:

- [ ] Responsive design (mobile-first)
- [ ] Uses design system tokens (no hardcoded colors)
- [ ] Proper TypeScript types from schemas
- [ ] Loading/error states
- [ ] Translations for both en/fr
- [ ] Unit tests
- [ ] Accessibility (ARIA labels, keyboard nav)
- [ ] Dark mode compatible (if applicable)

---

## 🚫 What NOT To Do

1. **Never define types locally** - import from @djerba-fun/schemas
2. **Never hardcode colors** - use design tokens
3. **Never skip loading states** - always show skeletons/spinners
4. **Never skip error boundaries** - wrap async components
5. **Never inline translations** - use message keys
6. **Never fetch in components directly** - use hooks with TanStack Query
7. **Never skip alt text** - all images need accessible descriptions
8. **Never break atomic design** - atoms don't import molecules
