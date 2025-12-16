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

export const mediaSchema = z.object({
  id: z.string().uuid(),
  url: z.string().url(),
  thumbnailUrl: z.string().url().nullable(),
  alt: z.string().max(200),
  type: z.enum(['image', 'video']),
  order: z.number().int().nonnegative(),
});

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
  price: z.number().int().nonnegative(),
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
  clientSecret: z.string().nullable(),
  metadata: z.record(z.unknown()).nullable(),
  capturedAt: z.string().datetime().nullable(),
  refundedAmount: z.number().int().nonnegative().default(0),
  createdAt: z.string().datetime(),
  updatedAt: z.string().datetime(),
});

export const bookingSchema = z.object({
  id: z.string().uuid(),
  code: z.string().regex(/^GA-\d{2}-\d{4,}$/),
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
  internalNotes: z.string().max(2000).nullable(),
  cancelledAt: z.string().datetime().nullable(),
  cancellationReason: z.string().nullable(),
  completedAt: z.string().datetime().nullable(),
  createdAt: z.string().datetime(),
  updatedAt: z.string().datetime(),
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
});

// Hold creation
export const createHoldRequestSchema = z.object({
  slotId: z.string().uuid(),
  guests: z.number().int().positive(),
  extras: z
    .array(
      z.object({
        id: z.string(),
        quantity: z.number().int().positive(),
      })
    )
    .default([]),
});

export const createHoldResponseSchema = z.object({
  holdId: z.string().uuid(),
  expiresAt: z.string().datetime(),
  calculatedTotal: z.number().int().nonnegative(),
  currency: z.string().length(3),
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
export type BookingExtra = z.infer<typeof bookingExtraSchema>;
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
