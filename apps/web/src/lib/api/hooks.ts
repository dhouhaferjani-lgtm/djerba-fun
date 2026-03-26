import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  authApi,
  listingsApi,
  bookingsApi,
  reviewsApi,
  couponsApi,
  vendorsApi,
  cartApi,
  participantsApi,
  vouchersApi,
  platformApi,
  userApi,
  locationsApi,
  activityTypesApi,
  categoryStatsApi,
  tagsApi,
  wishlistApi,
  menusApi,
  testimonialsApi,
  type ProcessPaymentRequest,
  type Cart,
  type UpdateParticipantData,
  type UpdateProfileData,
  type UpdatePasswordData,
  type UpdatePreferencesData,
  type Location,
} from './client';
import { getGuestSessionId } from '@/lib/utils/session';
import type {
  ListingSearchParams,
  CreateHoldRequest,
  CreateReviewRequest,
  TagType,
  Tag,
  TagGroup,
} from '@djerba-fun/schemas';

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
    mutationFn: ({
      email,
      password,
      cfTurnstileResponse,
    }: {
      email: string;
      password: string;
      cfTurnstileResponse?: string;
    }) => authApi.login(email, password, cfTurnstileResponse),
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
  cfTurnstileResponse?: string;
}

export function useRegister() {
  return useMutation({
    mutationFn: (data: RegisterData) => authApi.register(data),
    // No onSuccess — user is not logged in yet (must verify email first)
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

export function useListings(params: ListingSearchParams, locale: string = 'en') {
  return useQuery({
    queryKey: ['listings', 'search', locale, params],
    queryFn: async () => {
      const result = await listingsApi.search(params, locale);

      // Store the detected currency in a cookie for server-side requests
      // This ensures consistent currency across client-side (list page) and server-side (detail page) rendering
      if (typeof window !== 'undefined' && result.data && result.data.length > 0) {
        const detectedCurrency = result.data[0]?.pricing?.displayCurrency;
        if (detectedCurrency && (detectedCurrency === 'TND' || detectedCurrency === 'EUR')) {
          document.cookie = `user_currency=${detectedCurrency}; path=/; max-age=${60 * 60 * 24}; SameSite=Lax`;
        }
      }

      return result;
    },
    staleTime: 15 * 1000, // 15 seconds - faster updates for newly approved listings
    gcTime: 5 * 60 * 1000, // Keep in cache for 5 minutes
  });
}

export function useListing(slug: string, locale: string = 'en') {
  return useQuery({
    queryKey: ['listings', 'detail', locale, slug],
    queryFn: async () => {
      const response = await listingsApi.getBySlug(slug, locale);
      return response.data;
    },
    enabled: !!slug,
    staleTime: 30 * 1000, // 30 seconds - faster updates for listing details
    gcTime: 5 * 60 * 1000, // Keep in cache for 5 minutes
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

export function useListingExtras(
  listingSlug: string,
  options?: { slotId?: string; personTypes?: string[] },
  enabled = true,
  locale: string = 'en'
) {
  return useQuery({
    queryKey: ['listings', listingSlug, 'extras', locale, options?.slotId, options?.personTypes],
    queryFn: async () => {
      const response = await listingsApi.getExtras(listingSlug, options, locale);
      return response.data;
    },
    enabled: enabled && !!listingSlug,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

export function useHold(holdId: string | null) {
  return useQuery({
    queryKey: ['holds', holdId],
    queryFn: async () => {
      if (!holdId) return null;
      const response = await listingsApi.getHold(holdId);
      return response.data;
    },
    enabled: !!holdId,
    refetchInterval: 30000, // Refresh every 30s to check expiry
    retry: (failureCount, error) => {
      // Don't retry on 410 (expired) or 404 (not found)
      if (error && 'status' in error && (error.status === 410 || error.status === 404)) {
        return false;
      }
      return failureCount < 3;
    },
  });
}

// ============================================================================
// LOCATIONS HOOKS
// ============================================================================

export function useLocations() {
  return useQuery({
    queryKey: ['locations'],
    queryFn: async () => {
      const response = await locationsApi.list();
      return response.data;
    },
    staleTime: 10 * 60 * 1000, // 10 minutes - locations don't change often
    gcTime: 60 * 60 * 1000, // Keep in cache for 1 hour
  });
}

export function useLocation(slug: string, locale?: string) {
  return useQuery({
    queryKey: ['locations', slug, locale],
    queryFn: () => locationsApi.getBySlug(slug, locale),
    enabled: !!slug,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

// ============================================================================
// ACTIVITY TYPES HOOKS
// ============================================================================

export function useActivityTypes() {
  return useQuery({
    queryKey: ['activityTypes'],
    queryFn: async () => {
      const response = await activityTypesApi.list();
      return response.data;
    },
    staleTime: 60 * 60 * 1000, // 1 hour - activity types rarely change
    gcTime: 24 * 60 * 60 * 1000, // Keep in cache for 24 hours
  });
}

// ============================================================================
// TAGS HOOKS
// ============================================================================

/**
 * Fetch all active tags, optionally filtered by type or service type
 * @param params.type - Filter by tag type (tour_type, boat_type, etc.)
 * @param params.serviceType - Filter by applicable service type (tour, nautical, etc.)
 */
export function useTags(params?: { type?: TagType; serviceType?: string }) {
  return useQuery({
    queryKey: ['tags', params?.type, params?.serviceType],
    queryFn: async () => {
      const response = await tagsApi.list(params);
      if ('data' in response) {
        return response.data;
      }
      // If grouped response, flatten
      return (response as TagGroup[]).flatMap((group) => group.tags as Tag[]);
    },
    staleTime: 30 * 60 * 1000, // 30 minutes - tags change infrequently
    gcTime: 60 * 60 * 1000, // Keep in cache for 1 hour
  });
}

/**
 * Fetch tags for a specific service type, grouped by tag type
 * Used for building filter UI on listings page
 */
export function useTagsForServiceType(serviceType: string, enabled = true) {
  return useQuery({
    queryKey: ['tags', 'forService', serviceType],
    queryFn: () => tagsApi.forServiceType(serviceType),
    enabled: enabled && !!serviceType,
    staleTime: 30 * 60 * 1000, // 30 minutes
    gcTime: 60 * 60 * 1000, // 1 hour
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

export function useBooking(id: string, useGuestAccess = false) {
  return useQuery({
    queryKey: ['bookings', id, useGuestAccess ? 'guest' : 'auth'],
    queryFn: async () => {
      // Check if user is authenticated
      const token = typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null;

      if (token && !useGuestAccess) {
        // Use authenticated endpoint
        const response = await bookingsApi.getById(id);
        return response.data;
      } else {
        // Use guest endpoint with session_id
        const sessionId = getGuestSessionId();
        if (!sessionId) {
          throw new Error('No session ID available for guest access');
        }
        const response = await bookingsApi.getByIdGuest(id, sessionId);
        return response.data;
      }
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
// PARTICIPANTS HOOKS
// ============================================================================

export function useParticipants(bookingId: string, useGuestAccess = false) {
  return useQuery({
    queryKey: ['participants', bookingId, useGuestAccess ? 'guest' : 'auth'],
    queryFn: async () => {
      const token = typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null;

      if (token && !useGuestAccess) {
        return participantsApi.list(bookingId);
      } else {
        const sessionId = getGuestSessionId();
        if (!sessionId) {
          throw new Error('No session ID available for guest access');
        }
        return participantsApi.listGuest(bookingId, sessionId);
      }
    },
    enabled: !!bookingId,
  });
}

export function useUpdateParticipants(useGuestAccess = false) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({
      bookingId,
      participants,
    }: {
      bookingId: string;
      participants: UpdateParticipantData[];
    }) => {
      const token = typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null;

      if (token && !useGuestAccess) {
        return participantsApi.update(bookingId, participants);
      } else {
        const sessionId = getGuestSessionId();
        if (!sessionId) {
          throw new Error('No session ID available for guest access');
        }
        return participantsApi.updateGuest(bookingId, participants, sessionId);
      }
    },
    onSuccess: (_, { bookingId }) => {
      queryClient.invalidateQueries({ queryKey: ['participants', bookingId] });
      queryClient.invalidateQueries({ queryKey: ['bookings', bookingId] });
      queryClient.invalidateQueries({ queryKey: ['vouchers', bookingId] });
    },
  });
}

/**
 * Bulk apply participant names to multiple bookings at once.
 * Used when user selects "same participants for all tours" option after cart checkout.
 */
export function useBulkApplyParticipants(useGuestAccess = false) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({
      bookingIds,
      participants,
    }: {
      bookingIds: string[];
      participants: Array<{
        first_name: string;
        last_name: string;
        email?: string | null;
        phone?: string | null;
      }>;
    }) => {
      const token = typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null;

      if (token && !useGuestAccess) {
        return participantsApi.bulkApply(bookingIds, participants);
      } else {
        const sessionId = getGuestSessionId();
        if (!sessionId) {
          throw new Error('No session ID available for guest access');
        }
        return participantsApi.bulkApplyGuest(bookingIds, participants, sessionId);
      }
    },
    onSuccess: (_, { bookingIds }) => {
      // Invalidate all affected bookings
      bookingIds.forEach((bookingId) => {
        queryClient.invalidateQueries({ queryKey: ['participants', bookingId] });
        queryClient.invalidateQueries({ queryKey: ['bookings', bookingId] });
        queryClient.invalidateQueries({ queryKey: ['vouchers', bookingId] });
      });
    },
  });
}

// ============================================================================
// VOUCHERS HOOKS
// ============================================================================

export function useVouchers(bookingId: string, enabled = true, useGuestAccess = false) {
  return useQuery({
    queryKey: ['vouchers', bookingId, useGuestAccess ? 'guest' : 'auth'],
    queryFn: async () => {
      const token = typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null;

      if (token && !useGuestAccess) {
        return vouchersApi.list(bookingId);
      } else {
        const sessionId = getGuestSessionId();
        if (!sessionId) {
          throw new Error('No session ID available for guest access');
        }
        return vouchersApi.listGuest(bookingId, sessionId);
      }
    },
    enabled: !!bookingId && enabled,
  });
}

export function useVoucher(bookingId: string, voucherCode: string) {
  return useQuery({
    queryKey: ['vouchers', bookingId, voucherCode],
    queryFn: () => vouchersApi.get(bookingId, voucherCode),
    enabled: !!bookingId && !!voucherCode,
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

export function useReviewSummary(listingSlug: string) {
  return useQuery({
    queryKey: ['reviews', 'summary', listingSlug],
    queryFn: () => reviewsApi.getSummary(listingSlug),
    enabled: !!listingSlug,
  });
}

export function useCanReview(listingSlug: string, isAuthenticated: boolean) {
  return useQuery({
    queryKey: ['reviews', 'canReview', listingSlug],
    queryFn: () => reviewsApi.canReview(listingSlug),
    enabled: !!listingSlug && isAuthenticated,
    staleTime: 60 * 1000, // 1 minute
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

/**
 * Mutation hook for validating a coupon code for an entire cart.
 * Supports partial application - returns which items the coupon applies to.
 */
export function useValidateCartCoupon() {
  return useMutation({
    mutationFn: ({ code, sessionId }: { code: string; sessionId?: string }) =>
      couponsApi.validateForCart(code, sessionId),
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

// ============================================================================
// CART HOOKS
// ============================================================================

/**
 * Get or create a session ID for guest cart operations.
 * Uses the same session ID as booking holds for consistency.
 */
export function getOrCreateSessionId(): string {
  return getGuestSessionId();
}

export function useCart() {
  return useQuery({
    queryKey: ['cart'],
    queryFn: async () => {
      const sessionId = getGuestSessionId();
      const response = await cartApi.getCart(sessionId);
      // Handle both { data: Cart } and { message: string; cart: null }
      if ('data' in response && response.data) {
        return response.data as Cart;
      }
      return null;
    },
    staleTime: 30 * 1000, // 30 seconds
    refetchInterval: 60 * 1000, // Refresh every minute to check expiration
  });
}

export function useCartSummary() {
  return useQuery({
    queryKey: ['cart', 'summary'],
    queryFn: async () => {
      const sessionId = getGuestSessionId();
      return cartApi.getSummary(sessionId);
    },
    staleTime: 30 * 1000,
  });
}

export function useAddToCart() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (holdId: string) => {
      const sessionId = getOrCreateSessionId();
      return cartApi.addItem(holdId, sessionId);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cart'] });
    },
  });
}

export function useRemoveFromCart() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (itemId: string) => {
      const sessionId = getGuestSessionId();
      return cartApi.removeItem(itemId, sessionId);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cart'] });
    },
  });
}

export function useUpdateCartItem() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({
      itemId,
      data,
    }: {
      itemId: string;
      data: {
        primaryContact?: {
          first_name: string;
          last_name: string;
          email: string;
          phone?: string;
        };
        guestNames?: Array<{
          first_name: string;
          last_name: string;
          person_type?: string;
        }>;
        extras?: Array<{
          id: string;
          name: string;
          price: number;
          quantity: number;
        }>;
      };
    }) => {
      const sessionId = getGuestSessionId();
      return cartApi.updateItem(itemId, { ...data, sessionId });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cart'] });
    },
  });
}

export function useClearCart() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => {
      const sessionId = getGuestSessionId();
      return cartApi.clearCart(sessionId);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cart'] });
    },
  });
}

export function useExtendCartHolds() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => {
      const sessionId = getGuestSessionId();
      return cartApi.extendHolds(sessionId);
    },
    onSuccess: (data) => {
      // Immediately update the cart's expiration time in cache
      queryClient.setQueryData(['cart'], (oldCart: Cart | null | undefined) => {
        if (!oldCart) return oldCart;

        // Update hold validity on items based on what was extended
        const updatedItems = oldCart.items.map((item) => {
          // If item was in the unavailable list, mark it as invalid
          const isUnavailable = data.unavailable?.some((u) => u.item_id === item.id);
          return {
            ...item,
            holdValid: !isUnavailable && (item.holdValid || data.extended > 0),
          };
        });

        return {
          ...oldCart,
          items: updatedItems,
          expiresAt: data.expires_at,
          expiresInSeconds: data.expiresInSeconds,
          isExpired: data.extended === 0 && data.failed > 0,
          isActive: data.extended > 0,
        };
      });
      // Also trigger a refetch to ensure full consistency
      queryClient.invalidateQueries({ queryKey: ['cart'] });
    },
  });
}

export function useMergeCart() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => {
      const sessionId = getGuestSessionId();
      if (!sessionId) throw new Error('No session to merge');
      return cartApi.mergeCart(sessionId);
    },
    onSuccess: () => {
      // Clear the session ID after merge
      if (typeof window !== 'undefined') {
        localStorage.removeItem('cart_session_id');
      }
      queryClient.invalidateQueries({ queryKey: ['cart'] });
    },
  });
}

// Checkout hooks
export function useInitiateCheckout() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ paymentMethod, couponCode }: { paymentMethod: string; couponCode?: string }) => {
      const sessionId = getGuestSessionId();
      return cartApi.initiateCheckout(paymentMethod, sessionId, couponCode);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cart'] });
    },
  });
}

