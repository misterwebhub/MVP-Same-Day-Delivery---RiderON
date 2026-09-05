import type {
  AppNotification,
  CallInitiationResponse,
  CallTarget,
  CancelOrderPayload,
  City,
  CompleteProfilePayload,
  CompleteProfileResponse,
  CreateOrderPayload,
  CreateSavedContactPayload,
  Order,
  PaginatedData,
  PartnerAssignment,
  PartnerEarningsResponse,
  PartnerLoginPayload,
  PaymentStatusResponse,
  PlacesAutocompleteResponse,
  PlacesDetailsResponse,
  PlacesReverseGeocodeResponse,
  PricingQuoteResponse,
  Profile,
  ProhibitedItemsResponse,
  QuotePayload,
  RegenerateOrderOtpResponse,
  RequestOtpPayload,
  RequestOtpResponse,
  RouteScheduleAvailability,
  RouteSummary,
  SavedContact,
  Station,
  TokenPair,
  UpdateProfilePayload,
  VerifyOrderOtpPayload,
  VerifyOrderOtpResponse,
  VerifyOtpPayload,
  VerifyOtpResponse,
  VerifyPaymentPayload,
  VerifyPaymentResponse,
} from '@rideron/types';
import type { HttpClient, FormDataFile } from './httpClient';
import { generateIdempotencyKey } from './idempotency';

/**
 * Best-effort GPS point (docs fraud-prevention addendum). Rider-side only —
 * the customer app stays IP-only by design, no location permission prompt.
 * Always optional: a denied permission must never block the underlying
 * action, so every call site accepts `coords?: GeoCoords` and simply omits
 * the fields when unavailable.
 */
export interface GeoCoords {
  latitude: number;
  longitude: number;
}

/** Multipart form fields don't accept numbers, so GPS coords get stringified
 * for the `formData` photo-upload calls; omitted entirely when not given. */
function geoCoordsToFormFields(coords?: GeoCoords): Record<string, string> {
  if (!coords) return {};

  return { latitude: String(coords.latitude), longitude: String(coords.longitude) };
}

/**
 * Typed endpoint methods, grouped to mirror routes/api.php. Each group is a
 * thin layer over HttpClient#request — no business logic lives here, only
 * the exact path/method/param shape for each backend route.
 */
