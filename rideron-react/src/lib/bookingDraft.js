import { useCallback, useEffect, useState } from "react";

// Booking draft state — selected route/schedule/parcel/sender/receiver/quote —
// persisted to localStorage (non-sensitive, no tokens/payment data) so the
// flow survives an accidental refresh/back-navigation mid-booking, mirroring
// apps/customer's BookingDraftContext (AsyncStorage there, localStorage here).
const STORAGE_KEY = "rideron.bookingDraft";

export const INITIAL_DRAFT = {
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
  specialInstructions: "",
  // Local blob: URL of the required parcel photo, captured on the Parcel
  // Details step and uploaded to the order right after it's created (the
  // order doesn't exist yet while still in the draft).
  parcelPhotoUri: null,
  parcelPhotoFile: null,
  // Optional door-pickup add-on toggle, fed into pricing.getQuote()'s
  // door_pickup flag so the fee is reflected in the server-quoted breakdown.
  doorPickup: false,
  // Bill/invoice photo — required once declaredValuePaise crosses
  // INVOICE_REQUIRED_ABOVE_PAISE (@rideron/types). Uploaded with
  // type='invoice' right after the parcel photo, once the order exists.
  invoicePhotoUri: null,
  invoicePhotoFile: null,
  sender: { name: "", phone: "", landmark: "" },
  receiver: { name: "", phone: "", landmark: "" },
  pickupAddressText: "",
  pickupLatitude: null,
  pickupLongitude: null,
  pickupPostalCode: null,
  deliveryAddressText: "",
  deliveryLatitude: null,
  deliveryLongitude: null,
  deliveryPostalCode: null,
  quote: null,
  prohibitedItemsAccepted: false,
  // Set the moment orders.create() succeeds, cleared once payment actually
  // succeeds. Persisted so retapping Confirm & Pay after a later step fails
  // (or a page refresh mid-flow) resumes against the same order instead of
  // calling orders.create() again and minting a duplicate.
  pendingOrder: null,
};

function loadDraft() {
  if (typeof window === "undefined") return INITIAL_DRAFT;
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    if (!raw) return INITIAL_DRAFT;
    const parsed = JSON.parse(raw);
    // parcelPhotoFile/invoicePhotoFile (real File objects) can't survive JSON — never persisted.
    return { ...INITIAL_DRAFT, ...parsed, parcelPhotoFile: null, invoicePhotoFile: null };
  } catch {
    return INITIAL_DRAFT;
  }
}

export function useBookingDraft() {
  const [draft, setDraft] = useState(loadDraft);

  const persist = useCallback((value) => {
    if (typeof window === "undefined") return;
    try {
      // Never persist the blob: object URL across reloads (it's revoked/invalid
      // once the page unloads) or the raw File (not JSON-serialisable).
      const { parcelPhotoFile, parcelPhotoUri, ...rest } = value;
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(rest));
    } catch {
      // Non-critical — worst case the draft doesn't survive a refresh.
    }
  }, []);

  const update = useCallback(
    (patch) => {
      setDraft((prev) => {
        const next = { ...prev, ...patch };
        persist(next);
        return next;
      });
    },
    [persist],
  );

  const reset = useCallback(() => {
    setDraft(INITIAL_DRAFT);
    if (typeof window !== "undefined") {
      window.localStorage.removeItem(STORAGE_KEY);
    }
  }, []);

  useEffect(() => {
    return () => {
      if (draft.parcelPhotoUri) {
        URL.revokeObjectURL(draft.parcelPhotoUri);
      }
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return { draft, update, reset };
}