export function useProcessCartPayment() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({
      paymentId,
      paymentData,
    }: {
      paymentId: string;
      paymentData?: Record<string, unknown>;
    }) => {
      const sessionId = getGuestSessionId();
      return cartApi.processPayment(paymentId, paymentData, sessionId);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cart'] });
      queryClient.invalidateQueries({ queryKey: ['bookings'] });
    },
  });
}

export function useCheckoutStatus(paymentId: string | null) {
  return useQuery({
    queryKey: ['cart', 'checkout', paymentId],
    queryFn: async () => {
      if (!paymentId) return null;
      const sessionId = getGuestSessionId();
      return cartApi.getCheckoutStatus(paymentId, sessionId);
    },
    enabled: !!paymentId,
    refetchInterval: 5000, // Poll every 5 seconds during checkout
  });
}

export function useCancelCheckout() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => {
      const sessionId = getGuestSessionId();
      return cartApi.cancelCheckout(sessionId);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['cart'] });
    },
  });
}

// ============================================================================
// PLATFORM SETTINGS HOOKS
// ============================================================================

/**
 * Fetch public platform settings (non-sensitive configuration)
 * Used for frontend configuration, branding, feature flags, etc.
 *
 * @param locale - Optional locale for translated content
 */
export function usePlatformSettings(locale?: string) {
  return useQuery({
    queryKey: ['platform', 'settings', locale],
    queryFn: async () => {
      const response = await platformApi.getSettings(locale);
      return response.data;
    },
    staleTime: 5 * 60 * 1000, // 5 minutes - more responsive to admin changes
    gcTime: 60 * 60 * 1000, // Keep in cache for 1 hour
  });
}

