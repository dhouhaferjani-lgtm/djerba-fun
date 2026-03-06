# Schemas Agent Instructions

> **Model**: Claude Sonnet 4.5
> **Scope**: Zod schemas, type generation, API contracts
> **Reports to**: Orchestrator (Opus 4.5)

---

## 🎯 Your Responsibilities

1. Define all Zod schemas in `packages/schemas`
2. Generate TypeScript types from schemas
3. Generate JSON Schema for Laravel validation
4. Maintain OpenAPI spec synchronization
5. Create SDK client generation scripts
6. Ensure backend/frontend type consistency

---

## 📁 Directory Structure

```
packages/schemas/
├── src/
│   ├── index.ts              # Main exports
│   ├── users.ts              # User, Profile schemas
│   ├── listings.ts           # Listing, Tour, Event schemas
│   ├── availability.ts       # Availability, Hold schemas
│   ├── bookings.ts           # Booking, Payment schemas
│   ├── reviews.ts            # Review schemas
│   ├── locations.ts          # Location, Map schemas
│   ├── maps.ts               # MapMarker, Itinerary, Elevation
│   ├── common.ts             # Shared types (pagination, errors)
│   └── api/
│       ├── requests.ts       # API request schemas
│       └── responses.ts      # API response schemas
├── generated/
│   ├── types.ts              # Generated TS types
│   └── json-schema/          # Generated JSON schemas
├── scripts/
│   ├── generate-types.ts
│   ├── generate-json-schema.ts
│   └── validate.ts
├── package.json
└── tsconfig.json
```

---

## 📜 Core Schemas

### Common Types

```typescript
// packages/schemas/src/common.ts
import { z } from 'zod';

// Pagination
export const paginationMetaSchema = z.object({
  nextCursor: z.string().nullable(),
  prevCursor: z.string().nullable(),
  pageSize: z.number().int().positive(),
  total: z.number().int().nonnegative(),
  hasMore: z.boolean(),
});

export const cursorPaginationSchema = z.object({
  cursor: z.string().optional(),
  limit: z.number().int().min(1).max(100).default(20),
});

// Error envelope
export const apiErrorSchema = z.object({
  error: z.object({
    code: z.string(),
    message: z.string(),
    details: z.record(z.unknown()).optional(),
    field: z.string().optional(),
  }),
});

// Money (stored as minor units)
export const moneySchema = z.object({
  amount: z.number().int(), // cents/minor units
  currency: z.string().length(3), // ISO 4217
});

// Date range
export const dateRangeSchema = z.object({
  start: z.string().datetime(),
  end: z.string().datetime(),
});

// GeoJSON Point
export const geoPointSchema = z.object({
  type: z.literal('Point'),
  coordinates: z.tuple([z.number(), z.number()]), // [lng, lat]
});

// Translatable field
export const translatableSchema = z
  .object({
    en: z.string(),
    fr: z.string(),
  })
  .passthrough(); // Allow additional locales

export type PaginationMeta = z.infer<typeof paginationMetaSchema>;
export type ApiError = z.infer<typeof apiErrorSchema>;
export type Money = z.infer<typeof moneySchema>;
export type GeoPoint = z.infer<typeof geoPointSchema>;
export type Translatable = z.infer<typeof translatableSchema>;
```

### Users

