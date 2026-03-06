/**
 * Admin Panel Test Data Fixtures for E2E Tests
 * These fixtures provide test data for admin panel testing
 */

export const adminUsers = {
  admin: {
    email: 'admin@djerba.fun',
    password: 'password',
    firstName: 'Admin',
    lastName: 'User',
    role: 'admin',
  },
  vendor: {
    email: 'vendor@djerba.fun',
    password: 'password',
    firstName: 'Djerba',
    lastName: 'Fun',
    role: 'vendor',
    companyName: 'Djerba Fun',
  },
  traveler: {
    email: 'traveler@test.com',
    password: 'password',
    firstName: 'Traveler',
    lastName: 'User',
    role: 'traveler',
  },
};

export const adminListingData = {
  tour: {
    titleEn: 'Desert Safari Adventure',
    titleFr: 'Aventure Safari dans le Désert',
    summaryEn: 'Experience the magic of the Sahara desert',
    summaryFr: 'Découvrez la magie du désert du Sahara',
    descriptionEn:
      'Join us for an unforgettable journey through the golden dunes of the Sahara. This full-day tour includes camel riding, traditional lunch, and sunset photography.',
    descriptionFr:
      "Rejoignez-nous pour un voyage inoubliable à travers les dunes dorées du Sahara. Cette excursion d'une journée comprend une balade à dos de chameau, un déjeuner traditionnel et une séance photo au coucher du soleil.",
    serviceType: 'tour',
    tndPrice: 150,
    eurPrice: 45,
    duration: 8,
    durationUnit: 'hours',
    minParticipants: 2,
    maxParticipants: 15,
    difficulty: 'moderate',
  },
  nautical: {
    titleEn: 'Sunset Boat Cruise',
    titleFr: 'Croisière au Coucher du Soleil',
    summaryEn: 'Sail into the sunset along the coast of Djerba',
    summaryFr: 'Naviguez vers le coucher du soleil le long de la côte de Djerba',
    descriptionEn:
      'Enjoy a romantic sunset cruise along the beautiful coastline of Djerba. Includes refreshments and snacks.',
    descriptionFr:
      'Profitez dune croisière romantique au coucher du soleil le long de la magnifique côte de Djerba. Rafraîchissements et collations inclus.',
    serviceType: 'nautical',
    tndPrice: 200,
    eurPrice: 60,
    duration: 3,
    durationUnit: 'hours',
  },
  accommodation: {
    titleEn: 'Traditional Menzel Stay',
    titleFr: 'Séjour dans un Menzel Traditionnel',
    summaryEn: 'Stay in a traditional Djerbien house',
    summaryFr: 'Séjournez dans une maison traditionnelle djerbienne',
    descriptionEn:
      'Experience authentic Djerbien hospitality in a beautifully restored traditional menzel. Breakfast included.',
    descriptionFr:
      "Découvrez l'hospitalité authentique de Djerba dans un menzel traditionnel magnifiquement restauré. Petit-déjeuner inclus.",
    serviceType: 'accommodation',
    tndPrice: 250,
    eurPrice: 75,
    duration: 1,
    durationUnit: 'days',
  },
  event: {
    titleEn: 'Traditional Music Night',
    titleFr: 'Soirée Musique Traditionnelle',
    summaryEn: 'Live traditional Tunisian music performance',
    summaryFr: 'Spectacle de musique traditionnelle tunisienne en direct',
    descriptionEn:
      'Enjoy an evening of traditional Tunisian music with local artists. Includes dinner and drinks.',
    descriptionFr:
      "Profitez d'une soirée de musique traditionnelle tunisienne avec des artistes locaux. Dîner et boissons inclus.",
    serviceType: 'event',
    tndPrice: 100,
    eurPrice: 30,
  },
};

export const adminCouponData = {
  percentage: {
    code: 'SUMMER20',
    discountType: 'percentage',
    discountValue: 20,
    usageLimit: 100,
    minOrderAmount: null,
    maxDiscountAmount: null,
    isActive: true,
  },
  fixedAmount: {
    code: 'SAVE50TND',
    discountType: 'fixed_amount',
    discountValue: 50,
    usageLimit: 50,
    minOrderAmount: 200,
    maxDiscountAmount: null,
    isActive: true,
  },
  expired: {
    code: 'EXPIRED2023',
    discountType: 'percentage',
    discountValue: 15,
    usageLimit: 100,
    // validUntil will be set to yesterday
    isActive: true,
  },
  limitedUse: {
    code: 'ONEUSE',
    discountType: 'percentage',
    discountValue: 10,
    usageLimit: 1,
    isActive: true,
  },
  lowercase: {
    code: 'test123',
    discountType: 'percentage',
    discountValue: 5,
    usageLimit: 100,
    isActive: true,
  },
};

export const adminBookingData = {
  manual: {
    quantity: 2,
    personTypes: {
      adult: 2,
      child: 0,
    },
    specialRequests: 'Vegetarian meals please',
  },
};