/**
 * Fetch schema.org JSON-LD structured data
 * Used for SEO and search engine rich snippets
 *
 * @param locale - Optional locale for translated content
 */
export function useSchemaOrg(locale?: string) {
  return useQuery({
    queryKey: ['platform', 'schema', locale],
    queryFn: () => platformApi.getSchemaOrg(locale),
    staleTime: 60 * 60 * 1000, // 1 hour
    gcTime: 24 * 60 * 60 * 1000, // Keep in cache for 24 hours
  });
}

/**
 * Check if a specific feature is enabled
 * Convenience hook that uses platform settings
 *
 * @param feature - The feature name to check (e.g., 'reviews', 'blog', 'wishlists')
 * @param locale - Optional locale
 */
export function useFeatureEnabled(
  feature: keyof import('@djerba-fun/schemas').PlatformFeatures,
  locale?: string
) {
  const { data: settings, isLoading } = usePlatformSettings(locale);

  return {
    enabled: settings?.features?.[feature] ?? false,
    isLoading,
  };
}

// ============================================================================
// CMS MENU HOOKS
// ============================================================================

/**
 * Fetch a CMS-managed menu by its code
 * Used for header and footer navigation that can be edited via Filament admin
 *
 * @param menuCode - The menu code (e.g., 'header', 'footer-company', 'footer-support', 'footer-legal')
 * @param locale - The locale for translated labels (defaults to 'en')
 */