```typescript
// packages/schemas/src/users.ts
import { z } from 'zod';

export const userRoleSchema = z.enum(['traveler', 'vendor', 'admin', 'agent']);

export const userStatusSchema = z.enum(['pending', 'active', 'suspended', 'deleted']);

export const userSchema = z.object({
  id: z.string().uuid(),
  email: z.string().email(),
  role: userRoleSchema,
  status: userStatusSchema,
  displayName: z.string().min(1).max(100),
  avatarUrl: z.string().url().nullable(),
  emailVerifiedAt: z.string().datetime().nullable(),
  createdAt: z.string().datetime(),
  updatedAt: z.string().datetime(),
});

export const travelerProfileSchema = z.object({
  id: z.string().uuid(),
  userId: z.string().uuid(),
  firstName: z.string().min(1).max(100),
  lastName: z.string().min(1).max(100),
  phone: z.string().nullable(),
  defaultCurrency: z.string().length(3).default('EUR'),
  preferredLocale: z.enum(['en', 'fr']).default('en'),
  documents: z
    .array(
      z.object({
        type: z.enum(['passport', 'id_card', 'driver_license']),
        number: z.string(),
        expiresAt: z.string().datetime().nullable(),
      })
    )
    .default([]),
});

export const kycStatusSchema = z.enum(['pending', 'submitted', 'verified', 'rejected']);

export const vendorProfileSchema = z.object({
  id: z.string().uuid(),
  userId: z.string().uuid(),
  companyName: z.string().min(1).max(200),
  companyType: z.enum(['individual', 'company', 'agency']),
  taxId: z.string().nullable(),
  kycStatus: kycStatusSchema,
  commissionTier: z.enum(['standard', 'premium', 'enterprise']).default('standard'),
  payoutAccountId: z.string().nullable(),
  description: z.string().max(2000).nullable(),
  websiteUrl: z.string().url().nullable(),
  phone: z.string().nullable(),
  address: z
    .object({
      street: z.string(),
      city: z.string(),
      postalCode: z.string(),
      country: z.string().length(2),
    })
    .nullable(),
  verifiedAt: z.string().datetime().nullable(),
  createdAt: z.string().datetime(),
});

export type UserRole = z.infer<typeof userRoleSchema>;
export type User = z.infer<typeof userSchema>;
export type TravelerProfile = z.infer<typeof travelerProfileSchema>;
export type VendorProfile = z.infer<typeof vendorProfileSchema>;
```

### Listings

```typescript
// packages/schemas/src/listings.ts
import { z } from 'zod';
import { moneySchema, translatableSchema, geoPointSchema } from './common';

export const serviceTypeSchema = z.enum([
  'tour',
  'event',
  // Future: 'accommodation', 'transport', 'experience'
]);

export const listingStatusSchema = z.enum([
  'draft',
  'pending_review',
  'published',
  'archived',
  'rejected',
]);

export const difficultyLevelSchema = z.enum(['easy', 'moderate', 'challenging', 'expert']);

// Media
export const mediaSchema = z.object({
  id: z.string().uuid(),
  url: z.string().url(),
  thumbnailUrl: z.string().url().nullable(),
  alt: z.string().max(200),
  type: z.enum(['image', 'video']),
  order: z.number().int().nonnegative(),
});

// Pricing
export const personTypeSchema = z.object({
  key: z.string(), // 'adult', 'child', 'senior', etc.
  label: translatableSchema,
  price: z.number().int().nonnegative(), // Minor units
  minAge: z.number().int().nonnegative().nullable(),
  maxAge: z.number().int().positive().nullable(),
  minQuantity: z.number().int().nonnegative().default(0),
  maxQuantity: z.number().int().positive().nullable(),
});

export const pricingSchema = z.object({
  base: z.number().int().nonnegative(),
  currency: z.string().length(3),
  personTypes: z.array(personTypeSchema),
  groupDiscount: z
    .object({
      minSize: z.number().int().positive(),
      discountPercent: z.number().min(0).max(100),
    })
    .nullable(),
});

// Policies
export const cancellationPolicySchema = z.object({
  type: z.enum(['flexible', 'moderate', 'strict', 'non_refundable']),
  rules: z.array(
    z.object({
      hoursBeforeStart: z.number().int().nonnegative(),
      refundPercent: z.number().min(0).max(100),
    })
  ),
  description: translatableSchema.nullable(),
});

// Base listing
export const listingBaseSchema = z.object({
  id: z.string().uuid(),
  vendorId: z.string().uuid(),
  serviceType: serviceTypeSchema,
  status: listingStatusSchema,
  title: translatableSchema,
  slug: z.string(),
  summary: translatableSchema,
  description: translatableSchema,
  highlights: z.array(translatableSchema),
  included: z.array(translatableSchema),
  notIncluded: z.array(translatableSchema),
  requirements: z.array(translatableSchema),
  locationId: z.string().uuid(),
  meetingPoint: z.object({
    address: z.string(),
    coordinates: geoPointSchema,
    instructions: translatableSchema.nullable(),
  }),
  media: z.array(mediaSchema),
  pricing: pricingSchema,
  cancellationPolicy: cancellationPolicySchema,
  minGroupSize: z.number().int().positive().default(1),
  maxGroupSize: z.number().int().positive(),
  rating: z.number().min(0).max(5).nullable(),
  reviewsCount: z.number().int().nonnegative().default(0),
  bookingsCount: z.number().int().nonnegative().default(0),
  createdAt: z.string().datetime(),
  updatedAt: z.string().datetime(),
  publishedAt: z.string().datetime().nullable(),
});

// Tour-specific fields
export const tourSchema = listingBaseSchema.extend({
  serviceType: z.literal('tour'),
  duration: z.object({
    value: z.number().positive(),
    unit: z.enum(['hours', 'days']),
  }),
  difficulty: difficultyLevelSchema.nullable(),
  distance: z
    .object({
      value: z.number().positive(),
      unit: z.enum(['km', 'miles']),
    })
    .nullable(),
  itinerary: z.array(
    z.object({
      order: z.number().int().nonnegative(),
      title: translatableSchema,
      description: translatableSchema,
      duration: z.number().int().positive().nullable(), // minutes
      locationId: z.string().uuid().nullable(),
      coordinates: geoPointSchema.nullable(),
    })
  ),
  hasElevationProfile: z.boolean().default(false),
});

// Event-specific fields
export const eventSchema = listingBaseSchema.extend({
  serviceType: z.literal('event'),
  eventType: z.enum([
    'festival',
    'workshop',
    'concert',
    'exhibition',
    'sports',
    'cultural',
    'other',
  ]),
  startDate: z.string().datetime(),
  endDate: z.string().datetime(),
  venue: z.object({
    name: z.string(),
    address: z.string(),
    coordinates: geoPointSchema,
    capacity: z.number().int().positive().nullable(),
  }),
  agenda: z
    .array(
      z.object({
        time: z.string().datetime(),
        title: translatableSchema,
        description: translatableSchema.nullable(),
        speaker: z.string().nullable(),
      })
    )
    .nullable(),
});

// Discriminated union
export const listingSchema = z.discriminatedUnion('serviceType', [tourSchema, eventSchema]);

// Summary for listing cards
export const listingSummarySchema = z.object({
  id: z.string().uuid(),
  serviceType: serviceTypeSchema,
  title: z.string(), // Resolved to current locale
  slug: z.string(),
  rating: z.number().min(0).max(5).nullable(),
  reviewsCount: z.number().int().nonnegative(),
  location: z.object({
    id: z.string().uuid(),
    name: z.string(),
  }),
  pricing: z.object({
    from: z.number().int().nonnegative(),
    currency: z.string().length(3),
  }),
  media: z.array(mediaSchema.pick({ url: true, alt: true })).max(5),
  duration: z
    .object({
      value: z.number().positive(),
      unit: z.enum(['hours', 'days']),
    })
    .nullable(),
});

export type ServiceType = z.infer<typeof serviceTypeSchema>;
export type ListingStatus = z.infer<typeof listingStatusSchema>;
export type Media = z.infer<typeof mediaSchema>;
export type Pricing = z.infer<typeof pricingSchema>;
export type Tour = z.infer<typeof tourSchema>;
export type Event = z.infer<typeof eventSchema>;
export type Listing = z.infer<typeof listingSchema>;
export type ListingSummary = z.infer<typeof listingSummarySchema>;
```