export const adminPartnerData = {
  standard: {
    name: 'Travel Agency Partner',
    companyName: 'TravelCo Ltd',
    email: 'partner@travelco.com',
    kycStatus: 'approved',
    tier: 'standard',
    permissions: ['listings:read', 'bookings:create', 'bookings:read'],
    rateLimit: 100,
    webhookUrl: 'https://travelco.com/webhooks/evasion',
    ipWhitelist: [],
    sandboxMode: false,
  },
  sandbox: {
    name: 'Test Partner',
    companyName: 'Test Agency',
    email: 'test@testagency.com',
    kycStatus: 'approved',
    tier: 'standard',
    permissions: ['listings:read', 'bookings:create'],
    rateLimit: 50,
    webhookUrl: null,
    ipWhitelist: [],
    sandboxMode: true,
  },
  withIpWhitelist: {
    name: 'Secure Partner',
    companyName: 'Secure Travel Inc',
    email: 'secure@securetravel.com',
    kycStatus: 'approved',
    tier: 'premium',
    permissions: ['listings:read', 'bookings:create', 'bookings:read'],
    rateLimit: 200,
    webhookUrl: 'https://securetravel.com/api/webhooks',
    ipWhitelist: ['192.168.1.1', '10.0.0.1'],
    sandboxMode: false,
  },
};

export const adminPlatformSettings = {
  identity: {
    platformNameEn: 'Djerba Fun',
    platformNameFr: 'Djerba Fun',
    taglineEn: 'Experience the island differently',
    taglineFr: "Vivez l'île autrement",
  },
  payment: {
    exchangeRate: 3.35, // EUR to TND
    holdDuration: 10, // minutes
  },
  destinations: {
    name: 'Houmt Souk',
    slug: 'houmt-souk',
    descriptionEn: 'The vibrant capital of Djerba',
    descriptionFr: 'La capitale vibrante de Djerba',
  },
};

export const adminSelectors = {
  // Filament Login (Filament 3 uses label-based inputs)
  loginEmailLabel: 'Email address',
  loginPasswordLabel: 'Password',
  loginSubmitButton: 'button:has-text("Sign in")',

  // Navigation
  sidebarNav: '[data-sidebar]',
  navItem: (label: string) => `[data-nav-item="${label}"]`,

  // Tables
  tableContainer: '[wire\\:id*="table"]',
  tableRow: 'tr[wire\\:key*="table.records"]',
  tableCheckbox: 'input[type="checkbox"]',
  tableActions: '[data-actions]',
  bulkActionsDropdown: '[data-bulk-actions]',

  // Forms
  formField: (name: string) => `[wire\\:model*="${name}"]`,
  selectField: (name: string) => `select[wire\\:model*="${name}"]`,
  textInput: (name: string) => `input[wire\\:model*="${name}"]`,
  textArea: (name: string) => `textarea[wire\\:model*="${name}"]`,
  submitButton: 'button[type="submit"]',
  cancelButton: 'button[type="button"]:has-text("Cancel")',

  // Modals
  modal: '[x-data*="modal"]',
  modalConfirm: '[x-data*="modal"] button[type="submit"]',
  modalCancel: '[x-data*="modal"] button:has-text("Cancel")',

  // Notifications
  notification: '.filament-notifications',
  successNotification: '.filament-notifications .text-success-600',
  errorNotification: '.filament-notifications .text-danger-600',

  // Status badges
  statusBadge: '.filament-badge',
  draftBadge: '.filament-badge:has-text("Draft")',
  pendingBadge: '.filament-badge:has-text("Pending")',
  publishedBadge: '.filament-badge:has-text("Published")',
  archivedBadge: '.filament-badge:has-text("Archived")',
  rejectedBadge: '.filament-badge:has-text("Rejected")',

  // Actions
  actionButton: (label: string) => `button:has-text("${label}")`,
  rowAction: (label: string) => `[data-action="${label}"]`,

  // Filters
  filterButton: 'button:has-text("Filter")',
  filterDropdown: '[data-filters]',
  filterOption: (label: string) => `[data-filter="${label}"]`,
  clearFiltersButton: 'button:has-text("Reset")',

  // Pagination
  pagination: '.filament-pagination',
  nextPage: '.filament-pagination button:has-text("Next")',
  prevPage: '.filament-pagination button:has-text("Previous")',
};

// Admin panel URLs
export const adminUrls = {
  base: 'http://localhost:8000/admin',
  login: 'http://localhost:8000/admin/login',
  dashboard: 'http://localhost:8000/admin',
  listings: 'http://localhost:8000/admin/listings',
  listingCreate: 'http://localhost:8000/admin/listings/create',
  bookings: 'http://localhost:8000/admin/bookings',
  bookingCreate: 'http://localhost:8000/admin/bookings/create',
  users: 'http://localhost:8000/admin/users',
  userCreate: 'http://localhost:8000/admin/users/create',
  coupons: 'http://localhost:8000/admin/coupons',
  couponCreate: 'http://localhost:8000/admin/coupons/create',
  partners: 'http://localhost:8000/admin/partners',
  partnerCreate: 'http://localhost:8000/admin/partners/create',
  platformSettings: 'http://localhost:8000/admin/platform-settings',
  gdprDashboard: 'http://localhost:8000/admin/gdpr-dashboard',
};

// Helper to generate unique test data
export function generateUniqueCode(prefix: string): string {
  return `${prefix}_${Date.now()}`;
}

export function generateUniqueEmail(prefix: string): string {
  return `${prefix}_${Date.now()}@test.com`;
}

export function getYesterday(): Date {
  const date = new Date();
  date.setDate(date.getDate() - 1);
  return date;
}

export function getTomorrow(): Date {
  const date = new Date();
  date.setDate(date.getDate() + 1);
  return date;
}

export function getNextWeek(): Date {
  const date = new Date();
  date.setDate(date.getDate() + 7);
  return date;
}