export function useMenu(menuCode: string, locale: string = 'en') {
  return useQuery({
    queryKey: ['menus', menuCode, locale],
    queryFn: () => menusApi.getMenu(menuCode, locale),
    staleTime: 5 * 60 * 1000, // 5 minutes - menus change rarely
    gcTime: 60 * 60 * 1000, // Keep in cache for 1 hour
    retry: 2,
  });
}

// ============================================================================
// PASSWORDLESS AUTHENTICATION HOOKS
// ============================================================================

/**
 * Send magic link to user's email
 */
export function useSendMagicLink() {
  return useMutation({
    mutationFn: ({ email, cfTurnstileResponse }: { email: string; cfTurnstileResponse?: string }) =>
      authApi.sendMagicLink(email, cfTurnstileResponse),
  });
}

/**
 * Verify magic link token and log in user
 */
export function useVerifyMagicLink() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ token, deviceName }: { token: string; deviceName?: string }) =>
      authApi.verifyMagicLink(token, deviceName),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user'] });
    },
  });
}

/**
 * Register passwordless user account
 */
export function useRegisterPasswordless() {
  return useMutation({
    mutationFn: (data: {
      email: string;
      firstName: string;
      lastName: string;
      phone?: string;
      preferredLocale?: 'en' | 'fr';
      cfTurnstileResponse?: string;
    }) => authApi.registerPasswordless(data),
  });
}