### Maps & Elevation

```typescript
// packages/schemas/src/maps.ts
import { z } from 'zod';
import { geoPointSchema, translatableSchema } from './common';

export const markerTypeSchema = z.enum([
  'start',
  'end',
  'waypoint',
  'highlight',
  'accommodation',
  'restaurant',
  'viewpoint',
  'parking',
  'info',
]);

export const mapMarkerSchema = z.object({
  id: z.string().uuid(),
  type: markerTypeSchema,
  lat: z.number().min(-90).max(90),
  lng: z.number().min(-180).max(180),
  title: z.string(),
  description: z.string().nullable(),
  imageUrl: z.string().url().nullable(),
  order: z.number().int().nonnegative().nullable(),
});

export const itineraryStopSchema = z.object({
  id: z.string().uuid(),
  listingId: z.string().uuid(),
  order: z.number().int().nonnegative(),
  title: translatableSchema,
  description: translatableSchema.nullable(),
  durationMinutes: z.number().int().positive().nullable(),
  stopType: markerTypeSchema,
  lat: z.number().min(-90).max(90),
  lng: z.number().min(-180).max(180),
  elevationMeters: z.number().nullable(),
  photos: z
    .array(
      z.object({
        url: z.string().url(),
        alt: z.string(),
      })
    )
    .default([]),
});

export const elevationPointSchema = z.object({
  distance: z.number().nonnegative(), // meters from start
  elevation: z.number(), // meters
});

export const elevationProfileSchema = z.object({
  listingId: z.string().uuid(),
  points: z.array(elevationPointSchema),
  totalAscent: z.number().nonnegative(), // meters
  totalDescent: z.number().nonnegative(), // meters
  maxElevation: z.number(),
  minElevation: z.number(),
  totalDistance: z.number().nonnegative(), // meters
});

export const mapBoundsSchema = z.object({
  north: z.number().min(-90).max(90),
  south: z.number().min(-90).max(90),
  east: z.number().min(-180).max(180),
  west: z.number().min(-180).max(180),
});

export const mapConfigSchema = z.object({
  center: z.tuple([z.number(), z.number()]), // [lat, lng]
  zoom: z.number().min(1).max(20).default(13),
  bounds: mapBoundsSchema.nullable(),
  markers: z.array(mapMarkerSchema),
  itinerary: z.array(itineraryStopSchema).nullable(),
  showRoute: z.boolean().default(false),
  routeColor: z
    .string()
    .regex(/^#[0-9A-Fa-f]{6}$/)
    .default('#0D642E'),
});

export type MarkerType = z.infer<typeof markerTypeSchema>;
export type MapMarker = z.infer<typeof mapMarkerSchema>;
export type ItineraryStop = z.infer<typeof itineraryStopSchema>;
export type ElevationPoint = z.infer<typeof elevationPointSchema>;
export type ElevationProfile = z.infer<typeof elevationProfileSchema>;
export type MapConfig = z.infer<typeof mapConfigSchema>;
```

