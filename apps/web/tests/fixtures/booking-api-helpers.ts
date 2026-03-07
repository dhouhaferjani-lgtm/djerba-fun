/**
 * Booking API Helpers for E2E Tests
 * Helper functions for creating and managing bookings via API for test setup
 */

import { APIRequestContext } from '@playwright/test';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';
const LARAVEL_URL = process.env.LARAVEL_URL || 'http://localhost:8000';

export interface BookingParticipant {
  id: string;
  firstName: string;
  lastName: string;
  personType: string;
  voucherCode: string;
  checkedIn: boolean;
  checkedInAt: string | null;
}

export interface BookingWithParticipants {
  bookingId: string;
  bookingNumber: string;
  listingId: string;
  listingSlug: string;
  slotId: string;
  slotDate: string;
  slotTime: string;
  status: string;
  totalAmount: number;
  currency: string;
  participants: BookingParticipant[];
}

export interface CreateBookingOptions {
  listingSlug: string;
  quantity?: number;
  personTypes?: Record<string, number>;
  guestEmail?: string;
  guestFirstName?: string;
  guestLastName?: string;
  guestPhone?: string;
  sessionId?: string;
}

export interface ConfirmedBookingOptions extends CreateBookingOptions {
  participants?: Array<{
    firstName: string;
    lastName: string;
    personType?: string;
    email?: string;
    phone?: string;
  }>;
}

/**
 * Generate a unique session ID for guest checkouts
 */
export function generateSessionId(): string {
  return `test-session-${Date.now()}-${Math.random().toString(36).substring(7)}`;
}

/**
 * Generate a unique email for test users
 */
export function generateTestEmail(prefix = 'test'): string {
  return `${prefix}-${Date.now()}@test.djerbafun.com`;
}

/**
 * Get available slots for a listing
 */
export async function getAvailableSlots(
  request: APIRequestContext,
  listingSlug: string,
  options?: { token?: string; sessionId?: string }
): Promise<
  Array<{
    id: string;
    startTime: string;
    endTime: string;
    capacity: number;
    availableCapacity: number;
  }>
> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };

  if (options?.token) {
    headers.Authorization = `Bearer ${options.token}`;
  }
  if (options?.sessionId) {
    headers['X-Session-ID'] = options.sessionId;
  }

  // Get slots for next 30 days
  const startDate = new Date().toISOString().split('T')[0];
  const endDate = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

  const response = await request.get(
    `${API_BASE_URL}/listings/${listingSlug}/availability?start_date=${startDate}&end_date=${endDate}`,
    { headers }
  );

  if (!response.ok()) {
    throw new Error(`Failed to get availability: ${response.status()} - ${await response.text()}`);
  }

  const data = await response.json();
  return (data.data || data.slots || []).map((slot: any) => ({
    id: slot.id,
    startTime: slot.start_time || slot.startTime,
    endTime: slot.end_time || slot.endTime,
    capacity: slot.capacity,
    availableCapacity: slot.available_capacity || slot.availableCapacity,
  }));
}

/**
 * Create a booking hold
 */
export async function createBookingHold(
  request: APIRequestContext,
  listingSlug: string,
  slotId: string,
  options: {
    quantity?: number;
    personTypes?: Record<string, number>;
    token?: string;
    sessionId?: string;
  } = {}
): Promise<{ holdId: string; expiresAt: string }> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };

  if (options.token) {
    headers.Authorization = `Bearer ${options.token}`;
  }
  if (options.sessionId) {
    headers['X-Session-ID'] = options.sessionId;
  }

  const quantity = options.quantity || 1;
  const personTypes = options.personTypes || { adult: quantity };

  const response = await request.post(`${API_BASE_URL}/listings/${listingSlug}/holds`, {
    headers,
    data: {
      slot_id: slotId,
      quantity,
      person_types: personTypes,
    },
  });

  if (!response.ok()) {
    throw new Error(`Failed to create hold: ${response.status()} - ${await response.text()}`);
  }

  const data = await response.json();
  const hold = data.data || data.hold || data;

  return {
    holdId: hold.id || hold.hold_id,
    expiresAt: hold.expires_at || hold.expiresAt,
  };
}

/**
 * Create a booking from a hold (guest checkout)
 */
