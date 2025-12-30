/**
 * API helper functions for E2E tests
 */

import { APIRequestContext } from '@playwright/test';

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

export interface TestUser {
  id: string;
  email: string;
  token: string;
  role: string;
}

export interface TestListing {
  id: string;
  slug: string;
  title: string;
}

export interface TestBooking {
  id: string;
  bookingNumber: string;
  status: string;
}

/**
 * Create a test user via API
 */
export async function createTestUser(
  request: APIRequestContext,
  userData: {
    email: string;
    password: string;
    role?: string;
    firstName?: string;
    lastName?: string;
  }
): Promise<TestUser> {
  const response = await request.post(`${API_BASE_URL}/auth/register`, {
    data: {
      email: userData.email,
      password: userData.password,
      password_confirmation: userData.password,
      role: userData.role || 'traveler',
      first_name: userData.firstName || 'Test',
      last_name: userData.lastName || 'User',
      display_name: `${userData.firstName || 'Test'} ${userData.lastName || 'User'}`,
    },
  });

  const data = await response.json();
  return {
    id: data.user.id,
    email: data.user.email,
    token: data.token,
    role: data.user.role,
  };
}

/**
 * Login a test user via API
 */
export async function loginTestUser(
  request: APIRequestContext,
  email: string,
  password: string
): Promise<TestUser> {
  const response = await request.post(`${API_BASE_URL}/auth/login`, {
    data: { email, password },
  });

  const data = await response.json();
  return {
    id: data.user.id,
    email: data.user.email,
    token: data.token,
    role: data.user.role,
  };
}

/**
 * Create a test listing via API
 */
export async function createTestListing(
  request: APIRequestContext,
  token: string,
  listingData: Partial<TestListing>
): Promise<TestListing> {
  const response = await request.post(`${API_BASE_URL}/listings`, {
    headers: {
      Authorization: `Bearer ${token}`,
    },
    data: listingData,
  });

  const data = await response.json();
  return data.listing;
}

/**
 * Create availability slots for a listing
 */
export async function createAvailabilitySlots(
  request: APIRequestContext,
  token: string,
  listingId: string,
  dates: Date[]
): Promise<void> {
  for (const date of dates) {
    await request.post(`${API_BASE_URL}/listings/${listingId}/availability`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
      data: {
        start_datetime: date.toISOString(),
        capacity: 10,
      },
    });
  }
}

/**
 * Create a booking hold
 */
export async function createBookingHold(
  request: APIRequestContext,
  token: string | null,
  listingSlug: string,
  slotId: string,
  quantity: number
): Promise<string> {
  const headers: Record<string, string> = {};
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const response = await request.post(`${API_BASE_URL}/listings/${listingSlug}/holds`, {
    headers,
    data: {
      slot_id: slotId,
      quantity,
      person_type_breakdown: { adults: quantity },
    },
  });

  const data = await response.json();
  return data.hold.id;
}

/**
 * Create a booking
 */
export async function createBooking(
  request: APIRequestContext,
  token: string | null,
  holdId: string,
  bookingData: any
): Promise<TestBooking> {
  const headers: Record<string, string> = {};
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const response = await request.post(`${API_BASE_URL}/bookings`, {
    headers,
    data: {
      hold_id: holdId,
      ...bookingData,
    },
  });

  const data = await response.json();
  return data.booking;
}

/**
 * Clean up test data
 */
export async function cleanupTestData(
  request: APIRequestContext,
  adminToken: string
): Promise<void> {
  // This would require admin endpoints to clean up test data
  // For now, we rely on database refresh in tests
}

/**
 * Seed database with test data
 */
export async function seedTestData(request: APIRequestContext): Promise<void> {
  // Call seeder endpoints if available
  await request.post(`${API_BASE_URL}/test/seed`, {
    data: {
      listings: 5,
      users: 10,
    },
  });
}
