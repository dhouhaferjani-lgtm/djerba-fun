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

// ============================================================================
// COMPLETE LISTING TEMPLATES (Full Field Coverage)
// ============================================================================

/**
 * Complete listing templates with ALL type-specific fields
 * Used for comprehensive E2E testing of listing creation
 */
export const completeListingTemplates = {
  /**
   * TOUR - Complete template with all tour-specific fields
   */
  tour: {
    serviceType: 'tour' as const,
    // Basic info
    titleEn: 'Desert Camel Trek Adventure',
    titleFr: 'Aventure de Trek en Chameau dans le Desert',
    summaryEn: 'Experience the magic of the Sahara on a guided camel trek through golden dunes.',
    summaryFr: 'Decouvrez la magie du Sahara lors dune randonnee guidee a dos de chameau.',
    descriptionEn:
      'Join us for an unforgettable journey through the Sahara Desert. Our expert local guides will lead you through stunning sand dunes, traditional Berber villages, and breathtaking oases.',
    descriptionFr:
      'Rejoignez-nous pour un voyage inoubliable a travers le desert du Sahara. Nos guides locaux experts vous meneront a travers de superbes dunes de sable.',
    // Highlights, included, not included
    highlightsEn: [
      'Stunning desert sunrise views',
      'Traditional Berber lunch',
      'Expert local guide',
      'Camel ride through dunes',
    ],
    highlightsFr: [
      'Vues epoustouflantes au lever du soleil',
      'Dejeuner berbere traditionnel',
      'Guide local expert',
      'Balade a dos de chameau',
    ],
    includedEn: [
      'Camel ride',
      'Traditional lunch',
      'Bottled water',
      'Professional guide',
      'Sand boarding equipment',
    ],
    includedFr: [
      'Balade a chameau',
      'Dejeuner traditionnel',
      'Eau en bouteille',
      'Guide professionnel',
      'Equipement de sandboard',
    ],
    notIncludedEn: ['Personal expenses', 'Tips', 'Travel insurance', 'Hotel pickup outside zone'],
    notIncludedFr: [
      'Depenses personnelles',
      'Pourboires',
      'Assurance voyage',
      'Transfert hors zone',
    ],
    requirementsEn: ['Comfortable walking shoes', 'Sun protection', 'Light clothing', 'Camera'],
    requirementsFr: [
      'Chaussures de marche confortables',
      'Protection solaire',
      'Vetements legers',
      'Appareil photo',
    ],
    // Tour-specific fields
    duration: { value: 6, unit: 'hours' },
    difficulty: 'moderate',
    distance: { value: 15, unit: 'km' },
    activityType: 'hiking',
    hasElevationProfile: false,
    itinerary: [
      {
        title: 'Hotel Pickup',
        description: 'Pick up from your hotel in the tourist zone',
        durationMinutes: 30,
      },
      {
        title: 'Camel Trek Start',
        description: 'Begin your camel journey through the dunes',
        durationMinutes: 120,
      },
      {
        title: 'Oasis Visit',
        description: 'Rest at a traditional desert oasis',
        durationMinutes: 45,
      },
      {
        title: 'Traditional Lunch',
        description: 'Enjoy authentic Berber cuisine',
        durationMinutes: 60,
      },
      {
        title: 'Return Journey',
        description: 'Trek back to the starting point',
        durationMinutes: 90,
      },
    ],
    // Pricing
    priceTnd: 150,
    priceEur: 45,
    personTypes: [
      {
        key: 'adult',
        labelEn: 'Adult',
        labelFr: 'Adulte',
        priceTnd: 150,
        priceEur: 45,
        minAge: 12,
      },
      {
        key: 'child',
        labelEn: 'Child (4-11)',
        labelFr: 'Enfant (4-11)',
        priceTnd: 80,
        priceEur: 24,
        minAge: 4,
        maxAge: 11,
      },
      {
        key: 'infant',
        labelEn: 'Infant (0-3)',
        labelFr: 'Bebe (0-3)',
        priceTnd: 0,
        priceEur: 0,
        maxAge: 3,
      },
    ],
    // Group settings
    minGroupSize: 2,
    maxGroupSize: 15,
    minAdvanceBookingHours: 24,
    // Meeting point
    meetingPoint: {
      address: 'Hotel Zone, Houmt Souk, Djerba',
      instructions: 'Meet at the hotel lobby',
      coordinates: { lat: 33.8075, lng: 10.8572 },
    },
    // Cancellation
    cancellationPolicy: {
      type: 'flexible',
      description: 'Full refund up to 24 hours before start',
    },
  },

  /**
   * NAUTICAL - Complete template with all nautical-specific fields
   */
  nautical: {
    serviceType: 'nautical' as const,
    // Basic info
    titleEn: 'Sunset Catamaran Cruise',
    titleFr: 'Croisiere en Catamaran au Coucher du Soleil',
    summaryEn: 'Sail into the sunset on a luxury catamaran with swimming and snorkeling stops.',
    summaryFr:
      'Naviguez vers le coucher du soleil sur un catamaran de luxe avec arrets baignade et plongee.',
    descriptionEn:
      'Experience a magical evening sailing along the stunning coastline of Djerba. Our spacious catamaran offers the perfect setting for watching the sunset while enjoying refreshments.',
    descriptionFr:
      'Vivez une soiree magique en naviguant le long de la superbe cote de Djerba. Notre catamaran spacieux offre le cadre parfait pour admirer le coucher du soleil.',
    highlightsEn: [
      'Stunning sunset views',
      'Swimming and snorkeling stop',
      'Refreshments included',
      'Professional crew',
    ],
    highlightsFr: [
      'Vues magnifiques au coucher du soleil',
      'Arret baignade et snorkeling',
      'Rafraichissements inclus',
      'Equipage professionnel',
    ],
    includedEn: [
      'Catamaran cruise',
      'Snorkeling equipment',
      'Refreshments',
      'Life jackets',
      'Towels',
    ],
    includedFr: [
      'Croisiere en catamaran',
      'Equipement de snorkeling',
      'Rafraichissements',
      'Gilets de sauvetage',
      'Serviettes',
    ],
    notIncludedEn: ['Alcoholic beverages', 'Personal expenses', 'Tips'],
    notIncludedFr: ['Boissons alcoolisees', 'Depenses personnelles', 'Pourboires'],
    requirementsEn: ['Swimsuit', 'Sunscreen', 'Towel (optional)', 'Camera'],
    requirementsFr: ['Maillot de bain', 'Creme solaire', 'Serviette (optionnel)', 'Appareil photo'],
    // Nautical-specific fields
    boatName: 'Sea Breeze',
    boatLength: 14.5,
    boatCapacity: 20,
    boatYear: 2020,
    licenseRequired: false,
    licenseType: null,
    crewIncluded: true,
    fuelIncluded: true,
    equipmentIncluded: ['snorkeling_gear', 'life_jackets', 'towels', 'cooler'],
    minRentalHours: 2,
    duration: { value: 3, unit: 'hours' },
    difficulty: 'easy',
    // Pricing
    priceTnd: 200,
    priceEur: 60,
    personTypes: [
      { key: 'adult', labelEn: 'Adult', labelFr: 'Adulte', priceTnd: 200, priceEur: 60 },
      {
        key: 'child',
        labelEn: 'Child (4-12)',
        labelFr: 'Enfant (4-12)',
        priceTnd: 120,
        priceEur: 36,
        minAge: 4,
        maxAge: 12,
      },
    ],
    // Group settings
    minGroupSize: 4,
    maxGroupSize: 20,
    minAdvanceBookingHours: 12,
    // Meeting point
    meetingPoint: {
      address: 'Houmt Souk Marina, Pier 3, Djerba',
      instructions: 'Meet at Pier 3 near the marina office',
      coordinates: { lat: 33.8769, lng: 10.8575 },
    },
    cancellationPolicy: {
      type: 'moderate',
      description: 'Full refund up to 48 hours before departure',
    },
  },

  /**
   * ACCOMMODATION - Complete template with all accommodation-specific fields
   */
  accommodation: {
    serviceType: 'accommodation' as const,
    // Basic info
    titleEn: 'Traditional Menzel Villa with Pool',
    titleFr: 'Villa Menzel Traditionnelle avec Piscine',
    summaryEn:
      'Stay in a beautifully restored traditional Djerbien house with private pool and garden.',
    summaryFr:
      'Sejournez dans une maison traditionnelle djerbienne magnifiquement restauree avec piscine privee et jardin.',
    descriptionEn:
      'Experience authentic Tunisian hospitality in our stunning Menzel villa. This traditional Djerbien house has been lovingly restored, featuring whitewashed walls, blue accents, and beautiful tile work.',
    descriptionFr:
      'Decouvrez lhospitalite tunisienne authentique dans notre superbe villa Menzel. Cette maison traditionnelle djerbienne a ete soigneusement restauree.',
    highlightsEn: [
      'Private swimming pool',
      'Traditional architecture',
      'Lush garden',
      'Rooftop terrace',
      'Fully equipped kitchen',
    ],
    highlightsFr: [
      'Piscine privee',
      'Architecture traditionnelle',
      'Jardin luxuriant',
      'Terrasse sur le toit',
      'Cuisine equipee',
    ],
    includedEn: [
      'Daily breakfast',
      'Pool access',
      'WiFi',
      'Air conditioning',
      'Parking',
      'Weekly cleaning',
    ],
    includedFr: [
      'Petit-dejeuner quotidien',
      'Acces piscine',
      'WiFi',
      'Climatisation',
      'Parking',
      'Menage hebdomadaire',
    ],
    notIncludedEn: ['Airport transfer', 'Additional meals', 'Laundry service'],
    notIncludedFr: ['Transfert aeroport', 'Repas supplementaires', 'Service de blanchisserie'],
    requirementsEn: ['Valid ID or passport', 'Security deposit'],
    requirementsFr: ['Piece didentite valide', 'Depot de garantie'],
    // Accommodation-specific fields
    accommodationType: 'villa',
    bedrooms: 3,
    bathrooms: 2,
    maxGuests: 6,
    propertySize: 180,
    checkInTime: '15:00',
    checkOutTime: '11:00',
    mealsIncluded: { breakfast: true, lunch: false, dinner: false },
    amenities: [
      'wifi',
      'pool',
      'air_conditioning',
      'parking',
      'kitchen',
      'washer',
      'dryer',
      'tv',
      'bbq',
      'garden',
      'terrace',
    ],
    houseRules: {
      en: 'No smoking indoors. No parties or events. Quiet hours after 10pm. Pets allowed with prior approval.',
      fr: 'Non-fumeur a linterieur. Pas de fetes ou evenements. Heures calmes apres 22h. Animaux autorises avec accord prealable.',
    },
    // Duration for accommodation is per night
    duration: { value: 1, unit: 'days' },
    // Pricing (per night)
    priceTnd: 350,
    priceEur: 105,
    personTypes: [
      {
        key: 'per_night',
        labelEn: 'Per Night (up to 4 guests)',
        labelFr: 'Par Nuit (jusqua 4 personnes)',
        priceTnd: 350,
        priceEur: 105,
      },
      {
        key: 'extra_guest',
        labelEn: 'Extra Guest',
        labelFr: 'Personne supplementaire',
        priceTnd: 50,
        priceEur: 15,
      },
    ],
    // Group settings
    minGroupSize: 1,
    maxGroupSize: 6,
    minAdvanceBookingHours: 48,
    // Meeting point (property address)
    meetingPoint: {
      address: 'Zone Touristique, Midoun, Djerba',
      instructions: 'Property manager will meet you at the entrance',
      coordinates: { lat: 33.7892, lng: 10.9921 },
    },
    cancellationPolicy: {
      type: 'strict',
      description: 'Full refund up to 7 days before check-in. 50% refund up to 3 days before.',
    },
  },

  /**
   * EVENT - Complete template with all event-specific fields
   */
  event: {
    serviceType: 'event' as const,
    // Basic info
    titleEn: 'Traditional Music & Dance Festival',
    titleFr: 'Festival de Musique et Danse Traditionnelle',
    summaryEn:
      'Annual celebration of traditional Tunisian music and dance featuring local and international artists.',
    summaryFr:
      'Celebration annuelle de la musique et danse traditionnelle tunisienne avec des artistes locaux et internationaux.',
    descriptionEn:
      'Join us for three days of amazing performances celebrating the rich musical heritage of Tunisia. Enjoy traditional orchestras, folk dances, and contemporary interpretations.',
    descriptionFr:
      'Rejoignez-nous pour trois jours de spectacles celebrant le riche patrimoine musical de la Tunisie. Profitez dorchestres traditionnels et de danses folkloriques.',
    highlightsEn: [
      '3 days of performances',
      'Traditional & contemporary music',
      'Food vendors',
      'Artisan market',
      'Family friendly',
    ],
    highlightsFr: [
      '3 jours de spectacles',
      'Musique traditionnelle et contemporaine',
      'Stands de nourriture',
      'Marche dartisanat',
      'Familial',
    ],
    includedEn: ['Event entry', 'Program guide', 'Access to all stages'],
    includedFr: ['Entree evenement', 'Guide du programme', 'Acces a toutes les scenes'],
    notIncludedEn: ['Food and beverages', 'Merchandise', 'Parking'],
    notIncludedFr: ['Nourriture et boissons', 'Marchandises', 'Parking'],
    requirementsEn: ['Valid ticket', 'ID for age verification'],
    requirementsFr: ['Billet valide', 'Piece didentite pour verification dage'],
    // Event-specific fields
    eventType: 'festival',
    startDate: getFutureDate(30), // 30 days from now
    endDate: getFutureDate(32), // 32 days from now
    venue: {
      name: 'Amphitheatre de Djerba',
      address: 'Zone Touristique, Houmt Souk, Djerba',
      coordinates: { lat: 33.8769, lng: 10.8575 },
      capacity: 500,
    },
    agenda: [
      {
        time: '18:00',
        titleEn: 'Gates Open',
        titleFr: 'Ouverture des portes',
        descriptionEn: 'Welcome and registration',
        descriptionFr: 'Accueil et inscription',
      },
      {
        time: '19:00',
        titleEn: 'Opening Ceremony',
        titleFr: 'Ceremonie douverture',
        descriptionEn: 'Welcome speech and traditional blessing',
        descriptionFr: 'Discours de bienvenue et benediction traditionnelle',
      },
      {
        time: '20:00',
        titleEn: 'Traditional Orchestra',
        titleFr: 'Orchestre Traditionnel',
        descriptionEn: 'Local musicians perform classical Tunisian pieces',
        descriptionFr: 'Les musiciens locaux interpretent des pieces tunisiennes classiques',
      },
      {
        time: '21:30',
        titleEn: 'Folk Dance Performance',
        titleFr: 'Spectacle de Danse Folklorique',
        descriptionEn: 'Traditional Djerbien dance troupe',
        descriptionFr: 'Troupe de danse traditionnelle djerbienne',
      },
      {
        time: '23:00',
        titleEn: 'Contemporary Fusion',
        titleFr: 'Fusion Contemporaine',
        descriptionEn: 'Modern interpretations of traditional music',
        descriptionFr: 'Interpretations modernes de la musique traditionnelle',
      },
    ],
    // Pricing
    priceTnd: 80,
    priceEur: 25,
    personTypes: [
      {
        key: 'general',
        labelEn: 'General Admission',
        labelFr: 'Entree Generale',
        priceTnd: 80,
        priceEur: 25,
      },
      { key: 'vip', labelEn: 'VIP Access', labelFr: 'Acces VIP', priceTnd: 200, priceEur: 60 },
      { key: 'student', labelEn: 'Student', labelFr: 'Etudiant', priceTnd: 50, priceEur: 15 },
    ],
    // Group settings
    minGroupSize: 1,
    maxGroupSize: 500,
    minAdvanceBookingHours: 2,
    // Meeting point
    meetingPoint: {
      address: 'Amphitheatre de Djerba, Main Entrance',
      instructions: 'Present your ticket at the main gate',
      coordinates: { lat: 33.8769, lng: 10.8575 },
    },
    cancellationPolicy: {
      type: 'strict',
      description: 'No refunds after purchase. Tickets transferable.',
    },
  },
};

