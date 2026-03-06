/**
 * Vendor Panel Test Data Fixtures
 * Test data for vendor panel E2E tests
 */

/**
 * Vendor user test data
 */
export const vendorUsers = {
  // Pre-seeded vendor (from VendorSeeder)
  seeded: {
    email: 'vendor@goadventure.tn',
    password: 'password',
    companyName: 'Desert Adventures TN',
  },
  // Dynamic test vendor (created per test)
  test: {
    email: `vendor-${Date.now()}@test.com`,
    password: 'TestVendor123!',
    firstName: 'Test',
    lastName: 'Vendor',
    companyName: 'Test Adventures Co.',
  },
  // Second vendor for isolation tests
  secondary: {
    email: 'vendor2@goadventure.tn',
    password: 'password',
    companyName: 'Sahara Tours',
  },
};

/**
 * Listing templates for different service types
 */
export const listingTemplates = {
  tour: {
    serviceType: 'tour' as const,
    titleEn: 'Mountain Trek Adventure',
    titleFr: 'Aventure de Randonnée en Montagne',
    summaryEn: 'Experience the breathtaking mountain views on this guided trek.',
    summaryFr: 'Découvrez les vues à couper le souffle lors de cette randonnée guidée.',
    descriptionEn:
      'Join us for an unforgettable mountain trekking experience through scenic trails and pristine nature.',
    descriptionFr:
      'Rejoignez-nous pour une expérience de randonnée inoubliable à travers des sentiers pittoresques.',
    highlightsEn: ['Scenic views', 'Expert guide', 'Local lunch included'],
    highlightsFr: ['Vues panoramiques', 'Guide expert', 'Déjeuner local inclus'],
    priceTnd: 150,
    priceEur: 45,
    duration: 6,
    durationUnit: 'hours',
    difficulty: 'moderate',
    minGroupSize: 2,
    maxGroupSize: 15,
    activityType: 'hiking',
  },
  nautical: {
    serviceType: 'nautical' as const,
    titleEn: 'Sunset Sailing Experience',
    titleFr: 'Expérience de Voile au Coucher du Soleil',
    summaryEn: 'Sail into the sunset on a traditional boat.',
    summaryFr: 'Naviguez vers le coucher du soleil sur un bateau traditionnel.',
    descriptionEn: 'A magical evening sailing experience along the beautiful coastline.',
    descriptionFr: 'Une soirée magique de navigation le long de la belle côte.',
    priceTnd: 200,
    priceEur: 60,
    duration: 3,
    durationUnit: 'hours',
    boatType: 'sailboat',
    minGroupSize: 4,
    maxGroupSize: 12,
  },
  accommodation: {
    serviceType: 'accommodation' as const,
    titleEn: 'Desert Glamping Experience',
    titleFr: 'Expérience de Glamping dans le Désert',
    summaryEn: 'Luxury camping under the stars in the Sahara.',
    summaryFr: 'Camping de luxe sous les étoiles dans le Sahara.',
    descriptionEn: 'Experience the magic of the desert with our luxury glamping tents.',
    descriptionFr: 'Découvrez la magie du désert avec nos tentes de glamping de luxe.',
    priceTnd: 350,
    priceEur: 105,
    accommodationType: 'glamping',
    mealsIncluded: ['breakfast', 'dinner'],
    amenities: ['wifi', 'private_bathroom', 'air_conditioning'],
  },
  event: {
    serviceType: 'event' as const,
    titleEn: 'Traditional Music Festival',
    titleFr: 'Festival de Musique Traditionnelle',
    summaryEn: 'Annual celebration of traditional Tunisian music.',
    summaryFr: 'Célébration annuelle de la musique traditionnelle tunisienne.',
    descriptionEn: 'Join us for three days of amazing traditional music performances.',
    descriptionFr: 'Rejoignez-nous pour trois jours de spectacles de musique traditionnelle.',
    priceTnd: 80,
    priceEur: 25,
    eventType: 'festival',
    venueName: 'Amphithéâtre de Djerba',
    venueAddress: 'Zone Touristique, Djerba',
    startDate: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 30 days from now
    endDate: new Date(Date.now() + 32 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 32 days from now
  },
};

/**
 * Availability rule templates
 */
export const availabilityRules = {
  weekly: {
    ruleType: 'weekly',
    daysOfWeek: [1, 3, 5], // Monday, Wednesday, Friday
    startTime: '09:00',
    endTime: '17:00',
    capacity: 10,
  },
  daily: {
    ruleType: 'daily',
    startTime: '08:00',
    endTime: '18:00',
    capacity: 20,
  },
  specificDates: {
    ruleType: 'specific_dates',
    dates: [
      new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
      new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
      new Date(Date.now() + 21 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    ],
    startTime: '10:00',
    endTime: '16:00',
    capacity: 8,
  },
  blocked: {
    ruleType: 'blocked_dates',
    startDate: new Date(Date.now() + 5 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    endDate: new Date(Date.now() + 10 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    reason: 'Maintenance period',
  },
};

/**
 * Extras test data
 */
export const extrasData = {
  perBooking: {
    nameEn: 'Equipment Rental',
    nameFr: "Location d'équipement",
    descriptionEn: 'Full equipment set for the activity',
    descriptionFr: "Ensemble complet d'équipement pour l'activité",
    category: 'equipment',
    pricingType: 'per_booking',
    priceTnd: 50,
    priceEur: 15,
  },
  perPerson: {
    nameEn: 'Lunch Package',
    nameFr: 'Formule Déjeuner',
    descriptionEn: 'Traditional lunch with local specialties',
    descriptionFr: 'Déjeuner traditionnel avec spécialités locales',
    category: 'food',
    pricingType: 'per_person',
    priceTnd: 35,
    priceEur: 10,
  },
  perPersonType: {
    nameEn: 'Photo Package',
    nameFr: 'Forfait Photo',
    descriptionEn: 'Professional photos of your experience',
    descriptionFr: 'Photos professionnelles de votre expérience',
    category: 'other',
    pricingType: 'per_person_type',
    prices: {
      adult: { tnd: 40, eur: 12 },
      child: { tnd: 20, eur: 6 },
      infant: { tnd: 0, eur: 0 },
    },
  },
  required: {
    nameEn: 'Insurance',
    nameFr: 'Assurance',
    descriptionEn: 'Mandatory travel insurance',
    descriptionFr: 'Assurance voyage obligatoire',
    category: 'other',
    pricingType: 'per_person',
    priceTnd: 15,
    priceEur: 5,
    isRequired: true,
  },
  limited: {
    nameEn: 'Premium Upgrade',
    nameFr: 'Mise à niveau Premium',
    descriptionEn: 'Limited premium experience upgrade',
    descriptionFr: "Mise à niveau vers l'expérience premium limitée",
    category: 'activity',
    pricingType: 'per_booking',
    priceTnd: 100,
    priceEur: 30,
    maxCapacity: 5,
  },
};

/**
 * Booking test data
 */
export const bookingData = {
  confirmed: {
    status: 'confirmed',
    totalAmount: 300,
    participantCount: 2,
    travelerInfo: {
      firstName: 'Ahmed',
      lastName: 'Ben Ali',
      email: 'ahmed.benali@example.com',
      phone: '+216 20 123 456',
    },
  },
  pendingPayment: {
    status: 'pending_payment',
    totalAmount: 450,
    participantCount: 3,
    travelerInfo: {
      firstName: 'Sarah',
      lastName: 'Johnson',
      email: 'sarah.johnson@example.com',
      phone: '+1 555 123 4567',
    },
  },
  completed: {
    status: 'completed',
    totalAmount: 200,
    participantCount: 2,
    travelerInfo: {
      firstName: 'Marie',
      lastName: 'Dupont',
      email: 'marie.dupont@example.com',
      phone: '+33 6 12 34 56 78',
    },
  },
  pastDate: {
    status: 'confirmed',
    totalAmount: 150,
    participantCount: 1,
    bookingDate: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // 2 days ago
    travelerInfo: {
      firstName: 'John',
      lastName: 'Smith',
      email: 'john.smith@example.com',
      phone: '+44 20 1234 5678',
    },
  },
};

/**
 * Review test data
 */
export const reviewData = {
  pending: {
    rating: 5,
    title: 'Amazing experience!',
    content:
      'This was one of the best tours I have ever taken. The guide was knowledgeable and friendly.',
    status: 'pending',
  },
  approved: {
    rating: 4,
    title: 'Great tour, minor issues',
    content: 'Overall a great experience. The scenery was beautiful. Pickup was a bit late.',
    status: 'published',
  },
  lowRating: {
    rating: 2,
    title: 'Could be better',
    content: 'The experience did not match the description. Guide seemed unprepared.',
    status: 'pending',
  },
};

/**
 * Voucher codes for check-in tests
 */
export const voucherCodes = {
  valid: 'VCHK-TEST-001',
  invalid: 'INVALID-CODE-XYZ',
  alreadyCheckedIn: 'VCHK-CHECKED-001',
  wrongEvent: 'VCHK-OTHER-001',
  wrongDate: 'VCHK-WRONG-DATE',
};

/**
 * Email log test data
 */
export const emailLogData = {
  sent: {
    type: 'booking_confirmation',
    status: 'sent',
    recipient: 'customer@example.com',
  },
  failed: {
    type: 'booking_reminder',
    status: 'failed',
    recipient: 'invalid@email',
    failureReason: 'Invalid email address',
  },
  delivered: {
    type: 'voucher_sent',
    status: 'delivered',
    recipient: 'customer@example.com',
  },
};

/**
 * Generate unique test data with timestamp
 */
export function generateUniqueTestData(base: string): string {
  return `${base}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
}

/**
 * Get future date string (YYYY-MM-DD)
 */
export function getFutureDate(daysFromNow: number): string {
  const date = new Date();
  date.setDate(date.getDate() + daysFromNow);
  return date.toISOString().split('T')[0];
}

/**
 * Get past date string (YYYY-MM-DD)
 */
export function getPastDate(daysAgo: number): string {
  const date = new Date();
  date.setDate(date.getDate() - daysAgo);
  return date.toISOString().split('T')[0];
}
