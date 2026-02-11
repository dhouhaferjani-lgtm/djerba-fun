'use client';

import { useEffect, useState } from 'react';
import type { LatLngTuple } from 'leaflet';
import MapContainer from './MapContainer';
import MarkerPopup from './MarkerPopup';
import type { ItineraryStop } from '@go-adventure/schemas';

const DAY_COLORS = ['#0D642E', '#E67E22', '#3498DB', '#9B59B6', '#E74C3C', '#1ABC9C', '#F39C12'];

interface ListingMapProps {
  center: LatLngTuple;
  title: string;
  imageUrl?: string;
  itinerary?: ItineraryStop[];
  isSejour?: boolean;
  locale?: string;
  className?: string;
}

interface RouteProps {
  stops: ItineraryStop[];
}

interface SejourRouteProps {
  stops: ItineraryStop[];
  locale?: string;
}

export default function ListingMap({
  center,
  title,
  imageUrl,
  itinerary,
  isSejour,
  locale,
  className,
}: ListingMapProps) {
  const [RouteComponent, setRouteComponent] = useState<React.ComponentType<RouteProps> | null>(
    null
  );
  const [SejourRouteComponent, setSejourRouteComponent] =
    useState<React.ComponentType<SejourRouteProps> | null>(null);

  // Standard single-color route for tours
  useEffect(() => {
    if (itinerary && itinerary.length > 0 && !isSejour) {
      import('react-leaflet').then((module) => {
        const { Polyline } = module;

        const Component = ({ stops }: { stops: ItineraryStop[] }) => {
          const positions: LatLngTuple[] = stops.map((stop) => [stop.lat, stop.lng]);
          return (
            <Polyline
              positions={positions}
              pathOptions={{
                color: '#0D642E',
                weight: 3,
                opacity: 0.8,
              }}
            />
          );
        };

        setRouteComponent(() => Component);
      });
    }
  }, [itinerary, isSejour]);

  // Day-colored routes for séjours
  useEffect(() => {
    if (itinerary && itinerary.length > 0 && isSejour) {
      Promise.all([import('react-leaflet'), import('leaflet')]).then(([reactLeaflet, leaflet]) => {
        const { Polyline, Marker, Popup } = reactLeaflet;
        const L = leaflet.default;

        const Component = ({ stops, locale: loc }: SejourRouteProps) => {
          const dayLabel = loc === 'fr' ? 'JOUR' : 'DAY';

          // Group stops by day
          const dayGroups = new Map<number, ItineraryStop[]>();
          const sortedStops = [...stops].sort((a, b) => a.order - b.order);

          // Check if all stops have the same day value (likely unset defaults)
          const allSameDay =
            sortedStops.length > 1 &&
            sortedStops.every((s) => ((s as any).day ?? 1) === ((sortedStops[0] as any).day ?? 1));

          for (let i = 0; i < sortedStops.length; i++) {
            const stop = sortedStops[i];
            // Auto-derive day from index when all stops have same day value
            const day = allSameDay ? i + 1 : ((stop as any).day ?? i + 1);
            if (!dayGroups.has(day)) {
              dayGroups.set(day, []);
            }
            dayGroups.get(day)!.push(stop);
          }

          const days = Array.from(dayGroups.keys()).sort((a, b) => a - b);
          const elements: React.ReactNode[] = [];

          days.forEach((day, dayIndex) => {
            const dayStops = dayGroups.get(day)!;
            const color = DAY_COLORS[dayIndex % DAY_COLORS.length];

            // Within-day polyline (if day has 2+ stops)
            if (dayStops.length > 1) {
              const positions: LatLngTuple[] = dayStops.map((s) => [s.lat, s.lng]);
              elements.push(
                <Polyline
                  key={`day-${day}-route`}
                  positions={positions}
                  pathOptions={{
                    color,
                    weight: 4,
                    opacity: 0.8,
                  }}
                />
              );
            }

            // Colored segment to next day's first stop
            if (dayIndex < days.length - 1) {
              const lastStop = dayStops[dayStops.length - 1];
              const nextFirstStop = dayGroups.get(days[dayIndex + 1])![0];
              elements.push(
                <Polyline
                  key={`segment-${day}-to-${days[dayIndex + 1]}`}
                  positions={[
                    [lastStop.lat, lastStop.lng],
                    [nextFirstStop.lat, nextFirstStop.lng],
                  ]}
                  pathOptions={{
                    color,
                    weight: 4,
                    opacity: 0.8,
                  }}
                />
              );
            }

            // Day label marker at first stop of each day — clickable with popup
            const firstStop = dayStops[0];
            const stopTitle =
              typeof firstStop.title === 'string'
                ? firstStop.title
                : firstStop.title?.en || firstStop.title?.fr || '';
            const stopDesc = firstStop.description
              ? typeof firstStop.description === 'string'
                ? firstStop.description
                : firstStop.description?.en || firstStop.description?.fr || ''
              : '';

            const dayIcon = L.divIcon({
              className: 'day-label-marker',
              html: `<div style="background: ${color}; color: white; padding: 4px 12px; border-radius: 12px; font-weight: bold; font-size: 12px; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3); white-space: nowrap; cursor: pointer;">${dayLabel} ${day}</div>`,
              iconSize: [70, 28],
              iconAnchor: [35, 40],
            });

            elements.push(
              <Marker
                key={`day-label-${day}`}
                position={[firstStop.lat, firstStop.lng]}
                icon={dayIcon}
              >
                <Popup>
                  <div style={{ minWidth: 180 }}>
                    <strong style={{ color, fontSize: 14 }}>
                      {dayLabel} {day}
                    </strong>
                    {stopTitle && (
                      <p style={{ margin: '4px 0 0', fontSize: 13, fontWeight: 500 }}>
                        {stopTitle}
                      </p>
                    )}
                    {stopDesc && (
                      <p style={{ margin: '4px 0 0', fontSize: 12, color: '#666' }}>{stopDesc}</p>
                    )}
                  </div>
                </Popup>
              </Marker>
            );
          });

          return <>{elements}</>;
        };

        setSejourRouteComponent(() => Component);
      });
    }
  }, [itinerary, isSejour]);

  return (
    <MapContainer center={center} zoom={13} className={className}>
      {/* Main listing marker — hide for séjours since day markers replace it */}
      {!isSejour && (
        <MarkerPopup position={center} title={title} imageUrl={imageUrl} type="listing" />
      )}

      {/* Itinerary route and markers */}
      {itinerary && itinerary.length > 0 && (
        <>
          {/* Standard route for tours */}
          {!isSejour && RouteComponent && <RouteComponent stops={itinerary} />}

          {/* Day-colored routes for séjours */}
          {isSejour && SejourRouteComponent && (
            <SejourRouteComponent stops={itinerary} locale={locale} />
          )}

          {/* Individual stop markers (for tours only — séjours use day markers) */}
          {!isSejour &&
            itinerary.map((stop, index) => (
              <MarkerPopup
                key={stop.id}
                position={[stop.lat, stop.lng]}
                title={typeof stop.title === 'string' ? stop.title : stop.title.en}
                description={
                  stop.description
                    ? typeof stop.description === 'string'
                      ? stop.description
                      : stop.description.en
                    : undefined
                }
                type={index === 0 ? 'start' : index === itinerary.length - 1 ? 'end' : 'waypoint'}
              />
            ))}
        </>
      )}

      {/* Day color legend for séjours */}
      {isSejour && itinerary && itinerary.length > 0 && (
        <DayLegend stops={itinerary} locale={locale} />
      )}
    </MapContainer>
  );
}

