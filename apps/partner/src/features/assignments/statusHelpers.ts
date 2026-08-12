import type { OrderStatus } from '@rideron/types';

/** Assigned to this partner but not yet accepted — the Accept action lives on AssignmentDetail. */
export const PENDING_ACCEPT_STATUSES: OrderStatus[] = ['RIDER_ASSIGNMENT_PENDING'];

/** Accepted and in progress, pre-COMPLETED — docs/06's "Active Delivery card" criterion. */
export const ACTIVE_DELIVERY_STATUSES: OrderStatus[] = [
  'RIDER_ASSIGNED',
  'WAITING_FOR_PICKUP',
  'RIDER_ARRIVED_PICKUP',
  'PICKUP_OTP_PENDING',
  'PICKED_UP',
  'IN_TRANSIT',
  'ARRIVED_DESTINATION',
  'WAITING_FOR_RECEIVER',
  'DELIVERY_OTP_PENDING',
  'DELIVERED',
];

export function isPendingAccept(status: OrderStatus): boolean {
  return PENDING_ACCEPT_STATUSES.includes(status);
}

export function isActiveDelivery(status: OrderStatus): boolean {
  return ACTIVE_DELIVERY_STATUSES.includes(status);
}

/** "RIDER_ARRIVED_PICKUP" -> "Rider arrived pickup". */
export function statusLabel(status: OrderStatus): string {
  const lower = status.toLowerCase().replace(/_/g, ' ');
  return lower.charAt(0).toUpperCase() + lower.slice(1);
}
