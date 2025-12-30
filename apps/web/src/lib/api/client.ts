import type {
  User,
  Listing,
  ListingSearchParams,
  ListingSearchResponse,
  AvailabilitySlot,
  Booking,
  CreateHoldRequest,
  CreateHoldResponse,
  CreateBookingRequest,
  Review,
  CreateReviewRequest,
  CouponValidation,
  VendorPublicProfile,
  ListingSummary,
  ListingExtraForBooking,
  PlatformSettings,
  PlatformSettingsResponse,
  SchemaOrgData,
} from '@go-adventure/schemas';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public code?: string,
    public details?: Record<string, unknown>
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

async function fetchApi<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const token = typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null;

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };

  if (options.headers) {
    Object.assign(headers, options.headers);
  }

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const response = await fetch(`${API_URL}${endpoint}`, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({
      message: response.statusText,
    }));

    // Handle 401 Unauthorized - clear auth token and optionally redirect
    if (response.status === 401) {
      if (typeof window !== 'undefined') {
        localStorage.removeItem('auth_token');
        // Dispatch custom event for auth context to handle
        window.dispatchEvent(new CustomEvent('auth:unauthorized'));
      }
    }

    // Laravel returns validation errors in format: { message: "...", errors: { field: ["..."] } }
    // Custom API errors may use: { error: { message: "...", code: "...", details: {...} } }
    const message = error.message || error.error?.message || 'An error occurred';
    const code = error.error?.code || (error.errors ? 'VALIDATION_ERROR' : 'UNKNOWN_ERROR');
    const details = error.errors || error.error?.details;

    throw new ApiError(message, response.status, code, details);
  }

  return response.json();
}

// ============================================================================
// AUTH API
// ============================================================================

