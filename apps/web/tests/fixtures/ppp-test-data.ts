/**
 * Test Data Fixtures for PPP (Purchasing Power Parity) Pricing E2E Tests
 *
 * This file contains all test data needed for validating PPP pricing flows:
 * - IP addresses for different countries
 * - Billing addresses for each test scenario
 * - Traveler information profiles
 * - Expected pricing values
 */

export const testData = {
  // IP addresses for geo-location testing
  ips: {
    tunisia: '41.226.25.1', // Tunisia IP address
    france: '88.127.225.1', // France IP address
    usa: '8.8.8.8', // USA IP address (Google DNS)
    germany: '81.169.145.1', // Germany IP address
  },

  // Billing addresses for different countries
  addresses: {
    tunisia: {
      country_code: 'TN',
      country_name: 'Tunisia',
      city: 'Tunis',
      postal_code: '1000',
      address_line1: '123 Avenue Habib Bourguiba',
      address_line2: '',
    },
    france: {
      country_code: 'FR',
      country_name: 'France',
      city: 'Paris',
      postal_code: '75001',
      address_line1: '123 Rue de Rivoli',
      address_line2: '',
    },
    usa: {
      country_code: 'US',
      country_name: 'United States',
      city: 'New York',
      postal_code: '10001',
      address_line1: '123 Broadway',
      address_line2: '',
    },
    germany: {
      country_code: 'DE',
      country_name: 'Germany',
      city: 'Berlin',
      postal_code: '10115',
      address_line1: '123 Unter den Linden',
      address_line2: '',
    },
  },

  // Traveler information profiles
  travelers: {
    tunisia: {
      email: 'ahmed.bensalah@example.tn',
      firstName: 'Ahmed',
      lastName: 'Ben Salah',
      phone: '+216 20 123 456',
    },
    france: {
      email: 'marie.dupont@example.fr',
      firstName: 'Marie',
      lastName: 'Dupont',
      phone: '+33 1 23 45 67 89',
    },
    usa: {
      email: 'john.smith@example.com',
      firstName: 'John',
      lastName: 'Smith',
      phone: '+1 212 555 0123',
    },
    germany: {
      email: 'hans.mueller@example.de',
      firstName: 'Hans',
      lastName: 'Müller',
      phone: '+49 30 12345678',
    },
  },

  // Expected pricing values (based on PPP spec)
  pricing: {
    basePrice: {
      EUR: 100, // Base price in EUR
    },
    tunisia: {
      TND: 315, // 100 EUR × 3.15 (exchange rate)
      // PPP adjustment would reduce this for lower purchasing power
    },
    france: {
      EUR: 100, // Same as base (high-income country)
    },
    usa: {
      USD: 108, // Approximate conversion from EUR
    },
  },

  // Currency codes
  currencies: {
    tunisia: 'TND',
    france: 'EUR',
    usa: 'USD',
    germany: 'EUR',
  },

  // Test listing data
  listing: {
    slug: 'sahara-desert-adventure-3-days',
    name: 'Sahara Desert Adventure - 3 Days',
    basePrice: 100, // EUR
  },

  // Booking configuration
  booking: {
    adults: 2,
    children: 1,
    infants: 0,
    date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 7 days from now
    timeSlot: '09:00',
  },

  // Hold configuration
  hold: {
    durationMinutes: 15,
    warningThresholdMinutes: 5,
  },
};

/**
 * Helper function to calculate expected total price
 */
export function calculateExpectedTotal(
  basePrice: number,
  adults: number,
  children: number = 0,
  childDiscount: number = 0.5
): number {
  return basePrice * adults + basePrice * children * childDiscount;
}

/**
 * Mock API response for geolocation based on IP
 */
export function getMockGeoResponse(ip: string) {
  const ipToCountry: Record<
    string,
    { country_code: string; currency: string; country_name: string }
  > = {
    [testData.ips.tunisia]: { country_code: 'TN', currency: 'TND', country_name: 'Tunisia' },
    [testData.ips.france]: { country_code: 'FR', currency: 'EUR', country_name: 'France' },
    [testData.ips.usa]: { country_code: 'US', currency: 'USD', country_name: 'United States' },
    [testData.ips.germany]: { country_code: 'DE', currency: 'EUR', country_name: 'Germany' },
  };

  return ipToCountry[ip] || { country_code: 'US', currency: 'USD', country_name: 'United States' };
}

/**
 * Format price for display
 */
export function formatPrice(amount: number, currency: string): string {
  const currencySymbols: Record<string, string> = {
    TND: 'TND',
    EUR: '€',
    USD: '$',
  };

  const symbol = currencySymbols[currency] || currency;

  if (currency === 'EUR') {
    return `${amount.toFixed(2)}${symbol}`;
  }

  return `${symbol}${amount.toFixed(2)}`;
}