// ============================================================================
// BOOKING LINKING HOOKS
// ============================================================================

/**
 * Get claimable bookings for authenticated user
 */
export function useClaimableBookings(enabled: boolean = true) {
  return useQuery({
    queryKey: ['bookings', 'claimable'],
    queryFn: async () => {
      const response = await bookingsApi.getClaimable();
      return response.data;
    },
    enabled,
  });
}

/**
 * Link selected bookings to user account
 */
export function useLinkBookings() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (bookingIds: string[]) => bookingsApi.link(bookingIds),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] });
      queryClient.invalidateQueries({ queryKey: ['bookings', 'claimable'] });
    },
  });
}

/**
 * Claim booking by booking number
 */
export function useClaimBooking() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (bookingNumber: string) => bookingsApi.claim(bookingNumber),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['bookings'] });
      queryClient.invalidateQueries({ queryKey: ['bookings', 'claimable'] });
    },
  });
}

// ============================================================================
// USER PROFILE HOOKS
// ============================================================================

/**
 * Get user profile (alternative to useCurrentUser for profile pages)
 */
export function useProfile() {
  return useQuery({
    queryKey: ['user', 'profile'],
    queryFn: async () => {
      const response = await userApi.getProfile();
      return response.data;
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

/**
 * Update user profile
 */
export function useUpdateProfile() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: UpdateProfileData) => userApi.updateProfile(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user'] });
    },
  });
}

/**
 * Update user password
 */
export function useUpdatePassword() {
  return useMutation({
    mutationFn: (data: UpdatePasswordData) => userApi.updatePassword(data),
  });
}

/**
 * Upload user avatar
 */
export function useUploadAvatar() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (file: File) => userApi.uploadAvatar(file),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user'] });
    },
  });
}

/**
 * Delete user avatar
 */
export function useDeleteAvatar() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => userApi.deleteAvatar(),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user'] });
    },
  });
}

/**
 * Get user preferences
 */
export function usePreferences() {
  return useQuery({
    queryKey: ['user', 'preferences'],
    queryFn: async () => {
      const response = await userApi.getPreferences();
      return response.data;
    },
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
}

/**
 * Update user preferences
 */
export function useUpdatePreferences() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: UpdatePreferencesData) => userApi.updatePreferences(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['user'] });
    },
  });
}

/**
 * Export user data (GDPR)
 */
