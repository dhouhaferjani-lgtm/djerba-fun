/**
 * Analytics Utilities
 *
 * Provides utilities for tracking events and page views.
 * This is a placeholder implementation that logs to console in development.
 *
 * In production, integrate with analytics providers like:
 * - Plausible Analytics
 * - PostHog
 * - Google Analytics 4
 * - Mixpanel
 * - Amplitude
 */

type EventProperties = Record<string, string | number | boolean | null | undefined>;

/**
 * Track a custom event
 *
 * @param event - The event name (e.g., 'listing_viewed', 'booking_started')
 * @param properties - Additional properties to track with the event
 *
 * @example
 * trackEvent('listing_viewed', {
 *   listing_id: '123',
 *   listing_type: 'tour',
 *   price: 99.99,
 * });
 */
export function trackEvent(event: string, properties?: EventProperties): void {
  if (typeof window === 'undefined') {
    return; // Don't track on server
  }

  if (process.env.NODE_ENV === 'development') {
    console.log('[Analytics Event]', event, properties);
  }

  // Production implementation example:
  // if (process.env.NODE_ENV === 'production') {
  //   // Plausible
  //   if (window.plausible) {
  //     window.plausible(event, { props: properties });
  //   }
  //
  //   // PostHog
  //   if (window.posthog) {
  //     window.posthog.capture(event, properties);
  //   }
  //
  //   // Google Analytics 4
  //   if (window.gtag) {
  //     window.gtag('event', event, properties);
  //   }
  // }
}

/**
 * Track a page view
 *
 * @param url - The URL being viewed
 *
 * @example
 * trackPageView('/en/listings/mountain-hike');
 */
export function trackPageView(url: string): void {
  if (typeof window === 'undefined') {
    return;
  }

  if (process.env.NODE_ENV === 'development') {
    console.log('[Analytics Page View]', url);
  }

  // Production implementation example:
  // if (process.env.NODE_ENV === 'production') {
  //   // Plausible (automatically tracks pageviews)
  //   // PostHog
  //   if (window.posthog) {
  //     window.posthog.capture('$pageview', { $current_url: url });
  //   }
  //
  //   // Google Analytics 4
  //   if (window.gtag) {
  //     window.gtag('config', 'GA_MEASUREMENT_ID', {
  //       page_path: url,
  //     });
  //   }
  // }
}

/**
 * Identify a user (for analytics providers that support user tracking)
 *
 * @param userId - The user ID
 * @param traits - Additional user traits
 *
 * @example
 * identifyUser('user_123', {
 *   email: 'user@example.com',
 *   role: 'traveler',
 * });
 */
export function identifyUser(userId: string, traits?: EventProperties): void {
  if (typeof window === 'undefined') {
    return;
  }

  if (process.env.NODE_ENV === 'development') {
    console.log('[Analytics Identify]', userId, traits);
  }

  // Production implementation example:
  // if (process.env.NODE_ENV === 'production') {
  //   // PostHog
  //   if (window.posthog) {
  //     window.posthog.identify(userId, traits);
  //   }
  //
  //   // Mixpanel
  //   if (window.mixpanel) {
  //     window.mixpanel.identify(userId);
  //     if (traits) {
  //       window.mixpanel.people.set(traits);
  //     }
  //   }
  // }
}

/**
 * Track e-commerce events
 */
export const ecommerce = {
  /**
   * Track when a user views a listing
   */
  viewListing: (listingId: string, listingType: string, price: number) => {
    trackEvent('listing_viewed', {
      listing_id: listingId,
      listing_type: listingType,
      price,
    });
  },

  /**
   * Track when a user starts the booking process
   */
  startBooking: (listingId: string, listingType: string, price: number) => {
    trackEvent('booking_started', {
      listing_id: listingId,
      listing_type: listingType,
      price,
    });
  },

  /**
   * Track when a user completes a booking
   */
  completeBooking: (bookingId: string, listingId: string, revenue: number, currency: string) => {
    trackEvent('booking_completed', {
      booking_id: bookingId,
      listing_id: listingId,
      revenue,
      currency,
    });
  },

  /**
   * Track when a user adds travelers to a booking
   */
  addTravelers: (listingId: string, travelerCount: number) => {
    trackEvent('travelers_added', {
      listing_id: listingId,
      traveler_count: travelerCount,
    });
  },

  /**
   * Track search queries
   */
  search: (query: string, filters?: EventProperties) => {
    trackEvent('search', {
      query,
      ...filters,
    });
  },
};

/**
 * Performance monitoring
 */
export const performance = {
  /**
   * Track Core Web Vitals
   */
  trackWebVitals: (metric: { name: string; value: number; id: string }) => {
    trackEvent('web_vital', {
      metric_name: metric.name,
      metric_value: metric.value,
      metric_id: metric.id,
    });
  },
};

// Type augmentation for window object (for TypeScript)
declare global {
  interface Window {
    plausible?: (event: string, options?: { props?: EventProperties }) => void;
    posthog?: {
      capture: (event: string, properties?: EventProperties) => void;
      identify: (userId: string, properties?: EventProperties) => void;
    };
    gtag?: (
      command: string,
      targetId: string,
      config?: EventProperties | { page_path: string }
    ) => void;
    mixpanel?: {
      identify: (userId: string) => void;
      people: {
        set: (properties: EventProperties) => void;
      };
    };
  }
}
