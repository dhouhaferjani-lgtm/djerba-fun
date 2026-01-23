/**
 * Go Adventure Marketplace - Zod Schemas
 *
 * THIS IS THE SINGLE SOURCE OF TRUTH for all data contracts.
 *
 * Both Laravel (via JSON Schema generation) and Next.js (via TypeScript types)
 * must derive their types from these schemas.
 *
 * @version 1.0.0
 * @date 2025-12-13
 */

import { z } from 'zod';

// ============================================================================
// COMMON TYPES
// ============================================================================

/** Pagination metadata for cursor-based pagination */
export const paginationMetaSchema = z.object({
  nextCursor: z.string().nullable(),
  prevCursor: z.string().nullable(),
  pageSize: z.number().int().positive(),
  total: z.number().int().nonnegative(),
  hasMore: z.boolean(),
});

/** Cursor pagination request params */
export const cursorPaginationSchema = z.object({
  cursor: z.string().optional(),
  limit: z.number().int().min(1).max(100).default(20),
});

/** Standard API error envelope */
export const apiErrorSchema = z.object({
  error: z.object({
    code: z.string(),
    message: z.string(),
    details: z.record(z.unknown()).optional(),
    field: z.string().optional(),
  }),
});

/** Monetary amount (stored as minor units / cents) */
export const moneySchema = z.object({
  amount: z.number().int(),
  currency: z.string().length(3),
});

/** ISO 8601 date range */
export const dateRangeSchema = z.object({
  start: z.string().datetime(),
  end: z.string().datetime(),
});

/** GeoJSON Point for coordinates */
export const geoPointSchema = z.object({
  type: z.literal('Point'),
  coordinates: z.tuple([z.number(), z.number()]), // [lng, lat]
});

/** Translatable field (supports en, fr, + future locales) */
export const translatableSchema = z
  .object({
    en: z.string(),
    fr: z.string(),
  })
  .passthrough();

// ============================================================================
// USERS & PROFILES
// ============================================================================

export const userRoleSchema = z.enum(['traveler', 'vendor', 'admin', 'agent']);
export const userStatusSchema = z.enum(['pending', 'active', 'suspended', 'deleted']);
export const kycStatusSchema = z.enum(['pending', 'submitted', 'verified', 'rejected']);

export const userSchema = z.object({
  id: z.string().uuid(),
  email: z.string().email(),
  role: userRoleSchema,
  status: userStatusSchema,
  displayName: z.string().min(1).max(100),
  firstName: z.string().optional(),
  lastName: z.string().optional(),
  phone: z.string().optional(),
  preferredLocale: z.enum(['en', 'fr']).optional(),
  avatarUrl: z.string().url().nullable(),
  emailVerifiedAt: z.string().datetime().nullable(),
  // Passwordless auth
  prefersPasswordless: z.boolean().optional(),
  lastMagicLoginAt: z.string().datetime().nullable().optional(),
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

// ============================================================================
// LOCATIONS
// ============================================================================

export const locationSchema = z.object({
  id: z.string().uuid(),
  name: translatableSchema,
  slug: z.string(),
  description: translatableSchema.nullable(),
  coordinates: geoPointSchema,
  address: z.string().nullable(),
  city: z.string(),
  region: z.string().nullable(),
  country: z.string().length(2),
  timezone: z.string(),
  imageUrl: z.string().url().nullable(),
  listingsCount: z.number().int().nonnegative().default(0),
});

// ============================================================================
// MAPS & ELEVATION
// ============================================================================

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
  'photo_spot',
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
  elevation: z.number(), // meters above sea level
});

export const elevationProfileSchema = z.object({
  listingId: z.string().uuid(),
  points: z.array(elevationPointSchema),
  totalAscent: z.number().nonnegative(),
  totalDescent: z.number().nonnegative(),
  maxElevation: z.number(),
  minElevation: z.number(),
  totalDistance: z.number().nonnegative(),
});

export const mapBoundsSchema = z.object({
  north: z.number().min(-90).max(90),
  south: z.number().min(-90).max(90),
  east: z.number().min(-180).max(180),
  west: z.number().min(-180).max(180),
});

