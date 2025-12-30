/**
 * Test data fixtures for E2E tests
 */

export const testUsers = {
  traveler: {
    email: 'traveler@test.com',
    password: 'TestPassword123!',
    firstName: 'John',
    lastName: 'Doe',
    phone: '+1234567890',
  },
  vendor: {
    email: 'vendor@test.com',
    password: 'TestPassword123!',
    companyName: 'Adventure Co.',
    companyType: 'tour_operator',
  },
  guest: {
    email: 'guest@test.com',
    firstName: 'Jane',
    lastName: 'Smith',
    phone: '+0987654321',
  },
};

export const testListing = {
  title: 'Test Hiking Adventure',
  slug: 'test-hiking-adventure',
  description: 'An amazing hiking adventure in the mountains',
  basePrice: 150.0,
  currency: 'CAD',
  location: {
    name: 'Banff National Park',
    city: 'Banff',
    state: 'Alberta',
    country: 'Canada',
    countryCode: 'CA',
  },
};

export const testBookingInfo = {
  travelerInfo: {
    firstName: 'John',
    lastName: 'Doe',
    email: 'john@example.com',
    phone: '+1234567890',
  },
  billingInfo: {
    countryCode: 'CA',
    city: 'Toronto',
    postalCode: 'M5H 2N2',
    addressLine1: '123 Main Street',
  },
  personTypes: {
    adults: 2,
    children: 0,
  },
};

export const testPayment = {
  mock: {
    method: 'mock',
    cardNumber: '4242424242424242',
    expiryMonth: '12',
    expiryYear: '2028',
    cvv: '123',
  },
  offline: {
    method: 'offline_bank_transfer',
  },
};

export const testCoupon = {
  valid: {
    code: 'SAVE20',
    discount: 20,
  },
  expired: {
    code: 'EXPIRED',
  },
  invalid: {
    code: 'INVALID123',
  },
};

export const testExtras = [
  {
    name: 'Equipment Rental',
    price: 25.0,
    quantity: 1,
  },
  {
    name: 'Lunch Package',
    price: 15.0,
    quantity: 2,
  },
];
