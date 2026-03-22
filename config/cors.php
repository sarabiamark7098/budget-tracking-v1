<?php

/**
 * CORS configuration for Sanctum SPA cookie auth (S-02).
 *
 * supports_credentials: true is required so the browser includes the
 * HttpOnly session cookie + XSRF-TOKEN cookie on API requests from the SPA.
 *
 * Set FRONTEND_URL in .env to match your Vue dev server origin.
 */
return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:5173'),
        'http://localhost:3000',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:3000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | MUST be true for Sanctum SPA cookie auth. This tells the browser to
    | include cookies (session + XSRF) on cross-origin requests. The
    | allowed_origins list must NOT use a wildcard when this is true.
    |
    */
    'supports_credentials' => true,

];