export const mapConfigSchema = z.object({
  center: z.tuple([z.number(), z.number()]),
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

// ============================================================================
// MEDIA
// ============================================================================

export const mediaCategorySchema = z.enum(['hero', 'gallery', 'featured', 'itinerary_stop']);

export const mediaSchema = z.object({
  id: z.string().uuid(),
  url: z.string().url(),
  thumbnailUrl: z.string().url().nullable(),
  alt: z.string().max(200),
  type: z.enum(['image', 'video']),
  category: mediaCategorySchema.default('gallery'),
  order: z.number().int().nonnegative(),
});

export type MediaCategory = z.infer<typeof mediaCategorySchema>;

// ============================================================================
// ACTIVITY TYPES (Tour Subtypes)
// ============================================================================

export const activityTypeSchema = z.object({
  id: z.string().uuid(),
  name: translatableSchema,
  slug: z.string(),
  description: translatableSchema.nullable(),
  icon: z.string().nullable(),
  color: z.string().nullable(),
  displayOrder: z.number().int().nonnegative().default(0),
  isActive: z.boolean().default(true),
  listingsCount: z.number().int().nonnegative().default(0),
});

export type ActivityType = z.infer<typeof activityTypeSchema>;

// ============================================================================
// LISTINGS
// ============================================================================

export const serviceTypeSchema = z.enum(['tour', 'event']);
export const listingStatusSchema = z.enum([
  'draft',
  'pending_review',
  'published',
  'archived',
  'rejected',
]);
export const difficultyLevelSchema = z.enum(['easy', 'moderate', 'challenging', 'expert']);

export const personTypeSchema = z.object({
  key: z.string(),
  label: translatableSchema,
  tndPrice: z.number().nonnegative().optional(),
  eurPrice: z.number().nonnegative().optional(),
  displayPrice: z.number().nonnegative().optional(), // Price in user's detected currency
  // Legacy field for backward compatibility
  price: z.number().int().nonnegative().optional(),
  minAge: z.number().int().nonnegative().nullable(),
  maxAge: z.number().int().positive().nullable(),
  minQuantity: z.number().int().nonnegative().default(0),
  maxQuantity: z.number().int().positive().nullable(),
});

export const pricingSchema = z.object({
  // Dual pricing fields
  tndPrice: z.number().nonnegative().optional(),
  eurPrice: z.number().nonnegative().optional(),
  displayCurrency: z.enum(['TND', 'EUR']).optional(),
  displayPrice: z.number().nonnegative().optional(),

  // Legacy fields for backward compatibility
  basePrice: z.number().int().nonnegative().optional(),
  currency: z.string().length(3).optional(),

  personTypes: z.array(personTypeSchema).optional(),
  groupDiscount: z
    .object({
      minSize: z.number().int().positive(),
      discountPercent: z.number().min(0).max(100),
    })
    .nullable()
    .optional(),
});

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

export const listingFaqSchema = z.object({
  id: z.string().uuid(),
  listingId: z.string().uuid(),
  question: translatableSchema,
  answer: translatableSchema,
  order: z.number().int().nonnegative(),
  isActive: z.boolean().default(true),
  createdAt: z.string().datetime().optional(),
  updatedAt: z.string().datetime().optional(),
});

export const safetyInfoSchema = z.object({
  requiredFitnessLevel: translatableSchema.nullable(),
  minimumAge: z.number().int().nonnegative().nullable(),
  maximumAge: z.number().int().positive().nullable(),
  insuranceRequired: z.boolean().default(false),
  notSuitableFor: z.array(translatableSchema).default([]),
  safetyEquipmentProvided: z.array(translatableSchema).default([]),
});

export const accessibilityInfoSchema = z.object({
  wheelchairAccessible: z.boolean().default(false),
  mobilityAidAccessible: z.boolean().default(false),
  accessibleParking: z.boolean().default(false),
  accessibleRestrooms: z.boolean().default(false),
  serviceAnimalsAllowed: z.boolean().default(true),
  audioGuideAvailable: z.boolean().default(false),
  signLanguageAvailable: z.boolean().default(false),
  accessibilityNotes: translatableSchema.nullable(),
});

export const difficultyDetailsSchema = z.object({
  description: translatableSchema.nullable(),
  terrainType: translatableSchema.nullable(),
  elevationGainMeters: z.number().int().nonnegative().nullable(),
  technicalDifficulty: z.enum(['beginner', 'intermediate', 'advanced', 'expert']).nullable(),
  physicalIntensity: z.enum(['low', 'moderate', 'high', 'very_high']).nullable(),
  estimatedPace: translatableSchema.nullable(),
});

export type ListingFaq = z.infer<typeof listingFaqSchema>;
export type SafetyInfo = z.infer<typeof safetyInfoSchema>;
export type AccessibilityInfo = z.infer<typeof accessibilityInfoSchema>;
export type DifficultyDetails = z.infer<typeof difficultyDetailsSchema>;

// Base listing fields shared by all service types
const listingBaseFields = {
  id: z.string().uuid(),
  vendorId: z.string().uuid(),
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
  galleryImages: z.array(z.string()).nullable().optional(),
  galleryLayout: z
    .union([
      z.number().int().min(1).max(5),
      z.string(), // Accept string representation of number
    ])
    .nullable()
    .optional(),
  pricing: pricingSchema,
  cancellationPolicy: cancellationPolicySchema,
  faqs: z.array(listingFaqSchema).default([]),
  safetyInfo: safetyInfoSchema.nullable(),
  accessibilityInfo: accessibilityInfoSchema.nullable(),
  difficultyDetails: difficultyDetailsSchema.nullable(),
  minGroupSize: z.number().int().positive().default(1),
  maxGroupSize: z.number().int().positive(),
  minAdvanceBookingHours: z.number().int().nonnegative().default(0),
  rating: z.number().min(0).max(5).nullable(),
  reviewsCount: z.number().int().nonnegative().default(0),
  bookingsCount: z.number().int().nonnegative().default(0),
  createdAt: z.string().datetime(),
  updatedAt: z.string().datetime(),
  publishedAt: z.string().datetime().nullable(),
};

export const tourSchema = z.object({
  ...listingBaseFields,
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
      duration: z.number().int().positive().nullable(),
      locationId: z.string().uuid().nullable(),
      coordinates: geoPointSchema.nullable(),
    })
  ),
  hasElevationProfile: z.boolean().default(false),
  // Activity type for tour categorization
  activityType: activityTypeSchema.nullable().optional(),
  activityTypeId: z.string().uuid().nullable().optional(),
});

