import type { LatLngTuple } from 'leaflet';

const OSRM_BASE = 'https://router.project-osrm.org/route/v1/driving';
const routeCache = new Map<string, LatLngTuple[]>();

function cacheKey(waypoints: LatLngTuple[]): string {
  return waypoints.map(([lat, lng]) => `${lat.toFixed(6)},${lng.toFixed(6)}`).join(';');
}

/**
 * Fetch a road-following route from OSRM between waypoints.
 * Returns an array of [lat, lng] points following roads, or null on failure.
 */
export async function fetchRoute(waypoints: LatLngTuple[]): Promise<LatLngTuple[] | null> {
  if (waypoints.length < 2) return null;

  const key = cacheKey(waypoints);
  if (routeCache.has(key)) return routeCache.get(key)!;

  // OSRM expects lng,lat pairs separated by semicolons
  const coords = waypoints.map(([lat, lng]) => `${lng},${lat}`).join(';');
  const url = `${OSRM_BASE}/${coords}?overview=full&geometries=geojson`;

  try {
    const res = await fetch(url, { signal: AbortSignal.timeout(5000) });
    if (!res.ok) return null;

    const data = await res.json();
    if (data.code !== 'Ok' || !data.routes?.[0]?.geometry?.coordinates) return null;

    // OSRM GeoJSON coordinates are [lng, lat] — flip to [lat, lng]
    const points: LatLngTuple[] = data.routes[0].geometry.coordinates.map(
      ([lng, lat]: [number, number]) => [lat, lng] as LatLngTuple
    );

    routeCache.set(key, points);
    return points;
  } catch {
    return null;
  }
}
