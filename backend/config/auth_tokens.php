<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Refresh token lifetime
    |--------------------------------------------------------------------------
    |
    | RiderON issues a short-lived Sanctum access token (see config/sanctum.php
    | "expiration") alongside a longer-lived opaque refresh token stored
    | (hashed) in the refresh_tokens table. POST /auth/refresh exchanges a
    | valid, unexpired, unrevoked refresh token for a brand new pair and
    | rotates (revokes) the old one.
    |
    */

    'refresh_token_ttl_days' => (int) env('REFRESH_TOKEN_TTL_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Login OTP request rate limits
    |--------------------------------------------------------------------------
    */

    'otp_request_max_per_phone_per_hour' => (int) env('AUTH_OTP_REQUEST_MAX_PER_PHONE_PER_HOUR', 3),

    'otp_request_max_per_ip_per_hour' => (int) env('AUTH_OTP_REQUEST_MAX_PER_IP_PER_HOUR', 10),

];
