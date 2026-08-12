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
  PricingQuoteResponse,
  Profile,
  ProhibitedItemsResponse,
  QuotePayload,
  RequestOtpPayload,
  RequestOtpResponse,
  ResendOrderOtpResponse,
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
import type { HttpClient } from './httpClient';
import { generateIdempotencyKey } from './idempotency';

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

      resendOtp: (orderId: number, purpose: 'pickup' | 'delivery') =>
        http.request<ResendOrderOtpResponse>(`/orders/${orderId}/otp/${purpose}/resend`, { method: 'POST' }),

      /** Partner-only (role:partner) — verifies the pickup/delivery OTP the
       * customer/receiver reads out, advancing the order's real state machine. */
      verifyOtp: (orderId: number, purpose: 'pickup' | 'delivery', payload: VerifyOrderOtpPayload) =>
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
    },

    /** All role:partner-gated (see routes/api.php) — only meaningful when
     * logged in via auth.partnerLogin(). */
    partner: {
      assignments: {
        list: (params?: { date?: string }) =>
          http.request<PartnerAssignment[]>('/partner/assignments', { query: params }),

        get: (orderId: number) => http.request<PartnerAssignment>(`/partner/assignments/${orderId}`),

        accept: (orderId: number) =>
          http.request<PartnerAssignment>(`/partner/assignments/${orderId}/accept`, {
            method: 'POST',
            idempotencyKey: generateIdempotencyKey(),
          }),

        arrivedPickup: (orderId: number) =>
          http.request<PartnerAssignment>(`/partner/assignments/${orderId}/arrived-pickup`, { method: 'POST' }),

        /** Manual override — normally auto-fires after pickup OTP verify succeeds;
         * safe no-op if the order is already IN_TRANSIT. */
        startTransit: (orderId: number) =>
          http.request<PartnerAssignment>(`/partner/assignments/${orderId}/start-transit`, { method: 'POST' }),

        arrivedDestination: (orderId: number) =>
          http.request<PartnerAssignment>(`/partner/assignments/${orderId}/arrived-destination`, { method: 'POST' }),
      },

      earnings: {
        get: () => http.request<PartnerEarningsResponse>('/partner/earnings'),
      },
    },
  };
}

export type ApiResources = ReturnType<typeof createResources>;
export type { TokenPair };
