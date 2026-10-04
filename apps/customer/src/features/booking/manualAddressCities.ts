/**
 * City names (lower-cased) that support manual address entry on top of the
 * station pick — mirrors backend config('parcel.manual_address_cities').
 * Kept in one place so the station picker (inline capture) and Contact
 * Details (review/edit step) never drift out of sync.
 */
export const MANUAL_ADDRESS_CITIES = ['kanpur'];

export function isManualAddressCity(cityName: string | null | undefined): boolean {
  return MANUAL_ADDRESS_CITIES.includes((cityName ?? '').trim().toLowerCase());
}
