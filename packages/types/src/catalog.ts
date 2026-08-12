/**
 * Cities/Stations/Routes/Pricing catalog types — backend
 * app/Http/Resources/{City,Station,Route}Resource.php,
 * app/Services/Catalog/RouteScheduleAvailabilityService.php,
 * app/Services/PricingQuote.php, config/pricing.php.
 */

export interface City {
  id: number;
  name: string;
  state: string;
}

export interface Station {
  id: number;
  city_id: number;
  name: string;
  code: string;
  type: string;
  latitude: number | string;
  longitude: number | string;
  address: string;
}

export interface RouteSummary {
  id: number;
  origin_station?: Station;
  destination_station?: Station;
  distance_km: number;
  estimated_duration_minutes: number;
  cutoff_time: string;
  waiting_time_minutes: number;
  /** Paise, int — cheapest weight slab base price for this route. */
  price_from: number;
}

export interface RouteScheduleAvailability {
  route_schedule_id: number;
  departure_time: string;
  arrival_time: string;
  /** ISO 8601 — booking cutoff instant for the given date. */
  cutoff_at: string;
  seats_available: number;
  /** Paise, int — merged in by availableSchedulesForDate(). */
  price_from: number;
}

/** backend/config/pricing.php weight_slab_grams keys, mirrors Parcel::WEIGHT_* constants. */
export type WeightSlab = 'upto_1kg' | '1_3kg' | '3_5kg' | '5_10kg';

export const WEIGHT_SLAB_LABELS: Record<WeightSlab, string> = {
  upto_1kg: 'Up to 1 kg',
  '1_3kg': '1 – 3 kg',
  '3_5kg': '3 – 5 kg',
  '5_10kg': '5 – 10 kg',
};

/** Parcel::TYPE_* constants. */
export type ParcelType = 'documents' | 'clothing' | 'electronics' | 'gifts' | 'books' | 'other';

export const PARCEL_TYPE_LABELS: Record<ParcelType, string> = {
  documents: 'Documents',
  clothing: 'Clothing',
  electronics: 'Electronics',
  gifts: 'Gifts',
  books: 'Books',
  other: 'Other',
};

export interface QuotePayload {
  route_id: number;
  route_schedule_id: number;
  weight_slab: WeightSlab;
  quantity: number;
  declared_value_paise: number;
  coupon_code?: string | null;
}

export interface PriceBreakdownLine {
  label: string;
  /** Paise, int — negative for discount lines. */
  amount_paise: number;
}

export interface PricingQuoteResponse {
  breakdown: PriceBreakdownLine[];
  total_amount_paise: number;
  /** Opaque encrypted token — must be echoed back verbatim on POST /orders. */
  quote_token: string;
  /** ISO 8601. */
  quote_expires_at: string;
}

export interface ProhibitedItem {
  id: number;
  name: string;
  category: string;
  description: string | null;
}

export interface ProhibitedItemsResponse {
  items: ProhibitedItem[];
  version_id: number | null;
}
