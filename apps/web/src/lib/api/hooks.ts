import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { authApi, listingsApi, bookingsApi } from './client';
import type {
  ListingSearchParams,
  CreateHoldRequest,
  CreateBookingRequest,
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

export function useRegister() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({
      email,
      password,
      displayName,
    }: {
      email: string;
      password: string;
      displayName: string;
    }) => authApi.register(email, password, displayName),
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
  listingId: string,
  startDate: string,
  endDate: string,
  enabled = true
) {
  return useQuery({
    queryKey: ['listings', listingId, 'availability', startDate, endDate],
    queryFn: async () => {
      const response = await listingsApi.getAvailability(listingId, startDate, endDate);
      return response.data;
    },
    enabled: enabled && !!listingId && !!startDate && !!endDate,
    staleTime: 30 * 1000, // 30 seconds
  });
}

export function useCreateHold(listingId: string) {
  return useMutation({
    mutationFn: (request: CreateHoldRequest) => listingsApi.createHold(listingId, request),
  });
}

// ============================================================================
// BOOKINGS HOOKS
// ============================================================================

export function useCreateBooking() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (request: CreateBookingRequest) => bookingsApi.create(request),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] });
    },
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

export function useMyBookings() {
  return useQuery({
    queryKey: ['bookings', 'me'],
    queryFn: async () => {
      const response = await bookingsApi.getMyBookings();
      return response.data;
    },
  });
}
