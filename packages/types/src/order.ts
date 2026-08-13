/**
 * Order/Payment types — backend app/Constants/OrderStatus.php,
 * app/Http/Resources/OrderResource.php,
 * app/Http/Controllers/Api/V1/{Order,Payment}Controller.php.
 */
import type { ParcelType, WeightSlab, PriceBreakdownLine } from './catalog';

/** Exact string values from app/Constants/OrderStatus.php. */
export type OrderStatus =
  | 'DRAFT'
  | 'PAYMENT_PENDING'
  | 'PAYMENT_FAILED'
  | 'BOOKED'
  | 'RIDER_ASSIGNMENT_PENDING'
  | 'RIDER_ASSIGNED'
  | 'WAITING_FOR_PICKUP'
  | 'RIDER_ARRIVED_PICKUP'
  | 'PICKUP_OTP_PENDING'
  | 'PICKED_UP'
  | 'IN_TRANSIT'
  | 'ARRIVED_DESTINATION'
  | 'WAITING_FOR_RECEIVER'
  | 'DELIVERY_OTP_PENDING'
  | 'DELIVERED'
  | 'COMPLETED'
  | 'CANCELLED'
  | 'REFUND_PENDING'
  | 'REFUNDED'
  | 'FAILED_DELIVERY'
  | 'DISPUTED';

export const ORDER_STATUS_TERMINAL: OrderStatus[] = ['COMPLETED', 'CANCELLED', 'REFUNDED'];

export const ORDER_STATUS_SELF_SERVICE_CANCELLABLE: OrderStatus[] = [
  'PAYMENT_PENDING',
  'PAYMENT_FAILED',
  'BOOKED',
  'RIDER_ASSIGNMENT_PENDING',
  'RIDER_ASSIGNED',
  'WAITING_FOR_PICKUP',
];

export interface OrderPartyDetails {
  name: string;
  phone: string;
  landmark: string | null;
}

export interface OrderParcelDetails {
  parcel_type: ParcelType;
  weight_slab: WeightSlab;
  quantity: number;
  declared_value_paise: number;
  special_instructions: string | null;
  /** Photos the customer attached at booking time — full URLs, empty array if none uploaded. */
  photos: string[];
}

export interface OrderPaymentSummary {
  id: number;
  provider: 'razorpay' | 'mock';
  provider_order_id: string;
  amount_paise: number;
  status: PaymentStatus;
}

export type OrderOtpStatus = 'pending' | 'verified' | 'expired';

export interface OrderOtpField {
  status: OrderOtpStatus;
  /**
   * The live plaintext code, shown directly in the app so the sender can
   * read it to the pickup rider (and relay the delivery code to the
   * receiver themselves). Null whenever there's nothing safe/valid to
   * show — already verified, expired, or the short-lived display cache
   * on the backend has expired/missed (e.g. app opened long after the
   * code was generated). Treat null as "not available right now, use
   * resend" — never as an error.
   */
  code: string | null;
  /** ISO 8601. */
  expires_at: string | null;
  resend_count: number;
}

export interface Order {
  id: number;
  booking_reference: string;
  status: OrderStatus;
  /** "YYYY-MM-DD" or null. */
  booking_date: string | null;

  route?: {
    id: number;
    origin_station: { id: number; city_id: number; name: string; code: string; type: string; latitude: number | string; longitude: number | string; address: string } | null;
    destination_station: { id: number; city_id: number; name: string; code: string; type: string; latitude: number | string; longitude: number | string; address: string } | null;
    distance_km: number;
    estimated_duration_minutes: number;
  };

  route_schedule?: {
    id: number;
    departure_time: string;
    arrival_time: string;
  };

  sender: OrderPartyDetails;
  receiver: OrderPartyDetails;

  parcel?: OrderParcelDetails | null;

  price_breakdown: PriceBreakdownLine[];
  total_amount_paise: number;
  currency: string;

  payment?: OrderPaymentSummary | null;

  pickup_otp?: OrderOtpField | null;
  delivery_otp?: OrderOtpField | null;

  /** Rider-captured proof-of-custody photos — full URLs, null until the rider uploads. */
  pickup_proof_photo_url?: string | null;
  delivery_proof_photo_url?: string | null;

  /** ISO 8601 or null. */
  cancelled_at: string | null;
  cancellation_reason: string | null;
  /** ISO 8601 or null. */
  created_at: string | null;
}

export interface CreateOrderPayload {
  /** Opaque token from POST /pricing/quote — carries weight_slab/quantity/declared_value_paise server-side. */
  quote_token: string;
  /** "YYYY-MM-DD", must be >= today. */
  booking_date: string;
  sender_name: string;
  sender_phone: string;
  sender_landmark?: string | null;
  receiver_name: string;
  receiver_phone: string;
  receiver_landmark?: string | null;
  parcel_type: ParcelType;
  special_instructions?: string | null;
  /** Must be true — user must accept the prohibited-items declaration. */
  prohibited_items_accepted: true;
}

export interface CancelOrderPayload {
  reason?: string | null;
}

export type PaymentStatus = 'created' | 'pending' | 'success' | 'failed' | 'cancelled';

export interface VerifyPaymentPayload {
  razorpay_order_id: string;
  razorpay_payment_id: string;
  razorpay_signature: string;
  /** Only honoured when backend PAYMENT_DRIVER=mock — forces the signature-failure path for testing. */
  force_failure?: boolean;
}

export interface VerifyPaymentResponse {
  payment_status: PaymentStatus;
  order_status: OrderStatus;
}

export interface PaymentStatusResponse {
  payment_status: PaymentStatus;
  order_status: OrderStatus;
}

export interface VerifyOrderOtpPayload {
  otp: string;
}

export interface VerifyOrderOtpResponse {
  order_status: OrderStatus;
}

export interface RegenerateOrderOtpResponse {
  purpose: 'pickup' | 'delivery';
  /** ISO 8601. Deliberately no `code` field — the partner never sees the OTP value. */
  expires_at: string;
}
