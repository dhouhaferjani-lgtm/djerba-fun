'use client';

import { useEffect, useState } from 'react';
import type { LatLngTuple } from 'leaflet';
import Image from 'next/image';
import { getListingUrl } from '@/lib/utils/urls';
import type { Locale } from '@/i18n/routing';

interface MarkerPopupProps {
  position: LatLngTuple;
  title: string;
  description?: string;
  imageUrl?: string;
  price?: {
    amount: number;
    currency: string;
  };
  slug?: string;
  location?: string | { name: string };
  locale?: Locale;
  type?: 'listing' | 'poi' | 'start' | 'end' | 'waypoint';
}

export default function MarkerPopup({
  position,
  title,
  description,
  imageUrl,
  price,
  slug,
  location,
  locale = 'fr',
  type = 'listing',
}: MarkerPopupProps) {
  const [MarkerComponent, setMarkerComponent] =
    useState<React.ComponentType<MarkerPopupProps> | null>(null);

  useEffect(() => {
    // Lazy load Leaflet components
    Promise.all([import('react-leaflet'), import('leaflet')]).then(([reactLeaflet, leaflet]) => {
      const { Marker, Popup } = reactLeaflet;
      const L = leaflet.default;

      // Custom marker icon with brand color
      const getMarkerIcon = (type: string) => {
        // Use RGB values from Tailwind theme colors
        const colors: Record<string, string> = {
          listing: 'rgb(13, 100, 46)', // primary
          poi: 'rgb(139, 195, 74)', // secondary
          start: 'rgb(34, 197, 94)', // green-500
          end: 'rgb(239, 68, 68)', // red-500
          waypoint: 'rgb(245, 158, 11)', // yellow-500
        };

        const color = colors[type] || colors.listing;

        return L.divIcon({
          className: 'custom-marker',
          html: `
            <div style="
              background-color: ${color};
              width: 32px;
              height: 32px;
              border-radius: 50% 50% 50% 0;
              transform: rotate(-45deg);
              border: 3px solid white;
              box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            ">
              <div style="
                transform: rotate(45deg);
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 16px;
              ">
                ${type === 'start' ? '⬆' : type === 'end' ? '⬇' : '●'}
              </div>
            </div>
          `,
          iconSize: [32, 32],
          iconAnchor: [16, 32],
          popupAnchor: [0, -32],
        });
      };

      const Component = (props: MarkerPopupProps) => (
        <Marker position={props.position} icon={getMarkerIcon(props.type || 'listing')}>
          <Popup className="custom-popup">
            <div className="min-w-[200px]">
              {props.imageUrl && (
                <div className="relative mb-2 h-32 w-full overflow-hidden rounded">
                  <Image
                    src={props.imageUrl}
                    alt={props.title}
                    fill
                    className="object-cover"
                    sizes="200px"
                  />
                </div>
              )}
              <h3 className="mb-1 font-semibold text-neutral-900">{props.title}</h3>
              {props.description && (
                <p className="mb-2 text-sm text-neutral-600">{props.description}</p>
              )}
              {props.price && (
                <div className="mb-2 text-sm font-semibold text-primary">
                  From {props.price.amount / 100} {props.price.currency}
                </div>
              )}
              {props.slug && props.location && (
                <a
                  href={getListingUrl(props.slug, props.location, props.locale)}
                  className="inline-block rounded bg-primary px-3 py-1 text-sm text-white hover:bg-primary/90"
                >
                  View Details
                </a>
              )}
            </div>
          </Popup>
        </Marker>
      );

      setMarkerComponent(() => Component);
    });
  }, []);

  if (!MarkerComponent) {
    return null;
  }

  return (
    <MarkerComponent
      position={position}
      title={title}
      description={description}
      imageUrl={imageUrl}
      price={price}
      slug={slug}
      type={type}
    />
  );
}
