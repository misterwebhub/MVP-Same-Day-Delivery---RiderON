<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The mobile apps (apps/customer, apps/partner) run as Expo web builds
    | during local development, served from the Metro bundler's own origin
    | (e.g. http://localhost:8081) — a different origin than this API
    | (http://localhost:8000), so the browser enforces CORS on every request.
    | Auth is Bearer-token based (Sanctum personal access tokens), not
    | cookie/session based, so supports_credentials stays false and an
    | open allowed_origins is safe here — there's no session cookie for a
    | malicious origin to piggyback on.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