// ============================================================================
// VALIDATION ERROR SCENARIOS
// ============================================================================

/**
 * Test data for validation error scenarios
 */
export const validationErrorScenarios = {
  missingTitle: {
    titleEn: '',
    titleFr: '',
    summaryEn: 'A summary without title',
    priceTnd: 100,
    priceEur: 30,
    expectedError: 'Title is required',
  },
  missingPricing: {
    titleEn: 'Test Listing Without Pricing',
    titleFr: 'Annonce Test Sans Prix',
    summaryEn: 'A listing without pricing',
    // No pricing fields
    expectedError: 'Pricing is required',
  },
  missingLocation: {
    titleEn: 'Test Listing Without Location',
    titleFr: 'Annonce Test Sans Emplacement',
    summaryEn: 'A listing without location',
    priceTnd: 100,
    priceEur: 30,
    // No location_id
    expectedError: 'Location is required',
  },
  eventMissingStartDate: {
    serviceType: 'event' as const,
    titleEn: 'Event Without Start Date',
    titleFr: 'Evenement Sans Date de Debut',
    eventType: 'festival',
    // No start_date
    expectedError: 'Start date is required',
  },
  eventMissingVenue: {
    serviceType: 'event' as const,
    titleEn: 'Event Without Venue',
    titleFr: 'Evenement Sans Lieu',
    eventType: 'festival',
    startDate: getFutureDate(30),
    // No venue
    expectedError: 'Venue is required',
  },
  negativePricing: {
    titleEn: 'Test Listing With Negative Price',
    titleFr: 'Annonce Test Avec Prix Negatif',
    priceTnd: -50,
    priceEur: -15,
    expectedError: 'Price must be positive',
  },
  pastEventDate: {
    serviceType: 'event' as const,
    titleEn: 'Past Event',
    titleFr: 'Evenement Passe',
    eventType: 'festival',
    startDate: getPastDate(5), // 5 days ago
    endDate: getPastDate(3),
    expectedError: 'Start date must be in the future',
  },
};

