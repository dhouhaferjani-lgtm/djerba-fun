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

export const bookingsApi = {
  create: async (request: CreateBookingRequest) => {
    return fetchApi<{ data: Booking }>('/bookings', {
      method: 'POST',
      body: JSON.stringify(request),
    });
  },

  getById: async (id: string) => {
    return fetchApi<{ data: Booking }>(`/bookings/${id}`);
  },

  getMyBookings: async () => {
    return fetchApi<{ data: Booking[] }>('/bookings/me');
  },
};

export { ApiError };