export async function createBookingFromHold(
  request: APIRequestContext,
  holdId: string,
  options: {
    email: string;
    firstName: string;
    lastName: string;
    phone?: string;
    paymentMethod?: string;
    token?: string;
    sessionId?: string;
  }
): Promise<{ bookingId: string; bookingNumber: string; status: string }> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };

  if (options.token) {
    headers.Authorization = `Bearer ${options.token}`;
  }
  if (options.sessionId) {
    headers['X-Session-ID'] = options.sessionId;
  }

  const response = await request.post(`${API_BASE_URL}/bookings`, {
    headers,
    data: {
      hold_id: holdId,
      email: options.email,
      first_name: options.firstName,
      last_name: options.lastName,
      phone: options.phone || '+21612345678',
      payment_method: options.paymentMethod || 'offline_bank_transfer',
    },
  });

  if (!response.ok()) {
    throw new Error(`Failed to create booking: ${response.status()} - ${await response.text()}`);
  }

  const data = await response.json();
  const booking = data.data || data.booking || data;

  return {
    bookingId: booking.id,
    bookingNumber: booking.booking_number || booking.bookingNumber,
    status: booking.status,
  };
}

/**
 * Confirm booking payment (marks as CONFIRMED)
 * Note: This requires admin privileges or a test endpoint
 */
export async function confirmBookingPayment(
  request: APIRequestContext,
  bookingId: string,
  adminToken: string
): Promise<void> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    Authorization: `Bearer ${adminToken}`,
  };

  // Try the test/admin endpoint first
  const response = await request.post(
    `${API_BASE_URL}/admin/bookings/${bookingId}/confirm-payment`,
    {
      headers,
      data: {
        payment_method: 'offline_bank_transfer',
        payment_notes: 'Confirmed via E2E test',
      },
    }
  );

  if (!response.ok()) {
    // Fallback: Try marking as paid via standard endpoint
    const fallbackResponse = await request.post(`${API_BASE_URL}/bookings/${bookingId}/mark-paid`, {
      headers,
      data: {
        payment_notes: 'Confirmed via E2E test',
      },
    });

    if (!fallbackResponse.ok()) {
      throw new Error(`Failed to confirm payment: ${response.status()} - ${await response.text()}`);
    }
  }
}

/**
 * Update participant names for a booking
 */
export async function updateParticipants(
  request: APIRequestContext,
  bookingId: string,
  participants: Array<{
    id?: string;
    firstName: string;
    lastName: string;
    personType?: string;
    email?: string;
    phone?: string;
  }>,
  options?: { token?: string; sessionId?: string }
): Promise<BookingParticipant[]> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };

  if (options?.token) {
    headers.Authorization = `Bearer ${options.token}`;
  }
  if (options?.sessionId) {
    headers['X-Session-ID'] = options.sessionId;
  }

  const response = await request.put(`${API_BASE_URL}/bookings/${bookingId}/participants`, {
    headers,
    data: {
      participants: participants.map((p) => ({
        id: p.id,
        first_name: p.firstName,
        last_name: p.lastName,
        person_type: p.personType || 'adult',
        email: p.email,
        phone: p.phone,
      })),
    },
  });

  if (!response.ok()) {
    throw new Error(
      `Failed to update participants: ${response.status()} - ${await response.text()}`
    );
  }

  const data = await response.json();
  const updatedParticipants = data.data?.participants || data.participants || [];

  return updatedParticipants.map((p: any) => ({
    id: p.id,
    firstName: p.first_name || p.firstName,
    lastName: p.last_name || p.lastName,
    personType: p.person_type || p.personType,
    voucherCode: p.voucher_code || p.voucherCode,
    checkedIn: p.checked_in || p.checkedIn || false,
    checkedInAt: p.checked_in_at || p.checkedInAt,
  }));
}

/**
 * Get booking details including participants
 */
export async function getBooking(
  request: APIRequestContext,
  bookingId: string,
  options?: { token?: string; sessionId?: string }
): Promise<BookingWithParticipants> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
  };

  if (options?.token) {
    headers.Authorization = `Bearer ${options.token}`;
  }
  if (options?.sessionId) {
    headers['X-Session-ID'] = options.sessionId;
  }

  const response = await request.get(`${API_BASE_URL}/bookings/${bookingId}`, { headers });

  if (!response.ok()) {
    throw new Error(`Failed to get booking: ${response.status()} - ${await response.text()}`);
  }

  const data = await response.json();
  const booking = data.data || data.booking || data;

  return {
    bookingId: booking.id,
    bookingNumber: booking.booking_number || booking.bookingNumber,
    listingId: booking.listing_id || booking.listingId,
    listingSlug: booking.listing?.slug || booking.listingSlug,
    slotId: booking.availability_slot_id || booking.slotId,
    slotDate: booking.availability_slot?.start_time?.split('T')[0] || booking.slotDate,
    slotTime: booking.availability_slot?.start_time || booking.slotTime,
    status: booking.status,
    totalAmount: booking.total_amount || booking.totalAmount,
    currency: booking.currency,
    participants: (booking.participants || []).map((p: any) => ({
      id: p.id,
      firstName: p.first_name || p.firstName,
      lastName: p.last_name || p.lastName,
      personType: p.person_type || p.personType || 'adult',
      voucherCode: p.voucher_code || p.voucherCode,
      checkedIn: p.checked_in || p.checkedIn || false,
      checkedInAt: p.checked_in_at || p.checkedInAt,
    })),
  };
}

