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

// ============================================================================
// PLATFORM SETTINGS TEST DATA - Comprehensive test data for all 24 tabs
// ============================================================================

export const platformSettingsTestData = {
  // Tab 1: Platform Identity
  identity: {
    platformNameEn: 'Test Djerba Fun EN',
    platformNameFr: 'Test Djerba Fun FR',
    taglineEn: 'Test tagline in English',
    taglineFr: 'Test tagline en Français',
    descriptionEn:
      'Test description content in English. This is a comprehensive platform for tourism.',
    descriptionFr:
      'Contenu de description de test en Français. Ceci est une plateforme complète pour le tourisme.',
  },

  // Tab 3: Event of the Year
  eventOfYear: {
    tag: 'SPECIAL',
    titleEn: 'Summer Festival 2026',
    titleFr: "Festival d'Été 2026",
    descriptionEn: 'Join us for the biggest summer festival in Djerba!',
    descriptionFr: "Rejoignez-nous pour le plus grand festival d'été de Djerba!",
    link: 'https://djerbafun.com/events/summer-2026',
  },

  // Tab 6: Experience Categories
  experienceCategories: {
    titleEn: 'Test Experience Categories',
    titleFr: "Test Catégories d'Expériences",
    subtitleEn: 'Find your perfect adventure',
    subtitleFr: 'Trouvez votre aventure parfaite',
  },

  // Tab 7: Blog Section
  blogSection: {
    titleEn: 'Test Blog Section Title',
    titleFr: 'Test Titre Section Blog',
    subtitleEn: 'Latest stories from Djerba',
    subtitleFr: 'Les dernières histoires de Djerba',
    postLimit: 4,
  },

  // Tab 8: Featured Packages
  featuredPackages: {
    titleEn: 'Test Featured Packages',
    titleFr: 'Test Forfaits en Vedette',
    subtitleEn: 'Our most popular adventures',
    subtitleFr: 'Nos aventures les plus populaires',
    limit: 6,
  },

  // Tab 9: Custom Experience CTA
  customExperience: {
    titleEn: 'Create Your Dream Trip',
    titleFr: 'Créez Votre Voyage de Rêve',
    descriptionEn: 'Let us design a custom experience just for you',
    descriptionFr: 'Laissez-nous concevoir une expérience sur mesure pour vous',
    buttonTextEn: 'Get Started',
    buttonTextFr: 'Commencer',
    link: '/custom-trip',
  },

  // Tab 10: Newsletter
  newsletter: {
    titleEn: 'Test Newsletter Title',
    titleFr: 'Test Titre Newsletter',
    subtitleEn: 'Subscribe for exclusive updates and offers',
    subtitleFr: 'Abonnez-vous pour des mises à jour et offres exclusives',
    buttonTextEn: 'Subscribe Now',
    buttonTextFr: "S'abonner",
  },

  // Tab 11: About Page
  about: {
    heroTitleEn: 'Test About Title EN',
    heroTitleFr: 'Test Titre À Propos FR',
    heroSubtitleEn: 'Discover our story',
    heroSubtitleFr: 'Découvrez notre histoire',
    heroTaglineEn: 'Your gateway to authentic experiences',
    heroTaglineFr: 'Votre porte vers des expériences authentiques',
    founderName: 'Test Founder Name',
    founderStoryEn:
      'A passionate adventurer who fell in love with Djerba and decided to share its beauty with the world.',
    founderStoryFr:
      'Un aventurier passionné tombé amoureux de Djerba et qui a décidé de partager sa beauté avec le monde.',
    founderQuoteEn: 'Travel is the only thing you buy that makes you richer.',
    founderQuoteFr: "Le voyage est la seule chose qu'on achète qui nous enrichit.",
    storyHeadingEn: 'Our Journey',
    storyHeadingFr: 'Notre Parcours',
    storyIntroEn: 'Founded in 2024, we set out to transform travel in Tunisia.',
    storyIntroFr: 'Fondée en 2024, nous avons entrepris de transformer le voyage en Tunisie.',
    teamTitleEn: 'Meet Our Team',
    teamTitleFr: 'Rencontrez Notre Équipe',
    teamDescriptionEn: 'A dedicated group of travel enthusiasts.',
    teamDescriptionFr: 'Un groupe dévoué de passionnés de voyage.',
    impactTextEn: 'Over 10,000 happy travelers',
    impactTextFr: 'Plus de 10 000 voyageurs satisfaits',
  },

  // Tab 12: SEO & Metadata
  seo: {
    metaTitleEn: 'Test Meta Title - Djerba Fun',
    metaTitleFr: 'Test Titre Meta - Djerba Fun',
    metaDescriptionEn:
      'Test meta description for SEO purposes. Discover unique experiences in Djerba.',
    metaDescriptionFr:
      'Test description meta pour le SEO. Découvrez des expériences uniques à Djerba.',
    keywords: 'djerba, tunisia, travel, tourism, adventures',
    author: 'Test Author',
    organizationType: 'TravelAgency',
    foundedYear: '2024',
  },

  // Tab 13: Contact
  contact: {
    supportEmail: 'test-support@djerba.fun',
    generalEmail: 'test-info@djerba.fun',
    phone: '+216 75 123 456',
    whatsapp: '+216 75 123 457',
  },

  // Tab 14: Address
  address: {
    street: '123 Test Street',
    city: 'Houmt Souk',
    region: 'Medenine',
    postalCode: '4180',
    country: 'Tunisia',
    googleMapsUrl: 'https://maps.google.com/?q=djerba',
  },

  // Tab 15: Social Media
  social: {
    facebook: 'https://facebook.com/testdjerbafun',
    instagram: 'https://instagram.com/testdjerbafun',
    twitter: 'https://twitter.com/testdjerbafun',
    linkedin: 'https://linkedin.com/company/testdjerbafun',
    youtube: 'https://youtube.com/@testdjerbafun',
    tiktok: 'https://tiktok.com/@testdjerbafun',
  },

  // Tab 16: Email
  email: {
    fromName: 'Test Djerba Fun',
    fromAddress: 'test-noreply@djerba.fun',
    replyTo: 'test-reply@djerba.fun',
    termsUrl: 'https://djerbafun.com/terms',
    privacyUrl: 'https://djerbafun.com/privacy',
  },

  // Tab 17: Payment
  payment: {
    defaultCurrency: 'TND',
    enabledCurrencies: ['TND', 'EUR'],
    commissionPercent: 10,
    processingFeePercent: 2.5,
    eurToTndRate: 3.35,
    minBookingAmount: 50,
    maxBookingAmount: 10000,
    bankName: 'Test Bank Tunisia',
    accountHolder: 'Test Djerba Fun SARL',
    accountNumber: '1234567890',
    iban: 'TN59 1234 5678 9012 3456 7890',
    swiftBic: 'TESTTNTT',
  },

  // Tab 18: Booking
  booking: {
    holdDurationMinutes: 15,
    holdWarningMinutes: 5,
    autoCancelHours: 24,
  },

  // Tab 19: Localization
  localization: {
    defaultLocale: 'fr',
    availableLocales: ['fr', 'en'],
    fallbackLocale: 'en',
    dateFormat: 'DD/MM/YYYY',
    timeFormat: 'HH:mm',
    timezone: 'Africa/Tunis',
    weekStartsOn: 1, // Monday
  },

  // Tab 20: Features
  features: {
    reviewsEnabled: true,
    wishlistsEnabled: true,
    blogEnabled: true,
    instantBookingEnabled: true,
    giftCardsEnabled: false,
    loyaltyProgramEnabled: false,
    groupBookingsEnabled: true,
    customPackagesEnabled: true,
    requestToBookEnabled: false,
  },

  // Tab 21: Analytics
  analytics: {
    ga4MeasurementId: 'G-TESTMEASURE',
    gtmContainerId: 'GTM-TESTCONT',
    googleMapsApiKey: 'TEST-MAPS-API-KEY',
    facebookPixelId: '123456789',
    hotjarSiteId: '9876543',
    plausibleDomain: 'test.djerbafun.com',
  },

  // Tab 22: Legal & Compliance
  legal: {
    termsUrl: 'https://djerbafun.com/terms',
    privacyUrl: 'https://djerbafun.com/privacy',
    cookiePolicyUrl: 'https://djerbafun.com/cookies',
    refundPolicyUrl: 'https://djerbafun.com/refunds',
    cookieConsentEnabled: true,
    gdprModeEnabled: true,
    dataRetentionDays: 365,
    minimumAgeRequirement: 18,
  },

  // Tab 23: Vendor Settings
  vendor: {
    autoApproveEnabled: false,
    requireKycEnabled: true,
    commissionRate: 15,
    payoutFrequency: 'monthly',
    payoutMinimum: 100,
    payoutCurrency: 'TND',
    payoutDelayDays: 7,
  },

  // Tab 24: Brand Colors
  brandColors: {
    primaryColor: '#0D642E',
    accentColor: '#8BC34A',
    creamColor: '#f5f0d1',
  },

  // Test testimonial data
  testimonial: {
    name: 'Test User',
    quoteEn: 'Amazing experience! Would highly recommend.',
    quoteFr: 'Expérience incroyable! Je recommande vivement.',
    rating: 5,
  },

  // Test destination data
  destination: {
    nameEn: 'Test Destination',
    nameFr: 'Destination Test',
    slug: 'test-destination',
    descriptionEn: 'A beautiful test destination.',
    descriptionFr: 'Une belle destination de test.',
    highlightsEn: ['Beautiful beaches', 'Historic sites'],
    highlightsFr: ['Belles plages', 'Sites historiques'],
  },

  // Test commitment data (for About page)
  commitment: {
    icon: 'heart',
    titleEn: 'Test Commitment',
    titleFr: 'Engagement Test',
    descriptionEn: 'We are committed to providing excellent service.',
    descriptionFr: 'Nous nous engageons à fournir un excellent service.',
  },

  // Test partner data (for About page)
  partner: {
    name: 'Test Partner Company',
  },
};

