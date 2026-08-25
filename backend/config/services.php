<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | RiderON provider drivers
    |--------------------------------------------------------------------------
    |
    | Every external integration is selected via one of these env-driven
    | switches and bound to its interface in the matching ServiceProvider
    | (App\Providers\{Payment,Sms,Push,Call}ServiceProvider). "mock" is the
    | safe default for local dev — it logs instead of calling out.
    |
    */

    'payment_driver' => env('PAYMENT_DRIVER', 'mock'),
    'sms_driver' => env('SMS_DRIVER', 'mock'),
    'push_driver' => env('PUSH_DRIVER', 'mock'),
    'call_driver' => env('CALL_DRIVER', 'mock'),

    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],

    'msg91' => [
        'auth_key' => env('MSG91_AUTH_KEY'),
        'sender_id' => env('MSG91_SENDER_ID'),
        'template_id' => env('MSG91_TEMPLATE_ID'),
    ],

    'hanuotp' => [
        'api_key' => env('HANUOTP_API_KEY'),
        'base_url' => env('HANUOTP_BASE_URL', 'https://api.hanuotp.in/sms-otp.php'),
        'template_id' => env('HANUOTP_TEMPLATE_ID', 'default'),
    ],

    'fcm' => [
        'project_id' => env('FCM_PROJECT_ID'),
        'credentials_path' => env('FCM_CREDENTIALS_PATH'),
    ],

    'exotel' => [
        'sid' => env('EXOTEL_SID'),
        'token' => env('EXOTEL_TOKEN'),
        'caller_id' => env('EXOTEL_CALLER_ID'),
    ],

];
