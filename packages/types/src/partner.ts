/**
 * Delivery Partner (rider) app types — backend
 * app/Http/Controllers/Api/V1/{PartnerAssignment,PartnerEarnings,OrderCall}Controller.php,
 * app/Http/Resources/PartnerAssignmentResource.php,
 * app/Http/Requests/Auth/PartnerLoginRequest.php.
 */
import type { OrderStatus, OrderPickupAddress } from './order';
import type { ParcelType, WeightSlab, Station } from './catalog';

export interface PartnerLoginPayload {
  /** Must match /^[6-9]\d{9}$/ — same 10-digit Indian mobile format as customer phone. */
  phone: string;
  password: string;
}

/** Partner-facing order shape — deliberately narrower than the customer Order type.
 * Never includes: declared_value_paise (only a derived high_value boolean), unmasked
 * phone numbers, payment/pricing breakdown, other partners' identities, customer email,
 * or any OTP value. See PartnerAssignmentResource's maskPhone(). */
export interface PartnerAssignmentParty {
  name: string;
  /** Masked server-side: bullets + last 4 digits only, e.g. "••••••0001". */
  phone: string;
  landmark: string | null;
}

export interface PartnerAssignmentParcel {
  parcel_type: ParcelType;
  weight_slab: WeightSlab;
  quantity: number;
  /** True when declared_value_paise >= config('parcel.high_value_threshold_paise') — the raw amount is never exposed. */
  high_value: boolean;
  special_instructions: string | null;
  /** Photos the customer attached at booking — the rider compares these against the
   * physical parcel at pickup as a fraud check (does the parcel match what was declared). */
  photos: string[];
}

export interface PartnerAssignment {
  id: number;
  booking_reference: string;
  status: OrderStatus;
  /** "YYYY-MM-DD" or null. */
  booking_date: string | null;

  route?: {
    origin_station: Station | null;
    destination_station: Station | null;
  };

  route_schedule?: {
    departure_time: string;
    arrival_time: string;
  };

  sender: PartnerAssignmentParty;
  receiver: PartnerAssignmentParty;

  /** Real (unmasked) pickup coordinate + address text, set only when the
   *  customer entered one manually (currently Kanpur-origin orders) —
   *  unlike phone numbers this is never masked, the rider needs the actual
   *  location to judge distance/navigate. Null everywhere else; fall back
   *  to route.origin_station's own lat/lng in that case. */
  pickup_address?: OrderPickupAddress | null;

  /** Mirrors pickup_address above but for the delivery/destination end
   *  (currently Kanpur-destination orders) — lets the rider navigate to the
   *  real drop point instead of route.destination_station's fixed lat/lng
   *  once the customer entered one manually. */
  delivery_address?: OrderPickupAddress | null;

  parcel?: PartnerAssignmentParcel | null;

  /** Whether the rider has already captured proof-of-custody photos — used to gate the
   * OTP verify button client-side (backend also enforces this, see OrderOtpController). */
  pickup_photo_uploaded: boolean;
  delivery_photo_uploaded: boolean;

  /** ISO 8601 or null — set once ARRIVED_DESTINATION is reached; the receiver-wait deadline. */
  waiting_deadline_at: string | null;
  /** ISO 8601 or null. */
  created_at: string | null;
}

export interface PartnerEarningsRecentEntry {
  order_id: number;
  booking_reference: string;
  /** ISO 8601 or null. */
  completed_at: string | null;
  /** Paise, int — commission_percent share of that order's total_amount_paise. */
  earnings_paise: number;
}

/** A live read computed from real COMPLETED orders (no separate settled-payout ledger exists yet). */
export interface PartnerEarningsResponse {
  commission_percent: number;
  today_earnings_paise: number;
  week_earnings_paise: number;
  month_earnings_paise: number;
  completed_deliveries_count: number;
  recent: PartnerEarningsRecentEntry[];
}

export type CallTarget = 'sender' | 'receiver';

export interface CallInitiationResponse {
  /** False on a business-level failure (e.g. provider couldn't place the call) — still HTTP 200. */
  success: boolean;
  provider_call_sid: string | null;
}
