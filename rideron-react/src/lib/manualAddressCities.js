/**
 * City names (lower-cased) that support manual address entry on top of the
 * station pick — mirrors backend config('parcel.manual_address_cities') and
 * apps/customer/src/features/booking/manualAddressCities.ts.
 *
 * IMPORTANT: keep this list in sync with backend/config/parcel.php's
 * `manual_address_cities` (currently `['Kanpur']`) AND with the RN app's
 * copy of this list — there is no shared source of truth across the three,
 * so any change must be applied in all three places by hand.
 */
export const MANUAL_ADDRESS_CITIES = ['kanpur'];

export function isManualAddressCity(cityName) {
  return MANUAL_ADDRESS_CITIES.includes((cityName ?? '').trim().toLowerCase());
}