/**
 * Create a confirmed booking with participants (full setup for check-in tests)
 * This is a convenience function that chains multiple API calls.
 */
export async function createConfirmedBookingWithParticipants(
  request: APIRequestContext,
  options: ConfirmedBookingOptions & { adminToken: string }
): Promise<BookingWithParticipants> {
  const sessionId = generateSessionId();
  const email = options.guestEmail || generateTestEmail('booking');

  // 1. Get available slots
  const slots = await getAvailableSlots(request, options.listingSlug, { sessionId });
  if (slots.length === 0) {
    throw new Error(`No available slots for listing: ${options.listingSlug}`);
  }

  const slot = slots.find((s) => s.availableCapacity > 0) || slots[0];

  // 2. Create hold
  const quantity = options.quantity || options.participants?.length || 1;
  const { holdId } = await createBookingHold(request, options.listingSlug, slot.id, {
    quantity,
    personTypes: options.personTypes,
    sessionId,
  });

  // 3. Create booking
  const { bookingId } = await createBookingFromHold(request, holdId, {
    email,
    firstName: options.guestFirstName || 'Test',
    lastName: options.guestLastName || 'Guest',
    phone: options.guestPhone,
    sessionId,
  });

  // 4. Confirm payment (requires admin)
  await confirmBookingPayment(request, bookingId, options.adminToken);

  // 5. Add participant names if provided
  if (options.participants && options.participants.length > 0) {
    await updateParticipants(request, bookingId, options.participants, { sessionId });
  }

  // 6. Return full booking details
  return getBooking(request, bookingId, { sessionId });
}

/**
 * Create a pending payment booking (for vendor mark-as-paid tests)
 */
export async function createPendingBooking(
  request: APIRequestContext,
  options: CreateBookingOptions
): Promise<{ bookingId: string; bookingNumber: string; sessionId: string }> {
  const sessionId = options.sessionId || generateSessionId();
  const email = options.guestEmail || generateTestEmail('pending');

  // 1. Get available slots
  const slots = await getAvailableSlots(request, options.listingSlug, { sessionId });
  if (slots.length === 0) {
    throw new Error(`No available slots for listing: ${options.listingSlug}`);
  }

  const slot = slots.find((s) => s.availableCapacity > 0) || slots[0];

  // 2. Create hold
  const quantity = options.quantity || 1;
  const { holdId } = await createBookingHold(request, options.listingSlug, slot.id, {
    quantity,
    personTypes: options.personTypes,
    sessionId,
  });

  // 3. Create booking (will be in PENDING_PAYMENT status)
  const booking = await createBookingFromHold(request, holdId, {
    email,
    firstName: options.guestFirstName || 'Test',
    lastName: options.guestLastName || 'Pending',
    phone: options.guestPhone,
    sessionId,
  });

  return {
    bookingId: booking.bookingId,
    bookingNumber: booking.bookingNumber,
    sessionId,
  };
}

/**
 * Login as admin and get token (for test setup)
 */
export async function loginAsAdmin(
  request: APIRequestContext,
  email = 'admin@goadventure.tn',
  password = 'password'
): Promise<string> {
  const response = await request.post(`${API_BASE_URL}/auth/login`, {
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    data: { email, password },
  });

  if (!response.ok()) {
    throw new Error(`Admin login failed: ${response.status()} - ${await response.text()}`);
  }

  const data = await response.json();
  return data.token || data.data?.token;
}

/**
 * Get the seeded test listing slug
 * Uses the first active listing from the vendor's listings
 */
export async function getSeededListingSlug(request: APIRequestContext): Promise<string> {
  const response = await request.get(`${API_BASE_URL}/listings?limit=1&status=active`, {
    headers: { Accept: 'application/json' },
  });

  if (!response.ok()) {
    throw new Error(`Failed to get listings: ${response.status()}`);
  }

  const data = await response.json();
  const listings = data.data || data.listings || [];

  if (listings.length === 0) {
    // Fallback to known seeded slug
    return 'kroumirie-mountains-summit-trek';
  }

  return listings[0].slug;
}
