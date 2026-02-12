import type { LatLngTuple } from 'leaflet';

export type RoutingProfile = 'foot' | 'driving' | 'cycling';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';
const routeCache = new Map<string, LatLngTuple[]>();

function cacheKey(waypoints: LatLngTuple[], profile: RoutingProfile): string {
  const wp = waypoints.map(([lat, lng]) => `${lat.toFixed(6)},${lng.toFixed(6)}`).join(';');
  return `${profile}:${wp}`;
}

/**
 * Fetch a road-following route between waypoints via our API proxy.
 * Returns an array of [lat, lng] points following roads, or null on failure.
 */
export async function fetchRoute(
  waypoints: LatLngTuple[],
  profile: RoutingProfile = 'foot'
): Promise<LatLngTuple[] | null> {
  if (waypoints.length < 2) return null;

  const key = cacheKey(waypoints, profile);
  if (routeCache.has(key)) return routeCache.get(key)!;

  const waypointsParam = waypoints.map(([lat, lng]) => `${lat},${lng}`).join(';');
  const url = `${API_BASE}/route?waypoints=${waypointsParam}&profile=${profile}`;

  try {
    const res = await fetch(url, { signal: AbortSignal.timeout(15000) });
    if (!res.ok) return null;

    const data = await res.json();
    if (!data.coordinates) return null;

    const points: LatLngTuple[] = data.coordinates.map(
      ([lat, lng]: [number, number]) => [lat, lng] as LatLngTuple
    );

    routeCache.set(key, points);
    return points;
  } catch {
    return null;
  }
}
