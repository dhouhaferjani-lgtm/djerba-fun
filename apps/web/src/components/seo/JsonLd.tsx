import React from 'react';

type JsonLdBase = {
  '@context': string;
  '@type': string;
};

type OrganizationSchema = JsonLdBase & {
  '@type': 'Organization';
  name: string;
  url?: string;
  logo?: string;
  description?: string;
  sameAs?: string[];
  contactPoint?: {
    '@type': 'ContactPoint';
    telephone?: string;
    contactType?: string;
    email?: string;
  };
};

type LocalBusinessSchema = JsonLdBase & {
  '@type': 'LocalBusiness';
  name: string;
  description?: string;
  image?: string;
  address?: {
    '@type': 'PostalAddress';
    streetAddress?: string;
    addressLocality?: string;
    addressRegion?: string;
    postalCode?: string;
    addressCountry?: string;
  };
  geo?: {
    '@type': 'GeoCoordinates';
    latitude: number;
    longitude: number;
  };
  telephone?: string;
  url?: string;
  priceRange?: string;
};

type EventSchema = JsonLdBase & {
  '@type': 'Event';
  name: string;
  description?: string;
  image?: string | string[];
  startDate: string;
  endDate?: string;
  url?: string;
  eventStatus?: string;
  eventAttendanceMode?: string;
  location: {
    '@type': 'Place';
    name?: string;
    address?:
      | string
      | {
          '@type': 'PostalAddress';
          streetAddress?: string;
          addressLocality?: string;
          addressRegion?: string;
          postalCode?: string;
          addressCountry?: string;
        };
  };
  offers?: {
    '@type': 'Offer' | 'AggregateOffer';
    price: number | string;
    priceCurrency: string;
    availability?: string;
    url?: string;
    validFrom?: string;
    seller?: {
      '@type': 'Organization';
      name: string;
    };
  };
  performer?: {
    '@type': 'Organization' | 'Person';
    name: string;
  };
  organizer?: {
    '@type': 'Organization' | 'Person';
    name: string;
    url?: string;
    image?: string;
  };
};

type ProductSchema = JsonLdBase & {
  '@type': 'Product';
  name: string;
  description?: string;
  image?: string | string[];
  brand?: {
    '@type': 'Brand';
    name: string;
  };
  offers?: {
    '@type': 'Offer';
    price: number;
    priceCurrency: string;
    availability?: string;
    url?: string;
    priceValidUntil?: string;
  };
  aggregateRating?: {
    '@type': 'AggregateRating';
    ratingValue: number;
    reviewCount: number;
    bestRating?: number;
    worstRating?: number;
  };
  review?: Array<{
    '@type': 'Review';
    author: {
      '@type': 'Person';
      name: string;
    };
    datePublished: string;
    reviewBody: string;
    reviewRating: {
      '@type': 'Rating';
      ratingValue: number;
      bestRating?: number;
      worstRating?: number;
    };
  }>;
};

type BreadcrumbSchema = JsonLdBase & {
  '@type': 'BreadcrumbList';
  itemListElement: Array<{
    '@type': 'ListItem';
    position: number;
    name: string;
    item?: string;
  }>;
};

type ReviewSchema = JsonLdBase & {
  '@type': 'Review';
  author: {
    '@type': 'Person';
    name: string;
  };
  datePublished: string;
  reviewBody: string;
  reviewRating: {
    '@type': 'Rating';
    ratingValue: number;
    bestRating?: number;
    worstRating?: number;
  };
  itemReviewed: {
    '@type': string;
    name: string;
  };
};

type AggregateRatingSchema = JsonLdBase & {
  '@type': 'AggregateRating';
  itemReviewed: {
    '@type': string;
    name: string;
  };
  ratingValue: number;
  reviewCount: number;
  bestRating?: number;
  worstRating?: number;
};

type JsonLdSchema =
  | OrganizationSchema
  | LocalBusinessSchema
  | EventSchema
  | ProductSchema
  | BreadcrumbSchema
  | ReviewSchema
  | AggregateRatingSchema;

type JsonLdProps = {
  data: Omit<JsonLdSchema, '@context'>;
};

/**
 * JSON-LD Structured Data Component
 *
 * Renders structured data for search engines in JSON-LD format.
 * Supports multiple schema types including Organization, Product, Event, etc.
 *
 * @example
 * <JsonLd
 *   data={{
 *     '@type': 'Product',
 *     name: 'Mountain Hiking Tour',
 *     description: 'Experience the mountains',
 *     offers: {
 *       '@type': 'Offer',
 *       price: 99.99,
 *       priceCurrency: 'USD',
 *     }
 *   }}
 * />
 */
export function JsonLd({ data }: JsonLdProps) {
  const jsonLdData: JsonLdSchema = {
    '@context': 'https://schema.org',
    ...data,
  } as JsonLdSchema;

  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{
        __html: JSON.stringify(jsonLdData),
      }}
    />
  );
}

/**
 * Convenience component for Organization schema
 */
export function OrganizationJsonLd(props: Omit<OrganizationSchema, '@context' | '@type'>) {
  return (
    <JsonLd
      data={{
        '@type': 'Organization',
        ...props,
      }}
    />
  );
}

/**
 * Convenience component for Product schema
 */
export function ProductJsonLd(props: Omit<ProductSchema, '@context' | '@type'>) {
  return (
    <JsonLd
      data={{
        '@type': 'Product',
        ...props,
      }}
    />
  );
}

/**
 * Convenience component for Event schema
 */
export function EventJsonLd(props: Omit<EventSchema, '@context' | '@type'>) {
  return (
    <JsonLd
      data={{
        '@type': 'Event',
        ...props,
      }}
    />
  );
}

/**
 * Convenience component for LocalBusiness schema
 */
export function LocalBusinessJsonLd(props: Omit<LocalBusinessSchema, '@context' | '@type'>) {
  return (
    <JsonLd
      data={{
        '@type': 'LocalBusiness',
        ...props,
      }}
    />
  );
}

/**
 * Convenience component for BreadcrumbList schema
 */
export function BreadcrumbJsonLd(props: Omit<BreadcrumbSchema, '@context' | '@type'>) {
  return (
    <JsonLd
      data={{
        '@type': 'BreadcrumbList',
        ...props,
      }}
    />
  );
}