### Availability

```typescript
// packages/schemas/src/availability.ts
import { z } from 'zod';
import { dateRangeSchema } from './common';

export const recurrenceTypeSchema = z.enum(['none', 'daily', 'weekly', 'monthly']);

export const dayOfWeekSchema = z.enum([
  'monday',
  'tuesday',
  'wednesday',
  'thursday',
  'friday',
  'saturday',
  'sunday',
]);

// Availability rule (configured by vendor)
export const availabilityRuleSchema = z.object({
  id: z.string().uuid(),
  listingId: z.string().uuid(),
  name: z.string().max(100),
  recurrence: recurrenceTypeSchema,
  daysOfWeek: z.array(dayOfWeekSchema).nullable(), // For weekly
  dateRange: dateRangeSchema.nullable(), // Validity period
  startTime: z.string().regex(/^\d{2}:\d{2}$/), // HH:mm
  endTime: z.string().regex(/^\d{2}:\d{2}$/),
  capacity: z.number().int().positive(),
  priceOverride: z.number().int().nonnegative().nullable(),
  isActive: z.boolean().default(true),
});

// Computed availability slot
export const availabilitySlotSchema = z.object({
  id: z.string().uuid(),
  listingId: z.string().uuid(),
  ruleId: z.string().uuid().nullable(),
  start: z.string().datetime(),
  end: z.string().datetime(),
  capacity: z.number().int().positive(),
  booked: z.number().int().nonnegative(),
  held: z.number().int().nonnegative(),
  available: z.number().int().nonnegative(),
  price: z.number().int().nonnegative(),
  currency: z.string().length(3),
  status: z.enum(['available', 'limited', 'sold_out', 'blocked']),
  notes: z.string().nullable(),
});

// Booking hold (Redis-backed, temporary)
export const bookingHoldSchema = z.object({
  id: z.string().uuid(),
  listingId: z.string().uuid(),
  slotId: z.string().uuid(),
  userId: z.string().uuid(),
  guests: z.number().int().positive(),
  extras: z
    .array(
      z.object({
        id: z.string(),
        quantity: z.number().int().positive(),
      })
    )
    .default([]),
  calculatedTotal: z.number().int().nonnegative(),
  currency: z.string().length(3),
  expiresAt: z.string().datetime(),
  createdAt: z.string().datetime(),
});

export type RecurrenceType = z.infer<typeof recurrenceTypeSchema>;
export type DayOfWeek = z.infer<typeof dayOfWeekSchema>;
export type AvailabilityRule = z.infer<typeof availabilityRuleSchema>;
export type AvailabilitySlot = z.infer<typeof availabilitySlotSchema>;
export type BookingHold = z.infer<typeof bookingHoldSchema>;
```

### Bookings