// Legend component rendered inside the map
function DayLegend({ stops, locale }: { stops: ItineraryStop[]; locale?: string }) {
  const [LegendComponent, setLegendComponent] = useState<React.ComponentType | null>(null);

  useEffect(() => {
    import('react-leaflet').then((module) => {
      const { useMap } = module;

      const Legend = () => {
        const map = useMap();

        useEffect(() => {
          // Collect unique days — auto-derive if all same
          const allSameDay =
            stops.length > 1 &&
            stops.every((s) => ((s as any).day ?? 1) === ((stops[0] as any).day ?? 1));

          const days = new Set<number>();
          const sortedStops = [...stops].sort((a, b) => a.order - b.order);
          for (let i = 0; i < sortedStops.length; i++) {
            days.add(allSameDay ? i + 1 : ((sortedStops[i] as any).day ?? i + 1));
          }
          const sortedDays = Array.from(days).sort((a, b) => a - b);

          if (sortedDays.length <= 1) return;

          const dayLabel = locale === 'fr' ? 'Jour' : 'Day';

          // Create legend control
          const L = (window as any).L;
          if (!L) return;

          const legend = L.control({ position: 'bottomright' });
          legend.onAdd = () => {
            const div = L.DomUtil.create('div', 'day-legend');
            div.style.cssText =
              'background: white; padding: 8px 12px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); font-size: 12px;';
            div.innerHTML = sortedDays
              .map((day, i) => {
                const color = DAY_COLORS[i % DAY_COLORS.length];
                return `<div style="display: flex; align-items: center; gap: 6px; margin: 2px 0;"><span style="width: 16px; height: 4px; background: ${color}; border-radius: 2px; display: inline-block;"></span><span style="font-weight: 500;">${dayLabel} ${day}</span></div>`;
              })
              .join('');
            return div;
          };

          legend.addTo(map);

          return () => {
            legend.remove();
          };
        }, [map]);

        return null;
      };

      setLegendComponent(() => Legend);
    });
  }, [stops, locale]);

  if (!LegendComponent) return null;
  return <LegendComponent />;
}
