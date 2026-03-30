/**
 * Centralized React Query keys
 *
 * This file contains all query keys used across the application.
 * Centralizing them prevents key mismatches (e.g., using ['currentUser'] in one place
 * and ['user', 'me'] in another) which can cause cache invalidation bugs.
 *
 * Usage:
 *   import { queryKeys } from '@/lib/api/query-keys';
 *   queryClient.invalidateQueries({ queryKey: queryKeys.user.me });
 */

export const queryKeys = {
  // User/Auth queries
  user: {
    all: ['user'] as const,
    me: ['user', 'me'] as const,
    profile: ['user', 'profile'] as const,
    preferences: ['user', 'preferences'] as const,
  },

  // Cart queries
  cart: ['cart'] as const,

  // Bookings queries
  bookings: {
    all: ['bookings'] as const,
    claimable: ['bookings', 'claimable'] as const,
    detail: (id: string) => ['bookings', id] as const,
  },

  // Listings queries
  listings: {
    all: ['listings'] as const,
    detail: (slug: string) => ['listing', slug] as const,
    availability: (slug: string) => ['availability', slug] as const,
    extras: (slug: string) => ['listing-extras', slug] as const,
  },

  // Reviews queries
  reviews: {
    forListing: (listingId: string) => ['reviews', listingId] as const,
    summary: (listingSlug: string) => ['review-summary', listingSlug] as const,
    canReview: (listingSlug: string) => ['can-review', listingSlug] as const,
  },

  // Wishlist queries
  wishlist: ['wishlist'] as const,

  // Other queries
  locations: ['locations'] as const,
  activityTypes: ['activityTypes'] as const,
  tags: ['tags'] as const,
  testimonials: ['testimonials'] as const,
  menus: (code: string) => ['menus', code] as const,
} as const;
