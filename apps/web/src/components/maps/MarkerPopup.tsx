'use client';

import { useEffect, useState } from 'react';
import type { LatLngTuple } from 'leaflet';
import Image from 'next/image';

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
  type?: 'listing' | 'poi' | 'start' | 'end' | 'waypoint';
}

export default function MarkerPopup({
  position,
  title,
  description,
  imageUrl,
  price,
  slug,
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
        const colors: Record<string, string> = {
          listing: '#0D642E',
          poi: '#8BC34A',
          start: '#22c55e',
          end: '#ef4444',
          waypoint: '#f59e0b',
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
              {props.slug && (
                <a
                  href={`/listings/${props.slug}`}
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