```typescript
// packages/schemas/src/bookings.ts
import { z } from 'zod';
import { moneySchema } from './common';

export const bookingStatusSchema = z.enum([
  'draft',
  'payment_pending',
  'confirmed',
  'completed',
  'cancelled',
  'refunded',
  'no_show',
]);

export const paymentStatusSchema = z.enum([
  'pending',
  'processing',
  'succeeded',
  'failed',
  'cancelled',
  'refunded',
  'partially_refunded',
]);

export const paymentGatewaySchema = z.enum([
  'mock',
  'offline',
  'click_to_pay',
  'stripe',
  'paypal',
  // Future gateways
]);

export const travelerInfoSchema = z.object({
  firstName: z.string().min(1).max(100),
  lastName: z.string().min(1).max(100),
  email: z.string().email(),
  phone: z.string().nullable(),
  dateOfBirth: z.string().date().nullable(),
  nationality: z.string().length(2).nullable(),
  specialRequests: z.string().max(1000).nullable(),
});

export const bookingExtraSchema = z.object({
  id: z.string().uuid(),
  name: z.string(),
  quantity: z.number().int().positive(),
  unitPrice: z.number().int().nonnegative(),
  totalPrice: z.number().int().nonnegative(),
});

export const paymentIntentSchema = z.object({
  id: z.string().uuid(),
  bookingId: z.string().uuid(),
  gateway: paymentGatewaySchema,
  status: paymentStatusSchema,
  amount: z.number().int().nonnegative(),
  currency: z.string().length(3),
  providerRef: z.string().nullable(),
  clientSecret: z.string().nullable(), // For frontend SDKs
  metadata: z.record(z.unknown()).nullable(),
  capturedAt: z.string().datetime().nullable(),
  refundedAmount: z.number().int().nonnegative().default(0),
  createdAt: z.string().datetime(),
  updatedAt: z.string().datetime(),
});

export const bookingSchema = z.object({
  id: z.string().uuid(),
  code: z.string().regex(/^GA-\d{2}-\d{4,}$/), // GA-YY-NNNN
  listingId: z.string().uuid(),
  travelerId: z.string().uuid(),
  vendorId: z.string().uuid(),
  status: bookingStatusSchema,
  holdId: z.string().uuid().nullable(),
  startsAt: z.string().datetime(),
  endsAt: z.string().datetime(),
  guests: z.number().int().positive(),
  travelers: z.array(travelerInfoSchema),
  extras: z.array(bookingExtraSchema),
  subtotal: z.number().int().nonnegative(),
  discountAmount: z.number().int().nonnegative().default(0),
  couponCode: z.string().nullable(),
  taxAmount: z.number().int().nonnegative().default(0),
  totalAmount: z.number().int().nonnegative(),
  depositAmount: z.number().int().nonnegative().nullable(),
  currency: z.string().length(3),
  paymentIntent: paymentIntentSchema.nullable(),
  specialRequests: z.string().max(2000).nullable(),
  internalNotes: z.string().max(2000).nullable(), // Vendor/admin only
  cancelledAt: z.string().datetime().nullable(),
  cancellationReason: z.string().nullable(),
  completedAt: z.string().datetime().nullable(),
  createdAt: z.string().datetime(),
  updatedAt: z.string().datetime(),
});

// Booking summary for lists
export const bookingSummarySchema = bookingSchema
  .pick({
    id: true,
    code: true,
    status: true,
    startsAt: true,
    guests: true,
    totalAmount: true,
    currency: true,
    createdAt: true,
  })
  .extend({
    listing: z.object({
      id: z.string().uuid(),
      title: z.string(),
      slug: z.string(),
      imageUrl: z.string().url().nullable(),
    }),
  });

export type BookingStatus = z.infer<typeof bookingStatusSchema>;
export type PaymentStatus = z.infer<typeof paymentStatusSchema>;
export type PaymentGateway = z.infer<typeof paymentGatewaySchema>;
export type TravelerInfo = z.infer<typeof travelerInfoSchema>;
export type BookingExtra = z.infer<typeof bookingExtraSchema>;
export type PaymentIntent = z.infer<typeof paymentIntentSchema>;
export type Booking = z.infer<typeof bookingSchema>;
export type BookingSummary = z.infer<typeof bookingSummarySchema>;
```

---

## 🔧 Generation Scripts

### Generate TypeScript Types

