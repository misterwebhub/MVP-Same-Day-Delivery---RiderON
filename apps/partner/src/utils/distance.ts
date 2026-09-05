/** Great-circle distance between two lat/lng points, in kilometers.
 * Used to show the rider a rough "X.X km away" figure against a ride's
 * pickup point — no server round-trip, no maps SDK, just arithmetic against
 * the rider's own best-effort device GPS fix. */
export function haversineDistanceKm(
  from: { latitude: number; longitude: number },
  to: { latitude: number; longitude: number },
): number {
  const EARTH_RADIUS_KM = 6371;
  const toRad = (deg: number) => (deg * Math.PI) / 180;

  const dLat = toRad(to.latitude - from.latitude);
  const dLng = toRad(to.longitude - from.longitude);
  const lat1 = toRad(from.latitude);
  const lat2 = toRad(to.latitude);

  const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) ** 2;
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  return EARTH_RADIUS_KM * c;
}

export function formatDistanceKm(km: number): string {
  return `${km < 10 ? km.toFixed(1) : Math.round(km)} km away`;
}
