<?php

return [

    'length' => (int) env('OTP_LENGTH', 4),

    'expiry_minutes' => (int) env('OTP_EXPIRY_MINUTES', 10),

    'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', 5),

    'lock_minutes' => (int) env('OTP_LOCK_MINUTES', 15),

    'max_resends' => (int) env('OTP_MAX_RESENDS', 3),

    'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),

    /*
     * Order-bound OTPs (pickup/delivery) don't expire on a fixed
     * minutes-from-now timer like login OTPs — they expire relative to a
     * journey milestone (scheduled departure / estimated arrival), per
     * docs/05-payment-otp-architecture.md. These buffers are added on top
     * of that milestone to give the rider/receiver slack for real-world delay.
     */
    'pickup_expiry_buffer_minutes' => (int) env('OTP_PICKUP_EXPIRY_BUFFER_MINUTES', 240),

    'delivery_expiry_buffer_minutes' => (int) env('OTP_DELIVERY_EXPIRY_BUFFER_MINUTES', 240),

];