export const authApi = {
  login: async (email: string, password: string) => {
    const data = await fetchApi<{ user: User; token: string }>('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });

    if (typeof window !== 'undefined') {
      localStorage.setItem('auth_token', data.token);
    }

    return data;
  },

  register: async (data: {
    email: string;
    password: string;
    passwordConfirmation: string;
    firstName: string;
    lastName: string;
    displayName: string;
    role: string;
  }) => {
    const response = await fetchApi<{ user: User; token: string }>('/auth/register', {
      method: 'POST',
      body: JSON.stringify({
        email: data.email,
        password: data.password,
        password_confirmation: data.passwordConfirmation,
        first_name: data.firstName,
        last_name: data.lastName,
        display_name: data.displayName,
        role: data.role,
      }),
    });

    if (typeof window !== 'undefined') {
      localStorage.setItem('auth_token', response.token);
    }

    return response;
  },

  logout: async () => {
    try {
      await fetchApi('/auth/logout', { method: 'POST' });
    } finally {
      if (typeof window !== 'undefined') {
        localStorage.removeItem('auth_token');
      }
    }
  },

  getCurrentUser: async () => {
    return fetchApi<{ data: User }>('/auth/me');
  },

  // Passwordless authentication
  sendMagicLink: async (email: string) => {
    return fetchApi<{ message: string }>('/auth/magic-link/send', {
      method: 'POST',
      body: JSON.stringify({ email }),
    });
  },

  verifyMagicLink: async (token: string, deviceName?: string) => {
    const data = await fetchApi<{ user: User; token: string }>('/auth/magic-link/verify', {
      method: 'POST',
      body: JSON.stringify({ token, device_name: deviceName }),
    });

    if (typeof window !== 'undefined') {
      localStorage.setItem('auth_token', data.token);
    }

    return data;
  },

  registerPasswordless: async (data: {
    email: string;
    firstName: string;
    lastName: string;
    phone?: string;
    preferredLocale?: 'en' | 'fr';
  }) => {
    return fetchApi<{
      message: string;
      user: { email: string; first_name: string; last_name: string };
    }>('/auth/magic-link/register', {
      method: 'POST',
      body: JSON.stringify({
        email: data.email,
        first_name: data.firstName,
        last_name: data.lastName,
        phone: data.phone,
        preferred_locale: data.preferredLocale || 'en',
      }),
    });
  },
};

// ============================================================================
// LISTINGS API
// ============================================================================

export const listingsApi = {
  search: async (params: ListingSearchParams) => {
    const queryParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        queryParams.append(key, String(value));
      }
    });

    return fetchApi<ListingSearchResponse>(`/listings?${queryParams.toString()}`);
  },

  getBySlug: async (slug: string) => {
    return fetchApi<{ data: Listing }>(`/listings/${slug}`);
  },

  getAvailability: async (listingSlug: string, startDate: string, endDate: string) => {
    const params = new URLSearchParams({ start_date: startDate, end_date: endDate });
    return fetchApi<{ data: AvailabilitySlot[] }>(
      `/listings/${listingSlug}/availability?${params.toString()}`
    );
  },

  createHold: async (
    listingSlug: string,
    request: CreateHoldRequest & {
      session_id?: string;
      quantity?: number;
      person_types?: Record<string, number>;
    }
  ) => {
    // Build request body - either use person_types or quantity
    const body: Record<string, unknown> = {
      slot_id: request.slotId,
      session_id: request.session_id,
    };

    // If person_types is provided and has values, use it; otherwise use quantity
    if (request.person_types && Object.keys(request.person_types).length > 0) {
      body.person_types = request.person_types;
    } else {
      body.quantity = request.quantity ?? request.guests;
    }

    return fetchApi<{ data: CreateHoldResponse }>(`/listings/${listingSlug}/holds`, {
      method: 'POST',
      body: JSON.stringify(body),
    });
  },

  getHold: async (holdId: string) => {
    return fetchApi<{
      data: CreateHoldResponse & {
        listing: Listing;
        slot: AvailabilitySlot;
      };
    }>(`/holds/${holdId}`);
  },

  getExtras: async (listingSlug: string, params?: { slotId?: string; personTypes?: string[] }) => {
    const queryParams = new URLSearchParams();
    if (params?.slotId) {
      queryParams.append('slot_id', params.slotId);
    }
    if (params?.personTypes?.length) {
      queryParams.append('person_types', params.personTypes.join(','));
    }
    const queryString = queryParams.toString();
    return fetchApi<{ data: ListingExtraForBooking[] }>(
      `/listings/${listingSlug}/extras${queryString ? `?${queryString}` : ''}`
    );
  },

  calculateExtras: async (
    listingSlug: string,
    request: {
      extras: Array<{ id: string; quantity: number }>;
      personTypes?: Record<string, number>;
      currency?: 'TND' | 'EUR';
    }
  ) => {
    return fetchApi<{
      valid: boolean;
      errors?: Array<{ field: string; message: string; extraId?: string }>;
      calculation?: {
        items: Array<{
          listingExtraId: string;
          extraId: string;
          name: Record<string, string>;
          quantity: number;
          pricingType: string;
          unitPrice: number | null;
          subtotal: number;
          breakdown?: Record<string, { count: number; unit_price: number; total: number }>;
          calculation: string;
        }>;
        subtotal: number;
        currency: string;
        itemCount: number;
      };
    }>(`/listings/${listingSlug}/extras/calculate`, {
      method: 'POST',
      body: JSON.stringify({
        extras: request.extras,
        person_types: request.personTypes,
        currency: request.currency,
      }),
    });
  },
};

// ============================================================================
// BOOKINGS API
// ============================================================================

export interface ProcessPaymentRequest {
  paymentMethod: 'mock' | 'offline' | 'click_to_pay' | 'stripe' | 'paypal';
  paymentData?: Record<string, unknown>;
  sessionId?: string;
}

