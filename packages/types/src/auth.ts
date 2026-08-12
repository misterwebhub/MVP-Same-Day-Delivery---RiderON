/** Auth flow types — backend/app/Http/Controllers/Api/V1/AuthController.php. */

export interface RequestOtpPayload {
  phone: string;
  purpose: 'login';
}

export interface RequestOtpResponse {
  phone: string;
  /** ISO 8601. */
  expires_at: string;
}

export interface VerifyOtpPayload {
  phone: string;
  otp: string;
}

export interface VerifyOtpResponse {
  access_token: string;
  refresh_token: string;
  is_new_user: boolean;
}

export interface RefreshTokenPayload {
  refresh_token: string;
}

/** No expires_in/token_type field is returned by the backend — opaque tokens only. */
export interface TokenPair {
  access_token: string;
  refresh_token: string;
}

export interface CompleteProfilePayload {
  name: string;
  email?: string | null;
}

export interface CompleteProfileResponse {
  id: number;
  name: string | null;
  email: string | null;
  phone: string;
  role: string;
}