export const eventSchema = z.object({
  ...listingBaseFields,
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

export const listingSchema = z.discriminatedUnion('serviceType', [tourSchema, eventSchema]);

export const listingSummarySchema = z.object({
  id: z.string().uuid(),
  serviceType: serviceTypeSchema,
  title: z.string(),
  slug: z.string(),
  rating: z.number().min(0).max(5).nullable(),
  reviewsCount: z.number().int().nonnegative(),
  location: z.object({
    id: z.string().uuid(),
    name: z.string(),
    latitude: z.number().min(-90).max(90).nullable(),
    longitude: z.number().min(-180).max(180).nullable(),
  }),
  pricing: pricingSchema,
  media: z.array(mediaSchema.pick({ url: true, alt: true })).max(5),
  // Gallery images from Filament vendor upload (paths to storage)
  galleryImages: z.array(z.string()).optional(),
  duration: z
    .object({
      value: z.number().positive(),
      unit: z.enum(['hours', 'days']),
    })
    .nullable(),
  // Activity type for tours (null for events or unassigned tours)
  activityType: activityTypeSchema.nullable().optional(),
  activityTypeId: z.string().uuid().nullable().optional(),
});

// ============================================================================
// AVAILABILITY
// ============================================================================

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

export const availabilityRuleSchema = z.object({
  id: z.string().uuid(),
  listingId: z.string().uuid(),
  name: z.string().max(100),
  recurrence: recurrenceTypeSchema,
  daysOfWeek: z.array(dayOfWeekSchema).nullable(),
  dateRange: dateRangeSchema.nullable(),
  startTime: z.string().regex(/^\d{2}:\d{2}$/),
  endTime: z.string().regex(/^\d{2}:\d{2}$/),
  capacity: z.number().int().positive(),
  priceOverride: z.number().int().nonnegative().nullable(),
  isActive: z.boolean().default(true),
});

export const availabilitySlotSchema = z.object({
  id: z.string().uuid().or(z.number()),
  listingId: z.string().uuid().or(z.number()),
  date: z.string().optional(),
  start: z.string().datetime(),
  end: z.string().datetime(),
  startTime: z.string().optional(),
  endTime: z.string().optional(),
  capacity: z.number().int().positive(),
  remainingCapacity: z.number().int().nonnegative().optional(),
  // Dual pricing fields
  tndPrice: z.number().nonnegative().optional(),
  eurPrice: z.number().nonnegative().optional(),
  displayPrice: z.number().nonnegative().optional(),
  displayCurrency: z.enum(['TND', 'EUR']).optional(),
  // Legacy pricing fields
  basePrice: z.number().nonnegative(),
  currency: z.string().length(3),
  status: z.enum(['available', 'limited', 'sold_out', 'blocked']).or(z.string()),
  statusLabel: z.string().optional(),
  isBookable: z.boolean().optional(),
  createdAt: z.string().datetime().optional(),
  updatedAt: z.string().datetime().optional(),
});

export const bookingHoldSchema = z.object({
  id: z.string().uuid(),
  listingId: z.string().uuid(),
  slotId: z.string().uuid(),
  sessionId: z.string().uuid().optional(),
  quantity: z.number().int().positive(),
  personTypeBreakdown: z.record(z.string(), z.number().int().nonnegative()).optional(),
  expiresAt: z.string().datetime(),
  expiresInSeconds: z.number().int().nonnegative().optional(),
  status: z.string(),
  statusLabel: z.string().optional(),
  isActive: z.boolean().optional(),
  createdAt: z.string().datetime().optional(),
});

// ============================================================================
// BOOKINGS & PAYMENTS
// ============================================================================

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

export const paymentGatewaySchema = z.enum(['mock', 'offline', 'click_to_pay', 'stripe', 'paypal']);

export const travelerInfoSchema = z.object({
  firstName: z.string().min(1).max(100),
  lastName: z.string().min(1).max(100),
  email: z.string().email(),
  phone: z.string().nullish(),
  dateOfBirth: z.string().date().nullish(),
  nationality: z.string().length(2).nullish(),
  specialRequests: z.string().max(1000).nullish(),
  personType: z.string().optional(), // Person type key (adult, child, infant, etc.)
});

// ============================================================================
// EXTRAS (Add-ons)
// ============================================================================

export const extraPricingTypeSchema = z.enum([
  'per_person', // Price × total guests
  'per_booking', // Single flat price per booking
  'per_unit', // Customer selects quantity
  'per_person_type', // Different prices by age group (adult/child/infant)
]);

export const extraCategorySchema = z.enum([
  'equipment', // Bikes, helmets, GoPros
  'meal', // Lunch, breakfast, snacks
  'insurance', // Travel insurance
  'upgrade', // VIP, premium options
  'merchandise', // Photos, souvenirs
  'transport', // Pickup, transfers
  'accessibility', // Wheelchair, special assistance
  'other',
]);

export const bookingExtraStatusSchema = z.enum(['active', 'cancelled', 'refunded']);

export const inventoryChangeTypeSchema = z.enum([
  'reserved', // Reserved for a booking
  'released', // Released from cancelled/refunded booking
  'adjustment', // Manual inventory adjustment
  'restock', // New stock added
]);

/** Person type pricing structure for extras */
export const personTypePricesSchema = z.record(
  z.string(), // Person type key (adult, child, infant)
  z.object({
    tnd: z.number().nonnegative(),
    eur: z.number().nonnegative(),
  })
);

/** Extra definition (vendor's add-on catalog) */
export const extraSchema = z.object({
  id: z.string().uuid(),
  vendorId: z.number().or(z.string()),
  name: translatableSchema,
  description: translatableSchema.nullable(),
  shortDescription: translatableSchema.nullable(),
  imageUrl: z.string().url().nullable(),
  thumbnailUrl: z.string().url().nullable(),
  pricingType: extraPricingTypeSchema,
  basePriceTnd: z.number().nonnegative(),
  basePriceEur: z.number().nonnegative(),
  personTypePrices: personTypePricesSchema.nullable(),
  minQuantity: z.number().int().nonnegative().default(0),
  maxQuantity: z.number().int().positive().nullable(),
  defaultQuantity: z.number().int().positive().default(1),
  trackInventory: z.boolean().default(false),
  inventoryCount: z.number().int().nonnegative().nullable(),
  isRequired: z.boolean().default(false),
  autoAdd: z.boolean().default(false),
  allowQuantityChange: z.boolean().default(true),
  displayOrder: z.number().int().nonnegative().default(0),
  category: extraCategorySchema.nullable(),
  isActive: z.boolean().default(true),
  createdAt: z.string().datetime().optional(),
  updatedAt: z.string().datetime().optional(),
});

/** Listing-Extra pivot (configures extras for specific listings) */
export const listingExtraSchema = z.object({
  id: z.string().uuid(),
  listingId: z.number().or(z.string()),
  extraId: z.string().uuid(),
  // Override pricing (null = use extra's defaults)
  overridePriceTnd: z.number().nonnegative().nullable(),
  overridePriceEur: z.number().nonnegative().nullable(),
  overridePersonTypePrices: personTypePricesSchema.nullable(),
  // Override quantity limits
  overrideMinQuantity: z.number().int().nonnegative().nullable(),
  overrideMaxQuantity: z.number().int().positive().nullable(),
  // Override behavior
  overrideIsRequired: z.boolean().nullable(),
  // Availability constraints
  availableForSlots: z.array(z.string().uuid()).nullable(), // null = all slots
  availableForPersonTypes: z.array(z.string()).nullable(), // null = all person types
  // Conditional display rules
  displayConditions: z
    .object({
      conditions: z.array(
        z.object({
          field: z.string(),
          operator: z.enum(['==', '!=', '>', '>=', '<', '<=', 'in']),
          value: z.unknown(),
        })
      ),
      action: z.enum(['show', 'hide']),
    })
    .nullable(),
  displayOrder: z.number().int().nonnegative().default(0),
  isFeatured: z.boolean().default(false),
  isActive: z.boolean().default(true),
  createdAt: z.string().datetime().optional(),
  updatedAt: z.string().datetime().optional(),
  // Include extra details when loaded
  extra: extraSchema.optional(),
});

/** Booking extra (selected extra with price snapshot) */
export const bookingExtraSchema = z.object({
  id: z.string().uuid(),
  bookingId: z.string().uuid(),
  extraId: z.string().uuid().nullable(),
  listingExtraId: z.string().uuid().nullable(),
  quantity: z.number().int().positive(),
  pricingType: extraPricingTypeSchema,
  unitPriceTnd: z.number().nonnegative(),
  unitPriceEur: z.number().nonnegative(),
  // For per_person_type pricing
  personTypeBreakdown: z
    .record(
      z.string(),
      z.object({
        count: z.number().int().nonnegative(),
        priceTnd: z.number().nonnegative(),
        priceEur: z.number().nonnegative(),
      })
    )
    .nullable(),
  subtotalTnd: z.number().nonnegative(),
  subtotalEur: z.number().nonnegative(),
  // Snapshot for history
  extraName: translatableSchema,
  extraCategory: extraCategorySchema.nullable(),
  inventoryReserved: z.boolean().default(false),
  status: bookingExtraStatusSchema.default('active'),
  createdAt: z.string().datetime().optional(),
  updatedAt: z.string().datetime().optional(),
  // Convenience getters
  name: z.string().optional(), // Localized name
  unitPrice: z.number().nonnegative().optional(), // In display currency
  totalPrice: z.number().nonnegative().optional(), // In display currency
});

/** Inventory change log entry */
export const extraInventoryLogSchema = z.object({
  id: z.string().uuid(),
  extraId: z.string().uuid(),
  bookingId: z.string().uuid().nullable(),
  changeType: inventoryChangeTypeSchema,
  quantityChange: z.number().int(), // Can be negative
  previousCount: z.number().int().nonnegative(),
  newCount: z.number().int().nonnegative(),
  notes: z.string().nullable(),
  createdBy: z.number().nullable(),
  createdAt: z.string().datetime(),
});

/** Extra for display in booking flow (combines extra + listing overrides) */
export const listingExtraForBookingSchema = z.object({
  id: z.string().uuid(), // listing_extra id
  extraId: z.string().uuid(),
  name: z.string(), // Localized name
  description: z.string().nullable(),
  shortDescription: z.string().nullable(),
  imageUrl: z.string().url().nullable(),
  pricingType: extraPricingTypeSchema,
  category: extraCategorySchema.nullable(),
  // Effective values (with overrides applied)
  priceTnd: z.number().nonnegative(),
  priceEur: z.number().nonnegative(),
  displayPrice: z.number().nonnegative().optional(),
  displayCurrency: z.enum(['TND', 'EUR']).optional(),
  personTypePrices: personTypePricesSchema.nullable(),
  minQuantity: z.number().int().nonnegative(),
  maxQuantity: z.number().int().positive().nullable(),
  isRequired: z.boolean(),
  autoAdd: z.boolean(), // Automatically added to booking
  // Display flags
  isFeatured: z.boolean(),
  allowQuantityChange: z.boolean(),
  // Conditional display
  shouldDisplay: z.boolean().optional(), // Evaluated based on booking context
  displayConditions: z
    .object({
      conditions: z.array(
        z.object({
          field: z.string(),
          operator: z.enum(['==', '!=', '>', '>=', '<', '<=', 'in']),
          value: z.unknown(),
        })
      ),
      action: z.enum(['show', 'hide']),
    })
    .nullable()
    .optional(),
  // Inventory & Capacity
  trackInventory: z.boolean(),
  inventoryCount: z.number().int().nonnegative().nullable(),
  capacityPerUnit: z.number().int().positive().nullable().optional(), // Max people per unit (e.g., 4 for a vehicle)
  hasAvailableInventory: z.boolean(),
});

export const paymentIntentSchema = z.object({
  id: z.string().uuid(),
  bookingId: z.string().uuid(),
  gateway: paymentGatewaySchema,
  status: paymentStatusSchema,
  amount: z.number().int().nonnegative(),
  currency: z.string().length(3),
  providerRef: z.string().nullable(),
  clientSecret: z.string().nullable(),
  metadata: z.record(z.unknown()).nullable(),
  capturedAt: z.string().datetime().nullable(),
  refundedAmount: z.number().int().nonnegative().default(0),
  createdAt: z.string().datetime(),
  updatedAt: z.string().datetime(),
});

export const travelerDetailsStatusSchema = z.enum([
  'not_required',
  'pending',
  'partial',
  'complete',
]);

export const linkedMethodSchema = z.enum(['auto', 'manual', 'claimed']);

export const bookingSchema = z.object({
  id: z.string().uuid(),
  bookingNumber: z.string(),
  code: z.string().optional(), // Alias for bookingNumber
  userId: z.string().uuid().nullable().optional(),
  listingId: z.string().uuid(),
  availabilitySlotId: z.string().uuid().or(z.number()).optional(),
  quantity: z.number().int().positive(),
  guests: z.number().int().positive().optional(), // Alias for quantity
  totalAmount: z.number().nonnegative(),
  tndAmount: z.number().nonnegative().optional(), // TND equivalent for currency notice modal
  discountAmount: z.number().nonnegative().default(0),
  currency: z.string().length(3),
  status: z.string(),
  statusLabel: z.string().optional(),
  travelerInfo: travelerInfoSchema.optional(),
  travelers: z.array(travelerInfoSchema).optional(),
  billingContact: z
    .object({
      email: z.string().email(),
      firstName: z.string().optional(),
      lastName: z.string().optional(),
      phone: z.string().optional(),
    })
    .optional(),
  extras: z.array(bookingExtraSchema).optional(),
  confirmedAt: z.string().datetime().nullable().optional(),
  cancelledAt: z.string().datetime().nullable().optional(),
  cancellationReason: z.string().nullable().optional(),
  // Traveler details tracking
  travelerDetailsStatus: travelerDetailsStatusSchema.optional(),
  travelerDetailsCompletedAt: z.string().datetime().nullable().optional(),
  requiresTravelerDetails: z.boolean().optional(),
  travelerDetailsComplete: z.boolean().optional(),
  travelerDetailsPending: z.boolean().optional(),
  // Account linking
  linkedAt: z.string().datetime().nullable().optional(),
  linkedMethod: linkedMethodSchema.nullable().optional(),
  createdAt: z.string().datetime(),
  updatedAt: z.string().datetime(),
  // Computed properties
  canBeCancelled: z.boolean().optional(),
  isConfirmed: z.boolean().optional(),
  isCancelled: z.boolean().optional(),
  // Convenience aliases
  startsAt: z.string().datetime().nullable().optional(),
  // Relationships (optional when not loaded)
  user: z.unknown().optional(),
  listing: z.unknown().optional(),
  availabilitySlot: availabilitySlotSchema.optional(),
  paymentIntents: z.array(paymentIntentSchema).optional(),
  latestPaymentIntent: paymentIntentSchema.nullable().optional(),
  participants: z.array(z.unknown()).optional(),
});

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

// ============================================================================
// REVIEWS
// ============================================================================

export const reviewSchema = z.object({
  id: z.string().uuid(),
  bookingId: z.string().uuid(),
  listingId: z.string().uuid(),
  travelerId: z.string().uuid(),
  rating: z.number().int().min(1).max(5),
  title: z.string().max(200).nullable(),
  content: z.string().max(2000),
  pros: z.array(z.string()).default([]),
  cons: z.array(z.string()).default([]),
  photos: z
    .array(
      z.object({
        url: z.string().url(),
        caption: z.string().nullable(),
      })
    )
    .default([]),
  isVerified: z.boolean().default(true),
  isPublished: z.boolean().default(true),
  helpfulCount: z.number().int().nonnegative().default(0),
  vendorReply: z
    .object({
      content: z.string().max(1000),
      repliedAt: z.string().datetime(),
    })
    .nullable(),
  user: z
    .object({
      displayName: z.string(),
      avatarUrl: z.string().url().nullable(),
    })
    .nullable(),
  createdAt: z.string().datetime(),
  updatedAt: z.string().datetime(),
});

export const createReviewRequestSchema = z.object({
  rating: z.number().int().min(1).max(5),
  title: z.string().min(5).max(100),
  content: z.string().min(20).max(2000),
  pros: z.array(z.string()).optional(),
  cons: z.array(z.string()).optional(),
});

export const reviewSummarySchema = z.object({
  averageRating: z.number().min(0).max(5),
  totalCount: z.number().int().nonnegative(),
  ratingBreakdown: z.object({
    5: z.number().int().nonnegative(),
    4: z.number().int().nonnegative(),
    3: z.number().int().nonnegative(),
    2: z.number().int().nonnegative(),
    1: z.number().int().nonnegative(),
  }),
});

// ============================================================================
// COUPONS
// ============================================================================

export const couponSchema = z.object({
  id: z.string().uuid(),
  code: z.string().min(3).max(50).toUpperCase(),
  discountType: z.enum(['percentage', 'fixed']),
  discountValue: z.number().positive(),
  currency: z.string().length(3).nullable(), // For fixed discounts
  minOrderAmount: z.number().int().nonnegative().nullable(),
  maxUses: z.number().int().positive().nullable(),
  usedCount: z.number().int().nonnegative().default(0),
  validFrom: z.string().datetime(),
  validUntil: z.string().datetime().nullable(),
  applicableListings: z.array(z.string().uuid()).nullable(), // null = all
  isActive: z.boolean().default(true),
});

export const couponValidationSchema = z.object({
  valid: z.boolean(),
  discountAmount: z.number().int().nonnegative().optional(),
  message: z.string().optional(),
  coupon: z
    .object({
      code: z.string(),
      discountType: z.enum(['percentage', 'fixed']),
      discountValue: z.number(),
    })
    .optional(),
});

// ============================================================================
// VENDOR PROFILE
// ============================================================================

export const vendorPublicProfileSchema = z.object({
  id: z.string().uuid(),
  companyName: z.string(),
  description: z.string().nullable(),
  logoUrl: z.string().url().nullable(),
  coverImageUrl: z.string().url().nullable(),
  rating: z.number().min(0).max(5).nullable(),
  reviewsCount: z.number().int().nonnegative(),
  listingsCount: z.number().int().nonnegative(),
  memberSince: z.string().datetime(),
  verificationBadges: z.array(z.string()).optional(),
});

// ============================================================================
// API REQUEST/RESPONSE SCHEMAS
// ============================================================================

// Listing search
export const listingSearchParamsSchema = z.object({
  serviceType: serviceTypeSchema.optional(),
  location: z.string().optional(),
  activityType: z.string().optional(), // Activity type slug for tour filtering
  startDate: z.string().date().optional(),
  endDate: z.string().date().optional(),
  guests: z.number().int().positive().optional(),
  priceMin: z.number().int().nonnegative().optional(),
  priceMax: z.number().int().positive().optional(),
  difficulty: difficultyLevelSchema.optional(),
  sort: z.enum(['price_asc', 'price_desc', 'rating', 'popularity', 'newest']).default('popularity'),
  cursor: z.string().optional(),
  limit: z.number().int().min(1).max(50).default(20),
});

export const listingSearchResponseSchema = z.object({
  data: z.array(listingSummarySchema),
  meta: paginationMetaSchema,
});

// Create booking
export const createBookingRequestSchema = z.object({
  holdId: z.string().uuid(),
  travelers: z.array(travelerInfoSchema).min(1),
  couponCode: z.string().optional(),
  specialRequests: z.string().max(2000).optional(),
  sessionId: z.string().uuid().optional(),
});

// Hold creation
export const createHoldRequestSchema = z.object({
  slotId: z.string().uuid().or(z.string()),
  guests: z.number().int().positive().optional(),
  person_types: z.record(z.string(), z.number()).optional(),
  session_id: z.string().optional(),
  extras: z
    .array(
      z.object({
        id: z.string(),
        quantity: z.number().int().positive(),
      })
    )
    .default([]),
});

// CreateHoldResponse extends BookingHold with nested listing and slot
export const createHoldResponseSchema = bookingHoldSchema.extend({
  slot: availabilitySlotSchema.optional(),
  listing: z.lazy(() => listingSummarySchema).optional(),
});

// ============================================================================
// TYPE EXPORTS
// ============================================================================

export type PaginationMeta = z.infer<typeof paginationMetaSchema>;
export type ApiError = z.infer<typeof apiErrorSchema>;
export type Money = z.infer<typeof moneySchema>;
export type GeoPoint = z.infer<typeof geoPointSchema>;
export type Translatable = z.infer<typeof translatableSchema>;

export type UserRole = z.infer<typeof userRoleSchema>;
export type User = z.infer<typeof userSchema>;
export type TravelerProfile = z.infer<typeof travelerProfileSchema>;
export type VendorProfile = z.infer<typeof vendorProfileSchema>;

export type Location = z.infer<typeof locationSchema>;

export type MarkerType = z.infer<typeof markerTypeSchema>;
export type MapMarker = z.infer<typeof mapMarkerSchema>;
export type ItineraryStop = z.infer<typeof itineraryStopSchema>;
export type ElevationPoint = z.infer<typeof elevationPointSchema>;
export type ElevationProfile = z.infer<typeof elevationProfileSchema>;
export type MapConfig = z.infer<typeof mapConfigSchema>;

export type Media = z.infer<typeof mediaSchema>;

export type ServiceType = z.infer<typeof serviceTypeSchema>;
export type ListingStatus = z.infer<typeof listingStatusSchema>;
export type DifficultyLevel = z.infer<typeof difficultyLevelSchema>;
export type PersonType = z.infer<typeof personTypeSchema>;
export type Pricing = z.infer<typeof pricingSchema>;
export type CancellationPolicy = z.infer<typeof cancellationPolicySchema>;
export type Tour = z.infer<typeof tourSchema>;
export type Event = z.infer<typeof eventSchema>;
export type Listing = z.infer<typeof listingSchema>;
export type ListingSummary = z.infer<typeof listingSummarySchema>;

export type RecurrenceType = z.infer<typeof recurrenceTypeSchema>;
export type DayOfWeek = z.infer<typeof dayOfWeekSchema>;
export type AvailabilityRule = z.infer<typeof availabilityRuleSchema>;
export type AvailabilitySlot = z.infer<typeof availabilitySlotSchema>;
export type BookingHold = z.infer<typeof bookingHoldSchema>;

export type BookingStatus = z.infer<typeof bookingStatusSchema>;
export type PaymentStatus = z.infer<typeof paymentStatusSchema>;
export type PaymentGateway = z.infer<typeof paymentGatewaySchema>;
export type TravelerInfo = z.infer<typeof travelerInfoSchema>;

// Extras types
export type ExtraPricingType = z.infer<typeof extraPricingTypeSchema>;
export type ExtraCategory = z.infer<typeof extraCategorySchema>;
export type BookingExtraStatus = z.infer<typeof bookingExtraStatusSchema>;
export type InventoryChangeType = z.infer<typeof inventoryChangeTypeSchema>;
export type PersonTypePrices = z.infer<typeof personTypePricesSchema>;
export type Extra = z.infer<typeof extraSchema>;
export type ListingExtra = z.infer<typeof listingExtraSchema>;
export type BookingExtra = z.infer<typeof bookingExtraSchema>;
export type ExtraInventoryLog = z.infer<typeof extraInventoryLogSchema>;
export type ListingExtraForBooking = z.infer<typeof listingExtraForBookingSchema>;

export type PaymentIntent = z.infer<typeof paymentIntentSchema>;
export type Booking = z.infer<typeof bookingSchema>;
export type BookingSummary = z.infer<typeof bookingSummarySchema>;

export type Review = z.infer<typeof reviewSchema>;
export type CreateReviewRequest = z.infer<typeof createReviewRequestSchema>;
export type ReviewSummary = z.infer<typeof reviewSummarySchema>;
export type Coupon = z.infer<typeof couponSchema>;
export type CouponValidation = z.infer<typeof couponValidationSchema>;
export type VendorPublicProfile = z.infer<typeof vendorPublicProfileSchema>;

export type ListingSearchParams = z.infer<typeof listingSearchParamsSchema>;
export type ListingSearchResponse = z.infer<typeof listingSearchResponseSchema>;
export type CreateBookingRequest = z.infer<typeof createBookingRequestSchema>;
export type CreateHoldRequest = z.infer<typeof createHoldRequestSchema>;
export type CreateHoldResponse = z.infer<typeof createHoldResponseSchema>;

// ============================================================================
// PLATFORM SETTINGS
// ============================================================================

/** Platform identity settings */
export const platformIdentitySchema = z.object({
  name: z.string(),
  tagline: z.string().nullable(),
  description: z.string().nullable(),
  domain: z.string().nullable(),
  frontendUrl: z.string().nullable(),
});

/** Platform branding (logos, images) */
export const platformBrandingSchema = z.object({
  logoLight: z.string().url().nullable(),
  logoDark: z.string().url().nullable(),
  favicon: z.string().url().nullable(),
  ogImage: z.string().url().nullable(),
  appleTouchIcon: z.string().url().nullable(),
  heroBanner: z.string().url().nullable(),
  brandPillar1: z.string().url().nullable(),
  brandPillar2: z.string().url().nullable(),
  brandPillar3: z.string().url().nullable(),
});

/** SEO metadata */
export const platformSeoSchema = z.object({
  metaTitle: z.string().nullable(),
  metaDescription: z.string().nullable(),
  keywords: z.array(z.string()),
  author: z.string().nullable(),
  organizationType: z.string().nullable(),
  foundedYear: z.number().int().nullable(),
});

/** Platform contact information */
export const platformContactSchema = z.object({
  supportEmail: z.string().email().nullable(),
  generalEmail: z.string().email().nullable(),
  phone: z.string().nullable(),
  whatsapp: z.string().nullable(),
  businessHours: z
    .record(
      z.string(),
      z
        .object({
          open: z.string(),
          close: z.string(),
        })
        .nullable()
    )
    .nullable(),
});

/** Platform physical address */
export const platformAddressSchema = z.object({
  street: z.string().nullable(),
  city: z.string().nullable(),
  region: z.string().nullable(),
  postalCode: z.string().nullable(),
  country: z.string().nullable(),
  googleMapsUrl: z.string().url().nullable(),
  full: z.string().nullable(),
});

/** Platform social media links */
export const platformSocialSchema = z.object({
  facebook: z.string().url().nullable().optional(),
  instagram: z.string().url().nullable().optional(),
  twitter: z.string().url().nullable().optional(),
  linkedin: z.string().url().nullable().optional(),
  youtube: z.string().url().nullable().optional(),
  tiktok: z.string().url().nullable().optional(),
});

/** Platform localization settings */
export const platformLocalizationSchema = z.object({
  defaultLocale: z.string(),
  availableLocales: z.array(z.string()),
  fallbackLocale: z.string(),
  rtlLocales: z.array(z.string()),
  dateFormat: z.string(),
  timeFormat: z.string(),
  timezone: z.string(),
  weekStartsOn: z.union([z.number().int().min(0).max(6), z.string()]),
});

/** Platform feature flags */
export const platformFeaturesSchema = z.object({
  reviews: z.boolean(),
  wishlists: z.boolean(),
  giftCards: z.boolean(),
  loyaltyProgram: z.boolean(),
  blog: z.boolean(),
  instantBooking: z.boolean(),
  requestToBook: z.boolean(),
  groupBookings: z.boolean(),
  customPackages: z.boolean(),
});

/** Platform booking settings */
export const platformBookingSchema = z.object({
  holdDurationMinutes: z.number().int(),
  holdWarningMinutes: z.number().int(),
  defaultCurrency: z.string(),
  enabledCurrencies: z.array(z.string()),
  minBookingAmount: z.number(),
  maxBookingAmount: z.number(),
});

/** Platform legal settings */
export const platformLegalSchema = z.object({
  termsUrl: z.string().nullable(),
  privacyUrl: z.string().nullable(),
  cookiePolicyUrl: z.string().nullable(),
  refundPolicyUrl: z.string().nullable(),
  cookieConsentEnabled: z.boolean(),
  gdprModeEnabled: z.boolean(),
  minimumAgeRequirement: z.number().int().nullable(),
});

/** Platform analytics settings */
export const platformAnalyticsSchema = z.object({
  ga4MeasurementId: z.string().nullable(),
  gtmContainerId: z.string().nullable(),
  facebookPixelId: z.string().nullable(),
  hotjarSiteId: z.string().nullable(),
  plausibleDomain: z.string().nullable(),
});

/** Public platform settings (returned by API) */
export const platformSettingsSchema = z.object({
  platform: platformIdentitySchema,
  branding: platformBrandingSchema,
  seo: platformSeoSchema,
  contact: platformContactSchema,
  address: platformAddressSchema,
  social: platformSocialSchema,
  localization: platformLocalizationSchema,
  features: platformFeaturesSchema,
  booking: platformBookingSchema,
  legal: platformLegalSchema,
  analytics: platformAnalyticsSchema,
});

/** Platform settings API response */
export const platformSettingsResponseSchema = z.object({
  data: platformSettingsSchema,
  meta: z.object({
    locale: z.string(),
    cached_at: z.string(),
  }),
});

/** Schema.org JSON-LD data */
export const schemaOrgDataSchema = z.object({
  '@context': z.literal('https://schema.org'),
  '@type': z.string(),
  name: z.string(),
  description: z.string().nullable().optional(),
  url: z.string().nullable().optional(),
  logo: z.string().nullable().optional(),
  foundingDate: z.string().optional(),
  address: z
    .object({
      '@type': z.literal('PostalAddress'),
      streetAddress: z.string().nullable().optional(),
      addressLocality: z.string().nullable().optional(),
      addressRegion: z.string().nullable().optional(),
      postalCode: z.string().nullable().optional(),
      addressCountry: z.string().nullable().optional(),
    })
    .optional(),
  contactPoint: z
    .object({
      '@type': z.literal('ContactPoint'),
      telephone: z.string().nullable().optional(),
      email: z.string().nullable().optional(),
      contactType: z.string().optional(),
    })
    .optional(),
  sameAs: z.array(z.string()).optional(),
});

// ============================================================================
// PASSWORDLESS AUTHENTICATION
// ============================================================================

/** Send magic link request */
export const sendMagicLinkRequestSchema = z.object({
  email: z.string().email(),
});

/** Verify magic link request */
export const verifyMagicLinkRequestSchema = z.object({
  token: z.string().length(64),
  deviceName: z.string().optional(),
});

/** Register passwordless user request */
export const registerPasswordlessRequestSchema = z.object({
  email: z.string().email(),
  firstName: z.string().min(1).max(100),
  lastName: z.string().min(1).max(100),
  phone: z.string().optional(),
  preferredLocale: z.enum(['en', 'fr']).default('en'),
});

/** Magic link response */
export const magicLinkResponseSchema = z.object({
  message: z.string(),
});

/** Auth response with token */
export const authResponseSchema = z.object({
  user: userSchema,
  token: z.string(),
});

// ============================================================================
// PARTICIPANT MANAGEMENT
// ============================================================================

/** Booking participant */
export const bookingParticipantSchema = z.object({
  id: z.string().uuid(),
  bookingId: z.string().uuid(),
  personType: z.string().nullable().optional(),
  firstName: z.string().nullable().optional(),
  lastName: z.string().nullable().optional(),
  email: z.string().email().nullable().optional(),
  phone: z.string().nullable().optional(),
  createdAt: z.string().datetime(),
  updatedAt: z.string().datetime(),
});

/** Update participants request */
export const updateParticipantsRequestSchema = z.object({
  participants: z
    .array(
      z.object({
        id: z.string().uuid(),
        firstName: z.string().min(1).max(100),
        lastName: z.string().min(1).max(100),
        email: z.string().email().optional(),
        phone: z.string().optional(),
      })
    )
    .min(1),
  sessionId: z.string().optional(),
});

/** Update participants response */
export const updateParticipantsResponseSchema = z.object({
  data: z.array(bookingParticipantSchema),
  meta: z.object({
    travelerDetailsStatus: travelerDetailsStatusSchema,
    travelerDetailsCompletedAt: z.string().datetime().nullable().optional(),
  }),
});

// ============================================================================
// BOOKING LINKING
// ============================================================================

/** Link bookings request */
export const linkBookingsRequestSchema = z.object({
  bookingIds: z.array(z.string().uuid()).min(1),
});

/** Claim booking request */
export const claimBookingRequestSchema = z.object({
  bookingNumber: z.string().regex(/^GA-\d{6}-[A-Z0-9]{5}$/),
});

/** Claimable bookings response */
export const claimableBookingsResponseSchema = z.object({
  data: z.array(bookingSchema),
  meta: z.object({
    total: z.number().int().nonnegative(),
  }),
});

/** Link bookings response */
export const linkBookingsResponseSchema = z.object({
  data: z.array(bookingSchema),
  meta: z.object({
    linked: z.number().int().nonnegative(),
  }),
  message: z.string(),
});

/** Claim booking response */
export const claimBookingResponseSchema = z.object({
  data: bookingSchema,
  message: z.string(),
});

// Platform Settings Types
export type PlatformIdentity = z.infer<typeof platformIdentitySchema>;
export type PlatformBranding = z.infer<typeof platformBrandingSchema>;
export type PlatformSeo = z.infer<typeof platformSeoSchema>;
export type PlatformContact = z.infer<typeof platformContactSchema>;
export type PlatformAddress = z.infer<typeof platformAddressSchema>;
export type PlatformSocial = z.infer<typeof platformSocialSchema>;
export type PlatformLocalization = z.infer<typeof platformLocalizationSchema>;
export type PlatformFeatures = z.infer<typeof platformFeaturesSchema>;
export type PlatformBooking = z.infer<typeof platformBookingSchema>;
export type PlatformLegal = z.infer<typeof platformLegalSchema>;
export type PlatformAnalytics = z.infer<typeof platformAnalyticsSchema>;
export type PlatformSettings = z.infer<typeof platformSettingsSchema>;
export type PlatformSettingsResponse = z.infer<typeof platformSettingsResponseSchema>;
export type SchemaOrgData = z.infer<typeof schemaOrgDataSchema>;

// Passwordless Auth Types
export type SendMagicLinkRequest = z.infer<typeof sendMagicLinkRequestSchema>;
export type VerifyMagicLinkRequest = z.infer<typeof verifyMagicLinkRequestSchema>;
export type RegisterPasswordlessRequest = z.infer<typeof registerPasswordlessRequestSchema>;
export type MagicLinkResponse = z.infer<typeof magicLinkResponseSchema>;
export type AuthResponse = z.infer<typeof authResponseSchema>;

// Participant Management Types
export type BookingParticipant = z.infer<typeof bookingParticipantSchema>;
export type UpdateParticipantsRequest = z.infer<typeof updateParticipantsRequestSchema>;
export type UpdateParticipantsResponse = z.infer<typeof updateParticipantsResponseSchema>;
export type TravelerDetailsStatus = z.infer<typeof travelerDetailsStatusSchema>;

// Booking Linking Types
export type LinkBookingsRequest = z.infer<typeof linkBookingsRequestSchema>;
export type ClaimBookingRequest = z.infer<typeof claimBookingRequestSchema>;
export type ClaimableBookingsResponse = z.infer<typeof claimableBookingsResponseSchema>;
export type LinkBookingsResponse = z.infer<typeof linkBookingsResponseSchema>;
export type ClaimBookingResponse = z.infer<typeof claimBookingResponseSchema>;
export type LinkedMethod = z.infer<typeof linkedMethodSchema>;

// ============================================================================
// CUSTOM TRIP REQUESTS
// ============================================================================

/** Available interest types for custom trip requests */
export const customTripInterestSchema = z.enum([
  'history-culture',
  'desert-adventures',
  'beach-relaxation',
  'food-gastronomy',
  'hiking-nature',
  'photography',
  'local-festivals',
  'star-wars-sites',
]);

/** Accommodation style options */
export const accommodationStyleSchema = z.enum(['budget', 'mid-range', 'luxury']);

/** Travel pace options */
export const travelPaceSchema = z.enum(['relaxed', 'moderate', 'active']);

/** Special occasion options */
export const specialOccasionSchema = z.enum(['honeymoon', 'birthday', 'anniversary', 'other']);

/** Contact method preference */
export const preferredContactMethodSchema = z.enum(['email', 'phone', 'whatsapp']);

/** Custom trip request status */
export const customTripStatusSchema = z.enum([
  'pending',
  'reviewed',
  'assigned',
  'in_progress',
  'proposal_sent',
  'converted',
  'cancelled',
]);

/** Create custom trip request - request body */
export const createCustomTripRequestSchema = z.object({
  travel_dates: z.object({
    start: z
      .string()
      .refine((date) => new Date(date) >= new Date(new Date().setHours(0, 0, 0, 0)), {
        message: 'Travel start date must be today or in the future',
      }),
    end: z.string(),
    flexible: z.boolean().default(false),
  }),
  travelers: z.object({
    adults: z.number().int().min(1).max(20),
    children: z.number().int().min(0).max(10).default(0),
  }),
  duration_days: z.number().int().min(3).max(21),
  interests: z.array(customTripInterestSchema).min(1).max(5),
  budget: z.object({
    per_person: z.number().int().min(500).max(10000),
    currency: z.enum(['TND', 'EUR', 'USD']).default('TND'),
  }),
  accommodation_style: accommodationStyleSchema,
  travel_pace: travelPaceSchema,
  special_occasions: z.array(specialOccasionSchema).optional(),
  contact: z.object({
    name: z.string().min(2).max(255),
    email: z.string().email(),
    phone: z.string().min(8).max(50),
    whatsapp: z.string().max(50).optional().nullable(),
    country: z.string().length(2),
    preferred_method: preferredContactMethodSchema,
  }),
  special_requests: z.string().max(1000).optional().nullable(),
  newsletter_consent: z.boolean().default(false),
  locale: z.enum(['en', 'fr']).default('en'),
});

/** Custom trip request response */
export const customTripRequestResponseSchema = z.object({
  data: z.object({
    id: z.string().uuid(),
    reference: z.string(),
    status: customTripStatusSchema,
    created_at: z.string().datetime(),
  }),
  message: z.string(),
});

/** Full custom trip request entity */
export const customTripRequestSchema = z.object({
  id: z.string().uuid(),
  reference: z.string(),
  status: customTripStatusSchema,
  travel_start_date: z.string(),
  travel_end_date: z.string(),
  dates_flexible: z.boolean(),
  adults: z.number().int(),
  children: z.number().int(),
  duration_days: z.number().int(),
  interests: z.array(customTripInterestSchema),
  budget_per_person: z.number().int(),
  budget_currency: z.string().length(3),
  accommodation_style: accommodationStyleSchema,
  travel_pace: travelPaceSchema,
  special_occasions: z.array(specialOccasionSchema).nullable(),
  special_requests: z.string().nullable(),
  contact_name: z.string(),
  contact_email: z.string().email(),
  contact_phone: z.string(),
  contact_whatsapp: z.string().nullable(),
  contact_country: z.string().length(2),
  preferred_contact_method: preferredContactMethodSchema,
  newsletter_consent: z.boolean(),
  locale: z.enum(['en', 'fr']),
  created_at: z.string().datetime(),
  updated_at: z.string().datetime(),
});

// Custom Trip Request Types
export type CustomTripInterest = z.infer<typeof customTripInterestSchema>;
export type AccommodationStyle = z.infer<typeof accommodationStyleSchema>;
export type TravelPace = z.infer<typeof travelPaceSchema>;
export type SpecialOccasion = z.infer<typeof specialOccasionSchema>;
export type PreferredContactMethod = z.infer<typeof preferredContactMethodSchema>;
export type CustomTripStatus = z.infer<typeof customTripStatusSchema>;
export type CreateCustomTripRequest = z.infer<typeof createCustomTripRequestSchema>;
export type CustomTripRequestResponse = z.infer<typeof customTripRequestResponseSchema>;
export type CustomTripRequest = z.infer<typeof customTripRequestSchema>;