export const bookingsApi = {
  create: async (request: CreateBookingRequest & { sessionId?: string }) => {
    // Convert all travelers to snake_case for Laravel backend
    const travelers = request.travelers?.map((traveler) => ({
      first_name: traveler.firstName,
      last_name: traveler.lastName,
      email: traveler.email || '',
      phone: traveler.phone || '',
      person_type: traveler.personType || undefined,
      special_requests: traveler.specialRequests || undefined,
    }));

    return fetchApi<{ data: Booking }>('/bookings', {
      method: 'POST',
      body: JSON.stringify({
        hold_id: request.holdId,
        session_id: request.sessionId,
        travelers: travelers,
        extras: [],
      }),
    });
  },

  list: async (params?: { status?: string; page?: number }) => {
    const queryParams = new URLSearchParams();
    if (params?.status) queryParams.append('status', params.status);
    if (params?.page) queryParams.append('page', String(params.page));
    return fetchApi<{ data: Booking[]; meta: { total: number; page: number; limit: number } }>(
      `/bookings?${queryParams.toString()}`
    );
  },

  getById: async (id: string) => {
    return fetchApi<{ data: Booking }>(`/bookings/${id}`);
  },

  getByIdGuest: async (id: string, sessionId: string) => {
    return fetchApi<{ data: Booking }>(`/bookings/${id}/guest`, {
      headers: { 'X-Session-ID': sessionId },
    });
  },

  getMyBookings: async () => {
    return fetchApi<{ data: Booking[] }>('/bookings/me');
  },

  cancel: async (id: string, reason?: string) => {
    return fetchApi<{ data: Booking }>(`/bookings/${id}/cancel`, {
      method: 'POST',
      body: JSON.stringify({ reason }),
    });
  },

  processPayment: async (bookingId: string, request: ProcessPaymentRequest) => {
    return fetchApi<{ data: Booking }>(`/bookings/${bookingId}/pay`, {
      method: 'POST',
      body: JSON.stringify({
        payment_method: request.paymentMethod,
        payment_data: request.paymentData,
        session_id: request.sessionId,
      }),
    });
  },

  // Booking linking
  getClaimable: async () => {
    return fetchApi<{ data: Booking[]; meta: { total: number } }>('/bookings/claimable');
  },

  link: async (bookingIds: string[]) => {
    return fetchApi<{
      data: Booking[];
      meta: { linked: number };
      message: string;
    }>('/bookings/link', {
      method: 'POST',
      body: JSON.stringify({ booking_ids: bookingIds }),
    });
  },

  claim: async (bookingNumber: string) => {
    return fetchApi<{ data: Booking; message: string }>('/bookings/claim', {
      method: 'POST',
      body: JSON.stringify({ booking_number: bookingNumber }),
    });
  },
};

// ============================================================================
// PARTICIPANTS API
// ============================================================================

