import React, { createContext, useContext, useEffect, useMemo, useState, useCallback } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { ActivityIndicator, StyleSheet, View } from 'react-native';
import { color } from '@rideron/design-tokens';
import type { City, ParcelType, PricingQuoteResponse, RouteScheduleAvailability, RouteSummary, Station, WeightSlab } from '@rideron/types';

export interface BookingPartyDraft {
  name: string;
  phone: string;
  landmark: string;
}

export interface BookingDraft {
  originCity: City | null;
  originStation: Station | null;
  destinationCity: City | null;
  destinationStation: Station | null;
  route: RouteSummary | null;
  /** "YYYY-MM-DD". */
  bookingDate: string | null;
  schedule: RouteScheduleAvailability | null;
  weightSlab: WeightSlab | null;
  quantity: number;
  parcelType: ParcelType | null;
  declaredValuePaise: number;
  specialInstructions: string;
  /** Local device URI of the required parcel photo, captured on ParcelDetails
   * and uploaded to the order right after it's created (see BookingSummary's
   * onConfirm) — the order doesn't exist yet while still in the draft, so the
   * actual upload can't happen until then. */
  parcelPhotoUri: string | null;
  /** Optional door-pickup add-on toggle, fed into POST /pricing/quote's
   * door_pickup flag so the fee is reflected in the server-quoted breakdown. */
  doorPickup: boolean;
  /** Local device URI of the bill/invoice photo, required (like parcelPhotoUri
   * above) once declaredValuePaise crosses the invoice-required threshold
   * (see quote.breakdown / Parcel::INVOICE_REQUIRED_ABOVE_PAISE server-side).
   * Uploaded with type='invoice' right after the parcel photo, once the order
   * exists. */
  invoicePhotoUri: string | null;
  sender: BookingPartyDraft;
  receiver: BookingPartyDraft;
  /** Free-text pickup address label — only meaningful (and only sent to the
   * backend) when the resolved origin station's city opts into manual pickup
   * (currently Kanpur). Empty string otherwise/by default. */
  pickupAddressText: string;
  /** Device GPS captured at the moment the customer entered the address
   * above — never geocoded from the text. Null until captured. */
  pickupLatitude: number | null;
  pickupLongitude: number | null;
  /** Pincode captured alongside the address above (auto-filled from Place
   * Details/reverse-geocode, editable by the customer). Null until captured. */
  pickupPostalCode: string | null;
  /** Mirrors pickupAddressText above but for the delivery/destination side —
   * only meaningful when the resolved destination station's city opts into
   * manual address entry (currently Kanpur). Empty string otherwise/by default. */
  deliveryAddressText: string;
  /** Coordinate resolved via Places Autocomplete (or manual GPS/fallback) for
   * the delivery address above. Null until captured. */
  deliveryLatitude: number | null;
  deliveryLongitude: number | null;
  /** Mirrors pickupPostalCode above but for the delivery side. */
  deliveryPostalCode: string | null;
  quote: PricingQuoteResponse | null;
  prohibitedItemsAccepted: boolean;
  /** Set the moment orders.create() succeeds in BookingSummary's onConfirm,
   * cleared once payment actually succeeds (Payment.tsx's pay() calls
   * reset()). Persisted here — not component state — so that retapping
   * Confirm & Pay after a later step fails (e.g. the parcel photo upload),
   * or the screen remounting / the app restarting mid-flow, resumes against
   * the same order instead of calling orders.create() again and minting a
   * duplicate. orders.create() generates a fresh Idempotency-Key per call,
   * so the backend can't dedupe repeat taps for us — this has to happen here. */
  pendingOrder: { id: number; paymentId: number } | null;
}

const INITIAL_DRAFT: BookingDraft = {
  originCity: null,
  originStation: null,
  destinationCity: null,
  destinationStation: null,
  route: null,
  bookingDate: null,
  schedule: null,
  weightSlab: null,
  quantity: 1,
  parcelType: null,
  declaredValuePaise: 0,
  specialInstructions: '',
  parcelPhotoUri: null,
  doorPickup: false,
  invoicePhotoUri: null,
  sender: { name: '', phone: '', landmark: '' },
  receiver: { name: '', phone: '', landmark: '' },
  pickupAddressText: '',
  pickupLatitude: null,
  pickupLongitude: null,
  pickupPostalCode: null,
  deliveryAddressText: '',
  deliveryLatitude: null,
  deliveryLongitude: null,
  deliveryPostalCode: null,
  quote: null,
  prohibitedItemsAccepted: false,
  pendingOrder: null,
};

const STORAGE_KEY = 'rideron.bookingDraft';

interface BookingDraftContextValue {
  draft: BookingDraft;
  update: (patch: Partial<BookingDraft>) => void;
  reset: () => void;
}

const BookingDraftContext = createContext<BookingDraftContextValue | null>(null);

/**
 * Booking draft state — selected route/schedule/parcel/sender/receiver/quote
 * — lives here rather than in route params (per navigation/types.ts's
 * BookingStackParamList doc comment) so the flow survives back-navigation
 * and an app kill mid-flow (docs/01 "Draft Persistence" requirement).
 * Persisted to AsyncStorage (non-sensitive — no tokens/payment data), hydrated
 * once at mount before BookingStack's screens render.
 */
export function BookingDraftProvider({ children }: { children: React.ReactNode }) {
  const [draft, setDraft] = useState<BookingDraft>(INITIAL_DRAFT);
  const [hydrated, setHydrated] = useState(false);

  useEffect(() => {
    let cancelled = false;
    AsyncStorage.getItem(STORAGE_KEY)
      .then((raw) => {
        if (cancelled) return;
        if (raw) {
          try {
            setDraft({ ...INITIAL_DRAFT, ...JSON.parse(raw) });
          } catch {
            // Corrupt/stale draft — fall back to a clean slate rather than crash the flow.
          }
        }
      })
      .finally(() => {
        if (!cancelled) setHydrated(true);
      });
    return () => {
      cancelled = true;
    };
  }, []);

  const update = useCallback((patch: Partial<BookingDraft>) => {
    setDraft((prev) => {
      const next = { ...prev, ...patch };
      AsyncStorage.setItem(STORAGE_KEY, JSON.stringify(next)).catch(() => {});
      return next;
    });
  }, []);

  const reset = useCallback(() => {
    setDraft(INITIAL_DRAFT);
    AsyncStorage.removeItem(STORAGE_KEY).catch(() => {});
  }, []);

  const value = useMemo(() => ({ draft, update, reset }), [draft, update, reset]);

  if (!hydrated) {
    return (
      <View style={styles.loading}>
        <ActivityIndicator color={color.primary} />
      </View>
    );
  }

  return <BookingDraftContext.Provider value={value}>{children}</BookingDraftContext.Provider>;
}

export function useBookingDraft(): BookingDraftContextValue {
  const ctx = useContext(BookingDraftContext);
  if (!ctx) {
    throw new Error('useBookingDraft() must be called within a <BookingDraftProvider>.');
  }
  return ctx;
}

const styles = StyleSheet.create({
  loading: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: color.background,
  },
});