export function createResources(http: HttpClient) {
  return {
    auth: {
      requestOtp: (payload: RequestOtpPayload) =>
        http.request<RequestOtpResponse>('/auth/otp/request', { method: 'POST', body: payload, skipAuth: true }),

      verifyOtp: async (payload: VerifyOtpPayload): Promise<VerifyOtpResponse> => {
        const result = await http.request<VerifyOtpResponse>('/auth/otp/verify', {
          method: 'POST',
          body: payload,
          skipAuth: true,
        });
        await http.getTokenStorage().setTokens(result);
        return result;
      },

      completeProfile: (payload: CompleteProfilePayload) =>
        http.request<CompleteProfileResponse>('/auth/profile', { method: 'POST', body: payload }),

      partnerLogin: async (payload: PartnerLoginPayload): Promise<TokenPair> => {
        const result = await http.request<TokenPair>('/auth/partner/login', {
          method: 'POST',
          body: payload,
          skipAuth: true,
        });
        await http.getTokenStorage().setTokens(result);
        return result;
      },

      logout: async (): Promise<void> => {
        await http.request<null>('/auth/logout', { method: 'POST' });
        await http.getTokenStorage().clearTokens();
      },

      /** Restores a session from persisted tokens without hitting the network — used at app boot. */
      hasStoredSession: async (): Promise<boolean> => {
        const [access, refresh] = await Promise.all([
          http.getTokenStorage().getAccessToken(),
          http.getTokenStorage().getRefreshToken(),
        ]);
        return Boolean(access && refresh);
      },
    },

    catalog: {
      listCities: () => http.request<City[]>('/cities'),

      listStations: (cityId: number) => http.request<Station[]>(`/cities/${cityId}/stations`),

      /** Unfiltered listing only — GET /routes with both station ids set returns a
       * single object, not a collection (see RouteController::index), so that case
       * is a separate method (findRoute) rather than an overload of this one. */
      listRoutes: () => http.request<RouteSummary[]>('/routes'),

      /** GET /routes?origin_station_id=&destination_station_id= — backend resolves
       * this to a single route via firstOrFail(), 404 (NOT_FOUND) when none exists,
       * not an empty array. See RouteController::index. */
      findRoute: (params: { origin_station_id: number; destination_station_id: number }) =>
        http.request<RouteSummary>('/routes', { query: params }),

      getRoute: (routeId: number) => http.request<RouteSummary>(`/routes/${routeId}`),

      getPopularRoutes: () => http.request<RouteSummary[]>('/routes/popular'),

      getRouteSchedules: (routeId: number, date: string) =>
        http.request<RouteScheduleAvailability[]>(`/routes/${routeId}/schedules`, { query: { date } }),

      getProhibitedItems: () => http.request<ProhibitedItemsResponse>('/prohibited-items'),
    },

    /** Server-side proxy for Google Places — see backend PlacesController's
     * docblock for why AddressAutocompleteField can't call Google directly
     * (no CORS headers on Google's Autocomplete/Details JSON endpoints, so a
     * browser fetch from Expo web is blocked outright). */
    places: {
      /** `origin` biases results toward wherever the customer currently is
       * (Zomato/Porter-style "nearby first"), same as passing no bias when omitted. */
      autocomplete: (input: string, origin?: { latitude: number; longitude: number }) =>
        http.request<PlacesAutocompleteResponse>('/places/autocomplete', {
          query: { input, ...(origin ? { lat: origin.latitude, lng: origin.longitude } : {}) },
        }),

      details: (placeId: string) => http.request<PlacesDetailsResponse>('/places/details', { query: { place_id: placeId } }),

      /** Turns a GPS fix into an editable address + pincode for the "use my
       * current location" flow. */
      reverseGeocode: (latitude: number, longitude: number) =>
        http.request<PlacesReverseGeocodeResponse>('/places/reverse-geocode', { query: { lat: latitude, lng: longitude } }),
    },

    pricing: {
      getQuote: (payload: QuotePayload) =>
        http.request<PricingQuoteResponse>('/pricing/quote', { method: 'POST', body: payload }),
    },

    orders: {
      list: (params?: { per_page?: number; page?: number }) =>
        http.request<PaginatedData<Order>>('/orders', { query: params }),

      create: (payload: CreateOrderPayload) =>
        http.request<Order>('/orders', { method: 'POST', body: payload, idempotencyKey: generateIdempotencyKey() }),

      get: (orderId: number) => http.request<Order>(`/orders/${orderId}`),

      cancel: (orderId: number, payload: CancelOrderPayload = {}) =>
        http.request<Order>(`/orders/${orderId}/cancel`, {
          method: 'POST',
          body: payload,
          idempotencyKey: generateIdempotencyKey(),
        }),

      /** Customer-only — attaches a photo of the parcel to the order at booking time
       * (or any time before delivery), so the rider can visually confirm the physical
       * parcel matches what was declared. */
      uploadParcelPhoto: (orderId: number, file: FormDataFile) =>
        http.request<Order>(`/orders/${orderId}/parcel/photos`, {
          method: 'POST',
          formData: { photo: file },
        }),

      /** Partner-only (role:partner) — verifies the pickup/delivery OTP the
       * customer/receiver reads out, advancing the order's real state machine.
       * `payload` may include best-effort GPS (latitude/longitude) — the rider
       * is typically still standing at the pickup/delivery point when they
       * verify, so this feeds the same station-distance fraud check as the
       * arrival endpoints. Never blocks verification if GPS is unavailable. */
      verifyOtp: (orderId: number, purpose: 'pickup' | 'delivery', payload: VerifyOrderOtpPayload & Partial<GeoCoords>) =>
        http.request<VerifyOrderOtpResponse>(`/orders/${orderId}/otp/${purpose}/verify`, {
          method: 'POST',
          body: payload,
        }),

      /** Partner-only (role:partner) — bridges the partner's and the target
       * party's real numbers server-side via CallProvider; neither side's
       * real number is ever returned to the app. */
      call: (orderId: number, target: CallTarget) =>
        http.request<CallInitiationResponse>(`/orders/${orderId}/call/${target}`, { method: 'POST' }),
    },

    payments: {
      verify: (paymentId: number, payload: VerifyPaymentPayload) =>
        http.request<VerifyPaymentResponse>(`/payments/${paymentId}/verify`, {
          method: 'POST',
          body: payload,
          idempotencyKey: generateIdempotencyKey(),
        }),

      getStatus: (paymentId: number) => http.request<PaymentStatusResponse>(`/payments/${paymentId}/status`),
    },

    profile: {
      get: () => http.request<Profile>('/profile'),

      update: (payload: UpdateProfilePayload) => http.request<Profile>('/profile', { method: 'PATCH', body: payload }),

      listSavedContacts: () => http.request<SavedContact[]>('/profile/saved-contacts'),

      createSavedContact: (payload: CreateSavedContactPayload) =>
        http.request<SavedContact>('/profile/saved-contacts', { method: 'POST', body: payload }),

      deleteSavedContact: (contactId: number) =>
        http.request<null>(`/profile/saved-contacts/${contactId}`, { method: 'DELETE' }),
    },

    notifications: {
      list: () => http.request<AppNotification[]>('/notifications'),

      markRead: (notificationId: number) =>
        http.request<null>(`/notifications/${notificationId}/read`, { method: 'POST' }),

      /** Registers an Expo push token (or raw FCM token) for the current
       * user's device — see NotificationController::registerDevice. Called
       * once at boot after login so PartnerAssignmentService's broadcast
       * push (new-order-available) has somewhere to deliver to. */
      registerDevice: (payload: { token: string; platform: string }) =>
        http.request<{ id: number; platform: string }>('/devices', { method: 'POST', body: payload }),
    },

    /** All role:partner-gated (see routes/api.php) — only meaningful when
     * logged in via auth.partnerLogin(). */
    partner: {
      assignments: {
        list: (params?: { date?: string }) =>
          http.request<PartnerAssignment[]>('/partner/assignments', { query: params }),

        /** "Unassigned Rides" pool — orders not yet matched to any partner
         * (partner_id null), filtered server-side to ones this partner is
         * eligible for. Accepting one claims it (see accept() below). */
        listUnassigned: () => http.request<PartnerAssignment[]>('/partner/assignments/unassigned'),

        get: (orderId: number) => http.request<PartnerAssignment>(`/partner/assignments/${orderId}`),

        /** Also doubles as "claim" for a still-unassigned order from the
         * Unassigned Rides pool — the backend atomically assigns it to this
         * partner first (if not already assigned) before accepting. */
        accept: (orderId: number) =>
          http.request<PartnerAssignment>(`/partner/assignments/${orderId}/accept`, {
            method: 'POST',
            idempotencyKey: generateIdempotencyKey(),
          }),

        /** `coords` is best-effort — a denied location permission on the
         * partner app must never block the underlying action, so pass
         * undefined/omit rather than failing when GPS isn't available. */
        arrivedPickup: (orderId: number, coords?: GeoCoords) =>
          http.request<PartnerAssignment>(`/partner/assignments/${orderId}/arrived-pickup`, {
            method: 'POST',
            body: coords,
          }),

        /** Manual override — normally auto-fires after pickup OTP verify succeeds;
         * safe no-op if the order is already IN_TRANSIT. */
        startTransit: (orderId: number) =>
          http.request<PartnerAssignment>(`/partner/assignments/${orderId}/start-transit`, { method: 'POST' }),

        arrivedDestination: (orderId: number, coords?: GeoCoords) =>
          http.request<PartnerAssignment>(`/partner/assignments/${orderId}/arrived-destination`, {
            method: 'POST',
            body: coords,
          }),

        /** Regenerates the pickup/delivery OTP without ever revealing the code
         * to the partner — the new code is sent to the sender/receiver and
         * pushed to the customer app. Use when the customer says the code
         * never arrived or expired. */
        regenerateOtp: (orderId: number, purpose: 'pickup' | 'delivery') =>
          http.request<RegenerateOrderOtpResponse>(`/partner/assignments/${orderId}/otp/${purpose}/regenerate`, {
            method: 'POST',
          }),

        uploadPickupPhoto: (orderId: number, file: FormDataFile, coords?: GeoCoords) =>
          http.request<PartnerAssignment>(`/partner/assignments/${orderId}/pickup-photo`, {
            method: 'POST',
            formData: { photo: file, ...geoCoordsToFormFields(coords) },
          }),

        uploadDeliveryPhoto: (orderId: number, file: FormDataFile, coords?: GeoCoords) =>
          http.request<PartnerAssignment>(`/partner/assignments/${orderId}/delivery-photo`, {
            method: 'POST',
            formData: { photo: file, ...geoCoordsToFormFields(coords) },
          }),
      },

      earnings: {
        get: () => http.request<PartnerEarningsResponse>('/partner/earnings'),
      },
    },
  };
}

export type ApiResources = ReturnType<typeof createResources>;
export type { TokenPair };