export interface Participant {
  id: string;
  bookingId: string;
  voucherCode: string;
  firstName: string | null;
  lastName: string | null;
  fullName: string | null;
  email: string | null;
  phone: string | null;
  personType: string | null;
  specialRequests: string | null;
  checkedIn: boolean;
  checkedInAt: string | null;
  isComplete: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface UpdateParticipantData {
  id: string;
  first_name?: string;
  last_name?: string;
  email?: string;
  phone?: string;
  person_type?: string;
  special_requests?: string;
}

export const participantsApi = {
  list: async (bookingId: string) => {
    return fetchApi<{
      data: Participant[];
      meta: { total: number; complete: number; requiresNames: boolean };
    }>(`/bookings/${bookingId}/participants`);
  },

  listGuest: async (bookingId: string, sessionId: string) => {
    return fetchApi<{
      data: Participant[];
      meta: { total: number; complete: number; requiresNames: boolean };
    }>(`/bookings/${bookingId}/participants/guest`, {
      headers: { 'X-Session-ID': sessionId },
    });
  },

  update: async (bookingId: string, participants: UpdateParticipantData[]) => {
    return fetchApi<{ data: Booking; message: string; updated: number }>(
      `/bookings/${bookingId}/participants`,
      {
        method: 'PUT',
        body: JSON.stringify({ participants }),
      }
    );
  },

  updateGuest: async (
    bookingId: string,
    participants: UpdateParticipantData[],
    sessionId: string
  ) => {
    return fetchApi<{ data: Booking; message: string; updated: number }>(
      `/bookings/${bookingId}/participants/guest`,
      {
        method: 'PUT',
        body: JSON.stringify({ participants }),
        headers: { 'X-Session-ID': sessionId },
      }
    );
  },
};

// ============================================================================
// VOUCHERS API
// ============================================================================

export interface Voucher {
  voucherCode: string;
  qrCodeData: string;
  participant: {
    id: string;
    firstName: string | null;
    lastName: string | null;
    fullName: string | null;
    personType: string | null;
    checkedIn: boolean;
  };
  booking: {
    bookingNumber: string;
  };
  event: {
    title: string;
    date: string;
    time: string;
    location: string | null;
  };
  vendor: {
    name: string;
  };
}

export const vouchersApi = {
  list: async (bookingId: string) => {
    return fetchApi<{
      data: Voucher[];
      canGenerate: boolean;
      booking: { bookingNumber: string; listingTitle: string; eventDate: string };
    }>(`/bookings/${bookingId}/vouchers`);
  },

  listGuest: async (bookingId: string, sessionId: string) => {
    return fetchApi<{
      data: Voucher[];
      canGenerate: boolean;
      booking: { bookingNumber: string; listingTitle: string; eventDate: string };
    }>(`/bookings/${bookingId}/vouchers/guest`, {
      headers: { 'X-Session-ID': sessionId },
    });
  },

  get: async (bookingId: string, voucherCode: string) => {
    return fetchApi<{ data: Voucher }>(`/bookings/${bookingId}/vouchers/${voucherCode}`);
  },
};

// ============================================================================
// MAGIC LINKS API
// ============================================================================

export interface MagicLinkBookingResponse {
  data: Booking;
  magic_links: {
    details: string;
    participants: string;
    vouchers: string;
  };
}

export interface MagicLinkParticipant {
  id: string;
  firstName: string | null;
  lastName: string | null;
  email: string | null;
  phone: string | null;
  personType: string | null;
  voucherCode: string;
  checkedIn: boolean;
}

export interface MagicLinkParticipantsResponse {
  data: MagicLinkParticipant[];
  meta: {
    bookingNumber: string;
    requiresNames: boolean;
    totalParticipants: number;
    completeParticipants: number;
  };
}

export interface MagicLinkVoucher {
  voucherCode: string;
  qrCodeData: string;
  participant: {
    fullName: string | null;
    personType: string | null;
    checkedIn: boolean;
  };
  event: {
    title: string;
    date: string;
    time: string;
    location: string | null;
  };
}

export interface MagicLinkVouchersResponse {
  canGenerate: boolean;
  data?: MagicLinkVoucher[];
  booking?: {
    bookingNumber: string;
    listingTitle: string;
  };
  message?: string;
}

export const magicLinksApi = {
  /**
   * Validate a magic token and get booking data
   */
  getBooking: async (token: string) => {
    return fetchApi<MagicLinkBookingResponse>(`/bookings/magic/${token}`);
  },

  /**
   * Request a new magic link via email
   */
  resendMagicLink: async (email: string, bookingNumber: string) => {
    return fetchApi<{ message: string }>('/bookings/resend-magic-link', {
      method: 'POST',
      body: JSON.stringify({ email, booking_number: bookingNumber }),
    });
  },

  /**
   * Get participants for a booking via magic token
   */
  getParticipants: async (token: string) => {
    return fetchApi<MagicLinkParticipantsResponse>(`/bookings/magic/${token}/participants`);
  },

  /**
   * Update participants for a booking via magic token
   */
  updateParticipants: async (
    token: string,
    participants: Array<{
      id: string;
      first_name: string;
      last_name: string;
      email?: string;
      phone?: string;
    }>
  ) => {
    return fetchApi<{ message: string; data: MagicLinkParticipant[] }>(
      `/bookings/magic/${token}/participants`,
      {
        method: 'PUT',
        body: JSON.stringify({ participants }),
      }
    );
  },

  /**
   * Get vouchers for a booking via magic token
   */
  getVouchers: async (token: string) => {
    return fetchApi<MagicLinkVouchersResponse>(`/bookings/magic/${token}/vouchers`);
  },
};

// ============================================================================
// REVIEWS API
// ============================================================================

export const reviewsApi = {
  getForListing: async (listingId: string, params?: { page?: number; sort?: string }) => {
    const queryParams = new URLSearchParams();
    if (params?.page) queryParams.append('page', String(params.page));
    if (params?.sort) queryParams.append('sort', params.sort);
    return fetchApi<{ data: Review[]; meta: { total: number; page: number; limit: number } }>(
      `/listings/${listingId}/reviews?${queryParams}`
    );
  },

  create: async (bookingId: string, request: CreateReviewRequest) => {
    return fetchApi<{ data: Review }>(`/bookings/${bookingId}/review`, {
      method: 'POST',
      body: JSON.stringify(request),
    });
  },

  markHelpful: async (reviewId: string) => {
    return fetchApi<{ success: boolean }>(`/reviews/${reviewId}/helpful`, {
      method: 'POST',
    });
  },
};

// ============================================================================
// COUPONS API
// ============================================================================

export const couponsApi = {
  validate: async (code: string, listingId: string, amount: number) => {
    return fetchApi<{ data: CouponValidation }>('/coupons/validate', {
      method: 'POST',
      body: JSON.stringify({ code, listing_id: listingId, amount }),
    });
  },
};

// ============================================================================
// VENDORS API
// ============================================================================

export const vendorsApi = {
  getProfile: async (vendorId: string) => {
    return fetchApi<{ data: VendorPublicProfile }>(`/vendors/${vendorId}`);
  },

  getListings: async (vendorId: string, params?: { page?: number }) => {
    const queryParams = new URLSearchParams();
    if (params?.page) queryParams.append('page', String(params.page));
    return fetchApi<{
      data: ListingSummary[];
      meta: { total: number; page: number; limit: number };
    }>(`/vendors/${vendorId}/listings?${queryParams}`);
  },
};

// ============================================================================
// CART API
// ============================================================================

export interface CartItem {
  id: string;
  cartId: string;
  holdId: string;
  listingId: string;
  listingTitle: Record<string, string>;
  slotStart: string;
  slotEnd: string;
  quantity: number;
  personTypeBreakdown: Record<string, number> | null;
  unitPrice: number;
  currency: string;
  primaryContact: {
    first_name: string;
    last_name: string;
    email: string;
    phone?: string;
  } | null;
  guestNames: Array<{
    first_name: string;
    last_name: string;
    person_type?: string;
  }> | null;
  extras: Array<{
    id: string;
    name: string;
    price: number;
    quantity: number;
  }> | null;
  subtotal: number;
  extrasTotal: number;
  total: number;
  holdValid: boolean;
  requiresTravelerNames: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface Cart {
  id: string;
  userId: string | null;
  sessionId: string | null;
  status: 'active' | 'checking_out' | 'completed' | 'abandoned';
  expiresAt: string;
  expiresInSeconds: number;
  isExpired: boolean;
  isActive: boolean;
  isEmpty: boolean;
  itemCount: number;
  totalGuests: number;
  subtotal: number;
  currency: string;
  items: CartItem[];
  createdAt: string;
  updatedAt: string;
}

export interface CartSummary {
  id: string;
  status: string;
  expires_at: string;
  item_count: number;
  items: Array<{
    id: string;
    listing_id: string;
    title: string;
    slot_start: string;
    slot_end: string;
    quantity: number;
    person_type_breakdown: Record<string, number> | null;
    subtotal: number;
    extras_total: number;
    total: number;
  }>;
  subtotal: number;
  total: number;
  currency: string;
}

// ============================================================================
// LOCATIONS API
// ============================================================================

export interface Location {
  id: string;
  name: string;
  slug: string;
  description: string | null;
  latitude: number | null;
  longitude: number | null;
  imageUrl: string | null;
  city: string | null;
  region: string | null;
  country: string;
  timezone: string;
  listingsCount: number;
}

export const locationsApi = {
  list: async () => {
    return fetchApi<{ data: Location[] }>('/locations');
  },

  getBySlug: async (slug: string, locale?: string) => {
    const params = locale ? `?locale=${locale}` : '';
    return fetchApi<{
      location: Location;
      listings: ListingSummary[];
    }>(`/locations/${slug}${params}`);
  },
};

// ============================================================================
// CONSENT API
// ============================================================================

export interface ConsentStatus {
  [type: string]: {
    label: string;
    granted: boolean;
  };
}

export const consentApi = {
  /**
   * Record consent(s) - can be called without authentication
   */
  recordConsents: async (
    consents: Record<string, boolean>,
    options?: {
      sessionId?: string;
      email?: string;
      context?: string;
    }
  ) => {
    return fetchApi<{
      message: string;
      data: Array<{ type: string; granted: boolean; grantedAt: string | null }>;
    }>('/consent', {
      method: 'POST',
      body: JSON.stringify({
        consents,
        session_id: options?.sessionId,
        email: options?.email,
        context: options?.context,
      }),
    });
  },

  /**
   * Get current consent status
   */
  getStatus: async (options?: { sessionId?: string; email?: string }) => {
    const params = new URLSearchParams();
    if (options?.sessionId) params.append('session_id', options.sessionId);
    if (options?.email) params.append('email', options.email);
    const queryString = params.toString();
    return fetchApi<{ data: ConsentStatus }>(
      `/consent/status${queryString ? `?${queryString}` : ''}`
    );
  },

  /**
   * Revoke a specific consent
   */
  revokeConsent: async (type: string, options?: { sessionId?: string; email?: string }) => {
    return fetchApi<{ message: string }>('/consent/revoke', {
      method: 'POST',
      body: JSON.stringify({
        type,
        session_id: options?.sessionId,
        email: options?.email,
      }),
    });
  },

  /**
   * Get consent history (requires authentication)
   */
  getHistory: async () => {
    return fetchApi<{
      data: Array<{
        id: string;
        type: string;
        typeLabel: string;
        granted: boolean;
        context: string | null;
        grantedAt: string | null;
        revokedAt: string | null;
        createdAt: string;
      }>;
    }>('/consent/history');
  },
};

export const cartApi = {
  getCart: async (sessionId?: string) => {
    const params = sessionId ? `?session_id=${sessionId}` : '';
    return fetchApi<{ data: Cart } | { message: string; cart: null }>(`/cart${params}`);
  },

  addItem: async (holdId: string, sessionId?: string) => {
    return fetchApi<{ data: Cart }>('/cart/items', {
      method: 'POST',
      body: JSON.stringify({
        hold_id: holdId,
        session_id: sessionId,
      }),
    });
  },

  removeItem: async (itemId: string, sessionId?: string) => {
    const params = sessionId ? `?session_id=${sessionId}` : '';
    return fetchApi<{ message: string }>(`/cart/items/${itemId}${params}`, {
      method: 'DELETE',
    });
  },

  updateItem: async (
    itemId: string,
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
      sessionId?: string;
    }
  ) => {
    return fetchApi<{ data: CartItem }>(`/cart/items/${itemId}`, {
      method: 'PATCH',
      body: JSON.stringify({
        primary_contact: data.primaryContact,
        guest_names: data.guestNames,
        extras: data.extras,
        session_id: data.sessionId,
      }),
    });
  },