// ============================================================================
// AVAILABILITY RULE EDGE CASE TEMPLATES
// ============================================================================

/**
 * Additional availability rule templates for edge case testing
 */
export const availabilityRuleEdgeCases = {
  weekendOnly: {
    ruleType: 'weekly',
    daysOfWeek: [0, 6], // Sunday, Saturday
    startTime: '10:00',
    endTime: '18:00',
    capacity: 15,
  },
  morningSlot: {
    ruleType: 'weekly',
    daysOfWeek: [1, 2, 3, 4, 5], // Weekdays
    startTime: '06:00',
    endTime: '12:00',
    capacity: 8,
  },
  eveningSlot: {
    ruleType: 'weekly',
    daysOfWeek: [1, 2, 3, 4, 5], // Weekdays
    startTime: '18:00',
    endTime: '22:00',
    capacity: 12,
  },
  singleDate: {
    ruleType: 'specific_dates',
    dates: [getFutureDate(14)],
    startTime: '09:00',
    endTime: '17:00',
    capacity: 20,
  },
  longBlockedPeriod: {
    ruleType: 'blocked_dates',
    startDate: getFutureDate(20),
    endDate: getFutureDate(40),
    reason: 'Seasonal closure for renovation',
  },
  zeroCapacity: {
    ruleType: 'weekly',
    daysOfWeek: [1, 3, 5],
    startTime: '09:00',
    endTime: '17:00',
    capacity: 0, // Invalid - should be rejected
  },
  pastDate: {
    ruleType: 'specific_dates',
    dates: [getPastDate(5)], // Invalid - should be rejected
    startTime: '09:00',
    endTime: '17:00',
    capacity: 10,
  },
  invalidTimeRange: {
    ruleType: 'weekly',
    daysOfWeek: [1, 3, 5],
    startTime: '17:00',
    endTime: '09:00', // End before start - should be rejected
    capacity: 10,
  },
};