// ============================================================================
// BLOG POST TEST DATA
// ============================================================================

export const blogTestData = {
  validPost: {
    titleEn: 'Discover the Hidden Gems of Djerba',
    titleFr: 'Découvrez les Trésors Cachés de Djerba',
    excerptEn: 'A comprehensive guide to exploring the lesser-known attractions of Djerba island.',
    excerptFr: "Un guide complet pour explorer les attractions moins connues de l'île de Djerba.",
    contentEn: `<p>Djerba, the island of dreams, offers countless hidden treasures waiting to be discovered. From ancient synagogues to pristine beaches, every corner tells a story.</p>
<p>In this guide, we'll take you through the winding streets of Houmt Souk, explore the traditional pottery workshops of Guellala, and discover the flamingo lagoons that make this island truly magical.</p>
<p>Whether you're a history buff, nature lover, or simply seeking authentic Tunisian hospitality, Djerba has something special for everyone. Let's embark on this journey together and uncover the secrets that make Djerba an unforgettable destination.</p>`,
    contentFr: `<p>Djerba, l'île des rêves, offre d'innombrables trésors cachés qui attendent d'être découverts. Des synagogues anciennes aux plages immaculées, chaque coin raconte une histoire.</p>
<p>Dans ce guide, nous vous emmènerons à travers les ruelles sinueuses de Houmt Souk, explorerons les ateliers de poterie traditionnelle de Guellala et découvrirons les lagunes de flamants roses qui rendent cette île vraiment magique.</p>
<p>Que vous soyez un passionné d'histoire, un amoureux de la nature ou simplement à la recherche de l'hospitalité tunisienne authentique, Djerba a quelque chose de spécial pour tout le monde.</p>`,
    tags: ['travel', 'djerba', 'hidden-gems', 'adventure'],
    seoTitleEn: 'Hidden Gems of Djerba - Ultimate Travel Guide',
    seoTitleFr: 'Trésors Cachés de Djerba - Guide de Voyage',
    seoDescriptionEn:
      'Discover the best hidden attractions in Djerba. Explore ancient sites, pristine beaches, and authentic local experiences.',
    seoDescriptionFr:
      'Découvrez les meilleures attractions cachées de Djerba. Explorez des sites anciens, des plages préservées et des expériences locales authentiques.',
  },
  minimalPost: {
    titleEn: 'Quick Travel Tips',
    contentEn: '<p>Simple tips for traveling to Djerba.</p>',
  },
  scheduledPost: {
    titleEn: 'Upcoming Festival Guide',
    titleFr: 'Guide des Festivals à Venir',
    contentEn:
      '<p>Everything you need to know about the upcoming cultural festivals in Djerba.</p>',
    contentFr: '<p>Tout ce que vous devez savoir sur les festivals culturels à venir à Djerba.</p>',
  },
  category: {
    name: 'Travel Tips',
    color: '#2E9E6B',
  },
  // Edge case test data
  edgeCases: {
    longTitle: 'A'.repeat(256), // Exceeds 255 char limit
    longExcerpt: 'B'.repeat(501), // Exceeds 500 char limit
    longSeoTitle: 'C'.repeat(61), // Exceeds 60 char limit
    longSeoDescription: 'D'.repeat(161), // Exceeds 160 char limit
    whitespaceTitle: '   ',
    emptyHtmlContent: '<p></p><br><span></span>',
    xssContent: '<script>alert("xss")</script><p>Normal content</p>',
    sqlInjectionTitle: "Test'; DROP TABLE blog_posts; --",
    unicodeTitle: '🌴 Djerba Adventures ✨ مغامرات جربة',
    specialCharsTitle: 'Djerba\'s "Best" Spots & More <Guide>',
    frenchAccentsTitle: "Découvrez l'île de Djerba",
  },
};