  clearCart: async (sessionId?: string) => {
    const params = sessionId ? `?session_id=${sessionId}` : '';
    return fetchApi<{ message: string }>(`/cart${params}`, {
      method: 'DELETE',
    });
  },

  getSummary: async (sessionId?: string) => {
    const params = sessionId ? `?session_id=${sessionId}` : '';
    return fetchApi<{
      cart: CartSummary | null;
      validation: {
        valid: boolean;
        errors: Array<{ code: string; message: string; item_id?: string }>;
      };
    }>(`/cart/summary${params}`);
  },

  extendHolds: async (sessionId?: string) => {
    return fetchApi<{
      message: string;
      expires_at: string;
      expiresInSeconds: number;
      extended: number;
      failed: number;
      unavailable?: Array<{ item_id: string; title: string; reason: string }>;
    }>('/cart/extend-holds', {
      method: 'POST',
      body: JSON.stringify({ session_id: sessionId }),
    });
  },

  mergeCart: async (sessionId: string) => {
    return fetchApi<{ data: Cart } | { message: string; cart: null }>('/cart/merge', {
      method: 'POST',
      body: JSON.stringify({ session_id: sessionId }),
    });
  },

  // Checkout endpoints
  initiateCheckout: async (paymentMethod: string, sessionId?: string) => {
    return fetchApi<{
      message: string;
      payment_id: string;
      amount: number;
      currency: string;
    }>('/cart/checkout', {
      method: 'POST',
      body: JSON.stringify({
        payment_method: paymentMethod,
        session_id: sessionId,
      }),
    });
  },

