'use client';

import { useEffect, useMemo, useState } from 'react';
import type { LatLngTuple } from 'leaflet';
import MapContainer from './MapContainer';
import MarkerPopup from './MarkerPopup';
import type { ItineraryStop } from '@go-adventure/schemas';
import { fetchRoute } from '@/lib/utils/fetchRoute';

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

  // Sort itinerary by order for reliable first/last detection
  const sortedItinerary = useMemo(() => {
    if (!itinerary || itinerary.length === 0) return [];
    return [...itinerary].sort((a, b) => a.order - b.order);
  }, [itinerary]);

  // Compute bounds for séjours from all itinerary stops
  const sejourBounds: LatLngTuple[] | undefined = useMemo(() => {
    if (!isSejour || !itinerary || itinerary.length < 2) return undefined;
    const points = itinerary.map((stop) => [stop.lat, stop.lng] as LatLngTuple);
    // Check if all points are identical (degenerate bounds — fall back to center/zoom)
    const allSame = points.every((p) => p[0] === points[0][0] && p[1] === points[0][1]);
    if (allSame) return undefined;
    return points;
  }, [isSejour, itinerary]);

  // Standard single-color route for tours (road-following via OSRM)
  useEffect(() => {
    if (itinerary && itinerary.length > 0 && !isSejour) {
      import('react-leaflet').then((module) => {
        const { Polyline } = module;

        const Component = ({ stops }: { stops: ItineraryStop[] }) => {
          const straightPositions: LatLngTuple[] = stops.map((stop) => [stop.lat, stop.lng]);
          const [positions, setPositions] = useState<LatLngTuple[]>(straightPositions);

          useEffect(() => {
            let cancelled = false;
            fetchRoute(straightPositions).then((road) => {
              if (!cancelled && road) setPositions(road);
            });
            return () => {
              cancelled = true;
            };
          }, [straightPositions]);

          return (
            <Polyline
              positions={positions}
              pathOptions={{
                color: '#0D642E',
                weight: 4,
                opacity: 0.85,
              }}
            />
          );
        };

        setRouteComponent(() => Component);
      });
    }
  }, [itinerary, isSejour]);

  // Day-colored routes for séjours (road-following via OSRM)
  useEffect(() => {
    if (itinerary && itinerary.length > 0 && isSejour) {
      Promise.all([import('react-leaflet'), import('leaflet')]).then(([reactLeaflet, leaflet]) => {
        const { Polyline, Marker, Popup } = reactLeaflet;
        const L = leaflet.default;

        const Component = ({ stops, locale: loc }: SejourRouteProps) => {
          const dayLabel = loc === 'fr' ? 'JOUR' : 'DAY';

          // Road-following route segments keyed by segment id
          const [roadSegments, setRoadSegments] = useState<Map<string, LatLngTuple[]>>(new Map());

          // Group stops by day
          const dayGroups = new Map<number, ItineraryStop[]>();
          const sortedStops = [...stops].sort((a, b) => a.order - b.order);

          // Check if all stops have the same day value (likely unset defaults)
          const allSameDay =
            sortedStops.length > 1 &&
            sortedStops.every((s) => ((s as any).day ?? 1) === ((sortedStops[0] as any).day ?? 1));

          for (let i = 0; i < sortedStops.length; i++) {
            const stop = sortedStops[i];
            const day = allSameDay ? i + 1 : ((stop as any).day ?? i + 1);
            if (!dayGroups.has(day)) {
              dayGroups.set(day, []);
            }
            dayGroups.get(day)!.push(stop);
          }

          const days = Array.from(dayGroups.keys()).sort((a, b) => a - b);

          // Fetch road-following routes for all segments
          useEffect(() => {
            let cancelled = false;
            const fetches: Promise<void>[] = [];

            days.forEach((day, dayIndex) => {
              const dayStops = dayGroups.get(day)!;

              // Within-day route
              if (dayStops.length > 1) {
                const waypoints: LatLngTuple[] = dayStops.map((s) => [s.lat, s.lng]);
                fetches.push(
                  fetchRoute(waypoints).then((road) => {
                    if (!cancelled && road) {
                      setRoadSegments((prev) => new Map(prev).set(`day-${day}`, road));
                    }
                  })
                );
              }

              // Connecting segment to next day
              if (dayIndex < days.length - 1) {
                const lastStop = dayStops[dayStops.length - 1];
                const nextFirstStop = dayGroups.get(days[dayIndex + 1])![0];
                const waypoints: LatLngTuple[] = [
                  [lastStop.lat, lastStop.lng],
                  [nextFirstStop.lat, nextFirstStop.lng],
                ];
                const segKey = `seg-${day}-${days[dayIndex + 1]}`;
                fetches.push(
                  fetchRoute(waypoints).then((road) => {
                    if (!cancelled && road) {
                      setRoadSegments((prev) => new Map(prev).set(segKey, road));
                    }
                  })
                );
              }
            });

            Promise.all(fetches);
            return () => {
              cancelled = true;
            };
            // eslint-disable-next-line react-hooks/exhaustive-deps
          }, []);

          const elements: React.ReactNode[] = [];

          // Track used positions to offset overlapping day icons
          const usedPositions = new Map<string, number>();

          days.forEach((day, dayIndex) => {
            const dayStops = dayGroups.get(day)!;
            const color = DAY_COLORS[dayIndex % DAY_COLORS.length];

            // Within-day polyline (if day has 2+ stops)
            if (dayStops.length > 1) {
              const straightPositions: LatLngTuple[] = dayStops.map((s) => [s.lat, s.lng]);
              const positions = roadSegments.get(`day-${day}`) ?? straightPositions;
              elements.push(
                <Polyline
                  key={`day-${day}-route`}
                  positions={positions}
                  pathOptions={{
                    color,
                    weight: 4,
                    opacity: 0.85,
                  }}
                />
              );
            }

            // Colored segment to next day's first stop
            if (dayIndex < days.length - 1) {
              const lastStop = dayStops[dayStops.length - 1];
              const nextFirstStop = dayGroups.get(days[dayIndex + 1])![0];
              const segKey = `seg-${day}-${days[dayIndex + 1]}`;
              const straightPositions: LatLngTuple[] = [
                [lastStop.lat, lastStop.lng],
                [nextFirstStop.lat, nextFirstStop.lng],
              ];
              const positions = roadSegments.get(segKey) ?? straightPositions;
              elements.push(
                <Polyline
                  key={`segment-${day}-to-${days[dayIndex + 1]}`}
                  positions={positions}
                  pathOptions={{
                    color,
                    weight: 4,
                    opacity: 0.85,
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

            // Offset overlapping day icons vertically
            const posKey = `${firstStop.lat.toFixed(5)},${firstStop.lng.toFixed(5)}`;
            const overlapIndex = usedPositions.get(posKey) ?? 0;
            usedPositions.set(posKey, overlapIndex + 1);

            const dayIcon = L.divIcon({
              className: 'day-label-marker',
              html: `<div style="background: ${color}; color: white; padding: 4px 12px; border-radius: 12px; font-weight: bold; font-size: 12px; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3); white-space: nowrap; cursor: pointer;">${dayLabel} ${day}</div>`,
              iconSize: [70, 28],
              iconAnchor: [35, 40 + overlapIndex * 32],
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
    <MapContainer center={center} zoom={13} bounds={sejourBounds} className={className}>
      {/* Main listing marker — hide for séjours (day markers) and when itinerary exists (stop markers) */}
      {!isSejour && (!itinerary || itinerary.length === 0) && (
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

          {/* Intermediate waypoint markers (tours only — séjours use day labels instead) */}
          {!isSejour &&
            sortedItinerary
              .filter((_, index) => index !== 0 && index !== sortedItinerary.length - 1)
              .map((stop) => (
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
                  type="waypoint"
                />
              ))}

          {/* Start and End markers — tours only, séjours use day labels instead */}
          {!isSejour &&
            sortedItinerary.length > 0 &&
            (() => {
              const first = sortedItinerary[0];
              const last = sortedItinerary[sortedItinerary.length - 1];
              const getTitle = (stop: (typeof sortedItinerary)[0]) =>
                typeof stop.title === 'string' ? stop.title : stop.title.en || stop.title.fr || '';
              return (
                <>
                  {sortedItinerary.length > 1 && (
                    <MarkerPopup
                      key={`end-${last.id}`}
                      position={[last.lat, last.lng]}
                      title={getTitle(last)}
                      type="end"
                    />
                  )}
                  <MarkerPopup
                    key={`start-${first.id}`}
                    position={[first.lat, first.lng]}
                    title={getTitle(first)}
                    type="start"
                  />
                </>
              );
            })()}
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
