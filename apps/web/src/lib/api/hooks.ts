import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  authApi,
  listingsApi,
  bookingsApi,
  reviewsApi,
  couponsApi,
  vendorsApi,
  type ProcessPaymentRequest,
} from './client';
import type {
  ListingSearchParams,
  CreateHoldRequest,
  CreateReviewRequest,
} from '@go-adventure/schemas';

// ============================================================================
// AUTH HOOKS
// ============================================================================

export function useCurrentUser() {
  return useQuery({
    queryKey: ['user', 'me'],
    queryFn: async () => {
      const response = await authApi.getCurrentUser();
      return response.data;
    },
    retry: false,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

export function useLogin() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ email, password }: { email: string; password: string }) =>
      authApi.login(email, password),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user', 'me'] });
    },
  });
}

interface RegisterData {
  email: string;
  password: string;
  passwordConfirmation: string;
  firstName: string;
  lastName: string;
  displayName: string;
  role: string;
}

export function useRegister() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: RegisterData) => authApi.register(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user', 'me'] });
    },
  });
}

export function useLogout() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => authApi.logout(),
    onSuccess: () => {
      queryClient.clear();
    },
  });
}

// ============================================================================
// LISTINGS HOOKS
// ============================================================================

export function useListings(params: ListingSearchParams) {
  return useQuery({
    queryKey: ['listings', 'search', params],
    queryFn: () => listingsApi.search(params),
    staleTime: 60 * 1000, // 1 minute
  });
}

export function useListing(slug: string) {
  return useQuery({
    queryKey: ['listings', 'detail', slug],
    queryFn: async () => {
      const response = await listingsApi.getBySlug(slug);
      return response.data;
    },
    enabled: !!slug,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

export function useAvailability(
  listingSlug: string,
  startDate: string,
  endDate: string,
  enabled = true
) {
  return useQuery({
    queryKey: ['listings', listingSlug, 'availability', startDate, endDate],
    queryFn: async () => {
      const response = await listingsApi.getAvailability(listingSlug, startDate, endDate);
      return response.data;
    },
    enabled: enabled && !!listingSlug && !!startDate && !!endDate,
    staleTime: 30 * 1000, // 30 seconds
  });
}

export function useCreateHold(listingSlug: string) {
  return useMutation({
    mutationFn: (request: CreateHoldRequest) => listingsApi.createHold(listingSlug, request),
  });
}

// ============================================================================
// BOOKINGS HOOKS
// ============================================================================

export function useMyBookings(params?: { status?: string; page?: number }) {
  return useQuery({
    queryKey: ['bookings', 'mine', params],
    queryFn: () => bookingsApi.list(params),
  });
}

export function useBooking(id: string) {
  return useQuery({
    queryKey: ['bookings', id],
    queryFn: async () => {
      const response = await bookingsApi.getById(id);
      return response.data;
    },
    enabled: !!id,
  });
}

export function useCreateBooking() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: bookingsApi.create,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] });
    },
  });
}

export function useCancelBooking() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ id, reason }: { id: string; reason?: string }) => bookingsApi.cancel(id, reason),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] });
    },
  });
}

export function useProcessPayment() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ bookingId, request }: { bookingId: string; request: ProcessPaymentRequest }) =>
      bookingsApi.processPayment(bookingId, request),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] });
    },
  });
}

// ============================================================================
// REVIEWS HOOKS
// ============================================================================

export function useListingReviews(listingId: string, params?: { page?: number; sort?: string }) {
  return useQuery({
    queryKey: ['reviews', 'listing', listingId, params],
    queryFn: () => reviewsApi.getForListing(listingId, params),
    enabled: !!listingId,
  });
}

export function useCreateReview() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ bookingId, request }: { bookingId: string; request: CreateReviewRequest }) =>
      reviewsApi.create(bookingId, request),
    onSuccess: (_, { bookingId }) => {
      queryClient.invalidateQueries({ queryKey: ['bookings', bookingId] });
      queryClient.invalidateQueries({ queryKey: ['reviews'] });
    },
  });
}

export function useMarkReviewHelpful() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: reviewsApi.markHelpful,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['reviews'] });
    },
  });
}

// ============================================================================
// COUPONS HOOKS
// ============================================================================

export function useValidateCoupon() {
  return useMutation({
    mutationFn: ({
      code,
      listingId,
      amount,
    }: {
      code: string;
      listingId: string;
      amount: number;
    }) => couponsApi.validate(code, listingId, amount),
  });
}

// ============================================================================
// VENDORS HOOKS
// ============================================================================

export function useVendorProfile(vendorId: string) {
  return useQuery({
    queryKey: ['vendors', vendorId],
    queryFn: () => vendorsApi.getProfile(vendorId),
    enabled: !!vendorId,
  });
}

export function useVendorListings(vendorId: string, params?: { page?: number }) {
  return useQuery({
    queryKey: ['vendors', vendorId, 'listings', params],
    queryFn: () => vendorsApi.getListings(vendorId, params),
    enabled: !!vendorId,
  });
}