export function useExportData() {
  return useMutation({
    mutationFn: () => userApi.exportData(),
  });
}

/**
 * Delete user account (GDPR)
 */
export function useDeleteAccount() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => userApi.deleteAccount(),
    onSuccess: () => {
      queryClient.clear();
      if (typeof window !== 'undefined') {
        localStorage.removeItem('auth_token');
      }
    },
  });
}

// ============================================================================
// CATEGORY STATS HOOKS
// ============================================================================

/**
 * Fetch category statistics for homepage
 * Returns counts and images for tours and events
 */
export function useCategoryStats() {
  return useQuery({
    queryKey: ['category-stats'],
    queryFn: async () => {
      const response = await categoryStatsApi.getStats();
      return response.data;
    },
    staleTime: 5 * 60 * 1000, // 5 minutes - matches backend cache
    gcTime: 30 * 60 * 1000, // Keep in cache for 30 minutes
  });
}

// ============================================================================
// WISHLIST HOOKS
// ============================================================================

/**
 * Get user's wishlist (paginated)
 */
export function useWishlist(params?: { page?: number; per_page?: number }, enabled = true) {
  return useQuery({
    queryKey: ['wishlist', params],
    queryFn: () => wishlistApi.list(params),
    enabled,
    staleTime: 30 * 1000, // 30 seconds
  });
}

/**
 * Get wishlist listing IDs for efficient client-side checking
 * Used to show filled heart on listing cards
 */
export function useWishlistIds(enabled = true) {
  return useQuery({
    queryKey: ['wishlist', 'ids'],
    queryFn: async () => {
      const response = await wishlistApi.getIds();
      return response.data.listing_ids;
    },
    enabled,
    staleTime: 60 * 1000, // 1 minute
  });
}

/**
 * Toggle a listing in the wishlist
 */
export function useToggleWishlist() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (listingId: string) => wishlistApi.toggle(listingId),
    onMutate: async (listingId) => {
      // Cancel any outgoing refetches
      await queryClient.cancelQueries({ queryKey: ['wishlist'] });

      // Snapshot the previous value (default to empty array if not cached yet)
      const previousIds = queryClient.getQueryData<string[]>(['wishlist', 'ids']) ?? [];

      // Optimistically update wishlist IDs
      const isInWishlist = previousIds.includes(listingId);
      queryClient.setQueryData<string[]>(
        ['wishlist', 'ids'],
        isInWishlist ? previousIds.filter((id) => id !== listingId) : [...previousIds, listingId]
      );

      return { previousIds };
    },
    onError: (_err, _listingId, context) => {
      // Rollback on error
      if (context?.previousIds !== undefined) {
        queryClient.setQueryData(['wishlist', 'ids'], context.previousIds);
      }
    },
    onSettled: () => {
      // Refetch to ensure consistency
      queryClient.invalidateQueries({ queryKey: ['wishlist'] });
    },
  });
}

/**
 * Check if a specific listing is in the wishlist
 */
export function useIsInWishlist(listingId: string, enabled = true) {
  const { data: wishlistIds, isLoading } = useWishlistIds(enabled);

  return {
    isInWishlist: wishlistIds?.includes(listingId) ?? false,
    isLoading,
  };
}

// ============================================================================
// TESTIMONIALS HOOKS
// ============================================================================

/**
 * Fetch testimonials for homepage display
 * Returns active testimonials ordered by sort_order
 *
 * @param locale - Locale for translated content ('fr' or 'en')
 * @param limit - Maximum number of testimonials to fetch (default: 10, max: 20)
 */
export function useTestimonials(locale: string = 'fr', limit: number = 10) {
  return useQuery({
    queryKey: ['testimonials', locale, limit],
    queryFn: async () => {
      const response = await testimonialsApi.getAll(locale, limit);
      return response.data;
    },
    staleTime: 5 * 60 * 1000, // 5 minutes - testimonials change infrequently
    gcTime: 30 * 60 * 1000, // Keep in cache for 30 minutes
  });
}

/**
 * Fetch a single testimonial by UUID
 *
 * @param uuid - Testimonial UUID
 * @param locale - Locale for translated content
 */
export function useTestimonial(uuid: string, locale: string = 'fr') {
  return useQuery({
    queryKey: ['testimonials', uuid, locale],
    queryFn: async () => {
      const response = await testimonialsApi.getById(uuid, locale);
      return response.data;
    },
    enabled: !!uuid,
    staleTime: 5 * 60 * 1000,
  });
}