  processPayment: async (
    paymentId: string,
    paymentData?: Record<string, unknown>,
    sessionId?: string
  ) => {
    return fetchApi<{
      message: string;
      success: boolean;
      bookings: Booking[];
    }>(`/cart/checkout/${paymentId}/pay`, {
      method: 'POST',
      body: JSON.stringify({
        payment_data: paymentData,
        session_id: sessionId,
      }),
    });
  },

  getCheckoutStatus: async (paymentId: string, sessionId?: string) => {
    const params = sessionId ? `?session_id=${sessionId}` : '';
    return fetchApi<{
      payment_id: string;
      status: string;
      amount: number;
      currency: string;
      paid_at: string | null;
      bookings: Booking[];
    }>(`/cart/checkout/${paymentId}/status${params}`);
  },

  cancelCheckout: async (sessionId?: string) => {
    return fetchApi<{ message: string }>('/cart/checkout/cancel', {
      method: 'POST',
      body: JSON.stringify({ session_id: sessionId }),
    });
  },
};

// ============================================================================
// PLATFORM SETTINGS API
// ============================================================================

export const platformApi = {
  /**
   * Get public platform settings (non-sensitive configuration)
   * Used for frontend configuration, branding, feature flags, etc.
   */
  getSettings: async (locale?: string) => {
    const params = new URLSearchParams();
    if (locale) params.append('locale', locale);
    const queryString = params.toString();
    return fetchApi<PlatformSettingsResponse>(
      `/platform/settings${queryString ? `?${queryString}` : ''}`
    );
  },

  /**
   * Get schema.org JSON-LD structured data
   * Used for SEO and search engine rich snippets
   */
  getSchemaOrg: async (locale?: string) => {
    const params = new URLSearchParams();
    if (locale) params.append('locale', locale);
    const queryString = params.toString();
    return fetchApi<SchemaOrgData>(`/platform/schema${queryString ? `?${queryString}` : ''}`);
  },
};

// ============================================================================
// USER PROFILE API
// ============================================================================

export interface UserPreferences {
  locale: string;
  currency: string;
  notifications: {
    emailNotifications: boolean;
    marketingEmails: boolean;
    bookingReminders: boolean;
    reviewReminders: boolean;
  };
}

export interface UpdateProfileData {
  firstName?: string;
  lastName?: string;
  displayName?: string;
  email?: string;
  phone?: string;
  preferredLocale?: string;
}

export interface UpdatePasswordData {
  currentPassword: string;
  newPassword: string;
  newPasswordConfirmation: string;
}

export interface UpdatePreferencesData {
  locale?: string;
  currency?: string;
  notifications?: {
    emailNotifications?: boolean;
    marketingEmails?: boolean;
    bookingReminders?: boolean;
    reviewReminders?: boolean;
  };
}

export const userApi = {
  /**
   * Get current user profile
   */
  getProfile: async () => {
    return fetchApi<{ data: User }>('/me');
  },

  /**
   * Update user profile
   */
  updateProfile: async (data: UpdateProfileData) => {
    return fetchApi<{ message: string; data: User }>('/me', {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  },

  /**
   * Update user password
   */
  updatePassword: async (data: UpdatePasswordData) => {
    return fetchApi<{ message: string }>('/me/password', {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  },

  /**
   * Upload user avatar
   */
  uploadAvatar: async (file: File) => {
    const formData = new FormData();
    formData.append('avatar', file);

    const token = typeof window !== 'undefined' ? localStorage.getItem('auth_token') : null;
    const headers: Record<string, string> = {};
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    const response = await fetch(`${API_URL}/me/avatar`, {
      method: 'POST',
      headers,
      body: formData,
    });

    if (!response.ok) {
      const error = await response.json().catch(() => ({ message: response.statusText }));
      throw new ApiError(error.message || 'Failed to upload avatar', response.status);
    }

    return response.json();
  },

  /**
   * Delete user avatar
   */
  deleteAvatar: async () => {
    return fetchApi<{ message: string }>('/me/avatar', {
      method: 'DELETE',
    });
  },

  /**
   * Get user preferences
   */
  getPreferences: async () => {
    return fetchApi<{ data: UserPreferences }>('/me/preferences');
  },

  /**
   * Update user preferences
   */
  updatePreferences: async (data: UpdatePreferencesData) => {
    return fetchApi<{ message: string }>('/me/preferences', {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  },

  /**
   * Export user data (GDPR)
   */
  exportData: async () => {
    return fetchApi<{ data: Record<string, unknown> }>('/me/export');
  },

  /**
   * Delete user account (GDPR)
   */
  deleteAccount: async () => {
    return fetchApi<{ message: string }>('/me', {
      method: 'DELETE',
    });
  },
};

export { ApiError };