export const blogUrls = {
  list: 'http://localhost:8000/admin/blog-posts',
  create: 'http://localhost:8000/admin/blog-posts/create',
  categories: 'http://localhost:8000/admin/blog-categories',
};

export const blogSelectors = {
  // Form fields (Filament 3/Livewire patterns)
  titleInput: '#data\\.title, input[id*="data.title"], input[id$="-title"]',
  slugInput: '#data\\.slug, input[id*="data.slug"], input[id$="-slug"]',
  excerptInput: '#data\\.excerpt, textarea[id*="data.excerpt"], textarea[id$="-excerpt"]',
  contentEditor: 'iframe.tox-edit-area__iframe', // TinyMCE iframe
  authorSelect: '[wire\\:model*="author_id"], select[id*="author_id"]',
  categorySelect: '[wire\\:model*="blog_category_id"], select[id*="blog_category_id"]',
  tagsInput: '[wire\\:model*="data.tags"] input, input[id*="data.tags"]',
  statusSelect: 'select[wire\\:model*="data.status"], select[id*="data.status"], #data\\.status',
  publishedAtInput:
    '#data\\.published_at, input[id*="data.published_at"], input[id$="-published_at"]',
  featuredToggle: 'input[type="checkbox"][id*="is_featured"], [wire\\:model*="is_featured"]',
  seoTitleInput: '#data\\.seo_title, input[id*="data.seo_title"], input[id$="-seo_title"]',
  seoDescriptionInput:
    '#data\\.seo_description, textarea[id*="data.seo_description"], textarea[id$="-seo_description"]',
  heroImagesInput: 'input[type="file"]',

  // Locale switcher (Filament uses a dropdown button, not tabs)
  localeSwitcherButton:
    'button:has-text("English"), button:has-text("Français"), button:has-text("French"), .fi-locale-switcher button',
  enLocaleOption:
    '[role="option"]:has-text("English"), [role="menuitem"]:has-text("English"), li:has-text("English")',
  frLocaleOption:
    '[role="option"]:has-text("Français"), [role="menuitem"]:has-text("Français"), [role="option"]:has-text("French"), li:has-text("Français"), li:has-text("French")',
  // Legacy tab selectors (for backwards compatibility)
  enTab:
    '[role="tab"]:has-text("EN"), [role="tab"]:has-text("English"), button[data-locale="en"], [aria-label*="EN"]',
  frTab:
    '[role="tab"]:has-text("FR"), [role="tab"]:has-text("French"), [role="tab"]:has-text("Français"), button[data-locale="fr"], [aria-label*="FR"]',

  // Status badges
  draftBadge:
    '[role="status"]:has-text("Draft"), .fi-badge:has-text("Draft"), span.fi-badge-item:has-text("Draft")',
  publishedBadge:
    '[role="status"]:has-text("Published"), .fi-badge:has-text("Published"), span.fi-badge-item:has-text("Published")',
  scheduledBadge:
    '[role="status"]:has-text("Scheduled"), .fi-badge:has-text("Scheduled"), span.fi-badge-item:has-text("Scheduled")',

  // Actions
  previewButton: 'button:has-text("Preview")',
  createCategoryButton: 'button:has-text("Create"), button:has-text("New")',
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