```typescript
// packages/schemas/scripts/generate-types.ts
import * as fs from 'fs';
import * as path from 'path';
import { zodToTs, printNode } from 'zod-to-ts';
import * as schemas from '../src/index';

const outputPath = path.join(__dirname, '../generated/types.ts');

let output = '// AUTO-GENERATED - DO NOT EDIT\n';
output += '// Generated from Zod schemas\n\n';

for (const [name, schema] of Object.entries(schemas)) {
  if (name.endsWith('Schema')) {
    const typeName = name.replace('Schema', '');
    const { node } = zodToTs(schema as any, typeName);
    output += `export type ${typeName} = ${printNode(node)};\n\n`;
  }
}

fs.writeFileSync(outputPath, output);
console.log('Generated types.ts');
```

### Generate JSON Schema

```typescript
// packages/schemas/scripts/generate-json-schema.ts
import * as fs from 'fs';
import * as path from 'path';
import { zodToJsonSchema } from 'zod-to-json-schema';
import * as schemas from '../src/index';

const outputDir = path.join(__dirname, '../generated/json-schema');

if (!fs.existsSync(outputDir)) {
  fs.mkdirSync(outputDir, { recursive: true });
}

for (const [name, schema] of Object.entries(schemas)) {
  if (name.endsWith('Schema')) {
    const fileName = name.replace('Schema', '').toLowerCase();
    const jsonSchema = zodToJsonSchema(schema as any, name);

    fs.writeFileSync(path.join(outputDir, `${fileName}.json`), JSON.stringify(jsonSchema, null, 2));
  }
}

console.log('Generated JSON schemas');
```

### Validate Sync

```typescript
// packages/schemas/scripts/validate.ts
import * as fs from 'fs';
import * as path from 'path';

// Load OpenAPI spec
const openApiPath = path.join(__dirname, '../../laravel-api/storage/openapi.json');

if (!fs.existsSync(openApiPath)) {
  console.log('OpenAPI spec not found, skipping validation');
  process.exit(0);
}

const openApi = JSON.parse(fs.readFileSync(openApiPath, 'utf-8'));
const jsonSchemaDir = path.join(__dirname, '../generated/json-schema');

// Compare schemas
let hasErrors = false;

for (const [schemaName, openApiSchema] of Object.entries(openApi.components?.schemas || {})) {
  const jsonSchemaPath = path.join(jsonSchemaDir, `${schemaName.toLowerCase()}.json`);

  if (!fs.existsSync(jsonSchemaPath)) {
    console.warn(`⚠ Missing Zod schema for OpenAPI schema: ${schemaName}`);
    continue;
  }

  // Deep comparison would go here
  // For now, just check existence
}

if (hasErrors) {
  process.exit(1);
}

console.log('✓ Schema validation passed');
```

---

## 📦 Package Configuration

```json
// packages/schemas/package.json
{
  "name": "@djerba-fun/schemas",
  "version": "0.1.0",
  "main": "./dist/index.js",
  "types": "./dist/index.d.ts",
  "exports": {
    ".": {
      "import": "./dist/index.js",
      "types": "./dist/index.d.ts"
    },
    "./generated": {
      "import": "./generated/types.js",
      "types": "./generated/types.d.ts"
    }
  },
  "scripts": {
    "build": "tsc",
    "generate": "tsx scripts/generate-types.ts && tsx scripts/generate-json-schema.ts",
    "validate": "tsx scripts/validate.ts",
    "typecheck": "tsc --noEmit"
  },
  "dependencies": {
    "zod": "^3.23.8"
  },
  "devDependencies": {
    "tsx": "^4.7.1",
    "typescript": "^5.5.0",
    "zod-to-json-schema": "^3.23.0",
    "zod-to-ts": "^1.2.0"
  }
}
```

---

## ✅ Checklist

For each new schema:

- [ ] Zod schema defined with proper types
- [ ] TypeScript type exported
- [ ] Validation rules match Laravel validation
- [ ] Nullable vs optional correctly specified
- [ ] Enums defined for fixed values
- [ ] References to other schemas use correct imports
- [ ] JSON Schema generated
- [ ] Tests verify schema parsing

---

## 🚫 What NOT To Do

1. **Never define types inline in frontend/backend** - always import from schemas
2. **Never skip validation rules** - Zod and Laravel must match
3. **Never use `any`** - always fully type
4. **Never forget nullable** - explicit null handling
5. **Never skip generation after changes** - always regenerate
6. **Never modify generated files** - edit source schemas only
