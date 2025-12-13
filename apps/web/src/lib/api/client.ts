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
      error: {
        message: response.statusText,
        code: 'UNKNOWN_ERROR',
      },
    }));

    throw new ApiError(
      error.error?.message || 'An error occurred',
      response.status,
      error.error?.code,
      error.error?.details
    );
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

  register: async (email: string, password: string, displayName: string) => {
    const data = await fetchApi<{ user: User; token: string }>('/auth/register', {
      method: 'POST',
      body: JSON.stringify({ email, password, display_name: displayName }),
    });

    if (typeof window !== 'undefined') {
      localStorage.setItem('auth_token', data.token);
    }

    return data;
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

  getAvailability: async (listingId: string, startDate: string, endDate: string) => {
    const params = new URLSearchParams({ start_date: startDate, end_date: endDate });
    return fetchApi<{ data: AvailabilitySlot[] }>(
      `/listings/${listingId}/availability?${params.toString()}`
    );
  },

  createHold: async (listingId: string, request: CreateHoldRequest) => {
    return fetchApi<{ data: CreateHoldResponse }>(`/listings/${listingId}/holds`, {
      method: 'POST',
      body: JSON.stringify(request),
    });
  },
};

// ============================================================================
// BOOKINGS API
// ============================================================================

export interface ProcessPaymentRequest {
  paymentMethod: 'mock' | 'offline' | 'click_to_pay' | 'stripe' | 'paypal';
  paymentData?: Record<string, unknown>;
}

export const bookingsApi = {
  create: async (request: CreateBookingRequest) => {
    return fetchApi<{ data: Booking }>('/bookings', {
      method: 'POST',
      body: JSON.stringify(request),
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
      body: JSON.stringify(request),
    });
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

export { ApiError };
